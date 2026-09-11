<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class SettingsTest extends TestCase
{
    use LazilyRefreshDatabase;

    public function test_administrator_can_open_settings(): void
    {
        $admin = User::factory()->admin()->create();

        $this->actingAs($admin)
            ->get(route('settings.edit'))
            ->assertOk()
            ->assertSee('Settings')
            ->assertDontSee('Reports')
            ->assertSee($admin->name)
            ->assertSee($admin->school->name);
    }

    public function test_administrator_can_update_school_and_profile_settings(): void
    {
        $admin = User::factory()->admin()->create(['name' => 'Old Name']);
        $other = User::factory()->admin()->create();
        $otherSchoolName = $other->school->name;

        $this->actingAs($admin)
            ->from(route('settings.edit'))
            ->put(route('settings.update'), [
                'name' => 'New Name',
                'school_name' => 'New School',
            ])
            ->assertRedirect(route('settings.edit'))
            ->assertSessionHas('success');

        $this->assertSame('New Name', $admin->fresh()->name);
        $this->assertSame('New School', $admin->fresh()->school->name);
        $this->assertSame($otherSchoolName, $other->school->fresh()->name);
    }

    public function test_administrator_can_change_password_from_settings(): void
    {
        $admin = User::factory()->admin()->create();

        $this->actingAs($admin)
            ->put(route('settings.update'), [
                'name' => $admin->name,
                'school_name' => $admin->school->name,
                'password' => 'new-password',
                'password_confirmation' => 'new-password',
            ])
            ->assertRedirect(route('settings.edit'));

        $this->assertTrue(Hash::check('new-password', $admin->fresh()->password));
    }

    public function test_settings_update_rejects_a_missing_school_name(): void
    {
        $admin = User::factory()->admin()->create();

        $this->actingAs($admin)
            ->from(route('settings.edit'))
            ->put(route('settings.update'), [
                'name' => 'New Name',
                'school_name' => '',
            ])
            ->assertRedirect(route('settings.edit'))
            ->assertSessionHasErrors('school_name');
    }
}
