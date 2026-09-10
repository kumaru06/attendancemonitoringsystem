<?php

namespace Tests\Feature;

use App\Models\Attendance;
use App\Models\User;
use App\Services\AttendanceService;
use App\Services\StudentQrService;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Tests\TestCase;

class AttendanceScanTest extends TestCase
{
    use LazilyRefreshDatabase;

    public function test_first_scan_creates_present_attendance_and_returns_201(): void
    {
        $this->travelTo(now('Asia/Manila')->setTime(7, 15, 0));

        $scanner = User::factory()->scanner()->create();
        $student = $this->studentFor($scanner);
        $token = $this->issueStudentToken($student);

        $this->actingAs($scanner)
            ->postJson(route('scanner.scan'), ['token' => $token])
            ->assertCreated()
            ->assertJsonPath('code', 'recorded')
            ->assertJsonPath('message', 'Attendance recorded')
            ->assertJsonPath('student.student_number', $student->student_number);

        $this->assertTrue(
            Attendance::query()
                ->where('student_id', $student->id)
                ->whereDate('attendance_date', now('Asia/Manila')->toDateString())
                ->where('status', 'Present')
                ->where('recorded_by', $scanner->id)
                ->exists()
        );
    }

    public function test_repeat_scan_preserves_the_original_time_in(): void
    {
        $this->travelTo(now('Asia/Manila')->setTime(7, 10, 0));

        $scanner = User::factory()->scanner()->create();
        $student = $this->studentFor($scanner);
        $token = $this->issueStudentToken($student);

        $this->actingAs($scanner)->postJson(route('scanner.scan'), ['token' => $token])->assertCreated();

        $original = Attendance::query()->where('student_id', $student->id)->first();

        $this->travel(20)->minutes();

        $this->actingAs($scanner)
            ->postJson(route('scanner.scan'), ['token' => $token])
            ->assertOk()
            ->assertJsonPath('code', 'duplicate')
            ->assertJsonPath('message', 'Already checked in today')
            ->assertJsonPath('time_in', $original->time_in);

        $this->assertSame($original->time_in, $original->fresh()->time_in);
        $this->assertDatabaseCount('attendances', 1);
    }

    public function test_a_new_philippine_calendar_day_creates_another_record(): void
    {
        $this->travelTo(now('Asia/Manila')->setTime(16, 0, 0));

        $scanner = User::factory()->scanner()->create();
        $student = $this->studentFor($scanner);
        $token = $this->issueStudentToken($student);

        $this->actingAs($scanner)->postJson(route('scanner.scan'), ['token' => $token])->assertCreated();

        $this->travelTo(now('Asia/Manila')->addDay()->setTime(7, 5, 0));

        $this->actingAs($scanner)->postJson(route('scanner.scan'), ['token' => $token])->assertCreated();

        $this->assertSame(2, Attendance::query()->where('student_id', $student->id)->count());
    }

    public function test_invalid_qr_returns_422(): void
    {
        $scanner = User::factory()->scanner()->create();

        $this->actingAs($scanner)
            ->postJson(route('scanner.scan'), ['token' => 'not-a-real-token'])
            ->assertUnprocessable()
            ->assertJsonPath('code', 'invalid')
            ->assertJsonPath('message', 'Invalid QR code');
    }

    public function test_revoked_qr_returns_422(): void
    {
        $scanner = User::factory()->scanner()->create();
        $student = $this->studentFor($scanner);
        $oldToken = $this->issueStudentToken($student);
        app(StudentQrService::class)->replace($student);

        $this->actingAs($scanner)
            ->postJson(route('scanner.scan'), ['token' => $oldToken])
            ->assertUnprocessable()
            ->assertJsonPath('code', 'invalid');
    }

    public function test_inactive_student_returns_403(): void
    {
        $scanner = User::factory()->scanner()->create();
        $student = $this->studentFor($scanner, ['is_active' => false]);
        $token = $this->issueStudentToken($student);

        $this->actingAs($scanner)
            ->postJson(route('scanner.scan'), ['token' => $token])
            ->assertForbidden()
            ->assertJsonPath('code', 'inactive')
            ->assertJsonPath('message', 'Student account is inactive');

        $this->assertDatabaseCount('attendances', 0);
    }

    public function test_inactive_scanner_is_blocked_from_the_scanner(): void
    {
        $scanner = User::factory()->scanner()->inactive()->create();

        $this->actingAs($scanner)
            ->get(route('scanner.index'))
            ->assertRedirect(route('login'));
    }

    public function test_scan_requires_a_token(): void
    {
        $scanner = User::factory()->scanner()->create();

        $this->actingAs($scanner)
            ->postJson(route('scanner.scan'), [])
            ->assertUnprocessable()
            ->assertJsonValidationErrors('token');
    }

    public function test_attendance_service_uses_server_time_in_manila(): void
    {
        $this->travelTo(now('Asia/Manila')->setTime(6, 45, 12));

        $this->assertSame(now('Asia/Manila')->toDateString(), app(AttendanceService::class)->today());
        $this->assertSame('Asia/Manila', app(AttendanceService::class)->timezone());
    }

    public function test_scan_from_another_school_returns_invalid_qr(): void
    {
        $scanner = User::factory()->scanner()->create();
        $otherAdmin = User::factory()->admin()->create();
        $student = $this->studentFor($otherAdmin);
        $token = $this->issueStudentToken($student);

        $this->actingAs($scanner)
            ->postJson(route('scanner.scan'), ['token' => $token])
            ->assertUnprocessable()
            ->assertJsonPath('code', 'invalid')
            ->assertJsonPath('message', 'Invalid QR code');

        $this->assertDatabaseCount('attendances', 0);
    }
}
