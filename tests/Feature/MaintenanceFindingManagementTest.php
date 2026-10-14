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
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

final class MaintenanceFindingManagementTest extends TestCase
{
    use RefreshDatabase;

    /** @return array<string, array{UserRole}> */
    public static function monitoringRoles(): array
    {
        return ['admin' => [UserRole::Admin], 'supervisor' => [UserRole::Supervisor]];
    }

    /** @return array<string, array{UserRole}> */
    public static function rolesWithoutStatusAccess(): array
    {
        return ['admin' => [UserRole::Admin], 'petugas' => [UserRole::Petugas]];
    }

    public function test_nonconform_inspection_item_creates_one_open_finding_with_photo(): void
    {
        Storage::fake('public');
        $this->travelTo(CarbonImmutable::parse('2026-10-13 09:15:00'));
        $petugas = User::factory()->create(['role' => UserRole::Petugas]);
        $context = $this->createInspectionContext($petugas);

        $this->actingAs($petugas)->post('/inspections/schedules/'.$context['schedule']->id, [
            'maintenance_schedule_id' => $context['schedule']->id,
            'submission_type' => 'draft',
            'items' => [$context['item']->id => $this->inspectionItemPayload(
                $context['item'],
                'tidak_sesuai',
                'Conveyor tersendat saat dijalankan.',
                'Periksa motor dan ketegangan belt.',
                UploadedFile::fake()->image('conveyor.jpg'),
            )],
        ])->assertSessionHas('success');

        $finding = MaintenanceFinding::query()->firstOrFail();
        $detail = MaintenanceCheckDetail::query()->firstOrFail();
        $this->assertSame($detail->id, $finding->maintenance_check_detail_id);
        $this->assertSame($context['check']->id, $finding->maintenance_check_id);
        $this->assertSame($context['machine']->id, $finding->machine_id);
        $this->assertSame($context['item']->id, $finding->maintenance_item_id);
        $this->assertSame($petugas->id, $finding->reported_by);
        $this->assertSame('2026-10-13', $finding->finding_date->toDateString());
        $this->assertSame('Conveyor tersendat saat dijalankan.', $finding->finding_description);
        $this->assertSame('Periksa motor dan ketegangan belt.', $finding->follow_up);
        $this->assertSame('open', $finding->status);
        Storage::disk('public')->assertExists($finding->photo);
    }

    public function test_nonconform_item_requires_description_before_writing_details(): void
    {
        $this->travelTo(CarbonImmutable::parse('2026-10-13 09:15:00'));
        $petugas = User::factory()->create(['role' => UserRole::Petugas]);
        $context = $this->createInspectionContext($petugas);

        $response = $this->actingAs($petugas)->post('/inspections/schedules/'.$context['schedule']->id, [
            'maintenance_schedule_id' => $context['schedule']->id,
            'submission_type' => 'draft',
            'items' => [$context['item']->id => $this->inspectionItemPayload($context['item'], 'tidak_sesuai')],
        ]);

        $response->assertSessionHasErrors([
            'items.'.$context['item']->id.'.finding_description' => 'Keterangan temuan wajib diisi jika checklist tidak sesuai.',
        ]);
        $this->assertDatabaseCount('maintenance_check_details', 0);
        $this->assertDatabaseCount('maintenance_findings', 0);
    }

    public function test_conform_inspection_does_not_create_a_finding(): void
    {
        $this->travelTo(CarbonImmutable::parse('2026-10-13 09:15:00'));
        $petugas = User::factory()->create(['role' => UserRole::Petugas]);
        $context = $this->createInspectionContext($petugas);

        $this->actingAs($petugas)->post('/inspections/schedules/'.$context['schedule']->id, [
            'maintenance_schedule_id' => $context['schedule']->id,
            'submission_type' => 'final',
            'items' => [$context['item']->id => $this->inspectionItemPayload($context['item'], 'sesuai')],
        ])->assertSessionHas('success');

        $this->assertDatabaseCount('maintenance_findings', 0);
    }

