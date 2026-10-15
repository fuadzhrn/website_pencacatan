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
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

final class InspectionHistoryTest extends TestCase
{
    use RefreshDatabase;

    public function test_history_lists_submitted_checks_and_excludes_drafts(): void
    {
        $admin = User::factory()->create(['role' => UserRole::Admin]);
        $petugas = User::factory()->create(['role' => UserRole::Petugas]);
        $waiting = $this->createCheckContext($petugas, machineName: 'Mesin Final Waiting');
        $verified = $this->createCheckContext($petugas, machineName: 'Mesin Final Verified', check: [
            'verification_status' => 'verified',
            'verified_by' => $admin->id,
            'verified_at' => '2026-10-12 10:00:00',
        ]);
        $rejected = $this->createCheckContext($petugas, machineName: 'Mesin Final Rejected', check: [
            'verification_status' => 'rejected',
            'verified_by' => $admin->id,
            'verified_at' => '2026-10-12 11:00:00',
        ]);
        $this->createCheckContext($petugas, machineName: 'Mesin Draft Tersembunyi', schedule: [
            'status' => 'in_progress',
        ], check: [
            'end_time' => null,
            'overall_status' => 'incomplete',
        ]);

        $response = $this->actingAs($admin)->get('/inspections/history');

        $response->assertOk()
            ->assertSeeText('Mesin Final Waiting')
            ->assertSeeText('Mesin Final Verified')
            ->assertSeeText('Mesin Final Rejected')
            ->assertDontSeeText('Mesin Draft Tersembunyi')
            ->assertViewHas('checks', function (LengthAwarePaginator $checks) use ($waiting, $verified, $rejected): bool {
                $listedIds = $checks->getCollection()->pluck('id')->sort()->values()->all();
                $expectedIds = collect([$waiting['check']->id, $verified['check']->id, $rejected['check']->id])
                    ->sort()
                    ->values()
                    ->all();

                return $listedIds === $expectedIds
                    && $checks->getCollection()->every(fn (MaintenanceCheck $check): bool => $check->relationLoaded('machine')
                        && $check->relationLoaded('schedule')
                        && $check->relationLoaded('checkedBy'));
            });
    }

    public function test_history_filters_by_date_category_result_and_verification_status(): void
    {
        $admin = User::factory()->create(['role' => UserRole::Admin]);
        $petugas = User::factory()->create(['role' => UserRole::Petugas]);
        $target = $this->createCheckContext($petugas, machineName: 'X-Ray Sasaran Filter', check: [
            'check_date' => '2026-10-10',
            'overall_status' => 'has_finding',
            'verification_status' => 'rejected',
        ]);
        $this->createCheckContext($petugas, machineName: 'X-Ray Di Luar Filter', check: [
            'check_date' => '2026-10-13',
            'overall_status' => 'normal',
            'verification_status' => 'verified',
        ]);
        $query = http_build_query([
            'start_date' => '2026-10-09',
            'end_date' => '2026-10-11',
            'machine_id' => $target['machine']->id,
            'officer_id' => $petugas->id,
            'category_id' => $target['category']->id,
            'overall_status' => 'has_finding',
            'verification_status' => 'rejected',
            'search' => 'Sasaran Filter',
        ]);

        $response = $this->actingAs($admin)->get('/inspections/history?'.$query);

        $response->assertOk()
            ->assertSeeText('X-Ray Sasaran Filter')
            ->assertViewHas('checks', fn (LengthAwarePaginator $checks): bool => $checks->total() === 1
                && $checks->first()?->is($target['check']));
    }

    public function test_history_rejects_invalid_filter_values_with_clear_messages(): void
    {
        $admin = User::factory()->create(['role' => UserRole::Admin]);

        $this->actingAs($admin)
            ->from('/inspections/history')
            ->get('/inspections/history?start_date=2026-10-12&end_date=2026-10-10&verification_status=invalid')
            ->assertRedirect('/inspections/history')
            ->assertSessionHasErrors([
                'end_date',
                'verification_status',
            ]);
    }

    public function test_history_pagination_preserves_active_filters(): void
    {
        $admin = User::factory()->create(['role' => UserRole::Admin]);
        $petugas = User::factory()->create(['role' => UserRole::Petugas]);

        foreach (range(1, 16) as $number) {
            $this->createCheckContext($petugas, machineName: 'Mesin Pagination '.$number, check: [
                'verification_status' => 'verified',
            ]);
        }

        $response = $this->actingAs($admin)->get('/inspections/history?verification_status=verified&search=Pagination');

        $response->assertOk()
            ->assertViewHas('checks', fn (LengthAwarePaginator $checks): bool => $checks->total() === 16
                && $checks->perPage() === 15)
            ->assertSee('verification_status=verified', false)
            ->assertSee('search=Pagination', false);
    }

