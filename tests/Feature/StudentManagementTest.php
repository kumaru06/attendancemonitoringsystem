<?php

namespace Tests\Feature;

use App\Models\Attendance;
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

    public function test_students_directory_opens_records_from_the_list(): void
    {
        $admin = User::factory()->admin()->create();
        $student = Student::factory()->create();

        $this->actingAs($admin)
            ->get(route('students.index'))
            ->assertOk()
            ->assertSee('data-student-panel', false)
            ->assertSee('id="student-panel"', false)
            ->assertSee('Actions')
            ->assertSee(route('students.show', $student), false)
            ->assertSee(route('students.edit', $student), false);
    }

    public function test_student_panel_returns_the_profile_without_the_site_chrome(): void
    {
        $admin = User::factory()->admin()->create();
        $student = Student::factory()->create([
            'first_name' => 'Abner',
            'middle_name' => null,
            'last_name' => 'Auer',
        ]);

        $this->actingAs($admin)
            ->get(route('students.show', ['student' => $student, 'panel' => 1]))
            ->assertOk()
            ->assertSee('Abner Auer')
            ->assertSee('Attendance history')
            ->assertDontSee('Sign out')
            ->assertDontSee('id="app-sidebar"', false);
    }

    public function test_student_panel_escapes_student_names_in_html(): void
    {
        $admin = User::factory()->admin()->create();
        $student = Student::factory()->create([
            'first_name' => '<script>alert(1)</script>',
            'middle_name' => null,
            'last_name' => 'Auer',
        ]);

        $this->actingAs($admin)
            ->get(route('students.show', ['student' => $student, 'panel' => 1]))
            ->assertOk()
            ->assertDontSee('<script>alert(1)</script>', false)
            ->assertSee('&lt;script&gt;alert(1)&lt;/script&gt;', false);
    }

    public function test_student_panel_lists_the_fifty_most_recent_attendance_days(): void
    {
        $this->travelTo('2026-09-09 08:00:00');

        $admin = User::factory()->admin()->create();
        $student = Student::factory()->create();

        foreach (range(0, 50) as $daysAgo) {
            Attendance::factory()->create([
                'student_id' => $student->id,
                'recorded_by' => $admin->id,
                'attendance_date' => now()->subDays($daysAgo)->toDateString(),
                'time_in' => '07:15:00',
            ]);
        }

        $this->actingAs($admin)
            ->get(route('students.show', ['student' => $student, 'panel' => 1]))
            ->assertOk()
            ->assertSee('2026-09-09')
            ->assertSee(now()->subDays(49)->toDateString())
            ->assertDontSee(now()->subDays(50)->toDateString());
    }
}
