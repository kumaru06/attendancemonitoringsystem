<?php

namespace Tests\Feature;

use App\Enums\SchoolLevel;
use App\Models\Attendance;
use App\Models\Student;
use App\Models\User;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Tests\TestCase;

class SectionLevelTest extends TestCase
{
    use LazilyRefreshDatabase;

    public function test_administrator_can_create_a_section_with_a_school_level(): void
    {
        $admin = User::factory()->admin()->create();

        $this->actingAs($admin)
            ->post(route('sections.store'), [
                'name' => 'BSIT 1-A',
                'level' => SchoolLevel::College->value,
            ])
            ->assertRedirect(route('sections.index'));

        $this->assertDatabaseHas('sections', [
            'name' => 'BSIT 1-A',
            'level' => SchoolLevel::College->value,
            'school_id' => $admin->school_id,
        ]);
    }

    public function test_administrator_can_create_a_kinder_section(): void
    {
        $admin = User::factory()->admin()->create();

        $this->actingAs($admin)
            ->post(route('sections.store'), [
                'name' => 'Kinder 1-A',
                'level' => SchoolLevel::Kinder->value,
            ])
            ->assertRedirect(route('sections.index'));

        $this->assertDatabaseHas('sections', [
            'name' => 'Kinder 1-A',
            'level' => SchoolLevel::Kinder->value,
            'school_id' => $admin->school_id,
        ]);
    }

    public function test_section_store_rejects_a_missing_school_level(): void
    {
        $admin = User::factory()->admin()->create();

        $this->actingAs($admin)
            ->from(route('sections.index'))
            ->post(route('sections.store'), ['name' => 'Grade 11-A'])
            ->assertRedirect(route('sections.index'))
            ->assertSessionHasErrors('level');
    }

    public function test_students_index_filters_by_school_level(): void
    {
        $admin = User::factory()->admin()->create();
        $college = $this->sectionFor($admin, ['name' => 'BSIT 1-A', 'level' => SchoolLevel::College]);
        $shs = $this->sectionFor($admin, ['name' => 'Grade 11-A', 'level' => SchoolLevel::Shs]);
        $collegeStudent = Student::factory()->create([
            'first_name' => 'CollegeKid',
            'section_id' => $college->id,
        ]);
        $shsStudent = Student::factory()->create([
            'first_name' => 'ShsKid',
            'section_id' => $shs->id,
        ]);

        $this->actingAs($admin)
            ->get(route('students.index', ['level' => SchoolLevel::College->value]))
            ->assertOk()
            ->assertSee($collegeStudent->first_name)
            ->assertDontSee($shsStudent->first_name);
    }

    public function test_students_directory_partial_filters_by_school_level(): void
    {
        $admin = User::factory()->admin()->create();
        $college = $this->sectionFor($admin, ['name' => 'BSIT 1-A', 'level' => SchoolLevel::College]);
        $shs = $this->sectionFor($admin, ['name' => 'Grade 11-A', 'level' => SchoolLevel::Shs]);
        $collegeStudent = Student::factory()->create([
            'first_name' => 'CollegeKid',
            'section_id' => $college->id,
        ]);
        $shsStudent = Student::factory()->create([
            'first_name' => 'ShsKid',
            'section_id' => $shs->id,
        ]);

        $this->actingAs($admin)
            ->get(route('students.index', [
                'level' => SchoolLevel::College->value,
                'directory' => 1,
            ]))
            ->assertOk()
            ->assertSee($collegeStudent->first_name)
            ->assertDontSee($shsStudent->first_name)
            ->assertDontSee('Sign out');
    }

