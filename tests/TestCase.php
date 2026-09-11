<?php

namespace Tests;

use App\Models\Section;
use App\Models\Student;
use App\Models\User;
use App\Services\StudentQrService;
use Illuminate\Foundation\Testing\TestCase as BaseTestCase;

abstract class TestCase extends BaseTestCase
{
    protected function issueStudentToken(Student $student): string
    {
        $service = app(StudentQrService::class);

        return $service->decryptToken($service->issue($student));
    }

    protected function studentFor(User $user, array $attributes = []): Student
    {
        return Student::factory()->forSchool($user->school)->create($attributes);
    }

    protected function sectionFor(User $user, array $attributes = []): Section
    {
        return Section::factory()->create([
            'school_id' => $user->school_id,
            ...$attributes,
        ]);
    }

    protected function scannerFor(User $user, array $attributes = []): User
    {
        return User::factory()->scanner()->create([
            'school_id' => $user->school_id,
            ...$attributes,
        ]);
    }
}
