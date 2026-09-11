<?php

namespace App\Services;

use App\Models\Attendance;
use App\Models\Student;

class AttendanceScanResult
{
    public function __construct(
        public readonly string $code,
        public readonly string $message,
        public readonly ?Student $student = null,
        public readonly ?Attendance $attendance = null,
    ) {}

    public static function recorded(Student $student, Attendance $attendance): self
    {
        return new self(
            'recorded',
            'Attendance recorded',
            $student,
            $attendance,
        );
    }

    public static function duplicate(Student $student, Attendance $attendance): self
    {
        return new self(
            'duplicate',
            'Already checked in today',
            $student,
            $attendance,
        );
    }

    public static function invalid(): self
    {
        return new self('invalid', 'Invalid QR code');
    }

    public static function unrecognized(): self
    {
        return new self('unrecognized', 'Face not recognized');
    }

    public static function inactive(Student $student): self
    {
        return new self('inactive', 'Student account is inactive', $student);
    }

    public function isSuccess(): bool
    {
        return in_array($this->code, ['recorded', 'duplicate'], true);
    }

    public function httpStatus(): int
    {
        return match ($this->code) {
            'recorded' => 201,
            'duplicate' => 200,
            'inactive' => 403,
            'unrecognized' => 422,
            default => 422,
        };
    }
}
