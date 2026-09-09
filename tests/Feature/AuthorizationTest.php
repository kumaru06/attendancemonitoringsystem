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
        $student = Student::factory()->create();

        $this->actingAs($scanner)->get(route('dashboard'))->assertForbidden();
        $this->actingAs($scanner)->get(route('students.index'))->assertForbidden();
        $this->actingAs($scanner)->get(route('students.show', $student))->assertForbidden();
        $this->actingAs($scanner)->get(route('sections.index'))->assertForbidden();
        $this->actingAs($scanner)->get(route('attendances.index'))->assertForbidden();
        $this->actingAs($scanner)->get(route('attendances.export'))->assertForbidden();
        $this->actingAs($scanner)->get(route('users.index'))->assertForbidden();
        $this->actingAs($scanner)->delete(route('users.destroy', $scanner))->assertForbidden();
    }

    public function test_guest_cannot_open_the_scanner(): void
    {
        $this->get(route('scanner.index'))->assertRedirect(route('login'));
        $this->postJson(route('scanner.scan'), ['token' => 'abc'])->assertUnauthorized();
    }

    public function test_policies_allow_scanners_to_scan_but_not_manage_students(): void
    {
        $admin = User::factory()->admin()->create();
        $scanner = User::factory()->scanner()->create();
        $student = Student::factory()->create();

        $this->assertTrue($admin->can('viewAny', Student::class));
        $this->assertTrue($admin->can('create', Student::class));
        $this->assertTrue($admin->can('manageQr', $student));
        $this->assertTrue($admin->can('export', Attendance::class));

        $this->assertFalse($scanner->can('viewAny', Student::class));
        $this->assertFalse($scanner->can('create', Student::class));
        $this->assertFalse($scanner->can('manageQr', $student));
        $this->assertFalse($scanner->can('export', Attendance::class));
        $this->assertTrue($scanner->can('scan', Attendance::class));
        $this->assertTrue($scanner->can('viewPhoto', $student));
    }

    public function test_administrator_can_open_admin_pages(): void
    {
        $admin = User::factory()->admin()->create();

        $this->actingAs($admin)->get(route('dashboard'))->assertOk();
        $this->actingAs($admin)->get(route('scanner.index'))->assertOk();
        $this->actingAs($admin)->get(route('students.index'))->assertOk();
    }
}
