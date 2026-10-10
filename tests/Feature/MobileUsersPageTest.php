<?php

namespace Tests\Feature;

use App\Models\User;
use App\UserRole;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

final class MobileUsersPageTest extends TestCase
{
    use RefreshDatabase;

    public function test_unauthenticated_user_is_redirected_to_login(): void
    {
        $response = $this->get('/users');

        $response->assertRedirect(route('login'));
    }

    /**
     * @return array<string, array{UserRole}>
     */
    public static function unauthorizedRoles(): array
    {
        return [
            'petugas' => [UserRole::Petugas],
            'supervisor' => [UserRole::Supervisor],
        ];
    }

    #[DataProvider('unauthorizedRoles')]
    public function test_non_admin_user_is_forbidden_from_user_management(UserRole $role): void
    {
        $user = User::factory()->create(['role' => $role]);

        $response = $this->actingAs($user)->get('/users');

        $response->assertForbidden();
    }

    public function test_admin_receives_mobile_user_management_content_and_assets(): void
    {
        $admin = User::factory()->create(['role' => UserRole::Admin]);

        $response = $this->actingAs($admin)->get('/users');

        $response
            ->assertOk()
            ->assertSeeText('Kelola akun pengguna sistem')
            ->assertSeeText('Ahmad Admin')
            ->assertSeeText('Budi Santoso')
            ->assertSeeText('Siti Rahma')
            ->assertSeeText('Rudi Hartono')
            ->assertSeeText('Tambah User')
            ->assertSeeText('Reset Password')
            ->assertSee('aria-label="Tambah User"', false)
            ->assertSee('data-mobile-users-detail="name"', false)
            ->assertSee('data-mobile-users-result-count role="status" aria-live="polite"', false)
            ->assertSee(asset('assets/css/mobile-users.css'), false)
            ->assertSee(asset('assets/js/mobile-users.js'), false);
    }

    #[DataProvider('unauthorizedRoles')]
    public function test_non_admin_user_does_not_see_user_management_navigation(UserRole $role): void
    {
        $user = User::factory()->create(['role' => $role]);

        $response = $this->actingAs($user)->get('/dashboard');

        $response
            ->assertOk()
            ->assertDontSeeText('User Management');
    }

    public function test_admin_sees_user_management_navigation(): void
    {
        $admin = User::factory()->create(['role' => UserRole::Admin]);

        $response = $this->actingAs($admin)->get('/dashboard');

        $response
            ->assertOk()
            ->assertSee('data-user-management-navigation="desktop"', false)
            ->assertSee('data-user-management-navigation="mobile"', false)
            ->assertSeeText('User Management');
    }
}
