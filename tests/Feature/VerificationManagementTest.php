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

final class VerificationManagementTest extends TestCase
{
    use RefreshDatabase;

    /** @return array<string, array{UserRole}> */
    public static function rolesWithoutVerificationAccess(): array
    {
        return ['petugas' => [UserRole::Petugas]];
    }

    /** @return array<string, array{UserRole}> */
    public static function rolesWithoutDecisionAccess(): array
    {
        return ['admin' => [UserRole::Admin], 'petugas' => [UserRole::Petugas]];
    }

    public function test_guest_is_redirected_to_login_from_verification_pages(): void
    {
        $this->get('/verifications')->assertRedirect('/login');
    }

    #[DataProvider('rolesWithoutVerificationAccess')]
    public function test_roles_without_verification_access_are_forbidden(UserRole $role): void
    {
        $user = User::factory()->create(['role' => $role]);

        $this->actingAs($user)->get('/verifications')->assertForbidden();
    }

    #[DataProvider('rolesWithoutDecisionAccess')]
    public function test_only_supervisor_can_make_verification_decisions(UserRole $role): void
    {
        $actor = User::factory()->create(['role' => $role]);
        $petugas = $role === UserRole::Petugas ? $actor : User::factory()->create(['role' => UserRole::Petugas]);
        $context = $this->createCheckContext($petugas);

        $this->actingAs($actor)->patch('/verifications/'.$context['check']->id.'/verify')->assertForbidden();
        $this->actingAs($actor)->patch('/verifications/'.$context['check']->id.'/reject', [
            'supervisor_note' => 'Perlu revisi pemeriksaan.',
        ])->assertForbidden();

        $this->assertSame('waiting', $context['check']->fresh()->verification_status);
    }

    public function test_default_index_lists_only_completed_checks_waiting_for_verification(): void
    {
        $supervisor = User::factory()->create(['role' => UserRole::Supervisor]);
        $petugas = User::factory()->create(['role' => UserRole::Petugas]);
        $waiting = $this->createCheckContext($petugas, machine: ['equipment_name' => 'Mesin Menunggu']);
        $this->createCheckContext($petugas, machine: ['equipment_name' => 'Mesin Terverifikasi'], check: [
            'verification_status' => 'verified',
            'verified_by' => $supervisor->id,
            'verified_at' => '2026-10-13 11:00:00',
        ]);
        $this->createCheckContext($petugas, machine: ['equipment_name' => 'Mesin Masih Draft'], schedule: [
            'status' => 'in_progress',
        ], check: [
            'end_time' => null,
            'overall_status' => 'incomplete',
        ]);

        $this->actingAs($supervisor)->get('/verifications')
            ->assertOk()
            ->assertViewHas('checks', fn ($checks): bool => $checks->count() === 1 && $checks->first()->is($waiting['check']))
            ->assertSeeText('Mesin Menunggu');
    }

    public function test_admin_can_monitor_all_checks_and_apply_combined_filters(): void
    {
        $admin = User::factory()->create(['role' => UserRole::Admin]);
        $targetOfficer = User::factory()->create(['role' => UserRole::Petugas, 'name' => 'Petugas Target']);
        $otherOfficer = User::factory()->create(['role' => UserRole::Petugas, 'name' => 'Petugas Lain']);
        $target = $this->createCheckContext($targetOfficer, machine: [
            'equipment_name' => 'X-Ray Filter Target',
        ], check: [
            'check_date' => '2026-10-12',
            'overall_status' => 'has_finding',
            'verification_status' => 'rejected',
        ]);
        $this->createCheckContext($otherOfficer, machine: ['equipment_name' => 'Mesin Tidak Cocok'], check: [
            'check_date' => '2026-10-10',
            'overall_status' => 'normal',
            'verification_status' => 'verified',
        ]);

        $query = http_build_query([
            'start_date' => '2026-10-11',
            'end_date' => '2026-10-13',
            'machine_id' => $target['machine']->id,
            'officer_id' => $targetOfficer->id,
            'verification_status' => 'rejected',
            'overall_status' => 'has_finding',
            'search' => 'Filter Target',
        ]);

        $this->actingAs($admin)->get('/verifications?'.$query)
            ->assertOk()
            ->assertViewHas('checks', fn ($checks): bool => $checks->count() === 1 && $checks->first()->is($target['check']))
            ->assertSeeText('X-Ray Filter Target');
    }