    public function test_attendance_index_filters_by_school_level(): void
    {
        $this->travelTo(now('Asia/Manila')->setTime(9, 0, 0));

        $admin = User::factory()->admin()->create();
        $college = $this->sectionFor($admin, ['level' => SchoolLevel::College]);
        $elementary = $this->sectionFor($admin, ['level' => SchoolLevel::Elementary]);
        $collegeStudent = Student::factory()->create([
            'first_name' => 'Tertiary',
            'section_id' => $college->id,
        ]);
        $elementaryStudent = Student::factory()->create([
            'first_name' => 'ElemChild',
            'section_id' => $elementary->id,
        ]);

        Attendance::factory()->create([
            'student_id' => $collegeStudent->id,
            'attendance_date' => now('Asia/Manila')->toDateString(),
            'recorded_by' => $admin->id,
        ]);
        Attendance::factory()->create([
            'student_id' => $elementaryStudent->id,
            'attendance_date' => now('Asia/Manila')->toDateString(),
            'recorded_by' => $admin->id,
        ]);

        $this->actingAs($admin)
            ->get(route('attendances.index', ['level' => SchoolLevel::College->value]))
            ->assertOk()
            ->assertSee('Choose a section to open SF2.')
            ->assertDontSee('Tertiary')
            ->assertDontSee('ElemChild');

        $this->actingAs($admin)
            ->get(route('attendances.index', [
                'month' => now('Asia/Manila')->format('Y-m'),
                'level' => SchoolLevel::College->value,
                'section_id' => $college->id,
            ]))
            ->assertOk()
            ->assertSee($collegeStudent->sf2Name())
            ->assertDontSee($elementaryStudent->sf2Name());
    }

    public function test_attendance_index_shows_the_learner_instead_of_the_admin_recorder(): void
    {
        $this->travelTo(now('Asia/Manila')->setTime(9, 0, 0));

        $admin = User::factory()->admin()->create(['name' => 'School Administrator']);
        $section = $this->sectionFor($admin);
        $student = $this->studentFor($admin, [
            'first_name' => 'Ana',
            'last_name' => 'Santos',
            'section_id' => $section->id,
        ]);

        Attendance::factory()->create([
            'student_id' => $student->id,
            'attendance_date' => now('Asia/Manila')->toDateString(),
            'recorded_by' => $admin->id,
        ]);

        $this->actingAs($admin)
            ->get(route('attendances.index', [
                'month' => now('Asia/Manila')->format('Y-m'),
                'section_id' => $section->id,
            ]))
            ->assertOk()
            ->assertSee($student->sf2Name())
            ->assertSee('MALE | TOTAL Per Day')
            ->assertDontSee('Recorded by');
    }

    public function test_attendance_index_shows_scanner_instead_of_the_recorder_name(): void
    {
        $this->travelTo(now('Asia/Manila')->setTime(9, 0, 0));

        $admin = User::factory()->admin()->create();
        $scanner = $this->scannerFor($admin, ['name' => 'Gate Staff']);
        $section = $this->sectionFor($admin);
        $student = $this->studentFor($admin, [
            'first_name' => 'Ana',
            'last_name' => 'Santos',
            'section_id' => $section->id,
        ]);

        Attendance::factory()->create([
            'student_id' => $student->id,
            'attendance_date' => now('Asia/Manila')->toDateString(),
            'recorded_by' => $scanner->id,
        ]);

        $this->actingAs($admin)
            ->get(route('attendances.index', [
                'month' => now('Asia/Manila')->format('Y-m'),
                'section_id' => $section->id,
            ]))
            ->assertOk()
            ->assertSee($student->sf2Name())
            ->assertDontSee('Gate Staff')
            ->assertDontSee('Recorded by');
    }

    public function test_dashboard_shows_present_counts_by_school_level(): void
    {
        $this->travelTo(now('Asia/Manila')->setTime(9, 0, 0));

        $admin = User::factory()->admin()->create();
        $college = $this->sectionFor($admin, ['level' => SchoolLevel::College]);
        $student = Student::factory()->create(['section_id' => $college->id]);

        Attendance::factory()->create([
            'student_id' => $student->id,
            'attendance_date' => now('Asia/Manila')->toDateString(),
            'recorded_by' => $admin->id,
        ]);

        $this->actingAs($admin)
            ->get(route('dashboard'))
            ->assertOk()
            ->assertSee('College')
            ->assertSee('1 / 1');
    }

