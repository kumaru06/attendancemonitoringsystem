<?php

namespace Tests\Feature;

use App\Enums\UserRole;
use App\Models\Attendance;
use App\Models\Student;
use App\Models\User;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Tests\TestCase;

class UserManagementTest extends TestCase
{
    use LazilyRefreshDatabase;

    public function test_accounts_index_renders_an_actions_menu(): void
    {
        $superAdmin = User::factory()->superAdmin()->create();
        $scanner = User::factory()->scanner()->create(['name' => 'Gate Staff']);

        $this->actingAs($superAdmin)
            ->get(route('users.index'))
            ->assertOk()
            ->assertSee('Actions')
            ->assertSee('School')
            ->assertSee($scanner->school->name)
            ->assertSee('Edit')
            ->assertSee('Delete')
            ->assertSee(route('users.edit', $scanner), false)
            ->assertSee(route('users.destroy', $scanner), false);
    }

    public function test_accounts_index_does_not_list_super_administrators(): void
    {
        $superAdmin = User::factory()->superAdmin()->create(['name' => 'Root Owner']);
        $otherSuperAdmin = User::factory()->superAdmin()->create(['name' => 'Hidden Super']);
        User::factory()->admin()->create(['name' => 'School Admin']);

        $this->actingAs($superAdmin)
            ->get(route('users.index'))
            ->assertOk()
            ->assertSee('School Admin')
            ->assertDontSee('Hidden Super')
            ->assertDontSee(route('users.edit', $otherSuperAdmin), false);
    }

    public function test_super_administrator_can_create_an_administrator(): void
    {
        $superAdmin = User::factory()->superAdmin()->create();

        $this->actingAs($superAdmin)
            ->post(route('users.store'), [
                'name' => 'New Principal',
                'username' => 'principal',
                'password' => 'password',
                'password_confirmation' => 'password',
                'role' => UserRole::Admin->value,
                'school_name' => 'Riverside High',
                'is_active' => '1',
            ])
            ->assertRedirect(route('users.index'));

        $admin = User::query()->where('username', 'principal')->first();

        $this->assertNotNull($admin);
        $this->assertSame(UserRole::Admin, $admin->role);
        $this->assertNotNull($admin->school_id);
        $this->assertSame('Riverside High', $admin->school->name);
        $this->assertFalse($admin->schoolHasStudents());
    }

    public function test_create_account_form_does_not_offer_scanner_staff(): void
    {
        $superAdmin = User::factory()->superAdmin()->create();

        $this->actingAs($superAdmin)
            ->get(route('users.create'))
            ->assertOk()
            ->assertSee('School name')
            ->assertSee('value="admin"', false)
            ->assertDontSee('value="scanner"', false)
            ->assertDontSee('value="superadmin"', false);
    }

    public function test_super_administrator_cannot_create_scanner_staff(): void
    {
        $superAdmin = User::factory()->superAdmin()->create();

        $this->actingAs($superAdmin)
            ->from(route('users.create'))
            ->post(route('users.store'), [
                'name' => 'Gate Staff',
                'username' => 'gate',
                'password' => 'password',
                'password_confirmation' => 'password',
                'role' => UserRole::Scanner->value,
                'school_name' => 'Gate School',
                'is_active' => '1',
            ])
            ->assertRedirect(route('users.create'))
            ->assertSessionHasErrors('role');

        $this->assertDatabaseMissing('users', ['username' => 'gate']);
    }

    public function test_super_administrator_cannot_create_a_super_administrator(): void
    {
        $superAdmin = User::factory()->superAdmin()->create();

        $this->actingAs($superAdmin)
            ->from(route('users.create'))
            ->post(route('users.store'), [
                'name' => 'Another Root',
                'username' => 'root2',
                'password' => 'password',
                'password_confirmation' => 'password',
                'role' => UserRole::SuperAdmin->value,
                'school_name' => 'Root School',
                'is_active' => '1',
            ])
            ->assertRedirect(route('users.create'))
            ->assertSessionHasErrors('role');

        $this->assertDatabaseMissing('users', ['username' => 'root2']);
    }

