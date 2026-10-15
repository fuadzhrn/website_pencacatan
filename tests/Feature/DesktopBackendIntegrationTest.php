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
use Tests\TestCase;

final class DesktopBackendIntegrationTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->travelTo(CarbonImmutable::parse('2026-10-15 09:00:00'));
    }

    public function test_layout_uses_authenticated_identity_and_real_navigation_controls(): void
    {
        $supervisor = User::factory()->create(['name' => 'Rina Kusuma', 'role' => UserRole::Supervisor]);
        $petugas = User::factory()->create(['role' => UserRole::Petugas]);
        $context = $this->createScheduleContext($petugas, 'in_progress');
        $this->createFinding($context, $petugas, $this->createCheck($context, $petugas), 'review');

        $this->actingAs($supervisor)->get(route('findings.index'))
            ->assertOk()
            ->assertSeeText('Rina Kusuma')
            ->assertSee('>RK<', false)
            ->assertSeeText('Supervisor')
            ->assertDontSeeText('Ahmad Fauzi')
            ->assertSee('href="'.route('mobile.findings').'" data-mobile-notification', false)
            ->assertSee('aria-label="1 temuan aktif"', false)
            ->assertDontSee('aria-label="Buka menu profil"', false)
            ->assertSee('<button type="submit" data-mobile-logout>', false);
    }

    public function test_petugas_starts_inspection_from_schedule_page_and_can_fill_checklist(): void
    {
        $petugas = User::factory()->create(['role' => UserRole::Petugas]);
        $context = $this->createScheduleContext($petugas, 'scheduled');

        $this->actingAs($petugas)->get(route('schedules.index'))
            ->assertOk()
            ->assertSee('action="'.route('inspections.start', $context['schedule']).'"', false)
            ->assertDontSee('action="'.route('schedules.update-status', $context['schedule']).'"', false);

        $this->actingAs($petugas)->post(route('inspections.start', $context['schedule']))
            ->assertRedirect(route('inspections.today', ['schedule' => $context['schedule']->id]));

        $this->assertDatabaseHas('maintenance_checks', [
            'maintenance_schedule_id' => $context['schedule']->id,
            'checked_by' => $petugas->id,
            'verification_status' => 'waiting',
        ]);
        $this->assertSame('in_progress', $context['schedule']->fresh()->status);

        $this->actingAs($petugas)->get(route('inspections.today', ['schedule' => $context['schedule']->id]))
            ->assertOk()
            ->assertSee('name="submission_type" value="final"', false)
            ->assertSee('name="items['.$context['item']->id.'][result_status]"', false);
    }

    public function test_in_progress_schedule_without_check_can_still_be_started(): void
    {
        $petugas = User::factory()->create(['role' => UserRole::Petugas]);
        $context = $this->createScheduleContext($petugas, 'in_progress');

        $this->actingAs($petugas)->get(route('inspections.today'))
            ->assertOk()
            ->assertSee('action="'.route('inspections.start', $context['schedule']).'"', false);

        $this->actingAs($petugas)->post(route('inspections.start', $context['schedule']))->assertRedirect();

        $this->assertSame(1, MaintenanceCheck::query()->where('maintenance_schedule_id', $context['schedule']->id)->count());
    }

    public function test_findings_page_renders_approved_design_with_backend_actions(): void
    {
        $supervisor = User::factory()->create(['role' => UserRole::Supervisor]);
        $petugas = User::factory()->create(['role' => UserRole::Petugas]);
        $context = $this->createScheduleContext($petugas, 'completed');
        $finding = $this->createFinding($context, $petugas, $this->createCheck($context, $petugas));

        $this->actingAs($supervisor)->get(route('findings.index'))
            ->assertOk()
            ->assertSee('findings-summary-card--open', false)
            ->assertSee('findings-table-panel', false)
            ->assertDontSee('ri-error-warning-line', false)
            ->assertSee('data-action="'.route('findings.update-status', $finding).'"', false)
            ->assertSee('id="finding-status-dialog"', false)
            ->assertSeeText('Semesteran');

        $this->actingAs($supervisor)
            ->from(route('findings.index'))
            ->patch(route('findings.update-status', $finding), ['status' => 'review'])
            ->assertRedirect(route('findings.index'))
            ->assertSessionHas('success', 'Status temuan berhasil diubah.');

        $this->assertSame('review', $finding->fresh()->status);
        $this->assertSame($supervisor->id, $finding->fresh()->reviewed_by);

        $this->actingAs($supervisor)->get(route('findings.index'))
            ->assertSee('status-badge--review', false);
    }

    public function test_findings_list_is_paginated_and_keeps_filters(): void
    {
        $supervisor = User::factory()->create(['role' => UserRole::Supervisor]);
        $petugas = User::factory()->create(['role' => UserRole::Petugas]);

        foreach (range(1, 11) as $number) {
            $context = $this->createScheduleContext($petugas, 'completed');
            $this->createFinding($context, $petugas, $this->createCheck($context, $petugas));
        }

        $dailyContext = $this->createScheduleContext($petugas, 'completed', 'daily');
        $this->createFinding($dailyContext, $petugas, $this->createCheck($dailyContext, $petugas), 'open', 'Temuan Harian Tersaring');

        $this->actingAs($supervisor)->get(route('findings.index', ['frequency' => 'semester']))
            ->assertOk()
            ->assertSeeText('Halaman 1 dari 2')
            ->assertSee('frequency=semester&amp;page=2', false)
            ->assertDontSeeText('Temuan Harian Tersaring');

        $this->actingAs($supervisor)->get(route('findings.index', ['frequency' => 'daily']))
            ->assertOk()
            ->assertSeeText('Temuan Harian Tersaring')
            ->assertDontSeeText('Halaman 1 dari');
    }

    public function test_findings_page_shows_empty_state_without_sample_data(): void
    {
        $admin = User::factory()->create(['role' => UserRole::Admin]);

        $this->actingAs($admin)->get(route('findings.index'))
            ->assertOk()
            ->assertSeeText('Belum ada temuan')
            ->assertDontSeeText('Conveyor belt tidak stabil')
            ->assertDontSeeText('FND-2026-001');
    }

    public function test_petugas_can_update_own_open_finding_from_detail_page(): void
    {
        $petugas = User::factory()->create(['role' => UserRole::Petugas]);
        $context = $this->createScheduleContext($petugas, 'completed');
        $finding = $this->createFinding($context, $petugas, $this->createCheck($context, $petugas));

        $this->actingAs($petugas)->get(route('findings.show', $finding))
            ->assertOk()
            ->assertSee('id="finding-edit"', false)
            ->assertSee('enctype="multipart/form-data"', false);

        $payload = [
            'maintenance_check_id' => $finding->maintenance_check_id,
            'maintenance_check_detail_id' => $finding->maintenance_check_detail_id,
            'machine_id' => $finding->machine_id,
            'maintenance_item_id' => $finding->maintenance_item_id,
            'status' => 'open',
        ];

        $this->actingAs($petugas)
            ->from(route('findings.show', $finding))
            ->followingRedirects()
            ->put(route('findings.update', $finding), [...$payload, 'finding_description' => ''])
            ->assertOk()
            ->assertSee('class="form-error"', false);

        $this->actingAs($petugas)
            ->from(route('findings.show', $finding))
            ->put(route('findings.update', $finding), [...$payload, 'finding_description' => 'Keterangan diperbarui.', 'follow_up' => 'Ganti komponen.'])
            ->assertRedirect(route('findings.show', $finding))
            ->assertSessionHas('success', 'Temuan berhasil diperbarui.');

        $this->assertSame('Keterangan diperbarui.', $finding->fresh()->finding_description);
        $this->assertSame('Ganti komponen.', $finding->fresh()->follow_up);
    }

    public function test_verification_detail_uses_item_result_labels(): void
    {
        $admin = User::factory()->create(['role' => UserRole::Admin]);
        $petugas = User::factory()->create(['role' => UserRole::Petugas]);
        $context = $this->createScheduleContext($petugas, 'completed');
        $check = $this->createCheck($context, $petugas, 'belum_diperiksa', ['overall_status' => 'incomplete']);

        $this->actingAs($admin)->get(route('inspections.history.show', $check))
            ->assertOk()
            ->assertSeeText('Belum Diperiksa')
            ->assertSeeText('Belum Selesai');
    }

    public function test_schedule_list_uses_project_pagination_markup(): void
    {
        $admin = User::factory()->create(['role' => UserRole::Admin]);
        $petugas = User::factory()->create(['role' => UserRole::Petugas]);
        $context = $this->createScheduleContext($petugas, 'scheduled');

        foreach (range(1, 25) as $day) {
            MaintenanceSchedule::query()->create([
                'machine_id' => $context['machine']->id,
                'maintenance_category_id' => $context['category']->id,
                'scheduled_date' => CarbonImmutable::parse('2026-11-01')->addDays($day)->toDateString(),
                'assigned_user_id' => $petugas->id,
                'status' => 'scheduled',
            ]);
        }

        $this->actingAs($admin)->get(route('schedules.index', ['status' => 'scheduled']))
            ->assertOk()
            ->assertSee('class="schedules-pagination"', false)
            ->assertSee('status=scheduled&amp;page=2', false)
            ->assertSeeText('Menampilkan 1')
            ->assertSee('schedules-summary-card--total', false);
    }

    public function test_every_desktop_page_renders_for_each_authorized_role(): void
    {
        $users = [
            'admin' => User::factory()->create(['role' => UserRole::Admin]),
            'petugas' => User::factory()->create(['role' => UserRole::Petugas]),
            'supervisor' => User::factory()->create(['role' => UserRole::Supervisor]),
        ];
        $context = $this->createScheduleContext($users['petugas'], 'completed');
        $check = $this->createCheck($context, $users['petugas']);
        $finding = $this->createFinding($context, $users['petugas'], $check);
        $allRoles = array_keys($users);

        $pages = [
            [route('dashboard'), $allRoles],
            [route('inspections.today'), $allRoles],
            [route('inspections.today', ['schedule' => $context['schedule']->id]), $allRoles],
            [route('inspections.show', $check), $allRoles],
            [route('schedules.index'), $allRoles],
            [route('schedules.index', ['schedule_detail' => $context['schedule']->id]), $allRoles],
            [route('findings.index'), $allRoles],
            [route('findings.show', $finding), $allRoles],
            [route('inspections.history'), $allRoles],
            [route('inspections.history.show', $check), $allRoles],
            [route('reports.monthly', ['machine_id' => $context['machine']->id, 'month' => 10, 'year' => 2026]), $allRoles],
            [route('reports.monthly.pdf', ['machine_id' => $context['machine']->id, 'month' => 10, 'year' => 2026]), $allRoles],
            [route('reports.monthly.excel', ['machine_id' => $context['machine']->id, 'month' => 10, 'year' => 2026]), $allRoles],
            [route('verifications.index'), ['admin', 'supervisor']],
            [route('verifications.show', $check), ['admin', 'supervisor']],
            [route('machines.index'), ['admin']],
            [route('machines.create'), ['admin']],
            [route('machines.show', $context['machine']), ['admin']],
            [route('machines.edit', $context['machine']), ['admin']],
            [route('checklists.index'), ['admin']],
            [route('schedules.index', ['form' => 'generate']), $allRoles],
            [route('schedules.index', ['form' => 'edit', 'schedule_detail' => $context['schedule']->id]), $allRoles],
            [route('users.index'), ['admin']],
            [route('users.create'), ['admin']],
            [route('users.show', $users['petugas']), ['admin']],
            [route('users.edit', $users['petugas']), ['admin']],
        ];

        foreach ($pages as [$url, $authorizedRoles]) {
            foreach ($users as $role => $user) {
                $response = $this->actingAs($user)->get($url);
                $isAuthorized = in_array($role, $authorizedRoles, true);

                $this->assertSame(
                    $isAuthorized ? 200 : 403,
                    $response->getStatusCode(),
                    "{$role} membuka {$url}",
                );
            }
        }

        foreach (['petugas', 'supervisor'] as $role) {
            $this->actingAs($users[$role])->get(route('schedules.index', ['form' => 'generate']))
                ->assertDontSee('action="'.route('schedules.generate').'"', false)
                ->assertDontSee('action="'.route('schedules.update', $context['schedule']).'"', false);
        }
    }

    /** @return array{machine: Machine, category: MaintenanceCategory, item: MaintenanceItem, schedule: MaintenanceSchedule} */
    private function createScheduleContext(User $petugas, string $status, string $frequency = 'semester'): array
    {
        static $number = 0;
        $number++;

        $machine = Machine::query()->create([
            'equipment_name' => 'Mesin Integrasi '.$number, 'brand' => 'Smiths Detection', 'type' => 'HI-SCAN',
            'serial_number' => 'INTEGRASI-'.$number, 'location' => 'Terminal '.$number, 'airport' => 'UPBU Malikussaleh',
            'status' => 'active',
        ]);
        $category = MaintenanceCategory::query()->create([
            'name' => 'Kategori Integrasi '.$number, 'frequency' => $frequency, 'status' => 'active',
        ]);
        $item = MaintenanceItem::query()->create([
            'maintenance_category_id' => $category->id, 'item_name' => 'Item Integrasi '.$number,
            'criteria' => 'Kriteria integrasi '.$number, 'input_type' => 'sesuai_tidak_sesuai',
            'sort_order' => 1, 'status' => 'active',
        ]);
        $schedule = MaintenanceSchedule::query()->create([
            'machine_id' => $machine->id, 'maintenance_category_id' => $category->id,
            'scheduled_date' => '2026-10-15', 'scheduled_time' => '08:00', 'assigned_user_id' => $petugas->id,
            'status' => $status,
        ]);

        return compact('machine', 'category', 'item', 'schedule');
    }

    /**
     * @param  array{machine: Machine, item: MaintenanceItem, schedule: MaintenanceSchedule}  $context
     * @param  array<string, mixed>  $attributes
     */
    private function createCheck(array $context, User $petugas, string $resultStatus = 'tidak_sesuai', array $attributes = []): MaintenanceCheck
    {
        $check = MaintenanceCheck::query()->create([
            'maintenance_schedule_id' => $context['schedule']->id, 'machine_id' => $context['machine']->id,
            'checked_by' => $petugas->id, 'check_date' => '2026-10-15', 'start_time' => '08:00', 'end_time' => '08:30',
            'overall_status' => 'has_finding', 'verification_status' => 'waiting', ...$attributes,
        ]);

        MaintenanceCheckDetail::query()->create([
            'maintenance_check_id' => $check->id, 'maintenance_item_id' => $context['item']->id,
            'result_status' => $resultStatus,
        ]);

        return $check;
    }

    /** @param array{machine: Machine, item: MaintenanceItem} $context */
    private function createFinding(
        array $context,
        User $petugas,
        MaintenanceCheck $check,
        string $status = 'open',
        string $description = 'Keterangan temuan integrasi.',
    ): MaintenanceFinding {
        return MaintenanceFinding::query()->create([
            'maintenance_check_id' => $check->id,
            'maintenance_check_detail_id' => $check->details()->value('id'),
            'machine_id' => $context['machine']->id, 'maintenance_item_id' => $context['item']->id,
            'reported_by' => $petugas->id, 'finding_date' => '2026-10-15',
            'finding_description' => $description, 'status' => $status,
        ]);
    }
}