    public function test_resaving_same_nonconform_item_updates_without_duplication(): void
    {
        $this->travelTo(CarbonImmutable::parse('2026-10-13 09:15:00'));
        $petugas = User::factory()->create(['role' => UserRole::Petugas]);
        $context = $this->createInspectionContext($petugas);

        $this->submitInspectionFinding($petugas, $context, 'Keterangan pertama.');
        $this->submitInspectionFinding($petugas, $context, 'Keterangan diperbarui.');

        $this->assertDatabaseCount('maintenance_findings', 1);
        $this->assertDatabaseHas('maintenance_findings', [
            'maintenance_check_id' => $context['check']->id,
            'finding_description' => 'Keterangan diperbarui.',
            'status' => 'open',
        ]);
    }

    public function test_correcting_draft_removes_only_unreviewed_open_finding_and_photo(): void
    {
        Storage::fake('public');
        $this->travelTo(CarbonImmutable::parse('2026-10-13 09:15:00'));
        $petugas = User::factory()->create(['role' => UserRole::Petugas]);
        $context = $this->createInspectionContext($petugas);
        $this->submitInspectionFinding($petugas, $context, 'Temuan sementara.', UploadedFile::fake()->image('temuan.jpg'));
        $photoPath = MaintenanceFinding::query()->firstOrFail()->photo;

        $this->actingAs($petugas)->post('/inspections/schedules/'.$context['schedule']->id, [
            'maintenance_schedule_id' => $context['schedule']->id,
            'submission_type' => 'draft',
            'items' => [$context['item']->id => $this->inspectionItemPayload($context['item'], 'sesuai')],
        ])->assertSessionHas('success');

        $this->assertDatabaseCount('maintenance_findings', 0);
        Storage::disk('public')->assertMissing($photoPath);
    }

    public function test_reviewed_finding_prevents_petugas_from_correcting_its_item(): void
    {
        $this->travelTo(CarbonImmutable::parse('2026-10-13 09:15:00'));
        $petugas = User::factory()->create(['role' => UserRole::Petugas]);
        $supervisor = User::factory()->create(['role' => UserRole::Supervisor]);
        $context = $this->createInspectionContext($petugas);
        $this->submitInspectionFinding($petugas, $context, 'Temuan sudah direview.');
        $finding = MaintenanceFinding::query()->firstOrFail();
        $finding->update(['status' => 'review', 'reviewed_by' => $supervisor->id, 'reviewed_at' => now()]);

        $response = $this->actingAs($petugas)->post('/inspections/schedules/'.$context['schedule']->id, [
            'maintenance_schedule_id' => $context['schedule']->id,
            'submission_type' => 'draft',
            'items' => [$context['item']->id => $this->inspectionItemPayload($context['item'], 'sesuai')],
        ]);

        $response->assertSessionHasErrors('items.'.$context['item']->id.'.result_status');
        $this->assertSame('tidak_sesuai', MaintenanceCheckDetail::query()->firstOrFail()->result_status);
        $this->assertSame('review', $finding->fresh()->status);
    }

    public function test_reviewed_finding_does_not_block_finalizing_the_unchanged_nonconform_item(): void
    {
        $this->travelTo(CarbonImmutable::parse('2026-10-13 09:15:00'));
        $petugas = User::factory()->create(['role' => UserRole::Petugas]);
        $supervisor = User::factory()->create(['role' => UserRole::Supervisor]);
        $context = $this->createInspectionContext($petugas);
        $detail = $this->createDetail($context, 'tidak_sesuai');
        $finding = $this->createFinding($context, $petugas, $detail, [
            'status' => 'review',
            'reviewed_by' => $supervisor->id,
            'reviewed_at' => now(),
        ]);

        $this->actingAs($petugas)->post('/inspections/schedules/'.$context['schedule']->id, [
            'maintenance_schedule_id' => $context['schedule']->id,
            'submission_type' => 'final',
            'items' => [$context['item']->id => $this->inspectionItemPayload($context['item'], 'tidak_sesuai')],
        ])->assertSessionHas('success');

        $this->assertSame('completed', $context['schedule']->fresh()->status);
        $this->assertSame('review', $finding->fresh()->status);
        $this->assertSame('Keterangan temuan pengujian.', $finding->finding_description);
    }

