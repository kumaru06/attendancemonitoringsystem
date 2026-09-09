<?php

namespace Tests\Feature;

use App\Enums\SchoolLevel;
use App\Models\Attendance;
use App\Models\Section;
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
        $college = Section::factory()->college()->create(['name' => 'BSIT 1-A']);
        $shs = Section::factory()->shs()->create(['name' => 'Grade 11-A']);
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

    public function test_attendance_index_filters_by_school_level(): void
    {
        $this->travelTo(now('Asia/Manila')->setTime(9, 0, 0));

        $admin = User::factory()->admin()->create();
        $college = Section::factory()->college()->create();
        $elementary = Section::factory()->elementary()->create();
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
            ->assertSee('Tertiary')
            ->assertDontSee('ElemChild');
    }

    public function test_attendance_index_shows_admin_instead_of_the_recorder_name(): void
    {
        $this->travelTo(now('Asia/Manila')->setTime(9, 0, 0));

        $admin = User::factory()->admin()->create(['name' => 'School Administrator']);
        $student = Student::factory()->create();

        Attendance::factory()->create([
            'student_id' => $student->id,
            'attendance_date' => now('Asia/Manila')->toDateString(),
            'recorded_by' => $admin->id,
        ]);

        $this->actingAs($admin)
            ->get(route('attendances.index'))
            ->assertOk()
            ->assertSee('>Admin</td>', false)
            ->assertDontSee('>School Administrator</td>', false);
    }

    public function test_attendance_index_shows_scanner_instead_of_the_recorder_name(): void
    {
        $this->travelTo(now('Asia/Manila')->setTime(9, 0, 0));

        $admin = User::factory()->admin()->create();
        $scanner = User::factory()->scanner()->create(['name' => 'Gate Staff']);
        $student = Student::factory()->create();

        Attendance::factory()->create([
            'student_id' => $student->id,
            'attendance_date' => now('Asia/Manila')->toDateString(),
            'recorded_by' => $scanner->id,
        ]);

        $this->actingAs($admin)
            ->get(route('attendances.index'))
            ->assertOk()
            ->assertSee('>Scanner</td>', false)
            ->assertDontSee('>Gate Staff</td>', false);
    }

    public function test_dashboard_shows_present_counts_by_school_level(): void
    {
        $this->travelTo(now('Asia/Manila')->setTime(9, 0, 0));

        $admin = User::factory()->admin()->create();
        $college = Section::factory()->college()->create();
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
        Section::factory()->elementary()->create(['name' => 'Grade 1-A']);
        Section::factory()->jhs()->create(['name' => 'Grade 7-A']);
        Section::factory()->shs()->create(['name' => 'Grade 11-A']);
        Section::factory()->college()->create(['name' => 'BSIT 1-A']);

        $this->actingAs($admin)
            ->get(route('sections.index'))
            ->assertOk()
            ->assertSeeInOrder([
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
            ->assertDontSee('>Update</button>', false);
    }
}
