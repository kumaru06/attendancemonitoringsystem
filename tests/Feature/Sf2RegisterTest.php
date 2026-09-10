<?php

namespace Tests\Feature;

use App\Enums\StudentGender;
use App\Models\Attendance;
use App\Models\Student;
use App\Models\User;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use PhpOffice\PhpSpreadsheet\IOFactory;
use Tests\TestCase;

class Sf2RegisterTest extends TestCase
{
    use LazilyRefreshDatabase;

    public function test_attendance_index_shows_the_section_register_for_the_month(): void
    {
        $this->travelTo(now('Asia/Manila')->setDate(2026, 9, 10)->setTime(15, 0, 0));

        $admin = User::factory()->admin()->create();
        $section = $this->sectionFor($admin, ['name' => 'Grade 11-A']);
        $student = Student::factory()->create([
            'first_name' => 'Ana',
            'middle_name' => 'Cruz',
            'last_name' => 'Santos',
            'gender' => StudentGender::Male,
            'section_id' => $section->id,
        ]);

        $this->actingAs($admin)
            ->get(route('attendances.index', [
                'month' => '2026-09',
                'section_id' => $section->id,
            ]))
            ->assertOk()
            ->assertSee('Santos, Ana, Cruz')
            ->assertSee($student->sf2Name());
    }

    public function test_scanned_day_is_present_and_a_past_weekday_without_a_scan_is_absent(): void
    {
        $this->travelTo(now('Asia/Manila')->setDate(2026, 9, 10)->setTime(15, 0, 0));

        $admin = User::factory()->admin()->create();
        $section = $this->sectionFor($admin, ['name' => 'Grade 11-A']);
        $student = Student::factory()->create([
            'first_name' => 'Ana',
            'middle_name' => 'Cruz',
            'last_name' => 'Santos',
            'gender' => StudentGender::Male,
            'section_id' => $section->id,
        ]);

        Attendance::factory()->create([
            'student_id' => $student->id,
            'attendance_date' => '2026-09-10',
            'recorded_by' => $admin->id,
        ]);

        $this->actingAs($admin)
            ->get(route('attendances.index', [
                'month' => '2026-09',
                'section_id' => $section->id,
            ]))
            ->assertOk()
            ->assertSee('data-student="'.$student->id.'" data-day="10" data-mark="present"', false)
            ->assertSee('data-student="'.$student->id.'" data-day="9" data-mark="absent"', false)
            ->assertSee('>(x)</td>', false);
    }

    public function test_attendance_index_hides_a_student_from_another_section(): void
    {
        $this->travelTo(now('Asia/Manila')->setDate(2026, 9, 10)->setTime(15, 0, 0));

        $admin = User::factory()->admin()->create();
        $section = $this->sectionFor($admin, ['name' => 'Grade 11-A']);
        $otherSection = $this->sectionFor($admin, ['name' => 'Grade 12-B']);
        Student::factory()->create([
            'first_name' => 'Ana',
            'last_name' => 'Santos',
            'gender' => StudentGender::Female,
            'section_id' => $section->id,
        ]);
        Student::factory()->create([
            'first_name' => 'Other',
            'last_name' => 'SectionKid',
            'gender' => StudentGender::Male,
            'section_id' => $otherSection->id,
        ]);

        $this->actingAs($admin)
            ->get(route('attendances.index', [
                'month' => '2026-09',
                'section_id' => $section->id,
            ]))
            ->assertOk()
            ->assertSee('Santos, Ana')
            ->assertDontSee('SectionKid');
    }

    public function test_learners_without_gender_are_omitted_from_male_and_female_totals(): void
    {
        $this->travelTo(now('Asia/Manila')->setDate(2026, 9, 10)->setTime(15, 0, 0));

        $admin = User::factory()->admin()->create();
        $section = $this->sectionFor($admin, ['name' => 'Grade 11-A']);
        Student::factory()->create([
            'first_name' => 'Pat',
            'last_name' => 'NoGender',
            'gender' => null,
            'section_id' => $section->id,
        ]);
        Student::factory()->create([
            'first_name' => 'Ana',
            'last_name' => 'Santos',
            'gender' => StudentGender::Female,
            'section_id' => $section->id,
        ]);

        $this->actingAs($admin)
            ->get(route('attendances.index', [
                'month' => '2026-09',
                'section_id' => $section->id,
            ]))
            ->assertOk()
            ->assertSee('NoGender, Pat')
            ->assertSee('omitted from Male/Female totals until edited')
            ->assertSee('1 (0 / 1)');
    }

