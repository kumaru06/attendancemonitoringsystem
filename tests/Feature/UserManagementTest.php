<?php

namespace Tests\Feature;

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
        $admin = User::factory()->admin()->create();
        $scanner = User::factory()->scanner()->create(['name' => 'Gate Staff']);

        $this->actingAs($admin)
            ->get(route('users.index'))
            ->assertOk()
            ->assertSee('Actions')
            ->assertSee('Edit')
            ->assertSee('Delete')
            ->assertSee(route('users.edit', $scanner), false)
            ->assertSee(route('users.destroy', $scanner), false);
    }

    public function test_administrator_can_delete_another_account(): void
    {
        $admin = User::factory()->admin()->create();
        $scanner = User::factory()->scanner()->create(['username' => 'gate']);

        $this->actingAs($admin)
            ->delete(route('users.destroy', $scanner))
            ->assertRedirect(route('users.index'));

        $this->assertModelMissing($scanner);
        $this->assertDatabaseHas('audit_logs', [
            'action' => 'user.deleted',
            'subject_id' => $scanner->id,
        ]);
    }

    public function test_administrator_cannot_delete_their_own_account(): void
    {
        $admin = User::factory()->admin()->create();
        User::factory()->admin()->create();

        $this->actingAs($admin)
            ->delete(route('users.destroy', $admin))
            ->assertForbidden();

        $this->assertModelExists($admin);
    }

    public function test_deleting_a_scanner_keeps_attendance_history(): void
    {
        $admin = User::factory()->admin()->create();
        $scanner = User::factory()->scanner()->create();
        $student = Student::factory()->create();
        $attendance = Attendance::factory()->create([
            'student_id' => $student->id,
            'recorded_by' => $scanner->id,
        ]);

        $this->actingAs($admin)
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
}