    public function test_petugas_can_create_and_update_an_own_manual_open_finding(): void
    {
        Storage::fake('public');
        Storage::disk('public')->put('findings/foto-lama.jpg', 'lama');
        $this->travelTo(CarbonImmutable::parse('2026-10-13 10:00:00'));
        $petugas = User::factory()->create(['role' => UserRole::Petugas]);
        $context = $this->createInspectionContext($petugas);
        $detail = $this->createDetail($context, 'tidak_sesuai');

        $this->actingAs($petugas)->post('/findings', $this->findingPayload($context, $detail))
            ->assertSessionHas('success', 'Temuan berhasil dibuat.');
        $finding = MaintenanceFinding::query()->firstOrFail();
        $finding->update(['photo' => 'findings/foto-lama.jpg']);

        $this->actingAs($petugas)->put('/findings/'.$finding->id, $this->findingPayload($context, $detail, [
            'finding_description' => 'Keterangan terbaru.',
            'photo' => UploadedFile::fake()->image('foto-baru.jpg'),
        ]))->assertSessionHas('success', 'Temuan berhasil diperbarui.');

        $finding->refresh();
        $this->assertSame('Keterangan terbaru.', $finding->finding_description);
        $this->assertSame('open', $finding->status);
        Storage::disk('public')->assertMissing('findings/foto-lama.jpg');
        Storage::disk('public')->assertExists($finding->photo);
    }

    public function test_petugas_cannot_create_or_view_another_officers_finding(): void
    {
        $petugas = User::factory()->create(['role' => UserRole::Petugas]);
        $other = User::factory()->create(['role' => UserRole::Petugas]);
        $context = $this->createInspectionContext($other);
        $detail = $this->createDetail($context, 'tidak_sesuai');

        $this->actingAs($petugas)->post('/findings', $this->findingPayload($context, $detail))->assertForbidden();
        $finding = $this->createFinding($context, $other, $detail);
        $this->actingAs($petugas)->get('/findings/'.$finding->id)->assertNotFound();
    }

    public function test_manual_create_cannot_reset_an_existing_reviewed_finding(): void
    {
        $petugas = User::factory()->create(['role' => UserRole::Petugas]);
        $supervisor = User::factory()->create(['role' => UserRole::Supervisor]);
        $context = $this->createInspectionContext($petugas);
        $detail = $this->createDetail($context, 'tidak_sesuai');
        $finding = $this->createFinding($context, $petugas, $detail, [
            'status' => 'review',
            'reviewed_by' => $supervisor->id,
            'reviewed_at' => now(),
        ]);

        $this->actingAs($petugas)
            ->post('/findings', $this->findingPayload($context, $detail))
            ->assertStatus(409);

        $this->assertDatabaseCount('maintenance_findings', 1);
        $this->assertSame('review', $finding->fresh()->status);
        $this->assertSame($supervisor->id, $finding->reviewed_by);
    }