    public function test_supervisor_can_view_machine_checklist_and_finding_details(): void
    {
        $supervisor = User::factory()->create(['role' => UserRole::Supervisor]);
        $petugas = User::factory()->create(['role' => UserRole::Petugas, 'name' => 'Budi Pemeriksa']);
        $context = $this->createCheckContext($petugas, machine: [
            'equipment_name' => 'X-Ray Detail Verifikasi',
            'brand' => 'Smiths Detection',
            'type' => 'HI-SCAN 100100T',
            'serial_number' => 'VERIFY-DETAIL-001',
            'location' => 'Terminal Verifikasi',
        ], check: [
            'overall_status' => 'has_finding',
            'general_note' => 'Catatan umum pemeriksaan.',
        ], detail: [
            'result_status' => 'tidak_sesuai',
            'actual_value' => 'Tidak stabil',
            'note' => 'Catatan item pemeriksaan.',
        ]);
        $this->createFinding($context, $petugas, 'Motor conveyor perlu diperiksa.');

        $this->actingAs($supervisor)->get('/verifications/'.$context['check']->id)
            ->assertOk()
            ->assertSeeText('X-Ray Detail Verifikasi')
            ->assertSeeText('Smiths Detection')
            ->assertSeeText('VERIFY-DETAIL-001')
            ->assertSeeText('Budi Pemeriksa')
            ->assertSeeText($context['item']->item_name)
            ->assertSeeText('Catatan item pemeriksaan.')
            ->assertSeeText('Motor conveyor perlu diperiksa.')
            ->assertSee('mobile-verification-detail__decision', false)
            ->assertSee(route('verifications.verify', $context['check']), false)
            ->assertSee(route('verifications.reject', $context['check']), false);
    }

    public function test_supervisor_can_verify_waiting_completed_check_with_audit_fields(): void
    {
        $this->travelTo(CarbonImmutable::parse('2026-10-14 09:30:00'));
        $supervisor = User::factory()->create(['role' => UserRole::Supervisor]);
        $petugas = User::factory()->create(['role' => UserRole::Petugas]);
        $context = $this->createCheckContext($petugas);

        $this->actingAs($supervisor)->patch('/verifications/'.$context['check']->id.'/verify', [
            'supervisor_note' => 'Hasil pemeriksaan dapat diterima.',
        ])->assertSessionHas('success', 'Pemeriksaan berhasil diverifikasi.');

        $check = $context['check']->fresh();
        $this->assertSame('verified', $check->verification_status);
        $this->assertSame($supervisor->id, $check->verified_by);
        $this->assertSame('2026-10-14 09:30:00', $check->verified_at?->toDateTimeString());
        $this->assertSame('Hasil pemeriksaan dapat diterima.', $check->supervisor_note);
    }

    public function test_verify_allows_an_empty_supervisor_note(): void
    {
        $supervisor = User::factory()->create(['role' => UserRole::Supervisor]);
        $context = $this->createCheckContext(User::factory()->create(['role' => UserRole::Petugas]));

        $this->actingAs($supervisor)->patch('/verifications/'.$context['check']->id.'/verify')
            ->assertSessionHas('success');

        $this->assertSame('verified', $context['check']->fresh()->verification_status);
        $this->assertNull($context['check']->supervisor_note);
    }

    public function test_reject_requires_a_supervisor_note_without_changing_the_check(): void
    {
        $supervisor = User::factory()->create(['role' => UserRole::Supervisor]);
        $context = $this->createCheckContext(User::factory()->create(['role' => UserRole::Petugas]));

        $this->actingAs($supervisor)->patch('/verifications/'.$context['check']->id.'/reject')
            ->assertSessionHasErrors([
                'supervisor_note' => 'Catatan supervisor wajib diisi saat pemeriksaan ditolak.',
            ]);

        $check = $context['check']->fresh();
        $this->assertSame('waiting', $check->verification_status);
        $this->assertNull($check->verified_by);
        $this->assertNull($check->verified_at);
    }