    public function test_attendance_index_escapes_learner_names(): void
    {
        $this->travelTo(now('Asia/Manila')->setDate(2026, 9, 10)->setTime(15, 0, 0));

        $admin = User::factory()->admin()->create();
        $section = $this->sectionFor($admin, ['name' => 'Grade 11-A']);
        Student::factory()->create([
            'first_name' => '<script>alert(1)</script>',
            'middle_name' => null,
            'last_name' => 'Santos',
            'gender' => StudentGender::Male,
            'section_id' => $section->id,
        ]);

        $response = $this->actingAs($admin)
            ->get(route('attendances.index', [
                'month' => '2026-09',
                'section_id' => $section->id,
            ]));

        $response->assertOk();
        $this->assertStringContainsString('&lt;script&gt;', $response->getContent());
        $this->assertStringNotContainsString('<script>alert(1)</script>', $response->getContent());
    }

    public function test_sf2_download_returns_xlsx_with_the_section_and_learner_name(): void
    {
        $this->travelTo(now('Asia/Manila')->setDate(2026, 9, 10)->setTime(15, 0, 0));

        $admin = User::factory()->admin()->create();
        $section = $this->sectionFor($admin, ['name' => 'Grade 11-A']);
        $student = Student::factory()->create([
            'first_name' => 'Ana',
            'middle_name' => 'Cruz',
            'last_name' => 'Santos',
            'gender' => StudentGender::Male,
            'section_id' => $section->id,
        ]);

        $response = $this->actingAs($admin)->get(route('attendances.sf2', [
            'month' => '2026-09',
            'section_id' => $section->id,
        ]));

        $response->assertOk();
        $response->assertHeader('content-type', 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
        $this->assertStringContainsString('SF2-Grade-11-A-2026-09.xlsx', (string) $response->headers->get('content-disposition'));

        $path = tempnam(sys_get_temp_dir(), 'sf2').'.xlsx';
        file_put_contents($path, $response->streamedContent());

        $spreadsheet = IOFactory::load($path);
        $sheet = $spreadsheet->getActiveSheet();

        $this->assertSame($student->sf2Name(), $sheet->getCell('B14')->getValue());
        $this->assertStringContainsString(
            $section->name,
            collect($sheet->rangeToArray('A6:AM8', null, true, false))->flatten()->implode(' '),
        );

        unlink($path);
    }

    public function test_attendance_index_lists_sections_in_the_filter(): void
    {
        $admin = User::factory()->admin()->create();
        $section = $this->sectionFor($admin, ['name' => 'Grade 11-A']);

        $this->actingAs($admin)
            ->get(route('attendances.index'))
            ->assertOk()
            ->assertSee('value="'.$section->id.'"', false)
            ->assertSee('Grade 11-A')
            ->assertSee('Choose a section to open SF2.');
    }

    public function test_attendance_index_prompts_to_add_a_section_when_none_exist(): void
    {
        $admin = User::factory()->admin()->create();

        $this->actingAs($admin)
            ->get(route('attendances.index'))
            ->assertOk()
            ->assertSee('No sections yet.')
            ->assertSee(route('sections.index'), false)
            ->assertDontSee('Choose a section to open SF2.');
    }

    public function test_sf2_export_redirects_when_no_section_is_chosen(): void
    {
        $admin = User::factory()->admin()->create();

        $this->actingAs($admin)
            ->from(route('attendances.index'))
            ->get(route('attendances.sf2'))
            ->assertRedirect(route('attendances.index'))
            ->assertSessionHasErrors('section_id');
    }

    public function test_sf2_export_rejects_another_schools_section(): void
    {
        $adminA = User::factory()->admin()->create();
        $adminB = User::factory()->admin()->create();
        $sectionB = $this->sectionFor($adminB, ['name' => 'Grade 12-Z']);

        $this->actingAs($adminA)
            ->from(route('attendances.index'))
            ->get(route('attendances.sf2', [
                'month' => '2026-09',
                'section_id' => $sectionB->id,
            ]))
            ->assertRedirect(route('attendances.index'))
            ->assertSessionHasErrors('section_id');
    }
}