    public function test_supervisor_can_review_then_resolve_a_finding_with_audit_timestamps(): void
    {
        $this->travelTo(CarbonImmutable::parse('2026-10-13 10:00:00'));
        $supervisor = User::factory()->create(['role' => UserRole::Supervisor]);
        $petugas = User::factory()->create(['role' => UserRole::Petugas]);
        $context = $this->createInspectionContext($petugas);
        $finding = $this->createFinding($context, $petugas, $this->createDetail($context, 'tidak_sesuai'));

        $this->actingAs($supervisor)->patch('/findings/'.$finding->id.'/status', ['status' => 'review'])
            ->assertSessionHas('success', 'Status temuan berhasil diubah.');
        $finding->refresh();
        $this->assertSame('review', $finding->status);
        $this->assertSame($supervisor->id, $finding->reviewed_by);
        $this->assertSame('2026-10-13 10:00:00', $finding->reviewed_at?->toDateTimeString());
        $this->assertNull($finding->resolved_at);

        $this->travelTo(CarbonImmutable::parse('2026-10-13 11:30:00'));
        $this->actingAs($supervisor)->patch('/findings/'.$finding->id.'/status', ['status' => 'resolved'])->assertSessionHas('success');
        $finding->refresh();
        $this->assertSame('resolved', $finding->status);
        $this->assertSame('2026-10-13 10:00:00', $finding->reviewed_at?->toDateTimeString());
        $this->assertSame('2026-10-13 11:30:00', $finding->resolved_at?->toDateTimeString());
    }

    public function test_supervisor_cannot_move_resolved_finding_back_to_review(): void
    {
        $supervisor = User::factory()->create(['role' => UserRole::Supervisor]);
        $petugas = User::factory()->create(['role' => UserRole::Petugas]);
        $context = $this->createInspectionContext($petugas);
        $finding = $this->createFinding($context, $petugas, $this->createDetail($context, 'tidak_sesuai'), [
            'status' => 'resolved', 'reviewed_by' => $supervisor->id, 'reviewed_at' => now(), 'resolved_at' => now(),
        ]);

        $this->actingAs($supervisor)->patch('/findings/'.$finding->id.'/status', ['status' => 'review'])
            ->assertSessionHasErrors('status');
        $this->assertSame('resolved', $finding->fresh()->status);
    }

    public function test_supervisor_cannot_skip_review_when_resolving_a_finding(): void
    {
        $supervisor = User::factory()->create(['role' => UserRole::Supervisor]);
        $petugas = User::factory()->create(['role' => UserRole::Petugas]);
        $context = $this->createInspectionContext($petugas);
        $finding = $this->createFinding($context, $petugas, $this->createDetail($context, 'tidak_sesuai'));

        $this->actingAs($supervisor)
            ->patch('/findings/'.$finding->id.'/status', ['status' => 'resolved'])
            ->assertSessionHasErrors('status');

        $this->assertSame('open', $finding->fresh()->status);
        $this->assertNull($finding->reviewed_at);
        $this->assertNull($finding->resolved_at);
    }

    #[DataProvider('rolesWithoutStatusAccess')]
    public function test_only_supervisor_can_update_finding_status(UserRole $role): void
    {
        $actor = User::factory()->create(['role' => $role]);
        $petugas = $role === UserRole::Petugas ? $actor : User::factory()->create(['role' => UserRole::Petugas]);
        $context = $this->createInspectionContext($petugas);
        $finding = $this->createFinding($context, $petugas, $this->createDetail($context, 'tidak_sesuai'));

        $this->actingAs($actor)->patch('/findings/'.$finding->id.'/status', ['status' => 'review'])->assertForbidden();
        $this->assertSame('open', $finding->fresh()->status);
    }