    public function test_petugas_only_sees_and_opens_their_own_submitted_history(): void
    {
        $petugas = User::factory()->create(['role' => UserRole::Petugas]);
        $otherPetugas = User::factory()->create(['role' => UserRole::Petugas]);
        $own = $this->createCheckContext($petugas, machineName: 'Mesin Milik Petugas');
        $other = $this->createCheckContext($otherPetugas, machineName: 'Mesin Milik Orang Lain');
        $draft = $this->createCheckContext($petugas, machineName: 'Draft Milik Petugas', schedule: [
            'status' => 'in_progress',
        ], check: [
            'end_time' => null,
            'overall_status' => 'incomplete',
        ]);

        $this->actingAs($petugas)->get('/inspections/history')
            ->assertOk()
            ->assertSeeText('Mesin Milik Petugas')
            ->assertDontSeeText('Mesin Milik Orang Lain')
            ->assertDontSeeText('Draft Milik Petugas');
        $this->actingAs($petugas)->get(route('inspections.history.show', $own['check']))->assertOk();
        $this->actingAs($petugas)->get(route('inspections.history.show', $other['check']))->assertNotFound();
        $this->actingAs($petugas)->get(route('inspections.history.show', $draft['check']))->assertNotFound();
        $this->actingAs($petugas)->get(route('inspections.show', $draft['check']))->assertNotFound();
    }

    public function test_history_detail_is_read_only_and_displays_checklist_findings_and_verification_audit(): void
    {
        $supervisor = User::factory()->create([
            'name' => 'Siti Supervisor',
            'role' => UserRole::Supervisor,
        ]);
        $petugas = User::factory()->create([
            'name' => 'Budi Petugas',
            'role' => UserRole::Petugas,
        ]);
        $context = $this->createCheckContext($petugas, machineName: 'X-Ray Detail Riwayat', check: [
            'overall_status' => 'has_finding',
            'verification_status' => 'verified',
            'verified_by' => $supervisor->id,
            'verified_at' => '2026-10-14 09:30:00',
            'supervisor_note' => 'Pemeriksaan disetujui supervisor.',
            'general_note' => 'Catatan umum pemeriksaan final.',
        ], detail: [
            'result_status' => 'tidak_sesuai',
            'actual_value' => 'Belt bergeser 2 cm',
            'note' => 'Perlu penyetelan ulang.',
        ]);
        $this->createFinding($context, $petugas, [
            'finding_description' => 'Conveyor belt tidak stabil.',
            'follow_up' => 'Jadwalkan penyetelan motor.',
            'status' => 'review',
        ]);

        $response = $this->actingAs($supervisor)->get(route('inspections.history.show', $context['check']));

        $response->assertOk()
            ->assertSeeText('X-Ray Detail Riwayat')
            ->assertSeeText('Budi Petugas')
            ->assertSeeText('Belt bergeser 2 cm')
            ->assertSeeText('Perlu penyetelan ulang.')
            ->assertSeeText('Conveyor belt tidak stabil.')
            ->assertSeeText('Jadwalkan penyetelan motor.')
            ->assertSeeText('Siti Supervisor')
            ->assertSeeText('Pemeriksaan disetujui supervisor.')
            ->assertDontSee(route('verifications.verify', $context['check']), false)
            ->assertDontSee(route('verifications.reject', $context['check']), false);
    }

    public function test_waiting_history_is_read_only_and_links_supervisor_to_the_existing_verification_process(): void
    {
        $supervisor = User::factory()->create(['role' => UserRole::Supervisor]);
        $petugas = User::factory()->create(['role' => UserRole::Petugas]);
        $context = $this->createCheckContext($petugas, machineName: 'Mesin Menunggu Verifikasi');

        $response = $this->actingAs($supervisor)->get(route('inspections.history.show', $context['check']));

        $response->assertOk()
            ->assertSeeText('Buka Proses Verifikasi')
            ->assertSee(route('verifications.show', $context['check']), false)
            ->assertDontSee(route('verifications.verify', $context['check']), false)
            ->assertDontSee(route('verifications.reject', $context['check']), false);
    }

