<?php

namespace Tests\Feature;

use App\Models\Machine;
use App\Models\MaintenanceCategory;
use App\Models\MaintenanceCheck;
use App\Models\MaintenanceCheckDetail;
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

final class InspectionManagementTest extends TestCase
{
    use RefreshDatabase;

    /** @return array<string, array{UserRole}> */
    public static function monitoringRoles(): array
    {
        return [
            'admin' => [UserRole::Admin],
            'supervisor' => [UserRole::Supervisor],
        ];
    }

    /** @return array<string, array{string}> */
    public static function lockedScheduleStatuses(): array
    {
        return [
            'completed' => ['completed'],
            'cancelled' => ['cancelled'],
        ];
    }

    public function test_petugas_only_sees_own_supported_schedules_for_today(): void
    {
        $this->travelTo(CarbonImmutable::parse('2026-10-13 08:00:00'));
        $petugas = User::factory()->create(['role' => UserRole::Petugas]);
        $otherPetugas = User::factory()->create(['role' => UserRole::Petugas]);
        $category = $this->createCategory();
        $ownMachine = $this->createMachine(['equipment_name' => 'Mesin Jadwal Milik Saya']);
        $otherMachine = $this->createMachine(['equipment_name' => 'Mesin Jadwal Petugas Lain']);
        $completedMachine = $this->createMachine(['equipment_name' => 'Mesin Sudah Selesai']);
        $tomorrowMachine = $this->createMachine(['equipment_name' => 'Mesin Besok']);
        $ownSchedule = $this->createSchedule($ownMachine, $category, $petugas);
        $this->createSchedule($otherMachine, $category, $otherPetugas, ['scheduled_time' => '09:00']);
        $this->createSchedule($completedMachine, $category, $petugas, ['scheduled_time' => '10:00', 'status' => 'completed']);
        $this->createSchedule($tomorrowMachine, $category, $petugas, ['scheduled_date' => '2026-10-14']);

        $response = $this->actingAs($petugas)->get('/inspections/today');

        $response
            ->assertOk()
            ->assertViewIs('inspections.today')
            ->assertViewHas('schedules', fn ($schedules): bool => $schedules->count() === 1 && $schedules->first()->is($ownSchedule))
            ->assertSeeText('Mesin Jadwal Milik Saya')
            ->assertDontSeeText('Mesin Jadwal Petugas Lain')
            ->assertDontSeeText('Mesin Sudah Selesai')
            ->assertDontSeeText('Mesin Besok');
    }

    #[DataProvider('monitoringRoles')]
    public function test_monitoring_roles_see_all_today_schedules_as_read_only(UserRole $role): void
    {
        $this->travelTo(CarbonImmutable::parse('2026-10-13 08:00:00'));
        $monitor = User::factory()->create(['role' => $role]);
        $firstPetugas = User::factory()->create(['role' => UserRole::Petugas]);
        $secondPetugas = User::factory()->create(['role' => UserRole::Petugas]);
        $category = $this->createCategory();
        $this->createSchedule($this->createMachine(), $category, $firstPetugas);
        $this->createSchedule($this->createMachine(), $category, $secondPetugas, ['scheduled_time' => '09:00']);

        $response = $this->actingAs($monitor)->get('/inspections/today');

        $response
            ->assertOk()
            ->assertViewHas('schedules', fn ($schedules): bool => $schedules->count() === 2)
            ->assertViewHas('canEditInspection', false);
    }

