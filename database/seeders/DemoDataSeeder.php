<?php

namespace Database\Seeders;

use App\Enums\SchoolLevel;
use App\Enums\UserRole;
use App\Models\Attendance;
use App\Models\Section;
use App\Models\Student;
use App\Models\User;
use App\Services\StudentQrService;
use Illuminate\Database\Eloquent\Factories\Sequence;
use Illuminate\Database\Seeder;

class DemoDataSeeder extends Seeder
{
    public function run(StudentQrService $qrService): void
    {
        $scanner = User::query()->firstOrCreate(
            ['username' => 'scanner'],
            [
                'name' => 'Entrance Scanner',
                'password' => 'password',
                'role' => UserRole::Scanner,
                'is_active' => true,
            ]
        );

        $admin = User::query()->where('role', UserRole::Admin)->orderBy('id')->first() ?? $scanner;

        $sections = collect([
            ['name' => 'Grade 1-A', 'level' => SchoolLevel::Elementary],
            ['name' => 'Grade 4-A', 'level' => SchoolLevel::Elementary],
            ['name' => 'Grade 6-B', 'level' => SchoolLevel::Elementary],
            ['name' => 'Grade 7-A', 'level' => SchoolLevel::Jhs],
            ['name' => 'Grade 8-B', 'level' => SchoolLevel::Jhs],
            ['name' => 'Grade 10-C', 'level' => SchoolLevel::Jhs],
            ['name' => 'Grade 11-A', 'level' => SchoolLevel::Shs],
            ['name' => 'Grade 11-B', 'level' => SchoolLevel::Shs],
            ['name' => 'Grade 12-STEM', 'level' => SchoolLevel::Shs],
            ['name' => 'BSIT 1-A', 'level' => SchoolLevel::College],
            ['name' => 'BSBA 2-B', 'level' => SchoolLevel::College],
            ['name' => 'BSED 1-A', 'level' => SchoolLevel::College],
        ])->map(
            fn (array $section) => Section::query()->firstOrCreate(
                ['name' => $section['name']],
                ['level' => $section['level']],
            )
        )->values();

        $needed = 50 - Student::query()->count();

        if ($needed > 0) {
            $start = Student::query()->count();

            Student::factory()
                ->count($needed)
                ->state(new Sequence(function (Sequence $sequence) use ($sections, $start): array {
                    $index = $start + $sequence->index;
                    $usesYearPrefix = $index % 2 === 0;

                    return [
                        'student_number' => $usesYearPrefix
                            ? sprintf('2026-%05d', 30000 + $index)
                            : (string) (22016000000 + $index),
                        'section_id' => $sections[$index % $sections->count()]->id,
                        'is_active' => $index % 8 !== 0,
                    ];
                }))
                ->create()
                ->each(function (Student $student) use ($qrService): void {
                    if ($student->currentQrCredential()->doesntExist()) {
                        $qrService->issue($student);
                    }
                });
        }

        $today = now(config('attendance.timezone'))->toDateString();
        $present = (string) config('attendance.status_present');

        Student::query()
            ->where('is_active', true)
            ->whereDoesntHave('attendances', fn ($query) => $query->whereDate('attendance_date', $today))
            ->orderBy('id')
            ->get()
            ->each(function (Student $student, int $index) use ($today, $present, $admin, $scanner): void {
                $hour = 6 + ($index % 5);
                $minute = ($index * 11) % 60;
                $second = ($index * 7) % 60;
                $recorder = $index % 4 === 0 ? $scanner : $admin;

                Attendance::query()->create([
                    'student_id' => $student->id,
                    'attendance_date' => $today,
                    'time_in' => sprintf('%02d:%02d:%02d', $hour, $minute, $second),
                    'status' => $present,
                    'recorded_by' => $recorder->id,
                ]);
            });

        $historyStudents = Student::query()
            ->where('is_active', true)
            ->whereHas('attendances', fn ($query) => $query->whereDate('attendance_date', $today))
            ->orderBy('id')
            ->get();

        foreach ($historyStudents as $historyStudent) {
            foreach (range(1, 49) as $daysAgo) {
                $date = now(config('attendance.timezone'))->subDays($daysAgo)->toDateString();

                Attendance::query()->firstOrCreate(
                    [
                        'student_id' => $historyStudent->id,
                        'attendance_date' => $date,
                    ],
                    [
                        'time_in' => sprintf('07:%02d:%02d', ($historyStudent->id + $daysAgo) % 50, ($daysAgo * 3) % 60),
                        'status' => $present,
                        'recorded_by' => $daysAgo % 4 === 0 ? $scanner->id : $admin->id,
                    ]
                );
            }
        }
    }
}
