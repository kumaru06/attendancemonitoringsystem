<?php

namespace Database\Seeders;

use App\Enums\UserRole;
use App\Models\Section;
use App\Models\Student;
use App\Models\User;
use App\Services\StudentQrService;
use Illuminate\Database\Seeder;

class DemoDataSeeder extends Seeder
{
    public function run(StudentQrService $qrService): void
    {
        User::query()->firstOrCreate(
            ['username' => 'scanner'],
            [
                'name' => 'Entrance Scanner',
                'password' => 'password',
                'role' => UserRole::Scanner,
                'is_active' => true,
            ]
        );

        $sections = collect(['Grade 11-A', 'Grade 11-B', 'Grade 12-STEM'])->map(
            fn (string $name) => Section::query()->firstOrCreate(['name' => $name])
        );

        Student::factory()
            ->count(8)
            ->state(fn () => ['section_id' => $sections->random()->id])
            ->create()
            ->each(fn (Student $student) => $qrService->issue($student));
    }
}
