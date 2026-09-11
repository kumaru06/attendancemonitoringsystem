<?php

namespace Tests\Feature;

use App\Models\Attendance;
use App\Models\Student;
use App\Models\User;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Tests\TestCase;

class AuthorizationTest extends TestCase
{
    use LazilyRefreshDatabase;

    public function test_scanner_staff_are_forbidden_from_admin_pages(): void
    {
        $scanner = User::factory()->scanner()->create();
        $student = $this->studentFor($scanner);

        $this->actingAs($scanner)->get(route('dashboard'))->assertForbidden();
        $this->actingAs($scanner)->get(route('students.index'))->assertForbidden();
        $this->actingAs($scanner)->get(route('students.show', $student))->assertForbidden();
        $this->actingAs($scanner)->get(route('sections.index'))->assertForbidden();
        $this->actingAs($scanner)->get(route('attendances.index'))->assertForbidden();
        $this->actingAs($scanner)->get(route('attendances.export'))->assertForbidden();
        $this->actingAs($scanner)->get(route('attendances.sf2'))->assertForbidden();
        $this->actingAs($scanner)->get(route('users.index'))->assertForbidden();
        $this->actingAs($scanner)->delete(route('users.destroy', $scanner))->assertForbidden();
        $this->actingAs($scanner)->delete(route('students.destroy', $student))->assertForbidden();
    }

    public function test_guest_cannot_open_the_scanner(): void
    {
        $this->get(route('scanner.index'))->assertRedirect(route('login'));
        $this->postJson(route('scanner.scan'), ['token' => 'abc'])->assertUnauthorized();
        $this->getJson(route('scanner.faces', ['q' => 'Ana']))->assertUnauthorized();
        $this->postJson(route('scanner.faces.enroll'), ['student_id' => 1, 'descriptor' => array_fill(0, 128, 0.1)])->assertUnauthorized();
        $this->postJson(route('scanner.face'), ['descriptor' => array_fill(0, 128, 0.1)])->assertUnauthorized();
        $this->post(route('students.face.reset', 1), ['confirm' => '1'])->assertRedirect(route('login'));
        $this->delete(route('students.destroy', 1))->assertRedirect(route('login'));
    }

    public function test_policies_allow_scanners_to_scan_but_not_manage_students(): void
    {
        $admin = User::factory()->admin()->create();
        $scanner = $this->scannerFor($admin);
        $student = $this->studentFor($admin);

        $this->assertTrue($admin->can('viewAny', Student::class));
        $this->assertTrue($admin->can('create', Student::class));
        $this->assertTrue($admin->can('manageQr', $student));
        $this->assertTrue($admin->can('resetFace', $student));
        $this->assertTrue($admin->can('delete', $student));
        $this->assertTrue($admin->can('export', Attendance::class));

        $this->assertFalse($scanner->can('viewAny', Student::class));
        $this->assertFalse($scanner->can('create', Student::class));
        $this->assertFalse($scanner->can('manageQr', $student));
        $this->assertFalse($scanner->can('resetFace', $student));
        $this->assertFalse($scanner->can('delete', $student));
        $this->assertFalse($scanner->can('export', Attendance::class));
        $this->assertTrue($scanner->can('scan', Attendance::class));
        $this->assertTrue($scanner->can('viewPhoto', $student));
        $this->assertTrue($scanner->can('enrollFace', $student));
        $this->assertTrue($admin->can('enrollFace', $student));
    }

    public function test_administrator_can_open_admin_pages(): void
    {
        $admin = User::factory()->admin()->create();

        $this->actingAs($admin)->get(route('dashboard'))->assertOk();
        $this->actingAs($admin)->get(route('scanner.index'))->assertOk();
        $this->actingAs($admin)->get(route('students.index'))->assertOk();
    }

    public function test_administrator_is_forbidden_from_accounts_pages(): void
    {
        $admin = User::factory()->admin()->create();
        $scanner = User::factory()->scanner()->create();

        $this->actingAs($admin)->get(route('users.index'))->assertForbidden();
        $this->actingAs($admin)->get(route('users.create'))->assertForbidden();
        $this->actingAs($admin)->delete(route('users.destroy', $scanner))->assertForbidden();
    }

    public function test_super_administrator_is_forbidden_from_school_pages(): void
    {
        $superAdmin = User::factory()->superAdmin()->create();
        $student = Student::factory()->create();

        $this->actingAs($superAdmin)->get(route('dashboard'))->assertForbidden();
        $this->actingAs($superAdmin)->get(route('scanner.index'))->assertForbidden();
        $this->actingAs($superAdmin)->get(route('students.index'))->assertForbidden();
        $this->actingAs($superAdmin)->get(route('students.show', $student))->assertForbidden();
        $this->actingAs($superAdmin)->get(route('sections.index'))->assertForbidden();
        $this->actingAs($superAdmin)->get(route('attendances.index'))->assertForbidden();
        $this->actingAs($superAdmin)->get(route('attendances.export'))->assertForbidden();
        $this->actingAs($superAdmin)->get(route('attendances.sf2'))->assertForbidden();
    }

    public function test_policies_allow_super_administrators_to_manage_assignable_accounts(): void
    {
        $superAdmin = User::factory()->superAdmin()->create();
        $admin = User::factory()->admin()->create();
        $otherSuperAdmin = User::factory()->superAdmin()->create();

        $this->assertTrue($superAdmin->can('viewAny', User::class));
        $this->assertTrue($superAdmin->can('create', User::class));
        $this->assertTrue($superAdmin->can('update', $admin));
        $this->assertFalse($superAdmin->can('update', $otherSuperAdmin));
        $this->assertFalse($superAdmin->can('delete', $otherSuperAdmin));
        $this->assertFalse($admin->can('viewAny', User::class));
        $this->assertFalse($admin->can('create', User::class));
    }

    public function test_administrator_cannot_open_another_school_student_or_section(): void
    {
        $adminA = User::factory()->admin()->create();
        $adminB = User::factory()->admin()->create();
        $studentB = $this->studentFor($adminB);
        $sectionB = $this->sectionFor($adminB, ['name' => 'Grade 12-Z']);

        $this->actingAs($adminA)->get(route('students.show', $studentB))->assertNotFound();
        $this->actingAs($adminA)->get(route('students.edit', $studentB))->assertNotFound();
        $this->actingAs($adminA)->delete(route('students.destroy', $studentB))->assertNotFound();
        $this->actingAs($adminA)
            ->put(route('sections.update', $sectionB), [
                'name' => 'Hijacked',
                'level' => $sectionB->level->value,
            ])
            ->assertNotFound();
    }

    public function test_policies_deny_cross_school_student_actions(): void
    {
        $adminA = User::factory()->admin()->create();
        $adminB = User::factory()->admin()->create();
        $studentB = $this->studentFor($adminB);

        $this->assertFalse($adminA->can('view', $studentB));
        $this->assertFalse($adminA->can('update', $studentB));
        $this->assertFalse($adminA->can('manageQr', $studentB));
        $this->assertFalse($adminA->can('enrollFace', $studentB));
        $this->assertFalse($adminA->can('resetFace', $studentB));
        $this->assertFalse($adminA->can('delete', $studentB));
    }
}
