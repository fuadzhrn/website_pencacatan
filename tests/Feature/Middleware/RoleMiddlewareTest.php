<?php

namespace Tests\Feature\Middleware;

use App\Models\User;
use App\UserRole;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

final class RoleMiddlewareTest extends TestCase
{
    use RefreshDatabase;

    #[DataProvider('allowedRoutes')]
    public function test_authorized_role_can_open_the_route_directly(UserRole $role, string $path): void
    {
        $user = User::factory()->create(['role' => $role]);

        $response = $this->actingAs($user)->get($path);

        $response->assertOk();
    }

    #[DataProvider('forbiddenRoutes')]
    public function test_unauthorized_role_receives_403_when_opening_the_route_directly(UserRole $role, string $path): void
    {
        $user = User::factory()->create(['role' => $role]);

        $response = $this->actingAs($user)->get($path);

        $response->assertForbidden();
    }

    #[DataProvider('protectedRoutes')]
    public function test_guest_is_redirected_to_login_before_role_evaluation(string $path): void
    {
        $response = $this->get($path);

        $response->assertRedirectToRoute('login');
    }

    /**
     * @return array<string, array{UserRole, string}>
     */
    public static function allowedRoutes(): array
    {
        $routes = [];

        foreach (self::routePermissions() as $area => [$path, $allowedRoles]) {
            foreach ($allowedRoles as $role) {
                $routes[$role->value.' '.$area] = [$role, $path];
            }
        }

        return $routes;
    }

    /**
     * @return array<string, array{UserRole, string}>
     */
    public static function forbiddenRoutes(): array
    {
        $routes = [];

        foreach (self::routePermissions() as $area => [$path, $allowedRoles]) {
            foreach (UserRole::cases() as $role) {
                if (! in_array($role, $allowedRoles, true)) {
                    $routes[$role->value.' '.$area] = [$role, $path];
                }
            }
        }

        return $routes;
    }

    /**
     * @return array<string, array{string}>
     */
    public static function protectedRoutes(): array
    {
        $routes = [];

        foreach (self::routePermissions() as $area => [$path]) {
            $routes[$area] = [$path];
        }

        return $routes;
    }

    /**
     * @return array<string, array{string, list<UserRole>}>
     */
    private static function routePermissions(): array
    {
        return [
            'desktop dashboard' => ['/dashboard', [UserRole::Admin, UserRole::Petugas, UserRole::Supervisor]],
            'desktop machines' => ['/machines', [UserRole::Admin]],
            'desktop checklists' => ['/checklists', [UserRole::Admin]],
            'desktop schedules' => ['/schedules', [UserRole::Admin, UserRole::Petugas, UserRole::Supervisor]],
            'desktop inspection today' => ['/inspections/today', [UserRole::Admin, UserRole::Petugas, UserRole::Supervisor]],
            'desktop findings' => ['/findings', [UserRole::Admin, UserRole::Petugas, UserRole::Supervisor]],
            'desktop history' => ['/inspections/history', [UserRole::Admin, UserRole::Petugas, UserRole::Supervisor]],
            'desktop reports' => ['/reports/monthly', [UserRole::Admin, UserRole::Petugas, UserRole::Supervisor]],
            'desktop users' => ['/users', [UserRole::Admin]],
            'mobile dashboard' => ['/mobile/dashboard', [UserRole::Admin, UserRole::Petugas, UserRole::Supervisor]],
            'mobile machines' => ['/mobile/machines', [UserRole::Admin]],
            'mobile checklists' => ['/mobile/checklists', [UserRole::Admin]],
            'mobile schedules' => ['/mobile/schedules', [UserRole::Admin, UserRole::Petugas, UserRole::Supervisor]],
            'mobile inspection today' => ['/mobile/inspection-today', [UserRole::Admin, UserRole::Petugas, UserRole::Supervisor]],
            'mobile findings' => ['/mobile/findings', [UserRole::Admin, UserRole::Petugas, UserRole::Supervisor]],
            'mobile history' => ['/mobile/history', [UserRole::Admin, UserRole::Petugas, UserRole::Supervisor]],
            'mobile reports' => ['/mobile/report', [UserRole::Admin, UserRole::Petugas, UserRole::Supervisor]],
            'mobile users' => ['/mobile/users', [UserRole::Admin]],
        ];
    }
}
