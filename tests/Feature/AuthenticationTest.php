<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Tests\TestCase;

class AuthenticationTest extends TestCase
{
    use LazilyRefreshDatabase;

    public function test_guest_is_redirected_to_login(): void
    {
        $this->get(route('home'))->assertRedirect(route('login'));
    }

    public function test_administrator_is_redirected_to_the_dashboard(): void
    {
        $admin = User::factory()->admin()->create([
            'username' => 'principal',
            'password' => 'password',
        ]);

        $this->post(route('login.store'), [
            'username' => 'principal',
            'password' => 'password',
        ])->assertRedirect(route('dashboard'));

        $this->assertAuthenticatedAs($admin);
    }

    public function test_scanner_staff_is_redirected_to_the_scanner(): void
    {
        User::factory()->scanner()->create([
            'username' => 'gate',
            'password' => 'password',
        ]);

        $this->post(route('login.store'), [
            'username' => 'gate',
            'password' => 'password',
        ])->assertRedirect(route('scanner.index'));
    }

    public function test_inactive_account_cannot_sign_in(): void
    {
        User::factory()->admin()->inactive()->create([
            'username' => 'locked',
            'password' => 'password',
        ]);

        $this->from(route('login'))
            ->post(route('login.store'), [
                'username' => 'locked',
                'password' => 'password',
            ])
            ->assertRedirect(route('login'))
            ->assertSessionHasErrors(['username' => 'This account is inactive.']);

        $this->assertGuest();
    }

    public function test_invalid_credentials_are_rejected(): void
    {
        User::factory()->admin()->create([
            'username' => 'principal',
            'password' => 'password',
        ]);

        $this->from(route('login'))
            ->post(route('login.store'), [
                'username' => 'principal',
                'password' => 'wrong-password',
            ])
            ->assertRedirect(route('login'))
            ->assertSessionHasErrors('username');

        $this->assertGuest();
    }

    public function test_authenticated_user_can_sign_out(): void
    {
        $admin = User::factory()->admin()->create();

        $this->actingAs($admin)
            ->post(route('logout'))
            ->assertRedirect(route('login'));

        $this->assertGuest();
    }
}
