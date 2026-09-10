<?php

namespace App\Services;

use App\Models\Attendance;
use App\Models\Student;
use App\Models\User;
use Carbon\CarbonImmutable;
use Illuminate\Database\UniqueConstraintViolationException;

class AttendanceService
{
    public function __construct(private readonly StudentQrService $qrService) {}

    public function now(): CarbonImmutable
    {
        return CarbonImmutable::now($this->timezone());
    }

    public function today(): string
    {
        return $this->now()->toDateString();
    }

    public function timezone(): string
    {
        return (string) config('attendance.timezone', 'Asia/Manila');
    }

    public function recordFromToken(string $token, User $recorder): AttendanceScanResult
    {
        $credential = $this->qrService->findCurrentByToken($token);

        if (! $credential) {
            return AttendanceScanResult::invalid();
        }

        $student = $credential->student()->withoutGlobalScopes()->first();

        if (! $student || ($recorder->school_id && (int) $student->school_id !== (int) $recorder->school_id)) {
            return AttendanceScanResult::invalid();
        }

        if (! $student->is_active) {
            return AttendanceScanResult::inactive($student);
        }

        return $this->record($student, $recorder);
    }

    public function record(Student $student, User $recorder): AttendanceScanResult
    {
        $now = $this->now();
        $date = $now->toDateString();

        $existing = Attendance::query()
            ->where('student_id', $student->id)
            ->whereDate('attendance_date', $date)
            ->first();

        if ($existing) {
            return AttendanceScanResult::duplicate($student, $existing);
        }

        try {
            $attendance = Attendance::query()->create([
                'student_id' => $student->id,
                'attendance_date' => $date,
                'time_in' => $now->format('H:i:s'),
                'status' => config('attendance.status_present'),
                'recorded_by' => $recorder->id,
            ]);
        } catch (UniqueConstraintViolationException) {
            $attendance = Attendance::query()
                ->where('student_id', $student->id)
                ->whereDate('attendance_date', $date)
                ->firstOrFail();

            return AttendanceScanResult::duplicate($student, $attendance);
        }

        return AttendanceScanResult::recorded($student->fresh(['section']), $attendance);
    }
}