    public function test_schedule_detail_contains_only_active_items_from_its_category(): void
    {
        $this->travelTo(CarbonImmutable::parse('2026-10-13 08:00:00'));
        $petugas = User::factory()->create(['role' => UserRole::Petugas]);
        $category = $this->createCategory(['name' => 'Kategori Pemeriksaan Aktif']);
        $otherCategory = $this->createCategory(['name' => 'Kategori Lain']);
        $activeItem = $this->createItem($category, ['item_name' => 'Item Checklist Aktif']);
        $this->createItem($category, ['item_name' => 'Item Checklist Nonaktif', 'status' => 'inactive']);
        $this->createItem($otherCategory, ['item_name' => 'Item Kategori Lain']);
        $schedule = $this->createSchedule($this->createMachine(), $category, $petugas);

        $response = $this->actingAs($petugas)->followingRedirects()->get('/inspections/schedules/'.$schedule->id);

        $response
            ->assertOk()
            ->assertViewHas('selectedSchedule', fn ($selectedSchedule): bool => $selectedSchedule->is($schedule))
            ->assertViewHas('checklistItems', fn ($items): bool => $items->count() === 1 && $items->first()->is($activeItem))
            ->assertSeeText('Kategori Pemeriksaan Aktif')
            ->assertSeeText('Item Checklist Aktif')
            ->assertDontSeeText('Item Checklist Nonaktif')
            ->assertDontSeeText('Item Kategori Lain');
    }

    public function test_petugas_cannot_open_another_officers_schedule(): void
    {
        $this->travelTo(CarbonImmutable::parse('2026-10-13 08:00:00'));
        $petugas = User::factory()->create(['role' => UserRole::Petugas]);
        $otherPetugas = User::factory()->create(['role' => UserRole::Petugas]);
        $schedule = $this->createSchedule($this->createMachine(), $this->createCategory(), $otherPetugas);

        $this->actingAs($petugas)
            ->get('/inspections/schedules/'.$schedule->id)
            ->assertNotFound();
    }

    public function test_start_creates_one_check_and_does_not_reset_its_start_time(): void
    {
        $this->travelTo(CarbonImmutable::parse('2026-10-13 08:15:00'));
        $petugas = User::factory()->create(['role' => UserRole::Petugas]);
        $schedule = $this->createSchedule($this->createMachine(), $this->createCategory(), $petugas);

        $this->actingAs($petugas)
            ->post('/inspections/schedules/'.$schedule->id.'/start')
            ->assertRedirect('/inspections/today?schedule='.$schedule->id)
            ->assertSessionHas('success', 'Pemeriksaan dimulai.');

        $this->assertDatabaseHas('maintenance_checks', [
            'maintenance_schedule_id' => $schedule->id,
            'machine_id' => $schedule->machine_id,
            'checked_by' => $petugas->id,
            'check_date' => '2026-10-13 00:00:00',
            'start_time' => '08:15:00',
            'overall_status' => 'incomplete',
            'verification_status' => 'waiting',
        ]);
        $this->assertSame('in_progress', $schedule->fresh()->status);

        $this->travelTo(CarbonImmutable::parse('2026-10-13 08:45:00'));
        $this->actingAs($petugas)->post('/inspections/schedules/'.$schedule->id.'/start')->assertRedirect();

        $this->assertDatabaseCount('maintenance_checks', 1);
        $this->assertSame('08:15:00', MaintenanceCheck::query()->firstOrFail()->start_time);
    }

    public function test_petugas_cannot_start_another_officers_schedule(): void
    {
        $this->travelTo(CarbonImmutable::parse('2026-10-13 08:00:00'));
        $petugas = User::factory()->create(['role' => UserRole::Petugas]);
        $otherPetugas = User::factory()->create(['role' => UserRole::Petugas]);
        $schedule = $this->createSchedule($this->createMachine(), $this->createCategory(), $otherPetugas);

        $this->actingAs($petugas)
            ->post('/inspections/schedules/'.$schedule->id.'/start')
            ->assertNotFound();
        $this->assertDatabaseCount('maintenance_checks', 0);
        $this->assertSame('scheduled', $schedule->fresh()->status);
    }

