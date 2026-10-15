<?php

namespace Tests\Feature;

use App\Models\Machine;
use App\Models\MaintenanceCategory;
use App\Models\MaintenanceCheck;
use App\Models\MaintenanceCheckDetail;
use App\Models\MaintenanceFinding;
use App\Models\MaintenanceItem;
use App\Models\MaintenanceSchedule;
use App\Models\User;
use App\UserRole;
use Carbon\CarbonImmutable;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

final class DashboardControllerTest extends TestCase
{
    use RefreshDatabase;

    /** @return array<string, array{UserRole}> */
    public static function dashboardRoles(): array
    {
        return [
            'admin' => [UserRole::Admin],
            'petugas' => [UserRole::Petugas],
            'supervisor' => [UserRole::Supervisor],
        ];
    }

    public function test_guest_is_redirected_from_desktop_and_mobile_dashboards(): void
    {
        $this->get('/dashboard')->assertRedirect('/login');
        $this->get('/mobile/dashboard')->assertRedirect('/login');
    }

    #[DataProvider('dashboardRoles')]
    public function test_each_role_can_open_desktop_and_mobile_dashboards(UserRole $role): void
    {
        $user = User::factory()->create(['role' => $role]);

        $this->actingAs($user)->get('/dashboard')->assertOk();
        $this->actingAs($user)->get('/mobile/dashboard')->assertOk();
    }

    public function test_admin_and_supervisor_receive_database_statistics_and_monthly_summary(): void
    {
        $this->travelTo(CarbonImmutable::parse('2026-10-14 09:00:00'));
        $admin = User::factory()->create(['role' => UserRole::Admin]);
        $supervisor = User::factory()->create(['role' => UserRole::Supervisor]);
        $fixture = $this->createDashboardFixture();

        foreach ([$admin, $supervisor] as $monitor) {
            $this->actingAs($monitor)->get('/dashboard')
                ->assertOk()
                ->assertViewHas('statistics', fn (array $statistics): bool => $statistics === [
                    'total_machines' => 3,
                    'today_schedules' => 4,
                    'completed_today' => 1,
                    'pending_today' => 2,
                    'overdue' => 1,
                    'findings_open' => 3,
                    'findings_review' => 1,
                    'findings_resolved' => 2,
                    'waiting_verification' => 1,
                ])
                ->assertViewHas('dailyProgress', fn (array $progress): bool => $progress === [
                    'total' => 4,
                    'completed' => 1,
                    'percentage' => 25,
                ])
                ->assertViewHas('monthlySummary', fn (array $summary): bool => $summary === [
                    'total_checks' => 3,
                    'compliant_details' => 3,
                    'noncompliant_details' => 1,
                    'total_findings' => 6,
                    'completion_percentage' => 33,
                    'verified_checks' => 1,
                    'waiting_verification' => 1,
                ])
                ->assertViewHas('todaySchedules', fn ($schedules): bool => $schedules->count() === 4
                    && $schedules->every(fn (MaintenanceSchedule $schedule): bool => $schedule->relationLoaded('machine')
                        && $schedule->relationLoaded('maintenanceCategory')
                        && $schedule->relationLoaded('assignedUser')
                        && $schedule->relationLoaded('check')))
                ->assertViewHas('latestFindings', fn ($findings): bool => $findings->count() === 5
                    && $findings->first()->finding_description === 'Temuan terbaru 1'
                    && ! $findings->contains('finding_description', 'Temuan paling lama 6'))
                ->assertSeeText($fixture['machines'][0]->equipment_name)
                ->assertSeeText('Temuan terbaru 1');
        }
    }

    public function test_petugas_only_receives_own_dashboard_data(): void
    {
        $this->travelTo(CarbonImmutable::parse('2026-10-14 09:00:00'));
        $fixture = $this->createDashboardFixture();

        $this->actingAs($fixture['petugas'][0])->get('/mobile/dashboard')
            ->assertOk()
            ->assertViewHas('statistics', fn (array $statistics): bool => $statistics === [
                'total_machines' => 2,
                'today_schedules' => 3,
                'completed_today' => 1,
                'pending_today' => 1,
                'overdue' => 0,
                'findings_open' => 1,
                'findings_review' => 1,
                'findings_resolved' => 1,
                'waiting_verification' => 0,
            ])
            ->assertViewHas('todaySchedules', fn ($schedules): bool => $schedules->count() === 3
                && $schedules->every(fn (MaintenanceSchedule $schedule): bool => $schedule->assigned_user_id === $fixture['petugas'][0]->id))
            ->assertViewHas('latestFindings', fn ($findings): bool => $findings->count() === 3
                && $findings->every(fn (MaintenanceFinding $finding): bool => $finding->reported_by === $fixture['petugas'][0]->id))
            ->assertViewHas('monthlySummary', fn (array $summary): bool => $summary === [
                'total_checks' => 2,
                'compliant_details' => 2,
                'noncompliant_details' => 0,
                'total_findings' => 3,
                'completion_percentage' => 33,
                'verified_checks' => 1,
                'waiting_verification' => 0,
            ])
            ->assertSeeText($fixture['machines'][0]->equipment_name)
            ->assertDontSeeText($fixture['machines'][1]->equipment_name)
            ->assertDontSeeText('Temuan petugas lain 4');
    }