    public function test_petugas_index_is_scoped_and_admin_filters_database_findings(): void
    {
        $admin = User::factory()->create(['role' => UserRole::Admin]);
        $petugas = User::factory()->create(['role' => UserRole::Petugas]);
        $other = User::factory()->create(['role' => UserRole::Petugas]);
        $ownContext = $this->createInspectionContext($petugas, ['equipment_name' => 'Mesin Cargo Filter']);
        $otherContext = $this->createInspectionContext($other, ['equipment_name' => 'Mesin Petugas Lain']);
        $ownFinding = $this->createFinding($ownContext, $petugas, $this->createDetail($ownContext, 'tidak_sesuai'), [
            'status' => 'review', 'finding_date' => '2026-10-12', 'finding_description' => 'Motor conveyor bergetar',
        ]);
        $this->createFinding($otherContext, $other, $this->createDetail($otherContext, 'tidak_sesuai'));

        $this->actingAs($petugas)->get('/findings')
            ->assertOk()
            ->assertViewHas('findings', fn ($findings): bool => $findings->count() === 1)
            ->assertSeeText('Mesin Cargo Filter')
            ->assertDontSeeText('Mesin Petugas Lain');

        $this->actingAs($admin)
            ->get('/findings?machine_id='.$ownContext['machine']->id.'&status=review&date=2026-10-12&search=conveyor')
            ->assertOk()
            ->assertViewHas('findings', fn ($findings): bool => $findings->count() === 1 && $findings->first()->is($ownFinding));
    }

    #[DataProvider('monitoringRoles')]
    public function test_monitoring_roles_see_all_findings(UserRole $role): void
    {
        $monitor = User::factory()->create(['role' => $role]);
        $first = User::factory()->create(['role' => UserRole::Petugas]);
        $second = User::factory()->create(['role' => UserRole::Petugas]);
        $firstContext = $this->createInspectionContext($first);
        $secondContext = $this->createInspectionContext($second);
        $this->createFinding($firstContext, $first, $this->createDetail($firstContext, 'tidak_sesuai'));
        $this->createFinding($secondContext, $second, $this->createDetail($secondContext, 'tidak_sesuai'));

        $this->actingAs($monitor)->get('/findings')
            ->assertOk()
            ->assertViewHas('findings', fn ($findings): bool => $findings->count() === 2);
    }

    public function test_mobile_findings_page_uses_database_content_without_dummy_records(): void
    {
        $petugas = User::factory()->create(['role' => UserRole::Petugas]);
        $context = $this->createInspectionContext($petugas, ['equipment_name' => 'Mesin Temuan Mobile Database']);
        $this->createFinding($context, $petugas, $this->createDetail($context, 'tidak_sesuai'), [
            'finding_description' => 'Keterangan Mobile Database',
        ]);

        $this->actingAs($petugas)->get('/mobile/findings')
            ->assertOk()
            ->assertSeeText('Mesin Temuan Mobile Database')
            ->assertSeeText('Keterangan Mobile Database')
            ->assertDontSeeText('X-Ray Cabin');
    }

    /**
     * @param  array<string, string|null>  $machineAttributes
     * @return array{machine: Machine, category: MaintenanceCategory, item: MaintenanceItem, schedule: MaintenanceSchedule, check: MaintenanceCheck}
     */
    private function createInspectionContext(User $petugas, array $machineAttributes = []): array
    {
        static $number = 0;
        $number++;
        $machine = Machine::query()->create([
            'equipment_name' => 'Mesin Temuan '.$number, 'brand' => 'Smiths Detection', 'type' => 'HI-SCAN',
            'serial_number' => 'FINDING-'.$number, 'location' => 'Terminal '.$number, 'airport' => 'UPBU Malikussaleh',
            'status' => 'active', 'description' => null, ...$machineAttributes,
        ]);
        $category = MaintenanceCategory::query()->create([
            'name' => 'Kategori Temuan '.$number, 'frequency' => 'daily', 'description' => null, 'status' => 'active',
        ]);
        $item = MaintenanceItem::query()->create([
            'maintenance_category_id' => $category->id, 'item_name' => 'Item Temuan '.$number,
            'criteria' => 'Kriteria item temuan '.$number, 'input_type' => 'sesuai_tidak_sesuai',
            'sort_order' => 1, 'status' => 'active',
        ]);
        $schedule = MaintenanceSchedule::query()->create([
            'machine_id' => $machine->id, 'maintenance_category_id' => $category->id,
            'scheduled_date' => '2026-10-13', 'scheduled_time' => '08:00', 'assigned_user_id' => $petugas->id,
            'status' => 'in_progress', 'notes' => null,
        ]);
        $check = MaintenanceCheck::query()->create([
            'maintenance_schedule_id' => $schedule->id, 'machine_id' => $machine->id, 'checked_by' => $petugas->id,
            'check_date' => '2026-10-13', 'start_time' => '08:00', 'end_time' => null,
            'overall_status' => 'incomplete', 'verification_status' => 'waiting', 'general_note' => null,
        ]);

        return compact('machine', 'category', 'item', 'schedule', 'check');
    }