    public function test_draft_stores_details_actual_value_note_and_photo_as_incomplete(): void
    {
        Storage::fake('public');
        $this->travelTo(CarbonImmutable::parse('2026-10-13 09:00:00'));
        $petugas = User::factory()->create(['role' => UserRole::Petugas]);
        $category = $this->createCategory();
        $conditionItem = $this->createItem($category);
        $measurementItem = $this->createItem($category, [
            'item_name' => 'Suhu Ruang Mesin',
            'input_type' => 'nilai_aktual',
            'sort_order' => 2,
        ]);
        $schedule = $this->createSchedule($this->createMachine(), $category, $petugas, ['status' => 'in_progress']);

        $response = $this->actingAs($petugas)->post('/inspections/schedules/'.$schedule->id, [
            'maintenance_schedule_id' => $schedule->id,
            'submission_type' => 'draft',
            'general_note' => 'Draft pemeriksaan pagi.',
            'items' => [
                $conditionItem->id => $this->itemPayload($conditionItem, 'sesuai', note: 'Kondisi normal.'),
                $measurementItem->id => $this->itemPayload(
                    $measurementItem,
                    'belum_diperiksa',
                    actualValue: '27.5',
                    photo: UploadedFile::fake()->image('suhu.jpg'),
                ),
            ],
        ]);

        $response
            ->assertRedirect('/inspections/today?schedule='.$schedule->id)
            ->assertSessionHas('success', 'Draft pemeriksaan berhasil disimpan.');
        $check = MaintenanceCheck::query()->firstOrFail();
        $this->assertSame('incomplete', $check->overall_status);
        $this->assertNull($check->end_time);
        $this->assertSame('in_progress', $schedule->fresh()->status);
        $this->assertDatabaseHas('maintenance_check_details', [
            'maintenance_check_id' => $check->id,
            'maintenance_item_id' => $conditionItem->id,
            'result_status' => 'sesuai',
            'note' => 'Kondisi normal.',
        ]);
        $measurementDetail = MaintenanceCheckDetail::query()->where('maintenance_item_id', $measurementItem->id)->firstOrFail();
        $this->assertSame('27.5', $measurementDetail->actual_value);
        $this->assertNotNull($measurementDetail->photo);
        Storage::disk('public')->assertExists($measurementDetail->photo);
    }

    public function test_final_submission_marks_a_complete_normal_inspection_and_schedule_completed(): void
    {
        $this->travelTo(CarbonImmutable::parse('2026-10-13 10:30:00'));
        $petugas = User::factory()->create(['role' => UserRole::Petugas]);
        $category = $this->createCategory();
        $firstItem = $this->createItem($category);
        $secondItem = $this->createItem($category, ['item_name' => 'Item Tidak Dijadwalkan', 'sort_order' => 2]);
        $schedule = $this->createSchedule($this->createMachine(), $category, $petugas, ['status' => 'in_progress']);

        $response = $this->actingAs($petugas)->post('/inspections/schedules/'.$schedule->id, [
            'maintenance_schedule_id' => $schedule->id,
            'submission_type' => 'final',
            'general_note' => 'Semua kondisi normal.',
            'items' => [
                $firstItem->id => $this->itemPayload($firstItem, 'sesuai'),
                $secondItem->id => $this->itemPayload($secondItem, 'tidak_dijadwalkan'),
            ],
        ]);

        $response
            ->assertRedirect('/inspections/today?schedule='.$schedule->id)
            ->assertSessionHas('success', 'Pemeriksaan berhasil disimpan.');
        $this->assertDatabaseHas('maintenance_checks', [
            'maintenance_schedule_id' => $schedule->id,
            'checked_by' => $petugas->id,
            'overall_status' => 'normal',
            'verification_status' => 'waiting',
            'end_time' => '10:30:00',
            'general_note' => 'Semua kondisi normal.',
        ]);
        $this->assertSame('completed', $schedule->fresh()->status);
    }