    public function test_sections_index_renders_each_school_level_as_its_own_group(): void
    {
        $admin = User::factory()->admin()->create();
        $this->sectionFor($admin, ['name' => 'Kinder 1-A', 'level' => SchoolLevel::Kinder]);
        $this->sectionFor($admin, ['name' => 'Grade 1-A', 'level' => SchoolLevel::Elementary]);
        $this->sectionFor($admin, ['name' => 'Grade 7-A', 'level' => SchoolLevel::Jhs]);
        $this->sectionFor($admin, ['name' => 'Grade 11-A', 'level' => SchoolLevel::Shs]);
        $this->sectionFor($admin, ['name' => 'BSIT 1-A', 'level' => SchoolLevel::College]);

        $this->actingAs($admin)
            ->get(route('sections.index'))
            ->assertOk()
            ->assertSeeInOrder([
                'id="section-level-kinder"',
                'Kinder 1-A',
                'id="section-level-elementary"',
                'Grade 1-A',
                'id="section-level-jhs"',
                'Grade 7-A',
                'id="section-level-shs"',
                'Grade 11-A',
                'id="section-level-college"',
                'BSIT 1-A',
            ], false)
            ->assertSee('data-section-panel', false)
            ->assertSee('id="section-panel"', false)
            ->assertSee('data-section-form-panel', false)
            ->assertSee('id="section-form-panel"', false)
            ->assertSee('bg-rose-50', false)
            ->assertSee('bg-sky-50', false)
            ->assertSee('bg-amber-50', false)
            ->assertSee('bg-violet-50', false)
            ->assertSee('bg-emerald-50', false)
            ->assertSee('section-list', false)
            ->assertSee('Add section')
            ->assertDontSee('>Update</button>', false);
    }

    public function test_dashboard_counts_only_the_signed_in_administrator_school(): void
    {
        $this->travelTo(now('Asia/Manila')->setTime(9, 0, 0));

        $adminA = User::factory()->admin()->create();
        $adminB = User::factory()->admin()->create();
        $studentA = $this->studentFor($adminA, ['first_name' => 'AlphaKid']);
        $studentB = $this->studentFor($adminB, ['first_name' => 'BravoKid']);

        Attendance::factory()->create([
            'student_id' => $studentA->id,
            'attendance_date' => now('Asia/Manila')->toDateString(),
            'recorded_by' => $adminA->id,
        ]);
        Attendance::factory()->create([
            'student_id' => $studentB->id,
            'attendance_date' => now('Asia/Manila')->toDateString(),
            'recorded_by' => $adminB->id,
        ]);

        $this->actingAs($adminA)
            ->get(route('dashboard'))
            ->assertOk()
            ->assertSee('AlphaKid')
            ->assertDontSee('BravoKid')
            ->assertSee('1 / 1');
    }

    public function test_attendance_index_hides_another_school(): void
    {
        $this->travelTo(now('Asia/Manila')->setTime(9, 0, 0));

        $adminA = User::factory()->admin()->create();
        $adminB = User::factory()->admin()->create();
        $studentA = $this->studentFor($adminA, ['first_name' => 'AlphaPresent']);
        $studentB = $this->studentFor($adminB, ['first_name' => 'BravoPresent']);

        Attendance::factory()->create([
            'student_id' => $studentA->id,
            'attendance_date' => now('Asia/Manila')->toDateString(),
            'recorded_by' => $adminA->id,
        ]);
        Attendance::factory()->create([
            'student_id' => $studentB->id,
            'attendance_date' => now('Asia/Manila')->toDateString(),
            'recorded_by' => $adminB->id,
        ]);

        $this->actingAs($adminA)
            ->get(route('attendances.index', [
                'month' => now('Asia/Manila')->format('Y-m'),
                'section_id' => $studentA->section_id,
            ]))
            ->assertOk()
            ->assertSee('AlphaPresent')
            ->assertDontSee('BravoPresent');
    }
}