    public function test_super_administrator_can_delete_another_account(): void
    {
        $superAdmin = User::factory()->superAdmin()->create();
        $scanner = User::factory()->scanner()->create(['username' => 'gate']);

        $this->actingAs($superAdmin)
            ->delete(route('users.destroy', $scanner))
            ->assertRedirect(route('users.index'));

        $this->assertModelMissing($scanner);
        $this->assertDatabaseHas('audit_logs', [
            'action' => 'user.deleted',
            'subject_id' => $scanner->id,
        ]);
    }

    public function test_super_administrator_cannot_delete_their_own_account(): void
    {
        $superAdmin = User::factory()->superAdmin()->create();

        $this->actingAs($superAdmin)
            ->delete(route('users.destroy', $superAdmin))
            ->assertForbidden();

        $this->assertModelExists($superAdmin);
    }

    public function test_super_administrator_cannot_delete_another_super_administrator(): void
    {
        $superAdmin = User::factory()->superAdmin()->create();
        $other = User::factory()->superAdmin()->create();

        $this->actingAs($superAdmin)
            ->delete(route('users.destroy', $other))
            ->assertForbidden();

        $this->assertModelExists($other);
    }

    public function test_super_administrator_cannot_delete_an_administrator_whose_school_has_students(): void
    {
        $superAdmin = User::factory()->superAdmin()->create();
        $admin = User::factory()->admin()->create();
        $this->studentFor($admin);

        $this->actingAs($superAdmin)
            ->delete(route('users.destroy', $admin))
            ->assertForbidden();

        $this->assertModelExists($admin);
    }

    public function test_super_administrator_can_delete_an_administrator_of_an_empty_school(): void
    {
        $superAdmin = User::factory()->superAdmin()->create();
        $admin = User::factory()->admin()->create();
        $schoolId = $admin->school_id;
        User::factory()->admin()->create();

        $this->actingAs($superAdmin)
            ->delete(route('users.destroy', $admin))
            ->assertRedirect(route('users.index'));

        $this->assertModelMissing($admin);
        $this->assertDatabaseMissing('schools', ['id' => $schoolId]);
    }

    public function test_deleting_a_scanner_keeps_attendance_history(): void
    {
        $superAdmin = User::factory()->superAdmin()->create();
        $scanner = User::factory()->scanner()->create();
        $student = Student::factory()->create();
        $attendance = Attendance::factory()->create([
            'student_id' => $student->id,
            'recorded_by' => $scanner->id,
        ]);

        $this->actingAs($superAdmin)
            ->delete(route('users.destroy', $scanner))
            ->assertRedirect(route('users.index'));

        $this->assertModelMissing($scanner);
        $this->assertDatabaseHas('attendances', [
            'id' => $attendance->id,
            'student_id' => $student->id,
            'recorded_by' => null,
        ]);
    }

    public function test_scanner_staff_cannot_delete_an_account(): void
    {
        $scanner = User::factory()->scanner()->create();
        $other = User::factory()->scanner()->create();

        $this->actingAs($scanner)
            ->delete(route('users.destroy', $other))
            ->assertForbidden();

        $this->assertModelExists($other);
    }

    public function test_super_administrator_can_rename_an_administrator_school(): void
    {
        $superAdmin = User::factory()->superAdmin()->create();
        $admin = User::factory()->admin()->create();
        $schoolId = $admin->school_id;

        $this->actingAs($superAdmin)
            ->put(route('users.update', $admin), [
                'name' => $admin->name,
                'username' => $admin->username,
                'role' => UserRole::Admin->value,
                'school_name' => 'Renamed Academy',
                'is_active' => '1',
            ])
            ->assertRedirect(route('users.index'));

        $admin->refresh();

        $this->assertSame($schoolId, $admin->school_id);
        $this->assertSame('Renamed Academy', $admin->school->name);
    }
}