    public function test_related_photos_follow_the_history_visibility_scope(): void
    {
        Storage::fake('local');
        Storage::disk('local')->put('inspection-details/detail.jpg', 'detail-photo');
        Storage::disk('local')->put('findings/finding.jpg', 'finding-photo');
        $owner = User::factory()->create(['role' => UserRole::Petugas]);
        $otherPetugas = User::factory()->create(['role' => UserRole::Petugas]);
        $context = $this->createCheckContext($owner, machineName: 'Mesin Dengan Foto', detail: [
            'photo' => 'inspection-details/detail.jpg',
        ]);
        $finding = $this->createFinding($context, $owner, [
            'photo' => 'findings/finding.jpg',
        ]);
        $unrelatedContext = $this->createCheckContext($owner, machineName: 'Mesin Foto Lain');

        $this->actingAs($owner)
            ->get(route('inspections.history.details.photo', [$context['check'], $context['detail']]))
            ->assertOk();
        $this->actingAs($owner)
            ->get(route('inspections.history.findings.photo', [$context['check'], $finding]))
            ->assertOk();
        $this->actingAs($otherPetugas)
            ->get(route('inspections.history.details.photo', [$context['check'], $context['detail']]))
            ->assertNotFound();
        $this->actingAs($otherPetugas)
            ->get(route('inspections.history.findings.photo', [$context['check'], $finding]))
            ->assertNotFound();
        $this->actingAs($owner)
            ->get(route('inspections.history.details.photo', [$unrelatedContext['check'], $context['detail']]))
            ->assertNotFound();
    }

    public function test_mobile_history_uses_the_same_database_filters_and_detail_backend(): void
    {
        $petugas = User::factory()->create(['role' => UserRole::Petugas]);
        $context = $this->createCheckContext($petugas, machineName: 'Mesin Riwayat Mobile');

        $this->actingAs($petugas)->get('/mobile/history?verification_status=waiting')
            ->assertOk()
            ->assertSeeText('Mesin Riwayat Mobile')
            ->assertSee(route('inspections.history.show', ['check' => $context['check'], 'context' => 'mobile']), false);
    }

    /**
     * @param  array<string, mixed>  $schedule
     * @param  array<string, mixed>  $check
     * @param  array<string, mixed>  $detail
     * @return array{machine: Machine, category: MaintenanceCategory, item: MaintenanceItem, schedule: MaintenanceSchedule, check: MaintenanceCheck, detail: MaintenanceCheckDetail}
     */
    private function createCheckContext(
        User $petugas,
        string $machineName,
        array $schedule = [],
        array $check = [],
        array $detail = [],
    ): array {
        $unique = fake()->unique()->numberBetween(1000, 999999);
        $machine = Machine::query()->create([
            'equipment_name' => $machineName,
            'brand' => 'Smiths Detection',
            'type' => 'HI-SCAN',
            'serial_number' => 'HISTORY-'.$unique,
            'location' => 'Terminal '.$unique,
            'airport' => 'UPBU Malikussaleh',
            'status' => 'active',
            'description' => null,
        ]);
        $category = MaintenanceCategory::query()->create([
            'name' => 'Kategori '.$unique,
            'frequency' => 'daily',
            'description' => null,
            'status' => 'active',
        ]);
        $item = MaintenanceItem::query()->create([
            'maintenance_category_id' => $category->id,
            'item_name' => 'Lead curtain '.$unique,
            'criteria' => 'Tidak robek dan menutup sempurna.',
            'input_type' => 'sesuai_tidak_sesuai',
            'sort_order' => 1,
            'status' => 'active',
        ]);
        $scheduleModel = MaintenanceSchedule::query()->create([
            'machine_id' => $machine->id,
            'maintenance_category_id' => $category->id,
            'scheduled_date' => '2026-10-10',
            'scheduled_time' => '08:00',
            'assigned_user_id' => $petugas->id,
            'status' => 'completed',
            'notes' => 'Jadwal pemeriksaan.',
            ...$schedule,
        ]);
        $checkModel = MaintenanceCheck::query()->create([
            'maintenance_schedule_id' => $scheduleModel->id,
            'machine_id' => $machine->id,
            'checked_by' => $petugas->id,
            'verified_by' => null,
            'check_date' => '2026-10-10',
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
            'machine' => $machine,
            'category' => $category,
            'item' => $item,
            'schedule' => $scheduleModel,
            'check' => $checkModel,
            'detail' => $detailModel,
        ];
    }

    /**
     * @param  array{machine: Machine, item: MaintenanceItem, check: MaintenanceCheck, detail: MaintenanceCheckDetail}  $context
     * @param  array<string, mixed>  $attributes
     */
    private function createFinding(array $context, User $petugas, array $attributes = []): MaintenanceFinding
    {
        return MaintenanceFinding::query()->create([
            'maintenance_check_id' => $context['check']->id,
            'maintenance_check_detail_id' => $context['detail']->id,
            'machine_id' => $context['machine']->id,
            'maintenance_item_id' => $context['item']->id,
            'reported_by' => $petugas->id,
            'finding_date' => '2026-10-10',
            'finding_description' => 'Temuan pemeriksaan.',
            'follow_up' => 'Tindak lanjut temuan.',
            'photo' => null,
            'status' => 'open',
            'reviewed_by' => null,
            'reviewed_at' => null,
            'resolved_at' => null,
            ...$attributes,
        ]);
    }
}
