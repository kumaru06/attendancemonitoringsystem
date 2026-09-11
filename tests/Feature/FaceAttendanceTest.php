<?php

namespace Tests\Feature;

use App\Enums\AttendanceMethod;
use App\Models\Attendance;
use App\Models\User;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Tests\TestCase;

class FaceAttendanceTest extends TestCase
{
    use LazilyRefreshDatabase;

    /**
     * @return list<float>
     */
    private function descriptor(float $offset = 0.0): array
    {
        return array_map(
            fn (int $index): float => $offset + ($index * 0.001),
            range(0, 127),
        );
    }

    public function test_enroll_stores_a_face_descriptor_and_returns_201(): void
    {
        $scanner = User::factory()->scanner()->create();
        $student = $this->studentFor($scanner);
        $descriptor = $this->descriptor();

        $this->actingAs($scanner)
            ->postJson(route('scanner.faces.enroll'), [
                'student_id' => $student->id,
                'descriptor' => $descriptor,
            ])
            ->assertCreated()
            ->assertJsonPath('code', 'enrolled')
            ->assertJsonPath('message', 'Face registered')
            ->assertJsonPath('student.student_number', $student->student_number)
            ->assertJsonPath('student.face_enrolled', true);

        $student->refresh();

        $this->assertNotNull($student->face_enrolled_at);
        $this->assertEquals($descriptor, array_map('floatval', $student->face_descriptor));
    }

    public function test_reenroll_replaces_the_descriptor_and_returns_200(): void
    {
        $scanner = User::factory()->scanner()->create();
        $student = $this->studentFor($scanner, [
            'face_descriptor' => $this->descriptor(1),
            'face_enrolled_at' => now()->subDay(),
        ]);
        $descriptor = $this->descriptor(2);

        $this->actingAs($scanner)
            ->postJson(route('scanner.faces.enroll'), [
                'student_id' => $student->id,
                'descriptor' => $descriptor,
            ])
            ->assertOk()
            ->assertJsonPath('code', 'enrolled')
            ->assertJsonPath('student.face_enrolled', true);

        $this->assertEquals($descriptor, array_map('floatval', $student->fresh()->face_descriptor));
    }

    public function test_face_match_records_present_attendance_and_returns_201(): void
    {
        $this->travelTo(now('Asia/Manila')->setTime(7, 15, 0));

        $scanner = User::factory()->scanner()->create();
        $descriptor = $this->descriptor();
        $student = $this->studentFor($scanner, [
            'face_descriptor' => $descriptor,
            'face_enrolled_at' => now(),
        ]);

        $this->actingAs($scanner)
            ->postJson(route('scanner.face'), ['descriptor' => $descriptor])
            ->assertCreated()
            ->assertJsonPath('code', 'recorded')
            ->assertJsonPath('message', 'Attendance recorded')
            ->assertJsonPath('student.student_number', $student->student_number);

        $attendance = Attendance::query()->where('student_id', $student->id)->first();

        $this->assertNotNull($attendance);
        $this->assertSame('Present', $attendance->status);
        $this->assertSame(AttendanceMethod::Face, $attendance->method);
        $this->assertSame($scanner->id, $attendance->recorded_by);
    }

    public function test_repeat_face_match_preserves_the_original_time_in(): void
    {
        $this->travelTo(now('Asia/Manila')->setTime(7, 10, 0));

        $scanner = User::factory()->scanner()->create();
        $descriptor = $this->descriptor();
        $student = $this->studentFor($scanner, [
            'face_descriptor' => $descriptor,
            'face_enrolled_at' => now(),
        ]);

        $this->actingAs($scanner)
            ->postJson(route('scanner.face'), ['descriptor' => $descriptor])
            ->assertCreated();

        $original = Attendance::query()->where('student_id', $student->id)->first();

        $this->travel(20)->minutes();

        $this->actingAs($scanner)
            ->postJson(route('scanner.face'), ['descriptor' => $descriptor])
            ->assertOk()
            ->assertJsonPath('code', 'duplicate')
            ->assertJsonPath('message', 'Already checked in today')
            ->assertJsonPath('time_in', $original->time_in);

        $this->assertDatabaseCount('attendances', 1);
        $this->assertSame($original->time_in, $original->fresh()->time_in);
    }

    public function test_unknown_face_returns_422_and_does_not_record_attendance(): void
    {
        $scanner = User::factory()->scanner()->create();
        $this->studentFor($scanner, [
            'face_descriptor' => $this->descriptor(0),
            'face_enrolled_at' => now(),
        ]);

        $this->actingAs($scanner)
            ->postJson(route('scanner.face'), ['descriptor' => $this->descriptor(8)])
            ->assertUnprocessable()
            ->assertJsonPath('code', 'unrecognized')
            ->assertJsonPath('message', 'Face not recognized');

        $this->assertDatabaseCount('attendances', 0);
    }

