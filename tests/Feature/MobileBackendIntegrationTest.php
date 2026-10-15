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
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

final class MobileBackendIntegrationTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->travelTo(CarbonImmutable::parse('2026-10-15 09:00:00'));
    }

    public function test_every_mobile_page_renders_for_each_authorized_role(): void
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
        $reportFilters = ['machine_id' => $context['machine']->id, 'month' => 10, 'year' => 2026];

        $pages = [
            [route('mobile.dashboard'), $allRoles, 'data-mobile-dashboard'],
            [route('mobile.schedules'), $allRoles, 'data-mobile-schedules'],
            [route('mobile.schedules', ['schedule_detail' => $context['schedule']->id]), $allRoles, 'mobile-schedule-sheet is-open'],
            [route('mobile.inspection-today', ['schedule' => $context['schedule']->id]), $allRoles, 'mobile-checklist-category'],
            [route('mobile.findings'), $allRoles, 'mobile-finding-card'],
            [route('mobile.findings', ['finding_detail' => $finding->id]), $allRoles, 'mobile-finding-sheet is-open'],
            [route('findings.show', $finding), $allRoles, 'mobile-finding-sheet is-open'],
            [route('mobile.history'), $allRoles, 'mobile-history-database__card'],
            [route('inspections.history.show', ['check' => $check, 'context' => 'mobile']), $allRoles, 'mobile-verification-detail'],
            [route('mobile.report', $reportFilters), $allRoles, 'data-mobile-report-database'],
            [route('verifications.index'), ['admin', 'supervisor'], 'mobile-history-database'],
            [route('verifications.show', ['check' => $check, 'context' => 'mobile']), ['admin', 'supervisor'], 'mobile-verification-detail'],
            [route('mobile.users'), ['admin'], 'data-mobile-users'],
            [route('mobile.machines'), ['admin'], 'mobile-machines'],
            [route('mobile.checklists'), ['admin'], 'data-mobile-checklists'],
        ];

        foreach ($pages as [$url, $authorizedRoles, $mobileMarker]) {
            foreach ($users as $role => $user) {
                $response = $this->actingAs($user)->get($url);

                if (! in_array($role, $authorizedRoles, true)) {
                    $this->assertSame(403, $response->getStatusCode(), "{$role} membuka {$url}");

                    continue;
                }

                $this->assertSame(200, $response->getStatusCode(), "{$role} membuka {$url}");
                $response->assertSee($mobileMarker, false)->assertSeeText($user->name);
            }
        }
    }

    public function test_mobile_navigation_follows_role_and_uses_real_routes(): void
    {
        $petugas = User::factory()->create(['role' => UserRole::Petugas]);
        $supervisor = User::factory()->create(['role' => UserRole::Supervisor]);

        $this->actingAs($petugas)->get(route('mobile.dashboard'))
            ->assertOk()
            ->assertDontSee('href="'.route('mobile.machines').'"', false)
            ->assertDontSee('href="'.route('mobile.users').'"', false)
            ->assertDontSee('data-mobile-route="verifications"', false)
            ->assertSee('href="'.route('mobile.report').'"', false)
            ->assertSee('action="'.route('logout').'"', false)
            ->assertDontSee('simulasi');

        $this->actingAs($supervisor)->get(route('mobile.findings'))
            ->assertOk()
            ->assertSee('data-mobile-route="verifications"', false)
            ->assertSee('class="mobile-drawer__link is-active" data-mobile-nav-link data-mobile-route="findings"', false);
    }

    public function test_petugas_completes_mobile_inspection_and_result_appears_on_desktop(): void
    {
        Storage::fake('local');
        $petugas = User::factory()->create(['role' => UserRole::Petugas]);
        $supervisor = User::factory()->create(['role' => UserRole::Supervisor]);
        $context = $this->createScheduleContext($petugas, 'scheduled');

        $this->actingAs($petugas)->get(route('mobile.schedules'))
            ->assertSee('action="'.route('inspections.start', $context['schedule']).'"', false);

        $this->actingAs($petugas)
            ->post(route('inspections.start', $context['schedule']), ['context' => 'mobile'])
            ->assertRedirect(route('mobile.inspection-today', ['schedule' => $context['schedule']->id]));

        $this->actingAs($petugas)->get(route('mobile.inspection-today', ['schedule' => $context['schedule']->id]))
            ->assertOk()
            ->assertSee('data-mark-all-conform', false)
            ->assertSee('data-mobile-photo-input', false)
            ->assertSee('name="items['.$context['item']->id.'][result_status]"', false);

        $payload = fn (string $submission, string $result): array => [
            'context' => 'mobile',
            'maintenance_schedule_id' => $context['schedule']->id,
            'submission_type' => $submission,
            'items' => [$context['item']->id => array_filter([
                'maintenance_item_id' => $context['item']->id,
                'result_status' => $result,
                'finding_description' => $result === 'tidak_sesuai' ? 'Belt bergeser dari jalur.' : null,
                'finding_photo' => $result === 'tidak_sesuai' ? UploadedFile::fake()->image('belt.jpg') : null,
            ])],
        ];

        $this->actingAs($petugas)
            ->from(route('mobile.inspection-today', ['schedule' => $context['schedule']->id]))
            ->followingRedirects()
            ->post(route('inspections.store', $context['schedule']), $payload('final', 'belum_diperiksa'))
            ->assertSeeText('Masih ada checklist yang belum diisi.');
        $this->assertSame('in_progress', $context['schedule']->fresh()->status);

        $this->actingAs($petugas)
            ->post(route('inspections.store', $context['schedule']), $payload('draft', 'tidak_sesuai'))
            ->assertRedirect(route('mobile.inspection-today', ['schedule' => $context['schedule']->id]));
        $this->assertSame('in_progress', $context['schedule']->fresh()->status);

        $this->actingAs($petugas)
            ->post(route('inspections.store', $context['schedule']), $payload('final', 'tidak_sesuai'))
            ->assertSessionHas('success', 'Pemeriksaan berhasil disimpan.');

        $this->assertSame('completed', $context['schedule']->fresh()->status);
        $this->assertSame(1, MaintenanceFinding::query()->count());
        $finding = MaintenanceFinding::query()->firstOrFail();
        $this->assertSame($petugas->id, $finding->reported_by);
        Storage::disk('local')->assertExists($finding->photo);

        $this->actingAs($supervisor)->get(route('findings.index'))
            ->assertSeeText('Belt bergeser dari jalur.');
        $this->actingAs($supervisor)->get(route('verifications.index'))
            ->assertSeeText($context['machine']->equipment_name);
    }

    public function test_mobile_finding_sheet_is_limited_to_visible_findings(): void
    {
        $owner = User::factory()->create(['role' => UserRole::Petugas]);
        $otherPetugas = User::factory()->create(['role' => UserRole::Petugas]);
        $context = $this->createScheduleContext($owner, 'completed');
        $finding = $this->createFinding($context, $owner, $this->createCheck($context, $owner), 'open', 'Temuan Rahasia Pemilik');

        $this->actingAs($owner)->get(route('mobile.findings', ['finding_detail' => $finding->id, 'status' => 'open']))
            ->assertOk()
            ->assertSee('mobile-finding-sheet is-open', false)
            ->assertSee('action="'.route('findings.update', $finding).'"', false)
            ->assertSee('href="'.route('mobile.findings', ['status' => 'open']).'"', false);

        $this->actingAs($otherPetugas)->get(route('mobile.findings', ['finding_detail' => $finding->id]))
            ->assertOk()
            ->assertDontSee('mobile-finding-sheet is-open', false)
            ->assertDontSeeText('Temuan Rahasia Pemilik');

        $this->actingAs($otherPetugas)->get(route('findings.show', ['finding' => $finding, 'context' => 'mobile']))->assertNotFound();
        $this->actingAs($owner)->get(route('findings.show', ['finding' => $finding, 'context' => 'mobile']))
            ->assertRedirect(route('mobile.findings', ['finding_detail' => $finding->id]));
    }

    public function test_supervisor_processes_finding_status_from_mobile(): void
    {
        $petugas = User::factory()->create(['role' => UserRole::Petugas]);
        $supervisor = User::factory()->create(['role' => UserRole::Supervisor]);
        $context = $this->createScheduleContext($petugas, 'completed');
        $finding = $this->createFinding($context, $petugas, $this->createCheck($context, $petugas));

        $this->actingAs($supervisor)
            ->from(route('mobile.findings', ['finding_detail' => $finding->id]))
            ->patch(route('findings.update-status', $finding), ['status' => 'review', 'context' => 'mobile'])
            ->assertRedirect(route('mobile.findings', ['finding_detail' => $finding->id]));

        $this->assertSame('review', $finding->fresh()->status);

        $this->actingAs($supervisor)->get(route('mobile.findings', ['finding_detail' => $finding->id]))
            ->assertSee('value="resolved"', false)
            ->assertSeeText('Selesaikan');

        $this->actingAs($petugas)
            ->patch(route('findings.update-status', $finding), ['status' => 'resolved', 'context' => 'mobile'])
            ->assertForbidden();
        $this->assertSame('review', $finding->fresh()->status);
    }

    public function test_failed_checklist_edit_reopens_as_edit_instead_of_create(): void
    {
        $admin = User::factory()->create(['role' => UserRole::Admin]);
        $category = MaintenanceCategory::query()->create(['name' => 'Kategori Edit', 'frequency' => 'daily', 'status' => 'active']);
        $item = MaintenanceItem::query()->create([
            'maintenance_category_id' => $category->id, 'item_name' => 'Item Edit', 'criteria' => 'Kriteria',
            'input_type' => 'sesuai_tidak_sesuai', 'sort_order' => 1, 'status' => 'active',
        ]);

        $this->actingAs($admin)
            ->from(route('mobile.checklists'))
            ->followingRedirects()
            ->put(route('maintenance-items.update', $item), [
                'context' => 'mobile', 'form_type' => 'item', 'record_id' => $item->id,
                'maintenance_category_id' => $category->id, 'item_name' => '', 'criteria' => 'Kriteria',
                'input_type' => 'sesuai_tidak_sesuai', 'status' => 'active',
            ])
            ->assertOk()
            ->assertSee('action="'.route('maintenance-items.update', $item->id).'"', false)
            ->assertSee('name="_method" value="PUT"', false)
            ->assertSeeText('Edit Checklist')
            ->assertSee('class="form-error"', false);

        $this->assertSame(1, MaintenanceItem::query()->count());
        $this->assertSame('Item Edit', $item->fresh()->item_name);
    }

    public function test_mobile_report_offers_summary_detail_and_scroll_modes_from_shared_report(): void
    {
        $supervisor = User::factory()->create(['role' => UserRole::Supervisor]);
        $petugas = User::factory()->create(['role' => UserRole::Petugas]);
        $context = $this->createScheduleContext($petugas, 'completed');
        $this->createCheck($context, $petugas, 'tidak_sesuai');

        $this->actingAs($supervisor)
            ->get(route('mobile.report', ['machine_id' => $context['machine']->id, 'month' => 10, 'year' => 2026]))
            ->assertOk()
            ->assertSee('data-mobile-report-mode="summary"', false)
            ->assertSee('data-mobile-report-mode="detail"', false)
            ->assertSee('data-mobile-report-mode="table"', false)
            ->assertSee('data-mobile-report-detail-item="0"', false)
            ->assertSeeText('Ada Temuan')
            ->assertSeeText('15 Oktober')
            ->assertSee(asset('assets/js/mobile-report-database.js'), false)
            ->assertSee(e(route('reports.monthly.pdf', ['machine_id' => $context['machine']->id, 'month' => 10, 'year' => 2026])), false);
    }

    public function test_mobile_schedule_filters_include_frequency_and_survive_detail(): void
    {
        $admin = User::factory()->create(['role' => UserRole::Admin]);
        $petugas = User::factory()->create(['role' => UserRole::Petugas]);
        $semester = $this->createScheduleContext($petugas, 'scheduled', 'semester');
        $daily = $this->createScheduleContext($petugas, 'scheduled', 'daily');

        $this->actingAs($admin)->get(route('mobile.schedules', ['frequency' => 'daily']))
            ->assertOk()
            ->assertSee('<option value="daily" selected>', false)
            ->assertSee($daily['machine']->location.' · UPBU Malikussaleh</p><h3>'.$daily['machine']->equipment_name.'</h3>', false)
            ->assertDontSee($semester['machine']->location.' · UPBU Malikussaleh</p><h3>'.$semester['machine']->equipment_name.'</h3>', false)
            ->assertSee('frequency=daily&amp;schedule_detail='.$daily['schedule']->id, false);

        $this->actingAs($admin)->get(route('mobile.schedules', ['frequency' => 'daily', 'schedule_detail' => $daily['schedule']->id]))
            ->assertSee('class="mobile-schedule-sheet__close-button" href="'.route('mobile.schedules', ['frequency' => 'daily']).'"', false);
    }

    public function test_mobile_history_detail_uses_shared_labels(): void
    {
        $petugas = User::factory()->create(['role' => UserRole::Petugas]);
        $context = $this->createScheduleContext($petugas, 'completed');
        $check = $this->createCheck($context, $petugas, 'belum_diperiksa', ['overall_status' => 'incomplete']);

        $this->actingAs($petugas)->get(route('inspections.history.show', ['check' => $check, 'context' => 'mobile']))
            ->assertOk()
            ->assertSee('mobile-verification-detail', false)
            ->assertSeeText('Belum Diperiksa');
    }

    /** @return array{machine: Machine, category: MaintenanceCategory, item: MaintenanceItem, schedule: MaintenanceSchedule} */
    private function createScheduleContext(User $petugas, string $status, string $frequency = 'semester'): array
    {
        static $number = 0;
        $number++;

        $machine = Machine::query()->create([
            'equipment_name' => 'Mesin Mobile '.$number, 'brand' => 'Smiths Detection', 'type' => 'HI-SCAN',
            'serial_number' => 'MOBILE-'.$number, 'location' => 'Terminal '.$number, 'airport' => 'UPBU Malikussaleh',
            'status' => 'active',
        ]);
        $category = MaintenanceCategory::query()->create([
            'name' => 'Kategori Mobile '.$number, 'frequency' => $frequency, 'status' => 'active',
        ]);
        $item = MaintenanceItem::query()->create([
            'maintenance_category_id' => $category->id, 'item_name' => 'Item Mobile '.$number,
            'criteria' => 'Kriteria mobile '.$number, 'input_type' => 'sesuai_tidak_sesuai',
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
        string $description = 'Keterangan temuan mobile.',
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
