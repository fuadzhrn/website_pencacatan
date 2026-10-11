<?php

namespace Tests\Feature;

use App\Models\User;
use App\UserRole;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

final class MobileNavigationTest extends TestCase
{
    use RefreshDatabase;

    /**
     * @return array<string, array{string}>
     */
    public static function mobilePaths(): array
    {
        return [
            'dashboard' => ['/mobile/dashboard'],
            'machines' => ['/mobile/machines'],
            'checklists' => ['/mobile/checklists'],
            'schedules' => ['/mobile/schedules'],
            'inspection today' => ['/mobile/inspection-today'],
            'findings' => ['/mobile/findings'],
            'history' => ['/mobile/history'],
            'report' => ['/mobile/report'],
            'users' => ['/mobile/users'],
        ];
    }

    #[DataProvider('mobilePaths')]
    public function test_unauthenticated_mobile_route_redirects_to_login(string $path): void
    {
        $response = $this->get($path);

        $response->assertRedirect(route('login'));
    }

    /**
     * @return array<string, array{string, string, string}>
     */
    public static function generalMobilePages(): array
    {
        return [
            'dashboard' => ['mobile.dashboard', 'dashboard', 'Monitoring preventive maintenance hari ini'],
            'machines' => ['mobile.machines', 'data-mesin', 'Halaman ini akan dikembangkan'],
            'checklists' => ['mobile.checklists', 'master-checklist', 'Kelola kategori dan item pemeriksaan'],
            'schedules' => ['mobile.schedules', 'jadwal-maintenance', 'Pantau jadwal pemeriksaan mesin X-Ray'],
            'inspection today' => ['mobile.inspection-today', 'pemeriksaan-hari-ini', 'Pemeriksaan Hari Ini'],
            'findings' => ['mobile.findings', 'temuan', 'Pantau temuan hasil pemeriksaan'],
            'history' => ['mobile.history', 'riwayat-pemeriksaan', 'Lihat pemeriksaan yang sudah dilakukan'],
            'report' => ['mobile.report', 'laporan', 'Laporan Bulanan'],
        ];
    }

    #[DataProvider('generalMobilePages')]
    public function test_authenticated_user_receives_each_mobile_page_with_the_expected_active_menu(
        string $routeName,
        string $activeMenu,
        string $visibleText,
    ): void {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->get(route($routeName));

        $response
            ->assertOk()
            ->assertViewHas('activeMenu', $activeMenu)
            ->assertSeeText($visibleText)
            ->assertSee(asset('assets/css/mobile-layout.css'), false)
            ->assertSee(asset('assets/js/mobile-layout.js'), false);
    }

    public function test_mobile_navigation_links_target_every_mobile_page(): void
    {
        $admin = User::factory()->create(['role' => UserRole::Admin]);

        $response = $this->actingAs($admin)->get('/mobile/dashboard');

        $response
            ->assertOk()
            ->assertSee('href="'.route('mobile.dashboard').'"', false)
            ->assertSee('href="'.route('mobile.machines').'"', false)
            ->assertSee('href="'.route('mobile.checklists').'"', false)
            ->assertSee('href="'.route('mobile.schedules').'"', false)
            ->assertSee('href="'.route('mobile.inspection-today').'"', false)
            ->assertSee('href="'.route('mobile.findings').'"', false)
            ->assertSee('href="'.route('mobile.history').'"', false)
            ->assertSee('href="'.route('mobile.report').'"', false)
            ->assertSee('href="'.route('mobile.users').'"', false)
            ->assertSee('data-mobile-route="dashboard"', false)
            ->assertSee('data-mobile-logout', false)
            ->assertSee('data-mobile-navigation-toast', false);
    }

    public function test_petugas_cannot_see_or_open_mobile_user_management(): void
    {
        $petugas = User::factory()->create(['role' => UserRole::Petugas]);

        $pageResponse = $this->actingAs($petugas)->get('/mobile/dashboard');
        $usersResponse = $this->actingAs($petugas)->get('/mobile/users');

        $pageResponse
            ->assertOk()
            ->assertDontSee(route('mobile.users'), false)
            ->assertDontSeeText('User Management');
        $usersResponse->assertForbidden();
    }

    public function test_supervisor_is_forbidden_from_mobile_user_management(): void
    {
        $supervisor = User::factory()->create(['role' => UserRole::Supervisor]);

        $response = $this->actingAs($supervisor)->get('/mobile/users');

        $response->assertForbidden();
    }

    public function test_admin_receives_mobile_user_management(): void
    {
        $admin = User::factory()->create(['role' => UserRole::Admin]);

        $response = $this->actingAs($admin)->get('/mobile/users');

        $response
            ->assertOk()
            ->assertViewHas('activeMenu', 'user-management')
            ->assertSeeText('Kelola akun pengguna sistem');
    }
}