    public function test_ambiguous_faces_return_422_and_do_not_record_attendance(): void
    {
        $scanner = User::factory()->scanner()->create();
        $this->studentFor($scanner, [
            'face_descriptor' => $this->descriptor(0),
            'face_enrolled_at' => now(),
        ]);
        $this->studentFor($scanner, [
            'face_descriptor' => $this->descriptor(0.003),
            'face_enrolled_at' => now(),
        ]);

        $this->actingAs($scanner)
            ->postJson(route('scanner.face'), ['descriptor' => $this->descriptor(0)])
            ->assertUnprocessable()
            ->assertJsonPath('code', 'unrecognized');

        $this->assertDatabaseCount('attendances', 0);
    }

    public function test_scanner_cannot_enroll_a_student_from_another_school(): void
    {
        $scanner = User::factory()->scanner()->create();
        $otherAdmin = User::factory()->admin()->create();
        $student = $this->studentFor($otherAdmin);

        $this->actingAs($scanner)
            ->postJson(route('scanner.faces.enroll'), [
                'student_id' => $student->id,
                'descriptor' => $this->descriptor(),
            ])
            ->assertNotFound();

        $this->assertNull($student->fresh()->face_descriptor);
    }

    public function test_enroll_rejects_an_inactive_student(): void
    {
        $scanner = User::factory()->scanner()->create();
        $student = $this->studentFor($scanner, ['is_active' => false]);

        $this->actingAs($scanner)
            ->postJson(route('scanner.faces.enroll'), [
                'student_id' => $student->id,
                'descriptor' => $this->descriptor(),
            ])
            ->assertForbidden()
            ->assertJsonPath('code', 'inactive');

        $this->assertNull($student->fresh()->face_descriptor);
    }

    public function test_face_match_from_another_school_returns_unrecognized(): void
    {
        $scanner = User::factory()->scanner()->create();
        $otherAdmin = User::factory()->admin()->create();
        $descriptor = $this->descriptor();
        $this->studentFor($otherAdmin, [
            'face_descriptor' => $descriptor,
            'face_enrolled_at' => now(),
        ]);

        $this->actingAs($scanner)
            ->postJson(route('scanner.face'), ['descriptor' => $descriptor])
            ->assertUnprocessable()
            ->assertJsonPath('code', 'unrecognized');

        $this->assertDatabaseCount('attendances', 0);
    }

    public function test_inactive_enrolled_student_returns_403(): void
    {
        $scanner = User::factory()->scanner()->create();
        $descriptor = $this->descriptor();
        $student = $this->studentFor($scanner, [
            'is_active' => false,
            'face_descriptor' => $descriptor,
            'face_enrolled_at' => now(),
        ]);

        $this->actingAs($scanner)
            ->postJson(route('scanner.face'), ['descriptor' => $descriptor])
            ->assertForbidden()
            ->assertJsonPath('code', 'inactive')
            ->assertJsonPath('student.student_number', $student->student_number);

        $this->assertDatabaseCount('attendances', 0);
    }

    public function test_enroll_search_returns_school_students_and_hides_other_schools(): void
    {
        $scanner = User::factory()->scanner()->create();
        $visible = $this->studentFor($scanner, ['first_name' => 'Liza', 'last_name' => 'Cruz']);
        $otherAdmin = User::factory()->admin()->create();
        $this->studentFor($otherAdmin, ['first_name' => 'Liza', 'last_name' => 'Santos']);

        $this->actingAs($scanner)
            ->getJson(route('scanner.faces', ['q' => 'Liza']))
            ->assertOk()
            ->assertJsonCount(1, 'students')
            ->assertJsonPath('students.0.id', $visible->id)
            ->assertJsonMissingPath('students.0.face_descriptor');
    }

    public function test_enroll_search_can_select_a_student_from_a_qr_token(): void
    {
        $scanner = User::factory()->scanner()->create();
        $student = $this->studentFor($scanner);
        $token = $this->issueStudentToken($student);

        $this->actingAs($scanner)
            ->getJson(route('scanner.faces', ['token' => $token]))
            ->assertOk()
            ->assertJsonPath('students.0.id', $student->id);

        $this->assertDatabaseCount('attendances', 0);
    }

    public function test_enroll_lookup_by_id_returns_current_enrollment_status(): void
    {
        $scanner = User::factory()->scanner()->create();
        $student = $this->studentFor($scanner, [
            'face_descriptor' => $this->descriptor(),
            'face_enrolled_at' => now(),
        ]);

        $this->actingAs($scanner)
            ->getJson(route('scanner.faces', ['student_id' => $student->id]))
            ->assertOk()
            ->assertJsonPath('students.0.id', $student->id)
            ->assertJsonPath('students.0.face_enrolled', true);
    }

    public function test_enroll_lookup_hides_a_student_from_another_school(): void
    {
        $scanner = User::factory()->scanner()->create();
        $otherAdmin = User::factory()->admin()->create();
        $student = $this->studentFor($otherAdmin);

        $this->actingAs($scanner)
            ->getJson(route('scanner.faces', ['student_id' => $student->id]))
            ->assertOk()
            ->assertJsonPath('students', []);
    }