    public function test_final_submission_sets_has_finding_when_an_item_is_not_conform(): void
    {
        $this->travelTo(CarbonImmutable::parse('2026-10-13 11:00:00'));
        $petugas = User::factory()->create(['role' => UserRole::Petugas]);
        $category = $this->createCategory();
        $normalItem = $this->createItem($category);
        $findingItem = $this->createItem($category, ['item_name' => 'Conveyor Tidak Stabil', 'sort_order' => 2]);
        $schedule = $this->createSchedule($this->createMachine(), $category, $petugas, ['status' => 'in_progress']);

        $this->actingAs($petugas)->post('/inspections/schedules/'.$schedule->id, [
            'maintenance_schedule_id' => $schedule->id,
            'submission_type' => 'final',
            'items' => [
                $normalItem->id => $this->itemPayload($normalItem, 'sesuai'),
                $findingItem->id => $this->itemPayload(
                    $findingItem,
                    'tidak_sesuai',
                    note: 'Motor conveyor perlu diperiksa.',
                    findingDescription: 'Conveyor tidak stabil saat pengujian.',
                ),
            ],
        ])->assertSessionHas('success');

        $this->assertSame('has_finding', MaintenanceCheck::query()->firstOrFail()->overall_status);
        $this->assertSame('completed', $schedule->fresh()->status);
    }

    public function test_final_submission_rejects_an_unexamined_item_without_writing_a_check(): void
    {
        $this->travelTo(CarbonImmutable::parse('2026-10-13 11:00:00'));
        $petugas = User::factory()->create(['role' => UserRole::Petugas]);
        $category = $this->createCategory();
        $item = $this->createItem($category);
        $schedule = $this->createSchedule($this->createMachine(), $category, $petugas, ['status' => 'in_progress']);

        $response = $this->actingAs($petugas)->post('/inspections/schedules/'.$schedule->id, [
            'maintenance_schedule_id' => $schedule->id,
            'submission_type' => 'final',
            'items' => [$item->id => $this->itemPayload($item, 'belum_diperiksa')],
        ]);

        $response->assertSessionHasErrors(['items' => 'Masih ada checklist yang belum diisi.']);
        $this->assertDatabaseCount('maintenance_checks', 0);
        $this->assertSame('in_progress', $schedule->fresh()->status);
    }

    public function test_submission_rejects_an_item_outside_the_schedule_category(): void
    {
        $this->travelTo(CarbonImmutable::parse('2026-10-13 11:00:00'));
        $petugas = User::factory()->create(['role' => UserRole::Petugas]);
        $category = $this->createCategory();
        $expectedItem = $this->createItem($category);
        $outsideItem = $this->createItem($this->createCategory(), ['item_name' => 'Item Kategori Asing']);
        $schedule = $this->createSchedule($this->createMachine(), $category, $petugas, ['status' => 'in_progress']);

        $response = $this->actingAs($petugas)->post('/inspections/schedules/'.$schedule->id, [
            'maintenance_schedule_id' => $schedule->id,
            'submission_type' => 'draft',
            'items' => [
                $expectedItem->id => $this->itemPayload($expectedItem, 'sesuai'),
                $outsideItem->id => $this->itemPayload($outsideItem, 'sesuai'),
            ],
        ]);

        $response->assertSessionHasErrors(['items' => 'Item checklist tidak sesuai dengan kategori jadwal.']);
        $this->assertDatabaseCount('maintenance_checks', 0);
    }

    public function test_submission_rejects_a_malformed_checklist_payload_without_server_error(): void
    {
        $this->travelTo(CarbonImmutable::parse('2026-10-13 11:00:00'));
        $petugas = User::factory()->create(['role' => UserRole::Petugas]);
        $category = $this->createCategory();
        $this->createItem($category);
        $schedule = $this->createSchedule($this->createMachine(), $category, $petugas, ['status' => 'in_progress']);

        $response = $this->actingAs($petugas)->post('/inspections/schedules/'.$schedule->id, [
            'maintenance_schedule_id' => $schedule->id,
            'submission_type' => 'draft',
            'items' => ['payload-tidak-valid'],
        ]);

        $response->assertSessionHasErrors('items');
        $this->assertDatabaseCount('maintenance_checks', 0);
    }

