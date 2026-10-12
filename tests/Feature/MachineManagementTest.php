<?php

namespace Tests\Feature;

use App\Models\Machine;
use App\Models\MaintenanceCategory;
use App\Models\MaintenanceSchedule;
use App\Models\User;
use App\UserRole;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

final class MachineManagementTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_can_filter_database_machines_on_the_desktop_index(): void
    {
        $admin = User::factory()->create(['role' => UserRole::Admin]);
        $matchingMachine = $this->createMachine([
            'equipment_name' => 'X-Ray Baggage Utama',
            'serial_number' => 'XR-FILTER-001',
            'location' => 'SCP 1',
            'airport' => 'UPBU Malikussaleh',
            'status' => 'active',
        ]);
        $this->createMachine([
            'equipment_name' => 'X-Ray Cargo',
            'serial_number' => 'XR-FILTER-002',
            'location' => 'Area Cargo',
            'airport' => 'Bandara Lain',
            'status' => 'maintenance',
        ]);

        $response = $this->actingAs($admin)->get('/machines?search=FILTER-001&status=active&airport=UPBU+Malikussaleh');

        $response
            ->assertOk()
            ->assertViewIs('machines.index')
            ->assertViewHas('machines', fn ($machines): bool => $machines->count() === 1 && $machines->first()->is($matchingMachine))
            ->assertSeeText('X-Ray Baggage Utama')
            ->assertDontSeeText('X-Ray Cargo')
            ->assertSee('name="search"', false)
            ->assertSee('value="FILTER-001"', false);
    }

    public function test_admin_receives_database_machines_and_real_actions_on_the_mobile_index(): void
    {
        $admin = User::factory()->create(['role' => UserRole::Admin]);
        $machine = $this->createMachine([
            'equipment_name' => 'Mesin Database Mobile',
            'serial_number' => 'MOBILE-DB-001',
            'status' => 'maintenance',
        ]);

        $response = $this->actingAs($admin)->get('/mobile/machines');

        $response
            ->assertOk()
            ->assertViewIs('machines.index')
            ->assertSeeText('Mesin Database Mobile')
            ->assertSeeText('MOBILE-DB-001')
            ->assertSee('action="'.route('machines.destroy', $machine).'"', false)
            ->assertSee('href="'.route('machines.edit', $machine).'"', false)
            ->assertDontSeeText('Halaman ini akan dikembangkan');
    }

    public function test_guest_is_redirected_from_machine_creation_to_login(): void
    {
        $response = $this->get('/machines/create');

        $response->assertRedirectToRoute('login');
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
    public function test_non_admin_cannot_create_a_machine(UserRole $role): void
    {
        $user = User::factory()->create(['role' => $role]);

        $response = $this->actingAs($user)->post('/machines', $this->validPayload());

        $response->assertForbidden();
        $this->assertDatabaseMissing('machines', ['serial_number' => 'XR-NEW-001']);
    }

    public function test_admin_can_create_a_machine(): void
    {
        $admin = User::factory()->create(['role' => UserRole::Admin]);

        $response = $this->actingAs($admin)->post('/machines', $this->validPayload());

        $response
            ->assertRedirect('/machines')
            ->assertSessionHas('success', 'Mesin berhasil ditambahkan.');
        $this->assertDatabaseHas('machines', [
            'equipment_name' => 'Mesin X-Ray Baru',
            'serial_number' => 'XR-NEW-001',
            'status' => 'active',
            'description' => 'Mesin untuk pemeriksaan bagasi.',
        ]);
    }

    public function test_mobile_store_returns_to_the_mobile_machine_index(): void
    {
        $admin = User::factory()->create(['role' => UserRole::Admin]);

        $response = $this->actingAs($admin)->post('/machines', [
            ...$this->validPayload(),
            'context' => 'mobile',
        ]);

        $response
            ->assertRedirect('/mobile/machines')
            ->assertSessionHas('success', 'Mesin berhasil ditambahkan.');
    }

    public function test_store_rejects_missing_fields_and_an_invalid_status(): void
    {
        $admin = User::factory()->create(['role' => UserRole::Admin]);

        $response = $this->actingAs($admin)->from('/machines/create')->post('/machines', [
            'equipment_name' => '',
            'brand' => '',
            'type' => '',
            'serial_number' => '',
            'location' => '',
            'airport' => '',
            'status' => 'broken',
            'description' => null,
        ]);

        $response
            ->assertRedirect('/machines/create')
            ->assertSessionHasErrors([
                'equipment_name',
                'brand',
                'type',
                'serial_number',
                'location',
                'airport',
                'status',
            ]);
        $this->assertDatabaseCount('machines', 0);
    }

    public function test_store_rejects_a_duplicate_serial_number(): void
    {
        $admin = User::factory()->create(['role' => UserRole::Admin]);
        $this->createMachine(['serial_number' => 'XR-NEW-001']);

        $response = $this->actingAs($admin)->post('/machines', $this->validPayload());

        $response->assertSessionHasErrors([
            'serial_number' => 'Serial number sudah digunakan oleh mesin lain.',
        ]);
        $this->assertDatabaseCount('machines', 1);
    }

    public function test_admin_can_open_create_show_and_edit_pages(): void
    {
        $admin = User::factory()->create(['role' => UserRole::Admin]);
        $machine = $this->createMachine(['equipment_name' => 'Mesin Detail']);

        $this->actingAs($admin)->get('/machines/create')
            ->assertOk()
            ->assertViewIs('machines.create')
            ->assertSeeText('Tambah Mesin');
        $this->actingAs($admin)->get('/machines/'.$machine->id)
            ->assertOk()
            ->assertViewIs('machines.show')
            ->assertSeeText('Mesin Detail');
        $this->actingAs($admin)->get('/machines/'.$machine->id.'/edit')
            ->assertOk()
            ->assertViewIs('machines.edit')
            ->assertSee('value="Mesin Detail"', false);
    }

    public function test_admin_can_update_a_machine_and_keep_its_serial_number(): void
    {
        $admin = User::factory()->create(['role' => UserRole::Admin]);
        $machine = $this->createMachine(['serial_number' => 'XR-KEEP-001']);

        $response = $this->actingAs($admin)->put('/machines/'.$machine->id, [
            ...$this->validPayload(),
            'equipment_name' => 'Mesin Diperbarui',
            'serial_number' => 'XR-KEEP-001',
            'status' => 'maintenance',
        ]);

        $response
            ->assertRedirect('/machines')
            ->assertSessionHas('success', 'Mesin berhasil diperbarui.');
        $machine->refresh();
        $this->assertSame('Mesin Diperbarui', $machine->equipment_name);
        $this->assertSame('XR-KEEP-001', $machine->serial_number);
        $this->assertSame('maintenance', $machine->status);
    }

    public function test_update_rejects_another_machines_serial_number(): void
    {
        $admin = User::factory()->create(['role' => UserRole::Admin]);
        $machine = $this->createMachine(['serial_number' => 'XR-EDIT-001']);
        $otherMachine = $this->createMachine(['serial_number' => 'XR-EDIT-002']);

        $response = $this->actingAs($admin)->put('/machines/'.$machine->id, [
            ...$this->validPayload(),
            'serial_number' => $otherMachine->serial_number,
        ]);

        $response->assertSessionHasErrors([
            'serial_number' => 'Serial number sudah digunakan oleh mesin lain.',
        ]);
        $this->assertSame('XR-EDIT-001', $machine->fresh()->serial_number);
    }

    public function test_admin_can_delete_a_machine_without_maintenance_history(): void
    {
        $admin = User::factory()->create(['role' => UserRole::Admin]);
        $machine = $this->createMachine();

        $response = $this->actingAs($admin)->delete('/machines/'.$machine->id);

        $response
            ->assertRedirect('/machines')
            ->assertSessionHas('success', 'Mesin berhasil dihapus.');
        $this->assertModelMissing($machine);
    }

    public function test_admin_cannot_delete_a_machine_with_a_maintenance_schedule(): void
    {
        $admin = User::factory()->create(['role' => UserRole::Admin]);
        $machine = $this->createMachine();
        $category = MaintenanceCategory::query()->create([
            'name' => 'Harian',
            'frequency' => 'daily',
            'status' => 'active',
        ]);
        MaintenanceSchedule::query()->create([
            'machine_id' => $machine->id,
            'maintenance_category_id' => $category->id,
            'scheduled_date' => '2026-10-12',
            'status' => 'scheduled',
        ]);

        $response = $this->actingAs($admin)->delete('/machines/'.$machine->id);

        $response
            ->assertRedirect('/machines')
            ->assertSessionHas('error', 'Mesin tidak dapat dihapus karena memiliki jadwal atau riwayat maintenance.');
        $this->assertModelExists($machine);
    }

    /** @return array<string, array{string, string}> */
    public static function toggleStatuses(): array
    {
        return [
            'active becomes inactive' => ['active', 'inactive'],
            'inactive becomes active' => ['inactive', 'active'],
            'maintenance becomes active' => ['maintenance', 'active'],
        ];
    }

    #[DataProvider('toggleStatuses')]
    public function test_admin_can_toggle_machine_status(string $currentStatus, string $expectedStatus): void
    {
        $admin = User::factory()->create(['role' => UserRole::Admin]);
        $machine = $this->createMachine(['status' => $currentStatus]);

        $response = $this->actingAs($admin)->patch('/machines/'.$machine->id.'/toggle-status');

        $response
            ->assertRedirect('/machines')
            ->assertSessionHas('success', 'Status mesin berhasil diubah.');
        $this->assertSame($expectedStatus, $machine->fresh()->status);
    }

    public function test_machine_content_is_escaped_on_the_index(): void
    {
        $admin = User::factory()->create(['role' => UserRole::Admin]);
        $this->createMachine(['equipment_name' => '<script>alert("xss")</script>']);

        $response = $this->actingAs($admin)->get('/machines');

        $response
            ->assertOk()
            ->assertSee('&lt;script&gt;', false)
            ->assertDontSee('<script>alert("xss")</script>', false);
    }

    /** @param array<string, string|null> $attributes */
    private function createMachine(array $attributes = []): Machine
    {
        static $machineNumber = 0;
        $machineNumber++;

        return Machine::query()->create([
            'equipment_name' => 'Mesin X-Ray '.$machineNumber,
            'brand' => 'Smiths Detection',
            'type' => 'HI-SCAN 100100T',
            'serial_number' => 'XR-TEST-'.str_pad((string) $machineNumber, 3, '0', STR_PAD_LEFT),
            'location' => 'SCP '.$machineNumber,
            'airport' => 'UPBU Malikussaleh',
            'status' => 'active',
            'description' => null,
            ...$attributes,
        ]);
    }

    /** @return array<string, string|null> */
    private function validPayload(): array
    {
        return [
            'equipment_name' => 'Mesin X-Ray Baru',
            'brand' => 'Smiths Detection',
            'type' => 'HI-SCAN 7555si',
            'serial_number' => 'XR-NEW-001',
            'location' => 'SCP Utama',
            'airport' => 'UPBU Malikussaleh',
            'status' => 'active',
            'description' => 'Mesin untuk pemeriksaan bagasi.',
        ];
    }
}