    public function test_face_endpoints_require_valid_descriptors(): void
    {
        $scanner = User::factory()->scanner()->create();
        $student = $this->studentFor($scanner);

        $this->actingAs($scanner)
            ->postJson(route('scanner.faces.enroll'), [
                'student_id' => $student->id,
                'descriptor' => [0.1, 0.2],
            ])
            ->assertUnprocessable()
            ->assertJsonValidationErrors('descriptor');

        $this->actingAs($scanner)
            ->postJson(route('scanner.face'), [])
            ->assertUnprocessable()
            ->assertJsonValidationErrors('descriptor');
    }

    public function test_qr_scan_still_records_attendance_with_qr_method(): void
    {
        $scanner = User::factory()->scanner()->create();
        $student = $this->studentFor($scanner);
        $token = $this->issueStudentToken($student);

        $this->actingAs($scanner)
            ->postJson(route('scanner.scan'), ['token' => $token])
            ->assertCreated();

        $this->assertSame(
            AttendanceMethod::Qr,
            Attendance::query()->where('student_id', $student->id)->first()?->method,
        );
    }

    public function test_student_profile_shows_reset_when_a_face_is_enrolled(): void
    {
        $admin = User::factory()->admin()->create();
        $student = $this->studentFor($admin, [
            'face_descriptor' => $this->descriptor(),
            'face_enrolled_at' => now(),
        ]);

        $this->actingAs($admin)
            ->get(route('students.show', $student))
            ->assertOk()
            ->assertSee('Face enrolled')
            ->assertSee('Reset face recognition');

        $this->actingAs($admin)
            ->get(route('students.index'))
            ->assertOk()
            ->assertSee('Enrolled')
            ->assertSee('Reset face');
    }

    public function test_administrator_can_reset_an_enrolled_face(): void
    {
        $admin = User::factory()->admin()->create();
        $descriptor = $this->descriptor();
        $student = $this->studentFor($admin, [
            'face_descriptor' => $descriptor,
            'face_enrolled_at' => now(),
        ]);

        $this->actingAs($admin)
            ->from(route('students.index'))
            ->post(route('students.face.reset', $student), ['confirm' => '1'])
            ->assertRedirect(route('students.index'));

        $student->refresh();

        $this->assertNull($student->face_descriptor);
        $this->assertNull($student->face_enrolled_at);
        $this->assertDatabaseHas('audit_logs', [
            'action' => 'face.reset',
            'subject_id' => $student->id,
        ]);

        $this->actingAs($admin)
            ->postJson(route('scanner.face'), ['descriptor' => $descriptor])
            ->assertUnprocessable()
            ->assertJsonPath('code', 'unrecognized');

        $this->actingAs($admin)
            ->getJson(route('scanner.faces', ['student_id' => $student->id]))
            ->assertOk()
            ->assertJsonPath('students.0.id', $student->id)
            ->assertJsonPath('students.0.face_enrolled', false);
    }

    public function test_reset_face_from_the_profile_stays_on_the_profile(): void
    {
        $admin = User::factory()->admin()->create();
        $student = $this->studentFor($admin, [
            'face_descriptor' => $this->descriptor(),
            'face_enrolled_at' => now(),
        ]);

        $this->actingAs($admin)
            ->from(route('students.show', $student))
            ->post(route('students.face.reset', $student), ['confirm' => '1'])
            ->assertRedirect(route('students.show', $student));
    }

    public function test_reset_face_requires_confirmation(): void
    {
        $admin = User::factory()->admin()->create();
        $student = $this->studentFor($admin, [
            'face_descriptor' => $this->descriptor(),
            'face_enrolled_at' => now(),
        ]);

        $this->actingAs($admin)
            ->from(route('students.show', $student))
            ->post(route('students.face.reset', $student))
            ->assertRedirect(route('students.show', $student))
            ->assertSessionHasErrors('confirm');

        $this->assertNotNull($student->fresh()->face_descriptor);
    }

    public function test_scanner_staff_cannot_reset_a_face(): void
    {
        $admin = User::factory()->admin()->create();
        $scanner = $this->scannerFor($admin);
        $student = $this->studentFor($admin, [
            'face_descriptor' => $this->descriptor(),
            'face_enrolled_at' => now(),
        ]);

        $this->actingAs($scanner)
            ->post(route('students.face.reset', $student), ['confirm' => '1'])
            ->assertForbidden();

        $this->assertNotNull($student->fresh()->face_descriptor);
    }

    public function test_administrator_cannot_reset_a_face_from_another_school(): void
    {
        $admin = User::factory()->admin()->create();
        $otherAdmin = User::factory()->admin()->create();
        $student = $this->studentFor($otherAdmin, [
            'face_descriptor' => $this->descriptor(),
            'face_enrolled_at' => now(),
        ]);

        $this->actingAs($admin)
            ->post(route('students.face.reset', $student), ['confirm' => '1'])
            ->assertNotFound();

        $this->assertNotNull($student->fresh()->face_descriptor);
    }
}