    public function test_mobile_reject_validation_error_is_visible_on_the_detail_page(): void
    {
        $supervisor = User::factory()->create(['role' => UserRole::Supervisor]);
        $context = $this->createCheckContext(User::factory()->create(['role' => UserRole::Petugas]));
        $detailUrl = route('verifications.show', ['check' => $context['check'], 'context' => 'mobile']);

        $this->actingAs($supervisor)
            ->from($detailUrl)
            ->followingRedirects()
            ->patch(route('verifications.reject', $context['check']))
            ->assertOk()
            ->assertSeeText('Keputusan belum dapat disimpan.')
            ->assertSeeText('Catatan supervisor wajib diisi saat pemeriksaan ditolak.');
    }

    public function test_supervisor_can_reject_waiting_completed_check_with_audit_fields(): void
    {
        $this->travelTo(CarbonImmutable::parse('2026-10-14 10:45:00'));
        $supervisor = User::factory()->create(['role' => UserRole::Supervisor]);
        $context = $this->createCheckContext(User::factory()->create(['role' => UserRole::Petugas]));

        $this->actingAs($supervisor)->patch('/verifications/'.$context['check']->id.'/reject', [
            'supervisor_note' => 'Ulangi pemeriksaan conveyor dengan beban uji.',
        ])->assertSessionHas('success', 'Pemeriksaan berhasil ditolak/revisi.');

        $check = $context['check']->fresh();
        $this->assertSame('rejected', $check->verification_status);
        $this->assertSame($supervisor->id, $check->verified_by);
        $this->assertSame('2026-10-14 10:45:00', $check->verified_at?->toDateTimeString());
        $this->assertSame('Ulangi pemeriksaan conveyor dengan beban uji.', $check->supervisor_note);
    }

    public function test_processed_check_cannot_be_verified_or_rejected_again(): void
    {
        $originalSupervisor = User::factory()->create(['role' => UserRole::Supervisor]);
        $otherSupervisor = User::factory()->create(['role' => UserRole::Supervisor]);
        $context = $this->createCheckContext(User::factory()->create(['role' => UserRole::Petugas]), check: [
            'verification_status' => 'verified',
            'verified_by' => $originalSupervisor->id,
            'verified_at' => '2026-10-13 08:00:00',
            'supervisor_note' => 'Catatan pertama.',
        ]);

        $this->actingAs($otherSupervisor)->patch('/verifications/'.$context['check']->id.'/verify')
            ->assertSessionHas('error', 'Pemeriksaan sudah diverifikasi sebelumnya.');
        $this->actingAs($otherSupervisor)->patch('/verifications/'.$context['check']->id.'/reject', [
            'supervisor_note' => 'Catatan pengganti.',
        ])->assertSessionHas('error', 'Pemeriksaan sudah diverifikasi sebelumnya.');

        $check = $context['check']->fresh();
        $this->assertSame('verified', $check->verification_status);
        $this->assertSame($originalSupervisor->id, $check->verified_by);
        $this->assertSame('Catatan pertama.', $check->supervisor_note);
    }

    public function test_incomplete_check_cannot_be_verified(): void
    {
        $supervisor = User::factory()->create(['role' => UserRole::Supervisor]);
        $context = $this->createCheckContext(User::factory()->create(['role' => UserRole::Petugas]), schedule: [
            'status' => 'in_progress',
        ], check: [
            'end_time' => null,
            'overall_status' => 'incomplete',
        ]);

        $this->actingAs($supervisor)->patch('/verifications/'.$context['check']->id.'/verify')
            ->assertSessionHas('error', 'Pemeriksaan belum selesai dan tidak dapat diverifikasi.');

        $this->assertSame('waiting', $context['check']->fresh()->verification_status);
    }

    public function test_history_pages_use_database_and_scope_petugas_to_own_checks(): void
    {
        $petugas = User::factory()->create(['role' => UserRole::Petugas]);
        $other = User::factory()->create(['role' => UserRole::Petugas]);
        $this->createCheckContext($petugas, machine: ['equipment_name' => 'Mesin Riwayat Milik Sendiri']);
        $this->createCheckContext($other, machine: ['equipment_name' => 'Mesin Riwayat Petugas Lain']);

        $this->actingAs($petugas)->get('/inspections/history')
            ->assertOk()
            ->assertSeeText('Mesin Riwayat Milik Sendiri')
            ->assertDontSeeText('Mesin Riwayat Petugas Lain')
            ->assertDontSeeText('X-Ray Cabin');
        $this->actingAs($petugas)->get('/mobile/history')
            ->assertOk()
            ->assertSeeText('Mesin Riwayat Milik Sendiri')
            ->assertDontSeeText('Mesin Riwayat Petugas Lain');
    }

