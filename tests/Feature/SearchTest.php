<?php

namespace Tests\Feature;

use App\Enums\SchoolLevel;
use App\Models\User;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Tests\TestCase;

class SearchTest extends TestCase
{
    use LazilyRefreshDatabase;

    public function test_guest_cannot_search(): void
    {
        $this->getJson(route('search', ['q' => 'Ana']))
            ->assertUnauthorized();
    }

    public function test_scanner_staff_are_forbidden_from_search(): void
    {
        $scanner = User::factory()->scanner()->create();

        $this->actingAs($scanner)
            ->getJson(route('search', ['q' => 'Ana']))
            ->assertForbidden();
    }

    public function test_search_returns_matching_students_and_sections_for_the_signed_in_school(): void
    {
        $admin = User::factory()->admin()->create();
        $otherAdmin = User::factory()->admin()->create();
        $section = $this->sectionFor($admin, ['name' => 'Ana Homeroom', 'level' => SchoolLevel::College]);
        $this->sectionFor($otherAdmin, ['name' => 'Ana Hidden', 'level' => SchoolLevel::College]);
        $student = $this->studentFor($admin, [
            'first_name' => 'Ana',
            'last_name' => 'Santos',
            'student_number' => '2026-10001',
            'section_id' => $section->id,
        ]);
        $this->studentFor($otherAdmin, [
            'first_name' => 'Ana',
            'last_name' => 'Other',
            'student_number' => '2026-20002',
        ]);

        $this->actingAs($admin)
            ->getJson(route('search', ['q' => 'Ana']))
            ->assertOk()
            ->assertJsonPath('students.0.name', $student->full_name)
            ->assertJsonPath('students.0.url', route('students.show', $student))
            ->assertJsonPath('sections.0.name', 'Ana Homeroom')
            ->assertJsonCount(1, 'students')
            ->assertJsonCount(1, 'sections')
            ->assertJsonMissing(['name' => 'Ana Other'])
            ->assertJsonMissing(['name' => 'Ana Hidden']);
    }

    public function test_empty_search_returns_page_jumps(): void
    {
        $admin = User::factory()->admin()->create();

        $this->actingAs($admin)
            ->getJson(route('search'))
            ->assertOk()
            ->assertJsonPath('jumps.0.label', 'Scanner')
            ->assertJsonPath('jumps.0.url', route('scanner.index'))
            ->assertJsonCount(0, 'students')
            ->assertJsonCount(0, 'sections');
    }

    public function test_search_rejects_a_query_that_is_too_long(): void
    {
        $admin = User::factory()->admin()->create();

        $this->actingAs($admin)
            ->getJson(route('search', ['q' => str_repeat('a', 101)]))
            ->assertUnprocessable()
            ->assertJsonValidationErrors('q');
    }
}