    public function test_dashboard_returns_zero_values_and_empty_states_without_operational_data(): void
    {
        $this->travelTo(CarbonImmutable::parse('2026-10-14 09:00:00'));
        $admin = User::factory()->create(['role' => UserRole::Admin]);

        $this->actingAs($admin)->get('/dashboard')
            ->assertOk()
            ->assertViewHas('statistics', fn (array $statistics): bool => collect($statistics)->every(fn (int $value): bool => $value === 0))
            ->assertViewHas('dailyProgress', fn (array $progress): bool => $progress === ['total' => 0, 'completed' => 0, 'percentage' => 0])
            ->assertViewHas('monthlySummary', fn (array $summary): bool => collect($summary)->every(fn (int $value): bool => $value === 0))
            ->assertSeeText('Belum ada jadwal pemeriksaan hari ini')
            ->assertSeeText('Belum ada temuan terbaru');
    }

    public function test_dashboard_marks_past_pending_schedules_as_overdue_before_counting(): void
    {
        $this->travelTo(CarbonImmutable::parse('2026-10-14 09:00:00'));
        $admin = User::factory()->create(['role' => UserRole::Admin]);
        $petugas = User::factory()->create(['role' => UserRole::Petugas]);
        $machine = $this->createMachine('Mesin Overdue Dashboard', 'DASH-OVERDUE');
        $category = MaintenanceCategory::query()->create(['name' => 'Harian Overdue', 'frequency' => 'daily', 'status' => 'active']);
        $schedule = $this->createSchedule($machine, $category, $petugas, '2026-10-13', 'scheduled', '08:00');

        $this->actingAs($admin)->get('/dashboard')
            ->assertOk()
            ->assertViewHas('statistics', fn (array $statistics): bool => $statistics['overdue'] === 1);

        $this->assertSame('overdue', $schedule->refresh()->status);
    }

    public function test_sidebar_uses_database_progress_and_finding_counts_outside_dashboard(): void
    {
        $this->travelTo(CarbonImmutable::parse('2026-10-14 09:00:00'));
        $admin = User::factory()->create(['role' => UserRole::Admin]);
        $this->createDashboardFixture();

        $this->actingAs($admin)->get('/schedules')
            ->assertOk()
            ->assertSeeText('1 dari 4 pemeriksaan selesai')
            ->assertSee('aria-label="4 temuan aktif"', false)
            ->assertSee('aria-valuenow="25"', false);
    }

    public function test_petugas_dashboard_navigation_hides_management_links_but_shows_authorized_report_links(): void
    {
        $petugas = User::factory()->create(['role' => UserRole::Petugas]);

        $this->actingAs($petugas)->get('/dashboard')
            ->assertOk()
            ->assertDontSee('href="'.route('machines.index').'"', false)
            ->assertDontSee('href="'.route('mobile.machines').'"', false)
            ->assertDontSee('href="'.route('checklists.index').'"', false)
            ->assertDontSee('href="'.route('mobile.checklists').'"', false)
            ->assertSee('href="'.route('reports.monthly').'"', false)
            ->assertSee('href="'.route('mobile.report').'"', false)
            ->assertSee('href="'.route('mobile.history').'"', false);
    }

