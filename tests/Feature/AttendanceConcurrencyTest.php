<?php

namespace Tests\Feature;

use App\Models\Attendance;
use App\Models\User;
use App\Services\AttendanceService;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Tests\TestCase;

class AttendanceConcurrencyTest extends TestCase
{
    use LazilyRefreshDatabase;

    public function test_existing_daily_row_is_returned_instead_of_creating_a_duplicate(): void
    {
        $this->travelTo(now('Asia/Manila')->setTime(7, 0, 0));

        $scanner = User::factory()->scanner()->create();
        $student = $this->studentFor($scanner);
        $existing = Attendance::factory()->create([
            'student_id' => $student->id,
            'attendance_date' => now('Asia/Manila')->toDateString(),
            'time_in' => '07:00:00',
            'recorded_by' => $scanner->id,
        ]);

        $result = app(AttendanceService::class)->record($student, $scanner);

        $this->assertSame('duplicate', $result->code);
        $this->assertSame($existing->id, $result->attendance?->id);
        $this->assertSame('07:00:00', $result->attendance?->time_in);
        $this->assertDatabaseCount('attendances', 1);
    }

    public function test_two_scanners_cannot_create_two_rows_for_the_same_day(): void
    {
        $this->travelTo(now('Asia/Manila')->setTime(7, 30, 0));

        $first = User::factory()->scanner()->create();
        $second = $this->scannerFor($first);
        $student = $this->studentFor($first);
        $token = $this->issueStudentToken($student);

        $this->actingAs($first)->postJson(route('scanner.scan'), ['token' => $token])->assertCreated();
        $this->actingAs($second)->postJson(route('scanner.scan'), ['token' => $token])->assertOk()->assertJsonPath('code', 'duplicate');

        $this->assertDatabaseCount('attendances', 1);
    }
}