    #[DataProvider('lockedScheduleStatuses')]
    public function test_petugas_cannot_submit_a_locked_schedule_through_a_direct_url(string $status): void
    {
        $this->travelTo(CarbonImmutable::parse('2026-10-13 11:00:00'));
        $petugas = User::factory()->create(['role' => UserRole::Petugas]);
        $category = $this->createCategory();
        $item = $this->createItem($category);
        $schedule = $this->createSchedule($this->createMachine(), $category, $petugas, ['status' => $status]);

        $this->actingAs($petugas)->post('/inspections/schedules/'.$schedule->id, [
            'maintenance_schedule_id' => $schedule->id,
            'submission_type' => 'final',
            'items' => [$item->id => $this->itemPayload($item, 'sesuai')],
        ])->assertForbidden();

        $this->assertSame($status, $schedule->fresh()->status);
        $this->assertDatabaseCount('maintenance_checks', 0);
    }

    public function test_petugas_cannot_change_a_verified_inspection(): void
    {
        $this->travelTo(CarbonImmutable::parse('2026-10-13 11:00:00'));
        $petugas = User::factory()->create(['role' => UserRole::Petugas]);
        $category = $this->createCategory();
        $item = $this->createItem($category);
        $schedule = $this->createSchedule($this->createMachine(), $category, $petugas, ['status' => 'completed']);
        $check = $this->createCheck($schedule, $petugas, ['verification_status' => 'verified']);

        $response = $this->actingAs($petugas)->post('/inspections/schedules/'.$schedule->id, [
            'maintenance_schedule_id' => $schedule->id,
            'submission_type' => 'final',
            'items' => [$item->id => $this->itemPayload($item, 'sesuai')],
        ]);

        $response->assertForbidden();
        $this->assertSame('verified', $check->fresh()->verification_status);
        $this->assertDatabaseCount('maintenance_check_details', 0);
    }

    #[DataProvider('monitoringRoles')]
    public function test_monitoring_roles_cannot_submit_an_inspection(UserRole $role): void
    {
        $this->travelTo(CarbonImmutable::parse('2026-10-13 11:00:00'));
        $monitor = User::factory()->create(['role' => $role]);
        $petugas = User::factory()->create(['role' => UserRole::Petugas]);
        $category = $this->createCategory();
        $item = $this->createItem($category);
        $schedule = $this->createSchedule($this->createMachine(), $category, $petugas);

        $response = $this->actingAs($monitor)->post('/inspections/schedules/'.$schedule->id, [
            'maintenance_schedule_id' => $schedule->id,
            'submission_type' => 'final',
            'items' => [$item->id => $this->itemPayload($item, 'sesuai')],
        ]);

        $response->assertForbidden();
        $this->assertDatabaseCount('maintenance_checks', 0);
    }

    public function test_petugas_can_view_an_own_past_inspection_as_read_only(): void
    {
        $this->travelTo(CarbonImmutable::parse('2026-10-13 11:00:00'));
        $petugas = User::factory()->create(['role' => UserRole::Petugas]);
        $category = $this->createCategory(['name' => 'Kategori Riwayat']);
        $item = $this->createItem($category, ['item_name' => 'Item Riwayat Pemeriksaan']);
        $machine = $this->createMachine(['equipment_name' => 'Mesin Riwayat Pemeriksaan']);
        $schedule = $this->createSchedule($machine, $category, $petugas, [
            'scheduled_date' => '2026-10-12',
            'status' => 'completed',
        ]);
        $check = $this->createCheck($schedule, $petugas, ['check_date' => '2026-10-12']);
        MaintenanceCheckDetail::query()->create([
            'maintenance_check_id' => $check->id,
            'maintenance_item_id' => $item->id,
            'result_status' => 'sesuai',
            'actual_value' => null,
            'note' => 'Item masih harus tampil dalam riwayat.',
            'photo' => null,
        ]);
        $item->update(['status' => 'inactive']);

        $response = $this->actingAs($petugas)
            ->followingRedirects()
            ->get('/inspections/'.$check->id);

        $response
            ->assertOk()
            ->assertViewHas('selectedSchedule', fn ($selectedSchedule): bool => $selectedSchedule->is($schedule))
            ->assertViewHas('canEditInspection', false)
            ->assertSeeText('Mesin Riwayat Pemeriksaan')
            ->assertSeeText('Item Riwayat Pemeriksaan');
    }

