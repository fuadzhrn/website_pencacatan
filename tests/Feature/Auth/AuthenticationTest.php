<?php

namespace Tests\Feature\Auth;

use App\Models\User;
use App\UserRole;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AuthenticationTest extends TestCase
{
    use RefreshDatabase;

    public function test_login_page_can_be_displayed_to_guests(): void
    {
        $response = $this->get(route('login'));

        $response
            ->assertOk()
            ->assertViewIs('auth.login')
            ->assertSee('Masuk ke Sistem');
    }

    public function test_authenticated_user_is_redirected_away_from_login_page(): void
    {
        $user = User::factory()->create(['role' => UserRole::Admin]);

        $response = $this->actingAs($user)->get(route('login'));

        $response->assertRedirectToRoute('dashboard');
    }

    public function test_admin_is_redirected_to_the_desktop_dashboard_after_login(): void
    {
        $user = User::factory()->create(['role' => UserRole::Admin]);
        $this->withSession(['_token' => 'before-login']);
        $sessionId = session()->getId();

        $response = $this->post(route('login.store'), [
            'email' => $user->email,
            'password' => 'password',
        ]);

        $response->assertRedirectToRoute('dashboard')->assertSessionHasNoErrors();
        $this->assertAuthenticatedAs($user);
        $this->assertNotSame($sessionId, session()->getId());
        $this->assertNotSame('before-login', session()->token());
    }

    public function test_petugas_is_redirected_to_the_mobile_dashboard_after_login(): void
    {
        $user = User::factory()->create(['role' => UserRole::Petugas]);
        $this->withSession(['_token' => 'before-login']);
        $sessionId = session()->getId();

        $response = $this->post(route('login.store'), [
            'email' => $user->email,
            'password' => 'password',
        ]);

        $response->assertRedirectToRoute('mobile.dashboard')->assertSessionHasNoErrors();
        $this->assertAuthenticatedAs($user);
        $this->assertNotSame($sessionId, session()->getId());
        $this->assertNotSame('before-login', session()->token());
    }

    public function test_supervisor_is_redirected_to_the_desktop_dashboard_after_login(): void
    {
        $user = User::factory()->create(['role' => UserRole::Supervisor]);
        $this->withSession(['_token' => 'before-login']);
        $sessionId = session()->getId();

        $response = $this->post(route('login.store'), [
            'email' => $user->email,
            'password' => 'password',
        ]);

        $response->assertRedirectToRoute('dashboard')->assertSessionHasNoErrors();
        $this->assertAuthenticatedAs($user);
        $this->assertNotSame($sessionId, session()->getId());
        $this->assertNotSame('before-login', session()->token());
    }

    public function test_inactive_user_cannot_authenticate_with_a_valid_password(): void
    {
        $user = User::factory()->create([
            'status' => 'inactive',
            'remember_token' => null,
        ]);

        $response = $this->from(route('login'))->post(route('login.store'), [
            'email' => $user->email,
            'password' => 'password',
            'remember' => '1',
            'status' => 'active',
        ]);

        $response
            ->assertRedirectToRoute('login')
            ->assertSessionHasErrors([
                'email' => 'Email atau password yang Anda masukkan tidak sesuai.',
            ])
            ->assertSessionHasInput('email', $user->email)
            ->assertSessionMissing('_old_input.password');
        $this->assertGuest();
        $this->assertNull($user->fresh()->remember_token);
    }

    public function test_authenticated_petugas_is_redirected_away_from_login_to_the_mobile_dashboard(): void
    {
        $user = User::factory()->create(['role' => UserRole::Petugas]);

        $response = $this->actingAs($user)->get(route('login'));

        $response->assertRedirectToRoute('mobile.dashboard')->assertSessionHasNoErrors();
        $this->assertAuthenticatedAs($user);
    }

    public function test_login_preserves_an_intended_destination_accessible_to_the_user(): void
    {
        $user = User::factory()->create(['role' => UserRole::Petugas]);
        $this->get(route('mobile.schedules'))->assertRedirectToRoute('login');

        $response = $this->post(route('login.store'), [
            'email' => $user->email,
            'password' => 'password',
        ]);

        $response
            ->assertRedirectToRoute('mobile.schedules')
            ->assertSessionHasNoErrors()
            ->assertSessionMissing('url.intended');
        $this->assertAuthenticatedAs($user);
    }

    public function test_user_can_authenticate_with_email_and_password(): void
    {
        $user = User::factory()->create([
            'name' => 'Administrator X-Ray',
            'email' => 'admin@example.com',
            'password' => 'Password123!',
            'remember_token' => null,
            'role' => UserRole::Admin,
        ]);

        $response = $this->post(route('login.store'), [
            'email' => $user->email,
            'password' => 'Password123!',
            'remember' => '1',
        ]);

        $response->assertRedirectToRoute('dashboard');
        $this->assertAuthenticatedAs($user);
        $this->assertNotNull($user->fresh()->remember_token);

        $this->get(route('dashboard'))
            ->assertOk()
            ->assertSee('Administrator X-Ray')
            ->assertSee('Admin');
    }

    public function test_user_cannot_authenticate_with_an_invalid_password(): void
    {
        $user = User::factory()->create([
            'email' => 'petugas@example.com',
            'password' => 'Password123!',
        ]);

        $response = $this->from(route('login'))->post(route('login.store'), [
            'email' => $user->email,
            'password' => 'password-salah',
        ]);

        $response
            ->assertRedirectToRoute('login')
            ->assertSessionHasErrors('email')
            ->assertSessionHasInput('email', $user->email);
        $this->assertGuest();
    }

    public function test_login_requires_a_valid_email_and_password(): void
    {
        $response = $this->from(route('login'))->post(route('login.store'), [
            'email' => 'bukan-email',
            'password' => '',
        ]);

        $response
            ->assertRedirectToRoute('login')
            ->assertSessionHasErrors(['email', 'password']);
        $this->assertGuest();
    }

    public function test_guest_is_redirected_to_login_from_dashboard(): void
    {
        $response = $this->get(route('dashboard'));

        $response->assertRedirectToRoute('login');
    }

    public function test_authenticated_user_can_logout(): void
    {
        $user = User::factory()->create();
        $this->withSession(['_token' => 'before-logout', 'private-data' => 'value']);
        $sessionId = session()->getId();

        $response = $this->actingAs($user)->post(route('logout'));

        $response->assertRedirectToRoute('login')->assertSessionMissing('private-data');
        $this->assertGuest();
        $this->assertNotSame($sessionId, session()->getId());
        $this->assertNotSame('before-logout', session()->token());
    }

    public function test_login_attempts_are_rate_limited(): void
    {
        User::factory()->create([
            'email' => 'target@example.com',
            'password' => 'Password123!',
        ]);

        for ($attempt = 1; $attempt <= 5; $attempt++) {
            $this->post(route('login.store'), [
                'email' => 'target@example.com',
                'password' => 'password-salah',
            ])->assertSessionHasErrors('email');
        }

        $this->post(route('login.store'), [
            'email' => 'target@example.com',
            'password' => 'password-salah',
        ])->assertStatus(429);
    }
}
