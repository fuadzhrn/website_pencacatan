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
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

final class ChecklistManagementTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_can_filter_database_checklist_items_on_the_desktop_index(): void
    {
        $admin = User::factory()->create(['role' => UserRole::Admin]);
        $dailyCategory = $this->createCategory(['name' => 'Harian Operasional', 'frequency' => 'daily']);
        $matchingItem = $this->createItem($dailyCategory, [
            'item_name' => 'Periksa conveyor utama',
            'criteria' => 'Conveyor bergerak stabil',
            'input_type' => 'sesuai_tidak_sesuai',
            'status' => 'active',
        ]);
        $monthlyCategory = $this->createCategory(['name' => 'Bulanan Operasional', 'frequency' => 'monthly']);
        $this->createItem($monthlyCategory, [
            'item_name' => 'Pemeriksaan UPS',
            'input_type' => 'nilai_aktual',
            'status' => 'inactive',
        ]);

        $response = $this->actingAs($admin)->get('/checklists?search=conveyor&category='.$dailyCategory->id.'&frequency=daily&input_type=sesuai_tidak_sesuai&status=active');

        $response
            ->assertOk()
            ->assertViewIs('checklists.index')
            ->assertViewHas('items', fn ($items): bool => $items->count() === 1 && $items->first()->is($matchingItem))
            ->assertSeeText('Periksa conveyor utama')
            ->assertDontSeeText('Pemeriksaan UPS')
            ->assertSee('name="search"', false)
            ->assertSee('value="conveyor"', false);
    }

    public function test_admin_receives_database_categories_and_items_on_the_mobile_index(): void
    {
        $admin = User::factory()->create(['role' => UserRole::Admin]);
        $category = $this->createCategory(['name' => 'Mingguan Database', 'frequency' => 'weekly']);
        $item = $this->createItem($category, ['item_name' => 'Monitor database mobile']);

        $response = $this->actingAs($admin)->get('/mobile/checklists');

        $response
            ->assertOk()
            ->assertViewIs('checklists.index')
            ->assertSeeText('Mingguan Database')
            ->assertSeeText('Monitor database mobile')
            ->assertSee('action="'.route('maintenance-items.destroy', $item).'"', false)
            ->assertDontSeeText('Lead curtain dalam kondisi baik');
    }

    /** @return array<string, array{UserRole}> */
    public static function nonAdminRoles(): array
    {
        return [
            'petugas' => [UserRole::Petugas],
            'supervisor' => [UserRole::Supervisor],
        ];
    }

    #[DataProvider('nonAdminRoles')]
    public function test_non_admin_cannot_create_a_maintenance_category(UserRole $role): void
    {
        $user = User::factory()->create(['role' => $role]);

        $response = $this->actingAs($user)->post('/maintenance-categories', $this->validCategoryPayload());

        $response->assertForbidden();
        $this->assertDatabaseCount('maintenance_categories', 0);
    }

    #[DataProvider('nonAdminRoles')]
    public function test_non_admin_cannot_create_a_maintenance_item(UserRole $role): void
    {
        $user = User::factory()->create(['role' => $role]);
        $category = $this->createCategory();

        $response = $this->actingAs($user)->post('/maintenance-items', $this->validItemPayload($category));

        $response->assertForbidden();
        $this->assertDatabaseCount('maintenance_items', 0);
    }

    public function test_admin_can_create_a_maintenance_category(): void
    {
        $admin = User::factory()->create(['role' => UserRole::Admin]);

        $response = $this->actingAs($admin)->post('/maintenance-categories', $this->validCategoryPayload());

        $response
            ->assertRedirect('/checklists')
            ->assertSessionHas('success', 'Kategori berhasil ditambahkan.');
        $this->assertDatabaseHas('maintenance_categories', [
            'name' => 'Harian Safety',
            'frequency' => 'daily',
            'description' => 'Pemeriksaan keselamatan harian.',
            'status' => 'active',
        ]);
    }

    public function test_mobile_category_store_returns_to_the_mobile_checklist_index(): void
    {
        $admin = User::factory()->create(['role' => UserRole::Admin]);

        $response = $this->actingAs($admin)->post('/maintenance-categories', [
            ...$this->validCategoryPayload(),
            'context' => 'mobile',
        ]);

        $response->assertRedirect('/mobile/checklists');
    }

    public function test_category_store_rejects_required_fields_invalid_values_and_duplicate_name(): void
    {
        $admin = User::factory()->create(['role' => UserRole::Admin]);
        $this->createCategory(['name' => 'Harian Safety']);

        $response = $this->actingAs($admin)->post('/maintenance-categories', [
            'name' => 'Harian Safety',
            'frequency' => 'sometimes',
            'description' => null,
            'status' => 'archived',
        ]);

        $response->assertSessionHasErrors([
            'name' => 'Nama kategori sudah digunakan.',
            'frequency' => 'Frekuensi yang dipilih tidak valid.',
            'status' => 'Status yang dipilih tidak valid.',
        ]);
        $this->assertDatabaseCount('maintenance_categories', 1);
    }

    public function test_admin_can_update_a_category_without_changing_its_unique_name(): void
    {
        $admin = User::factory()->create(['role' => UserRole::Admin]);
        $category = $this->createCategory(['name' => 'Harian Tetap']);

        $response = $this->actingAs($admin)->put('/maintenance-categories/'.$category->id, [
            ...$this->validCategoryPayload(),
            'name' => 'Harian Tetap',
            'frequency' => 'weekly',
            'status' => 'inactive',
        ]);

        $response
            ->assertRedirect('/checklists')
            ->assertSessionHas('success', 'Kategori berhasil diperbarui.');
        $category->refresh();
        $this->assertSame('weekly', $category->frequency);
        $this->assertSame('inactive', $category->status);
    }

    public function test_admin_can_toggle_category_status(): void
    {
        $admin = User::factory()->create(['role' => UserRole::Admin]);
        $category = $this->createCategory(['status' => 'active']);

        $response = $this->actingAs($admin)->patch('/maintenance-categories/'.$category->id.'/toggle-status');

        $response
            ->assertRedirect('/checklists')
            ->assertSessionHas('success', 'Status kategori berhasil diubah.');
        $this->assertSame('inactive', $category->fresh()->status);
    }

    public function test_admin_can_delete_an_unused_category(): void
    {
        $admin = User::factory()->create(['role' => UserRole::Admin]);
        $category = $this->createCategory();

        $response = $this->actingAs($admin)->delete('/maintenance-categories/'.$category->id);

        $response
            ->assertRedirect('/checklists')
            ->assertSessionHas('success', 'Kategori berhasil dihapus.');
        $this->assertModelMissing($category);
    }

    public function test_admin_cannot_delete_a_category_that_has_items(): void
    {
        $admin = User::factory()->create(['role' => UserRole::Admin]);
        $category = $this->createCategory();
        $this->createItem($category);

        $response = $this->actingAs($admin)->delete('/maintenance-categories/'.$category->id);

        $response
            ->assertRedirect('/checklists')
            ->assertSessionHas('error', 'Kategori tidak dapat dihapus karena memiliki item atau jadwal maintenance.');
        $this->assertModelExists($category);
    }

    public function test_admin_cannot_delete_a_category_that_has_a_schedule(): void
    {
        $admin = User::factory()->create(['role' => UserRole::Admin]);
        $category = $this->createCategory();
        $machine = $this->createMachine();
        MaintenanceSchedule::query()->create([
            'machine_id' => $machine->id,
            'maintenance_category_id' => $category->id,
            'scheduled_date' => '2026-10-12',
            'status' => 'scheduled',
        ]);

        $response = $this->actingAs($admin)->delete('/maintenance-categories/'.$category->id);

        $response->assertSessionHas('error', 'Kategori tidak dapat dihapus karena memiliki item atau jadwal maintenance.');
        $this->assertModelExists($category);
    }

    public function test_category_show_redirects_to_its_database_detail(): void
    {
        $admin = User::factory()->create(['role' => UserRole::Admin]);
        $category = $this->createCategory();

        $response = $this->actingAs($admin)->get('/maintenance-categories/'.$category->id);

        $response->assertRedirect(route('checklists.index', ['category_detail' => $category->id]));
    }

    public function test_admin_can_create_a_maintenance_item(): void
    {
        $admin = User::factory()->create(['role' => UserRole::Admin]);
        $category = $this->createCategory();

        $response = $this->actingAs($admin)->post('/maintenance-items', $this->validItemPayload($category));

        $response
            ->assertRedirect('/checklists')
            ->assertSessionHas('success', 'Item checklist berhasil ditambahkan.');
        $this->assertDatabaseHas('maintenance_items', [
            'maintenance_category_id' => $category->id,
            'item_name' => 'Pemeriksaan lead curtain',
            'input_type' => 'sesuai_tidak_sesuai',
            'sort_order' => 10,
            'status' => 'active',
        ]);
    }

    public function test_mobile_item_store_returns_to_the_mobile_checklist_index(): void
    {
        $admin = User::factory()->create(['role' => UserRole::Admin]);
        $category = $this->createCategory();

        $response = $this->actingAs($admin)->post('/maintenance-items', [
            ...$this->validItemPayload($category),
            'context' => 'mobile',
        ]);

        $response->assertRedirect('/mobile/checklists');
    }

    public function test_item_store_rejects_invalid_relationship_type_order_and_status(): void
    {
        $admin = User::factory()->create(['role' => UserRole::Admin]);

        $response = $this->actingAs($admin)->post('/maintenance-items', [
            'maintenance_category_id' => 99999,
            'item_name' => '',
            'criteria' => '',
            'input_type' => 'gambar',
            'sort_order' => -1,
            'status' => 'archived',
        ]);

        $response->assertSessionHasErrors([
            'maintenance_category_id',
            'item_name',
            'criteria',
            'input_type' => 'Tipe input yang dipilih tidak valid.',
            'sort_order',
            'status' => 'Status yang dipilih tidak valid.',
        ]);
        $this->assertDatabaseCount('maintenance_items', 0);
    }

    public function test_item_store_rejects_a_duplicate_name_in_the_same_category(): void
    {
        $admin = User::factory()->create(['role' => UserRole::Admin]);
        $category = $this->createCategory();
        $this->createItem($category, ['item_name' => 'Pemeriksaan lead curtain']);

        $response = $this->actingAs($admin)->post('/maintenance-items', $this->validItemPayload($category));

        $response->assertSessionHasErrors([
            'item_name' => 'Nama item sudah digunakan pada kategori tersebut.',
        ]);
        $this->assertDatabaseCount('maintenance_items', 1);
    }

    public function test_admin_can_update_an_item_and_keep_its_unique_name(): void
    {
        $admin = User::factory()->create(['role' => UserRole::Admin]);
        $category = $this->createCategory();
        $item = $this->createItem($category, ['item_name' => 'Nama Item Tetap']);

        $response = $this->actingAs($admin)->put('/maintenance-items/'.$item->id, [
            ...$this->validItemPayload($category),
            'item_name' => 'Nama Item Tetap',
            'input_type' => 'nilai_aktual',
            'sort_order' => null,
            'status' => 'inactive',
        ]);

        $response
            ->assertRedirect('/checklists')
            ->assertSessionHas('success', 'Item checklist berhasil diperbarui.');
        $item->refresh();
        $this->assertSame('nilai_aktual', $item->input_type);
        $this->assertNull($item->sort_order);
        $this->assertSame('inactive', $item->status);
    }

    public function test_item_update_rejects_a_duplicate_name_in_the_target_category(): void
    {
        $admin = User::factory()->create(['role' => UserRole::Admin]);
        $sourceCategory = $this->createCategory(['name' => 'Sumber']);
        $targetCategory = $this->createCategory(['name' => 'Tujuan']);
        $item = $this->createItem($sourceCategory, ['item_name' => 'Item Dipindahkan']);
        $this->createItem($targetCategory, ['item_name' => 'Nama Duplikat']);

        $response = $this->actingAs($admin)->put('/maintenance-items/'.$item->id, [
            ...$this->validItemPayload($targetCategory),
            'item_name' => 'Nama Duplikat',
        ]);

        $response->assertSessionHasErrors([
            'item_name' => 'Nama item sudah digunakan pada kategori tersebut.',
        ]);
        $this->assertSame($sourceCategory->id, $item->fresh()->maintenance_category_id);
    }

    public function test_admin_can_toggle_item_status(): void
    {
        $admin = User::factory()->create(['role' => UserRole::Admin]);
        $category = $this->createCategory();
        $item = $this->createItem($category, ['status' => 'inactive']);

        $response = $this->actingAs($admin)->patch('/maintenance-items/'.$item->id.'/toggle-status');

        $response
            ->assertRedirect('/checklists')
            ->assertSessionHas('success', 'Status item checklist berhasil diubah.');
        $this->assertSame('active', $item->fresh()->status);
    }

    public function test_admin_can_delete_an_unused_item(): void
    {
        $admin = User::factory()->create(['role' => UserRole::Admin]);
        $category = $this->createCategory();
        $item = $this->createItem($category);

        $response = $this->actingAs($admin)->delete('/maintenance-items/'.$item->id);

        $response
            ->assertRedirect('/checklists')
            ->assertSessionHas('success', 'Item checklist berhasil dihapus.');
        $this->assertModelMissing($item);
    }

    public function test_admin_cannot_delete_an_item_used_by_an_inspection(): void
    {
        $admin = User::factory()->create(['role' => UserRole::Admin]);
        $category = $this->createCategory();
        $item = $this->createItem($category);
        $this->createCheckDetail($category, $item);

        $response = $this->actingAs($admin)->delete('/maintenance-items/'.$item->id);

        $response
            ->assertRedirect('/checklists')
            ->assertSessionHas('error', 'Item checklist tidak dapat dihapus karena sudah digunakan pada pemeriksaan atau temuan.');
        $this->assertModelExists($item);
    }

    public function test_item_show_redirects_to_its_database_detail(): void
    {
        $admin = User::factory()->create(['role' => UserRole::Admin]);
        $category = $this->createCategory();
        $item = $this->createItem($category);

        $response = $this->actingAs($admin)->get('/maintenance-items/'.$item->id);

        $response->assertRedirect(route('checklists.index', ['item_detail' => $item->id]));
    }

    public function test_checklist_content_is_escaped_on_the_index(): void
    {
        $admin = User::factory()->create(['role' => UserRole::Admin]);
        $category = $this->createCategory(['name' => '<script>category()</script>']);
        $this->createItem($category, ['item_name' => '<script>item()</script>']);

        $response = $this->actingAs($admin)->get('/checklists');

        $response
            ->assertOk()
            ->assertSee('&lt;script&gt;', false)
            ->assertDontSee('<script>category()</script>', false)
            ->assertDontSee('<script>item()</script>', false);
    }

    /** @param array<string, string|null> $attributes */
    private function createCategory(array $attributes = []): MaintenanceCategory
    {
        static $categoryNumber = 0;
        $categoryNumber++;

        return MaintenanceCategory::query()->create([
            'name' => 'Kategori Test '.$categoryNumber,
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
            'item_name' => 'Item Test '.$itemNumber,
            'criteria' => 'Kriteria pemeriksaan test.',
            'input_type' => 'sesuai_tidak_sesuai',
            'sort_order' => $itemNumber,
            'status' => 'active',
            ...$attributes,
        ]);
    }

    private function createMachine(): Machine
    {
        return Machine::query()->create([
            'equipment_name' => 'Mesin X-Ray Checklist',
            'brand' => 'Smiths Detection',
            'type' => 'HI-SCAN 100100T',
            'serial_number' => 'XR-CHECKLIST-001',
            'location' => 'SCP 1',
            'airport' => 'UPBU Malikussaleh',
            'status' => 'active',
        ]);
    }

    private function createCheckDetail(MaintenanceCategory $category, MaintenanceItem $item): MaintenanceCheckDetail
    {
        $machine = $this->createMachine();
        $petugas = User::factory()->create(['role' => UserRole::Petugas]);
        $schedule = MaintenanceSchedule::query()->create([
            'machine_id' => $machine->id,
            'maintenance_category_id' => $category->id,
            'scheduled_date' => '2026-10-12',
            'status' => 'completed',
        ]);
        $check = MaintenanceCheck::query()->create([
            'maintenance_schedule_id' => $schedule->id,
            'machine_id' => $machine->id,
            'checked_by' => $petugas->id,
            'check_date' => '2026-10-12',
            'overall_status' => 'normal',
            'verification_status' => 'verified',
        ]);

        return MaintenanceCheckDetail::query()->create([
            'maintenance_check_id' => $check->id,
            'maintenance_item_id' => $item->id,
            'result_status' => 'sesuai',
        ]);
    }

    /** @return array<string, string|null> */
    private function validCategoryPayload(): array
    {
        return [
            'name' => 'Harian Safety',
            'frequency' => 'daily',
            'description' => 'Pemeriksaan keselamatan harian.',
            'status' => 'active',
        ];
    }

    /** @return array<string, int|string|null> */
    private function validItemPayload(MaintenanceCategory $category): array
    {
        return [
            'maintenance_category_id' => $category->id,
            'item_name' => 'Pemeriksaan lead curtain',
            'criteria' => 'Tidak robek dan menutup dengan baik.',
            'input_type' => 'sesuai_tidak_sesuai',
            'sort_order' => 10,
            'status' => 'active',
        ];
    }
}
