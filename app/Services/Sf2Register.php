<?php

namespace App\Services;

use App\Models\Section;

class Sf2Register
{
    /**
     * @param  list<array{number: int, date: string, weekday_code: string, is_weekday: bool, is_weekend: bool, is_past: bool, is_today: bool}>  $days
     * @param  list<array{student_id: int, name: string, marks: array<int, string>, absent_count: int}>  $males  Marks are present, (x), or blank.
     * @param  list<array{student_id: int, name: string, marks: array<int, string>, absent_count: int}>  $females  Marks are present, (x), or blank.
     * @param  list<array{student_id: int, name: string}>  $ungendered
     * @param  array<int, int>  $maleDailyPresent
     * @param  array<int, int>  $femaleDailyPresent
     * @param  array<int, int>  $combinedDailyPresent
     */
    public function __construct(
        public readonly Section $section,
        public readonly string $yearMonth,
        public readonly string $monthLabel,
        public readonly string $schoolYear,
        public readonly string $schoolName,
        public readonly string $gradeLevel,
        public readonly string $sectionName,
        public readonly array $days,
        public readonly array $males,
        public readonly array $females,
        public readonly array $ungendered,
        public readonly array $maleDailyPresent,
        public readonly array $femaleDailyPresent,
        public readonly array $combinedDailyPresent,
        public readonly int $maleEnrolment,
        public readonly int $femaleEnrolment,
        public readonly int $enrolment,
        public readonly float $averageDailyAttendance,
        public readonly float $attendancePercentage,
    ) {}
}