    /**
     * @param  array<string, mixed>  $machine
     * @param  array<string, mixed>  $schedule
     * @param  array<string, mixed>  $check
     * @param  array<string, mixed>  $detail
     * @return array{machine: Machine, category: MaintenanceCategory, item: MaintenanceItem, schedule: MaintenanceSchedule, check: MaintenanceCheck, detail: MaintenanceCheckDetail}
     */
    private function createCheckContext(
        User $petugas,
        array $machine = [],
        array $schedule = [],
        array $check = [],
        array $detail = [],
    ): array {
        static $number = 0;
        $number++;
        $machineModel = Machine::query()->create([
            'equipment_name' => 'Mesin Verifikasi '.$number,
            'brand' => 'Smiths Detection',
            'type' => 'HI-SCAN',
            'serial_number' => 'VERIFY-'.$number,
            'location' => 'Terminal '.$number,
            'airport' => 'UPBU Malikussaleh',
            'status' => 'active',
            'description' => null,
            ...$machine,
        ]);
        $category = MaintenanceCategory::query()->create([
            'name' => 'Kategori Verifikasi '.$number,
            'frequency' => 'daily',
            'description' => null,
            'status' => 'active',
        ]);
        $item = MaintenanceItem::query()->create([
            'maintenance_category_id' => $category->id,
            'item_name' => 'Item Verifikasi '.$number,
            'criteria' => 'Kriteria pemeriksaan verifikasi '.$number,
            'input_type' => 'sesuai_tidak_sesuai',
            'sort_order' => 1,
            'status' => 'active',
        ]);
        $scheduleModel = MaintenanceSchedule::query()->create([
            'machine_id' => $machineModel->id,
            'maintenance_category_id' => $category->id,
            'scheduled_date' => '2026-10-13',
            'scheduled_time' => '08:00',
            'assigned_user_id' => $petugas->id,
            'status' => 'completed',
            'notes' => null,
            ...$schedule,
        ]);
        $checkModel = MaintenanceCheck::query()->create([
            'maintenance_schedule_id' => $scheduleModel->id,
            'machine_id' => $machineModel->id,
            'checked_by' => $petugas->id,
            'verified_by' => null,
            'check_date' => '2026-10-13',
            'start_time' => '08:00',
            'end_time' => '08:30',
            'overall_status' => 'normal',
            'verification_status' => 'waiting',
            'general_note' => 'Pemeriksaan selesai.',
            'supervisor_note' => null,
            'verified_at' => null,
            ...$check,
        ]);
        $detailModel = MaintenanceCheckDetail::query()->create([
            'maintenance_check_id' => $checkModel->id,
            'maintenance_item_id' => $item->id,
            'result_status' => 'sesuai',
            'actual_value' => null,
            'note' => null,
            'photo' => null,
            ...$detail,
        ]);

        return [
            'machine' => $machineModel,
            'category' => $category,
            'item' => $item,
            'schedule' => $scheduleModel,
            'check' => $checkModel,
            'detail' => $detailModel,
        ];
    }

    /**
     * @param  array{machine: Machine, item: MaintenanceItem, check: MaintenanceCheck, detail: MaintenanceCheckDetail}  $context
     */
    private function createFinding(array $context, User $petugas, string $description): MaintenanceFinding
    {
        return MaintenanceFinding::query()->create([
            'maintenance_check_id' => $context['check']->id,
            'maintenance_check_detail_id' => $context['detail']->id,
            'machine_id' => $context['machine']->id,
            'maintenance_item_id' => $context['item']->id,
            'reported_by' => $petugas->id,
            'finding_date' => '2026-10-13',
            'finding_description' => $description,
            'follow_up' => 'Tindak lanjut temuan.',
            'photo' => null,
            'status' => 'open',
            'reviewed_by' => null,
            'reviewed_at' => null,
            'resolved_at' => null,
        ]);
    }
}