    /** @param array{item: MaintenanceItem, check: MaintenanceCheck} $context */
    private function createDetail(array $context, string $resultStatus): MaintenanceCheckDetail
    {
        return MaintenanceCheckDetail::query()->create([
            'maintenance_check_id' => $context['check']->id, 'maintenance_item_id' => $context['item']->id,
            'result_status' => $resultStatus, 'actual_value' => null, 'note' => null, 'photo' => null,
        ]);
    }

    /**
     * @param  array{machine: Machine, item: MaintenanceItem, check: MaintenanceCheck}  $context
     * @param  array<string, mixed>  $attributes
     */
    private function createFinding(array $context, User $petugas, MaintenanceCheckDetail $detail, array $attributes = []): MaintenanceFinding
    {
        return MaintenanceFinding::query()->create([
            'maintenance_check_id' => $context['check']->id, 'maintenance_check_detail_id' => $detail->id,
            'machine_id' => $context['machine']->id, 'maintenance_item_id' => $context['item']->id,
            'reported_by' => $petugas->id, 'finding_date' => '2026-10-13',
            'finding_description' => 'Keterangan temuan pengujian.', 'follow_up' => 'Tindak lanjut pengujian.',
            'photo' => null, 'status' => 'open', 'reviewed_by' => null, 'reviewed_at' => null, 'resolved_at' => null,
            ...$attributes,
        ]);
    }

    /** @return array<string, mixed> */
    private function inspectionItemPayload(
        MaintenanceItem $item,
        string $resultStatus,
        ?string $description = null,
        ?string $followUp = null,
        ?UploadedFile $findingPhoto = null,
    ): array {
        return array_filter([
            'maintenance_item_id' => $item->id, 'result_status' => $resultStatus,
            'finding_description' => $description, 'follow_up' => $followUp, 'finding_photo' => $findingPhoto,
        ], fn (mixed $value): bool => $value !== null);
    }

    /** @param array{item: MaintenanceItem, schedule: MaintenanceSchedule} $context */
    private function submitInspectionFinding(User $petugas, array $context, string $description, ?UploadedFile $photo = null): void
    {
        $this->actingAs($petugas)->post('/inspections/schedules/'.$context['schedule']->id, [
            'maintenance_schedule_id' => $context['schedule']->id, 'submission_type' => 'draft',
            'items' => [$context['item']->id => $this->inspectionItemPayload(
                $context['item'], 'tidak_sesuai', $description, 'Tindak lanjut otomatis.', $photo,
            )],
        ])->assertSessionHas('success');
    }

    /**
     * @param  array{machine: Machine, item: MaintenanceItem, check: MaintenanceCheck}  $context
     * @param  array<string, mixed>  $attributes
     * @return array<string, mixed>
     */
    private function findingPayload(array $context, MaintenanceCheckDetail $detail, array $attributes = []): array
    {
        return [
            'maintenance_check_id' => $context['check']->id, 'maintenance_check_detail_id' => $detail->id,
            'machine_id' => $context['machine']->id, 'maintenance_item_id' => $context['item']->id,
            'finding_description' => 'Keterangan temuan manual.', 'follow_up' => null, 'status' => 'open',
            ...$attributes,
        ];
    }
}
