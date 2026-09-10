<?php

namespace Tests\Feature;

use App\Models\Attendance;
use App\Models\Student;
use App\Models\User;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Tests\TestCase;

class AttendanceExportTest extends TestCase
{
    use LazilyRefreshDatabase;

    public function test_csv_export_matches_filters_and_neutralizes_formula_text(): void
    {
        $this->travelTo(now('Asia/Manila')->setTime(8, 0, 0));

        $admin = User::factory()->admin()->create(['name' => 'Registrar']);
        $includedSection = $this->sectionFor($admin, ['name' => 'Grade 11-A']);
        $otherSection = $this->sectionFor($admin, ['name' => 'Grade 12-B']);

        $included = Student::factory()->create([
            'student_number' => '=1+1',
            'first_name' => '+Danger',
            'middle_name' => null,
            'last_name' => 'Student',
            'section_id' => $includedSection->id,
        ]);

        $excluded = Student::factory()->create([
            'student_number' => '2026-20002',
            'section_id' => $otherSection->id,
        ]);

        Attendance::factory()->create([
            'student_id' => $included->id,
            'attendance_date' => now('Asia/Manila')->toDateString(),
            'time_in' => '08:00:00',
            'recorded_by' => $admin->id,
        ]);

        Attendance::factory()->create([
            'student_id' => $excluded->id,
            'attendance_date' => now('Asia/Manila')->toDateString(),
            'recorded_by' => $admin->id,
        ]);

        $response = $this->actingAs($admin)->get(route('attendances.export', [
            'from' => now('Asia/Manila')->toDateString(),
            'to' => now('Asia/Manila')->toDateString(),
            'section_id' => $includedSection->id,
            'search' => 'Danger',
        ]));

        $response->assertOk();
        $response->assertHeader('content-type', 'text/csv; charset=UTF-8');

        $csv = $response->streamedContent();

        $this->assertStringContainsString('USN/ID Number', $csv);
        $this->assertStringContainsString("'=1+1", $csv);
        $this->assertStringContainsString("'+Danger Student", $csv);
        $this->assertStringContainsString('Grade 11-A', $csv);
        $this->assertStringContainsString('SHS', $csv);
        $this->assertStringContainsString('Admin', $csv);
        $this->assertStringNotContainsString('Registrar', $csv);
        $this->assertStringNotContainsString('2026-20002', $csv);
        $this->assertStringNotContainsString('Grade 12-B', $csv);
    }

    public function test_attendance_index_shows_the_monthly_register_for_the_section(): void
    {
        $this->travelTo(now('Asia/Manila')->setTime(9, 0, 0));

        $admin = User::factory()->admin()->create();
        $section = $this->sectionFor($admin);
        $todayStudent = $this->studentFor($admin, [
            'first_name' => 'Today',
            'last_name' => 'Present',
            'section_id' => $section->id,
        ]);
        $yesterdayStudent = $this->studentFor($admin, [
            'first_name' => 'Yesterday',
            'last_name' => 'Scanned',
            'section_id' => $section->id,
        ]);

        Attendance::factory()->create([
            'student_id' => $todayStudent->id,
            'attendance_date' => now('Asia/Manila')->toDateString(),
            'recorded_by' => $admin->id,
        ]);

        Attendance::factory()->create([
            'student_id' => $yesterdayStudent->id,
            'attendance_date' => now('Asia/Manila')->subDay()->toDateString(),
            'recorded_by' => $admin->id,
        ]);

        $this->actingAs($admin)
            ->get(route('attendances.index', [
                'month' => now('Asia/Manila')->format('Y-m'),
                'section_id' => $section->id,
            ]))
            ->assertOk()
            ->assertSee('Present, Today')
            ->assertSee('Scanned, Yesterday');
    }
}
