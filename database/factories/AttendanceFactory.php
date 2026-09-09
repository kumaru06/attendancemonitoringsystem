<?php

namespace Database\Factories;

use App\Models\Attendance;
use App\Models\Student;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Attendance>
 */
class AttendanceFactory extends Factory
{
    public function definition(): array
    {
        $now = now(config('attendance.timezone'));

        return [
            'student_id' => Student::factory(),
            'attendance_date' => $now->toDateString(),
            'time_in' => $now->format('H:i:s'),
            'status' => config('attendance.status_present'),
            'recorded_by' => User::factory(),
        ];
    }
}
