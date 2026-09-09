<?php

namespace Tests\Feature;

use App\Models\Student;
use App\Models\StudentQrCredential;
use App\Models\User;
use App\Services\StudentQrService;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Tests\TestCase;

class QrCredentialTest extends TestCase
{
    use LazilyRefreshDatabase;

    public function test_replacing_a_qr_revokes_the_previous_credential(): void
    {
        $admin = User::factory()->admin()->create();
        $student = Student::factory()->create();
        $oldToken = $this->issueStudentToken($student);
        $oldHash = app(StudentQrService::class)->hashToken($oldToken);

        $this->actingAs($admin)
            ->post(route('students.qr.replace', $student), ['confirm' => '1'])
            ->assertRedirect(route('students.show', $student));

        $this->assertNotNull(
            StudentQrCredential::query()->where('token_hash', $oldHash)->whereNotNull('revoked_at')->first()
        );
        $this->assertNull(app(StudentQrService::class)->findCurrentByToken($oldToken));
        $this->assertNotNull($student->fresh()->currentQrCredential);
        $this->assertNotSame($oldHash, $student->fresh()->currentQrCredential->token_hash);
    }

    public function test_qr_lists_do_not_expose_plaintext_tokens(): void
    {
        $admin = User::factory()->admin()->create();
        $student = Student::factory()->create();
        $token = $this->issueStudentToken($student);

        $this->actingAs($admin)
            ->get(route('students.index'))
            ->assertOk()
            ->assertDontSee($token);
    }

    public function test_authorized_staff_can_download_a_qr_svg(): void
    {
        $admin = User::factory()->admin()->create();
        $student = Student::factory()->create();
        $this->issueStudentToken($student);

        $this->actingAs($admin)
            ->get(route('students.qr.download', $student))
            ->assertOk()
            ->assertHeader('content-type', 'image/svg+xml');
    }
}
