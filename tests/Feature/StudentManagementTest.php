<?php

namespace Tests\Feature;

use App\Enums\StudentGender;
use App\Models\Attendance;
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
        $section = $this->sectionFor($admin, ['name' => 'Grade 11-A']);

        $this->actingAs($admin)
            ->post(route('students.store'), [
                'student_number' => '2026-10001',
                'first_name' => 'Ana',
                'middle_name' => 'Cruz',
                'last_name' => 'Reyes',
                'gender' => StudentGender::Female->value,
                'section_id' => $section->id,
                'is_active' => '1',
            ])
            ->assertRedirect();

        $student = Student::query()->where('student_number', '2026-10001')->first();

        $this->assertNotNull($student);
        $this->assertSame(StudentGender::Female, $student->gender);
        $this->assertNotNull($student->currentQrCredential);
        $this->assertDatabaseHas('audit_logs', [
            'action' => 'student.created',
            'subject_id' => $student->id,
        ]);
    }

    public function test_student_number_must_be_unique(): void
    {
        $admin = User::factory()->admin()->create();
        $student = $this->studentFor($admin, ['student_number' => '2026-10001']);

        $this->actingAs($admin)
            ->from(route('students.index'))
            ->post(route('students.store'), [
                'student_number' => '2026-10001',
                'first_name' => 'Ben',
                'last_name' => 'Santos',
                'gender' => StudentGender::Male->value,
                'section_id' => $student->section_id,
            ])
            ->assertRedirect(route('students.index'))
            ->assertSessionHasErrors([
                'student_number' => 'The USN/ID Number has already been taken.',
            ]);
    }

    public function test_student_store_rejects_a_missing_gender(): void
    {
        $admin = User::factory()->admin()->create();
        $section = $this->sectionFor($admin, ['name' => 'Grade 11-A']);

        $this->actingAs($admin)
            ->from(route('students.index'))
            ->post(route('students.store'), [
                'student_number' => '2026-10002',
                'first_name' => 'Ben',
                'last_name' => 'Santos',
                'section_id' => $section->id,
            ])
            ->assertRedirect(route('students.index'))
            ->assertSessionHasErrors('gender');
    }

    public function test_administrator_can_deactivate_a_student_and_keep_history(): void
    {
        $admin = User::factory()->admin()->create();
        $student = $this->studentFor($admin, ['is_active' => true]);

        $this->actingAs($admin)
            ->put(route('students.update', $student), [
                'student_number' => $student->student_number,
                'first_name' => $student->first_name,
                'middle_name' => $student->middle_name,
                'last_name' => $student->last_name,
                'gender' => $student->gender->value,
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
        $student = $this->studentFor($admin);

        $this->actingAs($admin)
            ->get(route('students.index'))
            ->assertOk()
            ->assertSee('data-student-panel', false)
            ->assertSee('id="student-panel"', false)
            ->assertSee('data-student-form-panel', false)
            ->assertSee('id="student-form-panel"', false)
            ->assertSee('Actions')
            ->assertSee(route('students.show', $student), false)
            ->assertSee(route('students.edit', $student), false)
            ->assertDontSee('Apply filters')
            ->assertSee('id="student-search"', false);
    }

    public function test_students_directory_partial_returns_the_list_without_site_chrome(): void
    {
        $admin = User::factory()->admin()->create();
        $student = $this->studentFor($admin, [
            'first_name' => 'PartialKid',
            'last_name' => 'Reyes',
        ]);

        $this->actingAs($admin)
            ->get(route('students.index', ['directory' => 1]))
            ->assertOk()
            ->assertSee('PartialKid')
            ->assertSee('id="student-directory"', false)
            ->assertDontSee('Sign out')
            ->assertDontSee('id="app-sidebar"', false)
            ->assertDontSee('Apply filters');
    }

    public function test_students_directory_filters_by_name_or_usn(): void
    {
        $admin = User::factory()->admin()->create();
        $match = $this->studentFor($admin, [
            'first_name' => 'Findable',
            'last_name' => 'Reyes',
            'student_number' => '2026-88888',
        ]);
        $other = $this->studentFor($admin, [
            'first_name' => 'Hidden',
            'last_name' => 'Santos',
            'student_number' => '2026-11111',
        ]);

        $this->actingAs($admin)
            ->get(route('students.index', ['search' => 'Findable']))
            ->assertOk()
            ->assertSee($match->first_name)
            ->assertDontSee($other->first_name);

        $this->actingAs($admin)
            ->get(route('students.index', ['search' => '2026-88888', 'directory' => 1]))
            ->assertOk()
            ->assertSee($match->first_name)
            ->assertDontSee($other->first_name);
    }

    public function test_student_panel_returns_the_profile_without_the_site_chrome(): void
    {
        $admin = User::factory()->admin()->create();
        $student = $this->studentFor($admin, [
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
        $student = $this->studentFor($admin, [
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
        $student = $this->studentFor($admin);

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

    public function test_two_schools_can_reuse_the_same_student_number(): void
    {
        $adminA = User::factory()->admin()->create();
        $adminB = User::factory()->admin()->create();
        $sectionA = $this->sectionFor($adminA, ['name' => 'Grade 11-A']);
        $this->studentFor($adminB, ['student_number' => '2026-10001']);

        $this->actingAs($adminA)
            ->post(route('students.store'), [
                'student_number' => '2026-10001',
                'first_name' => 'Ana',
                'last_name' => 'Reyes',
                'gender' => StudentGender::Female->value,
                'section_id' => $sectionA->id,
                'is_active' => '1',
            ])
            ->assertRedirect();

        $this->assertSame(
            2,
            Student::withoutGlobalScopes()->where('student_number', '2026-10001')->count()
        );
    }

    public function test_administrator_does_not_see_another_school_on_the_directory(): void
    {
        $adminA = User::factory()->admin()->create();
        $adminB = User::factory()->admin()->create();
        $studentA = $this->studentFor($adminA, ['first_name' => 'AlphaKid']);
        $studentB = $this->studentFor($adminB, ['first_name' => 'BravoKid']);

        $this->actingAs($adminA)
            ->get(route('students.index'))
            ->assertOk()
            ->assertSee($studentA->first_name)
            ->assertDontSee($studentB->first_name);
    }

    public function test_add_student_opens_the_floating_form_on_the_directory(): void
    {
        $admin = User::factory()->admin()->create();

        $this->actingAs($admin)
            ->get(route('students.create'))
            ->assertRedirect(route('students.index', ['add' => 1]));

        $this->actingAs($admin)
            ->get(route('students.index', ['add' => 1]))
            ->assertOk()
            ->assertSee('id="student-form-panel"', false)
            ->assertSee('student-form-panel is-open', false)
            ->assertSee('Register student');
    }
}
