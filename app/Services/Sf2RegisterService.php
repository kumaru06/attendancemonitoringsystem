<?php

namespace App\Services;

use App\Enums\StudentGender;
use App\Models\Attendance;
use App\Models\Section;
use App\Models\Student;
use Carbon\CarbonImmutable;
use InvalidArgumentException;

class Sf2RegisterService
{
    public function build(Section $section, string $yearMonth): Sf2Register
    {
        $timezone = (string) config('attendance.timezone', 'Asia/Manila');
        $monthStart = CarbonImmutable::createFromFormat('!Y-m', $yearMonth, $timezone);

        if ($monthStart === false) {
            throw new InvalidArgumentException("Invalid SF2 month [{$yearMonth}].");
        }

        $monthStart = $monthStart->startOfMonth();
        $monthEnd = $monthStart->endOfMonth();
        $today = CarbonImmutable::now($timezone)->toDateString();

        $section->loadMissing('school');

        $students = Student::query()
            ->whereBelongsTo($section)
            ->where('is_active', true)
            ->orderBy('last_name')
            ->orderBy('first_name')
            ->orderBy('id')
            ->get();

        $presentDates = [];

        if ($students->isNotEmpty()) {
            $presentDates = Attendance::query()
                ->whereIn('student_id', $students->modelKeys())
                ->whereDate('attendance_date', '>=', $monthStart->toDateString())
                ->whereDate('attendance_date', '<=', $monthEnd->toDateString())
                ->get(['student_id', 'attendance_date'])
                ->reduce(function (array $carry, Attendance $attendance): array {
                    $studentId = (int) $attendance->student_id;
                    $date = optional($attendance->attendance_date)->toDateString();

                    if ($date) {
                        $carry[$studentId][$date] = true;
                    }

                    return $carry;
                }, []);
        }

        $days = [];
        $cursor = $monthStart;

        while ($cursor->lte($monthEnd)) {
            $isoWeekday = $cursor->isoWeekday();
            $date = $cursor->toDateString();

            $days[] = [
                'number' => $cursor->day,
                'date' => $date,
                'weekday_code' => $this->weekdayCode($isoWeekday),
                'is_weekday' => $isoWeekday <= 5,
                'is_weekend' => $isoWeekday >= 6,
                'is_past' => $date < $today,
                'is_today' => $date === $today,
            ];

            $cursor = $cursor->addDay();
        }

        $males = [];
        $females = [];
        $ungendered = [];
        $maleDailyPresent = [];
        $femaleDailyPresent = [];

        foreach ($days as $day) {
            $maleDailyPresent[$day['number']] = 0;
            $femaleDailyPresent[$day['number']] = 0;
        }

        foreach ($students as $student) {
            if ($student->gender === StudentGender::Male) {
                $row = $this->learnerRow($student, $days, $presentDates[$student->id] ?? []);
                $males[] = $row;
                $this->addDailyPresent($maleDailyPresent, $row['marks']);

                continue;
            }

            if ($student->gender === StudentGender::Female) {
                $row = $this->learnerRow($student, $days, $presentDates[$student->id] ?? []);
                $females[] = $row;
                $this->addDailyPresent($femaleDailyPresent, $row['marks']);

                continue;
            }

            $ungendered[] = [
                'student_id' => $student->id,
                'name' => $student->sf2Name(),
            ];
        }

        $combinedDailyPresent = [];

        foreach ($days as $day) {
            $number = $day['number'];
            $combinedDailyPresent[$number] = $maleDailyPresent[$number] + $femaleDailyPresent[$number];
        }

        $maleEnrolment = count($males);
        $femaleEnrolment = count($females);
        $enrolment = $maleEnrolment + $femaleEnrolment;
        $elapsedWeekdays = 0;
        $presentStudentDays = 0;

        foreach ($days as $day) {
            if (! $day['is_weekday'] || $day['date'] > $today) {
                continue;
            }

            $elapsedWeekdays++;
            $presentStudentDays += $combinedDailyPresent[$day['number']];
        }

        $averageDailyAttendance = $elapsedWeekdays > 0
            ? round($presentStudentDays / $elapsedWeekdays, 2)
            : 0.0;

        $attendancePercentage = $enrolment > 0
            ? round(($averageDailyAttendance / $enrolment) * 100, 2)
            : 0.0;

        return new Sf2Register(
            section: $section,
            yearMonth: $monthStart->format('Y-m'),
            monthLabel: $monthStart->format('F Y'),
            schoolYear: $this->schoolYear($monthStart),
            schoolName: (string) $section->school?->name,
            gradeLevel: $section->level?->label() ?? '',
            sectionName: $section->name,
            days: $days,
            males: $males,
            females: $females,
            ungendered: $ungendered,
            maleDailyPresent: $maleDailyPresent,
            femaleDailyPresent: $femaleDailyPresent,
            combinedDailyPresent: $combinedDailyPresent,
            maleEnrolment: $maleEnrolment,
            femaleEnrolment: $femaleEnrolment,
            enrolment: $enrolment,
            averageDailyAttendance: $averageDailyAttendance,
            attendancePercentage: $attendancePercentage,
        );
    }

    /**
     * @param  list<array{number: int, date: string, weekday_code: string, is_weekday: bool, is_weekend: bool, is_past: bool, is_today: bool}>  $days
     * @param  array<string, bool>  $presentDates
     * @return array{student_id: int, name: string, marks: array<int, string>, absent_count: int}
     */
    private function learnerRow(Student $student, array $days, array $presentDates): array
    {
        $marks = [];
        $absentCount = 0;

        foreach ($days as $day) {
            if (isset($presentDates[$day['date']])) {
                $marks[$day['number']] = 'present';

                continue;
            }

            if ($day['is_weekday'] && $day['is_past']) {
                $marks[$day['number']] = '(x)';
                $absentCount++;

                continue;
            }

            $marks[$day['number']] = '';
        }

        return [
            'student_id' => $student->id,
            'name' => $student->sf2Name(),
            'marks' => $marks,
            'absent_count' => $absentCount,
        ];
    }

    /**
     * @param  array<int, int>  $dailyPresent
     * @param  array<int, string>  $marks
     */
    private function addDailyPresent(array &$dailyPresent, array $marks): void
    {
        foreach ($marks as $day => $mark) {
            if ($mark === 'present') {
                $dailyPresent[$day]++;
            }
        }
    }

    private function weekdayCode(int $isoWeekday): string
    {
        return match ($isoWeekday) {
            1 => 'M',
            2 => 'T',
            3 => 'W',
            4 => 'TH',
            5 => 'F',
            default => '',
        };
    }

    private function schoolYear(CarbonImmutable $monthStart): string
    {
        $year = $monthStart->year;

        if ($monthStart->month >= 6) {
            return $year.'-'.($year + 1);
        }

        return ($year - 1).'-'.$year;
    }
}
