<?php

namespace Tests;

use App\Models\Student;
use App\Services\StudentQrService;
use Illuminate\Foundation\Testing\TestCase as BaseTestCase;

abstract class TestCase extends BaseTestCase
{
    protected function issueStudentToken(Student $student): string
    {
        $service = app(StudentQrService::class);

        return $service->decryptToken($service->issue($student));
    }
}