    /** @return array{petugas: array{User, User}, machines: array{Machine, Machine, Machine}} */
    private function createDashboardFixture(): array
    {
        $petugasA = User::factory()->create(['name' => 'Petugas Dashboard A', 'role' => UserRole::Petugas]);
        $petugasB = User::factory()->create(['name' => 'Petugas Dashboard B', 'role' => UserRole::Petugas]);
        $machines = [
            $this->createMachine('Mesin Dashboard Milik A', 'DASH-A'),
            $this->createMachine('Mesin Dashboard Milik B', 'DASH-B'),
            $this->createMachine('Mesin Dashboard Cadangan A', 'DASH-C'),
        ];
        $daily = MaintenanceCategory::query()->create(['name' => 'Harian Dashboard', 'frequency' => 'daily', 'status' => 'active']);
        $weekly = MaintenanceCategory::query()->create(['name' => 'Mingguan Dashboard', 'frequency' => 'weekly', 'status' => 'active']);
        $itemA = MaintenanceItem::query()->create(['maintenance_category_id' => $daily->id, 'item_name' => 'Lead curtain dashboard', 'criteria' => 'Utuh', 'input_type' => 'sesuai_tidak_sesuai', 'sort_order' => 1, 'status' => 'active']);
        $itemB = MaintenanceItem::query()->create(['maintenance_category_id' => $daily->id, 'item_name' => 'Conveyor dashboard', 'criteria' => 'Stabil', 'input_type' => 'sesuai_tidak_sesuai', 'sort_order' => 2, 'status' => 'active']);

        $completedToday = $this->createSchedule($machines[0], $daily, $petugasA, '2026-10-14', 'completed', '08:00');
        $this->createSchedule($machines[1], $daily, $petugasB, '2026-10-14', 'scheduled', '09:00');
        $this->createSchedule($machines[2], $daily, $petugasA, '2026-10-14', 'pending', '10:00');
        $inProgressToday = $this->createSchedule($machines[0], $weekly, $petugasA, '2026-10-14', 'in_progress', '11:00');
        $this->createSchedule($machines[1], $weekly, $petugasB, '2026-10-14', 'cancelled', '12:00');
        $this->createSchedule($machines[1], $daily, $petugasB, '2026-10-13', 'overdue', '08:00');
        $completedEarlier = $this->createSchedule($machines[1], $daily, $petugasB, '2026-10-10', 'completed', '08:00');

        $verifiedCheck = $this->createCheck($completedToday, $petugasA, '2026-10-14', 'normal', 'verified', true);
        $draftCheck = $this->createCheck($inProgressToday, $petugasA, '2026-10-14', 'incomplete', 'waiting', false);
        $waitingCheck = $this->createCheck($completedEarlier, $petugasB, '2026-10-10', 'has_finding', 'waiting', true);

        $this->createDetail($verifiedCheck, $itemA, 'sesuai');
        $this->createDetail($verifiedCheck, $itemB, 'sesuai');
        $this->createDetail($draftCheck, $itemA, 'belum_diperiksa');
        $this->createDetail($waitingCheck, $itemA, 'sesuai');
        $this->createDetail($waitingCheck, $itemB, 'tidak_sesuai');

        $findingData = [
            [$verifiedCheck, $machines[0], $petugasA, $itemA, '2026-10-14', 'open', 'Temuan terbaru 1'],
            [$verifiedCheck, $machines[0], $petugasA, $itemB, '2026-10-13', 'review', 'Temuan petugas A 2'],
            [$verifiedCheck, $machines[0], $petugasA, $itemA, '2026-10-12', 'resolved', 'Temuan petugas A 3'],
            [$waitingCheck, $machines[1], $petugasB, $itemB, '2026-10-11', 'open', 'Temuan petugas lain 4'],
            [$waitingCheck, $machines[1], $petugasB, $itemA, '2026-10-10', 'open', 'Temuan petugas lain 5'],
            [$waitingCheck, $machines[1], $petugasB, $itemB, '2026-10-09', 'resolved', 'Temuan paling lama 6'],
        ];
        foreach ($findingData as [$check, $machine, $petugas, $item, $date, $status, $description]) {
            MaintenanceFinding::query()->create([
                'maintenance_check_id' => $check->id,
                'machine_id' => $machine->id,
                'maintenance_item_id' => $item->id,
                'reported_by' => $petugas->id,
                'finding_date' => $date,
                'finding_description' => $description,
                'status' => $status,
            ]);
        }

        return ['petugas' => [$petugasA, $petugasB], 'machines' => $machines];
    }

    private function createMachine(string $name, string $serialNumber): Machine
    {
        return Machine::query()->create([
            'equipment_name' => $name,
            'brand' => 'Smiths Detection',
            'type' => 'HI-SCAN',
            'serial_number' => $serialNumber,
            'location' => 'Terminal Dashboard',
            'airport' => 'UPBU Malikussaleh',
            'status' => 'active',
        ]);
    }

    private function createSchedule(Machine $machine, MaintenanceCategory $category, User $petugas, string $date, string $status, string $time): MaintenanceSchedule
    {
        return MaintenanceSchedule::query()->create([
            'machine_id' => $machine->id,
            'maintenance_category_id' => $category->id,
            'scheduled_date' => $date,
            'scheduled_time' => $time,
            'assigned_user_id' => $petugas->id,
            'status' => $status,
        ]);
    }

    private function createCheck(MaintenanceSchedule $schedule, User $petugas, string $date, string $overallStatus, string $verificationStatus, bool $isCompleted): MaintenanceCheck
    {
        return MaintenanceCheck::query()->create([
            'maintenance_schedule_id' => $schedule->id,
            'machine_id' => $schedule->machine_id,
            'checked_by' => $petugas->id,
            'check_date' => $date,
            'start_time' => '08:00',
            'end_time' => $isCompleted ? '08:30' : null,
            'overall_status' => $overallStatus,
            'verification_status' => $verificationStatus,
        ]);
    }

    private function createDetail(MaintenanceCheck $check, MaintenanceItem $item, string $status): MaintenanceCheckDetail
    {
        return MaintenanceCheckDetail::query()->create([
            'maintenance_check_id' => $check->id,
            'maintenance_item_id' => $item->id,
            'result_status' => $status,
        ]);
    }
}
