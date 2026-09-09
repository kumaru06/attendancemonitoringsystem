<?php

namespace Tests\Feature;

use App\Models\Section;
use App\Models\Student;
use App\Models\User;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Tests\TestCase;

class StudentManagementTest extends TestCase
{
    use LazilyRefreshDatabase;

    public function test_administrator_can_register_a_student_and_issue_a_qr(): void
    {
        $admin = User::factory()->admin()->create();
        $section = Section::factory()->create(['name' => 'Grade 11-A']);

        $this->actingAs($admin)
            ->post(route('students.store'), [
                'student_number' => '2026-10001',
                'first_name' => 'Ana',
                'middle_name' => 'Cruz',
                'last_name' => 'Reyes',
                'section_id' => $section->id,
                'is_active' => '1',
            ])
            ->assertRedirect();

        $student = Student::query()->where('student_number', '2026-10001')->first();

        $this->assertNotNull($student);
        $this->assertNotNull($student->currentQrCredential);
        $this->assertDatabaseHas('audit_logs', [
            'action' => 'student.created',
            'subject_id' => $student->id,
        ]);
    }

    public function test_student_number_must_be_unique(): void
    {
        $admin = User::factory()->admin()->create();
        $student = Student::factory()->create(['student_number' => '2026-10001']);

        $this->actingAs($admin)
            ->from(route('students.create'))
            ->post(route('students.store'), [
                'student_number' => '2026-10001',
                'first_name' => 'Ben',
                'last_name' => 'Santos',
                'section_id' => $student->section_id,
            ])
            ->assertRedirect(route('students.create'))
            ->assertSessionHasErrors('student_number');
    }

    public function test_administrator_can_deactivate_a_student_and_keep_history(): void
    {
        $admin = User::factory()->admin()->create();
        $student = Student::factory()->create(['is_active' => true]);

        $this->actingAs($admin)
            ->put(route('students.update', $student), [
                'student_number' => $student->student_number,
                'first_name' => $student->first_name,
                'middle_name' => $student->middle_name,
                'last_name' => $student->last_name,
                'section_id' => $student->section_id,
            ])
            ->assertRedirect(route('students.show', $student));

        $this->assertFalse($student->fresh()->is_active);
        $this->assertDatabaseHas('audit_logs', [
            'action' => 'student.deactivated',
            'subject_id' => $student->id,
        ]);
    }
}