    public function test_uploading_a_replacement_photo_removes_the_previous_file(): void
    {
        Storage::fake('public');
        Storage::disk('public')->put('inspection-photos/foto-lama.jpg', 'foto-lama');
        $this->travelTo(CarbonImmutable::parse('2026-10-13 11:00:00'));
        $petugas = User::factory()->create(['role' => UserRole::Petugas]);
        $category = $this->createCategory();
        $item = $this->createItem($category);
        $schedule = $this->createSchedule($this->createMachine(), $category, $petugas, ['status' => 'in_progress']);
        $check = $this->createCheck($schedule, $petugas, ['end_time' => null, 'overall_status' => 'incomplete']);
        MaintenanceCheckDetail::query()->create([
            'maintenance_check_id' => $check->id,
            'maintenance_item_id' => $item->id,
            'result_status' => 'belum_diperiksa',
            'actual_value' => null,
            'note' => null,
            'photo' => 'inspection-photos/foto-lama.jpg',
        ]);

        $this->actingAs($petugas)->post('/inspections/schedules/'.$schedule->id, [
            'maintenance_schedule_id' => $schedule->id,
            'submission_type' => 'draft',
            'items' => [
                $item->id => $this->itemPayload(
                    $item,
                    'sesuai',
                    photo: UploadedFile::fake()->image('foto-baru.jpg'),
                ),
            ],
        ])->assertSessionHas('success');

        $detail = MaintenanceCheckDetail::query()->where('maintenance_item_id', $item->id)->firstOrFail();
        Storage::disk('public')->assertMissing('inspection-photos/foto-lama.jpg');
        Storage::disk('public')->assertExists($detail->photo);
    }

    public function test_final_status_preserves_a_nonconformity_from_an_item_deactivated_after_a_draft(): void
    {
        $this->travelTo(CarbonImmutable::parse('2026-10-13 11:00:00'));
        $petugas = User::factory()->create(['role' => UserRole::Petugas]);
        $category = $this->createCategory();
        $activeItem = $this->createItem($category);
        $deactivatedItem = $this->createItem($category, ['sort_order' => 2]);
        $schedule = $this->createSchedule($this->createMachine(), $category, $petugas, ['status' => 'in_progress']);
        $check = $this->createCheck($schedule, $petugas, ['end_time' => null, 'overall_status' => 'incomplete']);
        MaintenanceCheckDetail::query()->create([
            'maintenance_check_id' => $check->id,
            'maintenance_item_id' => $deactivatedItem->id,
            'result_status' => 'tidak_sesuai',
            'actual_value' => null,
            'note' => 'Temuan draft tetap menjadi bagian audit.',
            'photo' => null,
        ]);
        $deactivatedItem->update(['status' => 'inactive']);

        $this->actingAs($petugas)->post('/inspections/schedules/'.$schedule->id, [
            'maintenance_schedule_id' => $schedule->id,
            'submission_type' => 'final',
            'items' => [$activeItem->id => $this->itemPayload($activeItem, 'sesuai')],
        ])->assertSessionHas('success');

        $this->assertSame('has_finding', $check->fresh()->overall_status);
        $this->assertDatabaseHas('maintenance_check_details', [
            'maintenance_check_id' => $check->id,
            'maintenance_item_id' => $deactivatedItem->id,
            'result_status' => 'tidak_sesuai',
        ]);
    }

    public function test_mobile_today_page_uses_database_schedules_and_checklist_items(): void
    {
        $this->travelTo(CarbonImmutable::parse('2026-10-13 11:00:00'));
        $petugas = User::factory()->create(['role' => UserRole::Petugas]);
        $category = $this->createCategory(['name' => 'Kategori Mobile Database']);
        $item = $this->createItem($category, ['item_name' => 'Item Mobile Database']);
        $machine = $this->createMachine(['equipment_name' => 'Mesin Mobile Database']);
        $schedule = $this->createSchedule($machine, $category, $petugas);

        $response = $this->actingAs($petugas)->get('/mobile/inspection-today?schedule='.$schedule->id);

        $response
            ->assertOk()
            ->assertViewHas('checklistItems', fn ($items): bool => $items->count() === 1 && $items->first()->is($item))
            ->assertSeeText('Mesin Mobile Database')
            ->assertSeeText('Kategori Mobile Database')
            ->assertSeeText('Item Mobile Database')
            ->assertSee('name="items['.$item->id.'][result_status]"', false)
            ->assertDontSeeText('X-Ray Cabin');
    }

    /** @param array<string, string|null> $attributes */
    private function createMachine(array $attributes = []): Machine
    {
        static $machineNumber = 0;
        $machineNumber++;

        return Machine::query()->create([
            'equipment_name' => 'Mesin Pemeriksaan '.$machineNumber,
            'brand' => 'Smiths Detection',
            'type' => 'HI-SCAN',
            'serial_number' => 'INSPECTION-'.$machineNumber,
            'location' => 'Terminal '.$machineNumber,
            'airport' => 'UPBU Malikussaleh',
            'status' => 'active',
            'description' => null,
            ...$attributes,
        ]);
    }

    /** @param array<string, string|null> $attributes */
    private function createCategory(array $attributes = []): MaintenanceCategory
    {
        static $categoryNumber = 0;
        $categoryNumber++;

        return MaintenanceCategory::query()->create([
            'name' => 'Kategori Pemeriksaan '.$categoryNumber,
            'frequency' => 'daily',
            'description' => null,
            'status' => 'active',
            ...$attributes,
        ]);
    }

    /** @param array<string, int|string|null> $attributes */
    private function createItem(MaintenanceCategory $category, array $attributes = []): MaintenanceItem
    {
        static $itemNumber = 0;
        $itemNumber++;

        return MaintenanceItem::query()->create([
            'maintenance_category_id' => $category->id,
            'item_name' => 'Item Pemeriksaan '.$itemNumber,
            'criteria' => 'Kriteria pemeriksaan item '.$itemNumber.'.',
            'input_type' => 'sesuai_tidak_sesuai',
            'sort_order' => 1,
            'status' => 'active',
            ...$attributes,
        ]);
    }

    /** @param array<string, int|string|null> $attributes */
    private function createSchedule(
        Machine $machine,
        MaintenanceCategory $category,
        User $petugas,
        array $attributes = [],
    ): MaintenanceSchedule {
        return MaintenanceSchedule::query()->create([
            'machine_id' => $machine->id,
            'maintenance_category_id' => $category->id,
            'scheduled_date' => '2026-10-13',
            'scheduled_time' => '08:00',
            'assigned_user_id' => $petugas->id,
            'status' => 'scheduled',
            'notes' => null,
            ...$attributes,
        ]);
    }

    /** @param array<string, int|string|null> $attributes */
    private function createCheck(MaintenanceSchedule $schedule, User $petugas, array $attributes = []): MaintenanceCheck
    {
        return MaintenanceCheck::query()->create([
            'maintenance_schedule_id' => $schedule->id,
            'machine_id' => $schedule->machine_id,
            'checked_by' => $petugas->id,
            'check_date' => '2026-10-13',
            'start_time' => '08:00',
            'end_time' => '09:00',
            'overall_status' => 'normal',
            'verification_status' => 'waiting',
            'general_note' => null,
            ...$attributes,
        ]);
    }

    /** @return array<string, mixed> */
    private function itemPayload(
        MaintenanceItem $item,
        string $resultStatus,
        ?string $actualValue = null,
        ?string $note = null,
        ?UploadedFile $photo = null,
        ?string $findingDescription = null,
    ): array {
        return array_filter([
            'maintenance_item_id' => $item->id,
            'result_status' => $resultStatus,
            'actual_value' => $actualValue,
            'note' => $note,
            'photo' => $photo,
            'finding_description' => $findingDescription,
        ], fn (mixed $value): bool => $value !== null);
    }
}
