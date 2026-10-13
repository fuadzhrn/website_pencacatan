<?php

namespace Tests\Feature;

use App\Models\Machine;
use App\Models\MaintenanceCategory;
use App\Models\MaintenanceCheck;
use App\Models\MaintenanceSchedule;
use App\Models\User;
use App\UserRole;
use Carbon\CarbonImmutable;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

final class MaintenanceScheduleManagementTest extends TestCase
{
    use RefreshDatabase;

    /** @return array<string, array{string, string, list<string>}> */
    public static function generationFrequencies(): array
    {
        return [
            'daily' => ['daily', '2026-10-03', ['2026-10-01', '2026-10-02', '2026-10-03']],
            'weekly' => ['weekly', '2026-10-20', ['2026-10-01', '2026-10-08', '2026-10-15']],
            'monthly without overflow' => ['monthly', '2026-04-30', ['2026-01-31', '2026-02-28', '2026-03-31', '2026-04-30']],
            'quarterly without overflow' => ['quarterly', '2026-10-31', ['2026-01-31', '2026-04-30', '2026-07-31', '2026-10-31']],
            'semester without overflow' => ['semester', '2026-12-31', ['2026-01-31', '2026-07-31']],
            'yearly without overflow' => ['yearly', '2026-03-01', ['2024-02-29', '2025-02-28', '2026-02-28']],
        ];
    }

    #[DataProvider('generationFrequencies')]
    public function test_admin_generates_inclusive_schedules_for_each_frequency(
        string $frequency,
        string $endDate,
        array $expectedDates,
    ): void {
        $admin = User::factory()->create(['role' => UserRole::Admin]);
        $petugas = User::factory()->create(['role' => UserRole::Petugas]);
        $machine = $this->createMachine();
        $category = $this->createCategory(['frequency' => $frequency]);
        $startDate = $frequency === 'yearly' ? '2024-02-29' : ($frequency === 'monthly' || $frequency === 'quarterly' || $frequency === 'semester' ? '2026-01-31' : '2026-10-01');

        $response = $this->actingAs($admin)->post('/schedules/generate', [
            'machine_id' => $machine->id,
            'maintenance_category_id' => $category->id,
            'start_date' => $startDate,
            'end_date' => $endDate,
            'assigned_user_id' => $petugas->id,
            'scheduled_time' => '08:30',
            'notes' => 'Jadwal otomatis.',
        ]);

        $response
            ->assertRedirect('/schedules')
            ->assertSessionHas('success');
        $this->assertSame(
            $expectedDates,
            MaintenanceSchedule::query()->orderBy('scheduled_date')->pluck('scheduled_date')->map->toDateString()->all(),
        );
        $this->assertDatabaseCount('maintenance_schedules', count($expectedDates));
        $this->assertDatabaseHas('maintenance_schedules', [
            'machine_id' => $machine->id,
            'maintenance_category_id' => $category->id,
            'assigned_user_id' => $petugas->id,
            'scheduled_time' => '08:30:00',
            'status' => 'scheduled',
            'notes' => 'Jadwal otomatis.',
        ]);
    }

    public function test_generator_skips_existing_machine_category_and_date_combinations(): void
    {
        $admin = User::factory()->create(['role' => UserRole::Admin]);
        $machine = $this->createMachine();
        $category = $this->createCategory(['frequency' => 'daily']);
        $existingSchedule = $this->createSchedule($machine, $category, ['scheduled_date' => '2026-10-02']);

        $response = $this->actingAs($admin)->post('/schedules/generate', [
            'machine_id' => $machine->id,
            'maintenance_category_id' => $category->id,
            'start_date' => '2026-10-01',
            'end_date' => '2026-10-03',
        ]);

        $response
            ->assertRedirect('/schedules')
            ->assertSessionHas('success', 'Jadwal otomatis berhasil digenerate: 2 dibuat, 1 jadwal duplikat dilewati.');
        $this->assertDatabaseCount('maintenance_schedules', 3);
        $this->assertDatabaseHas('maintenance_schedules', ['id' => $existingSchedule->id]);
    }

    public function test_generate_validation_requires_a_petugas_assignee_and_valid_date_range(): void
    {
        $admin = User::factory()->create(['role' => UserRole::Admin]);
        $supervisor = User::factory()->create(['role' => UserRole::Supervisor]);
        $machine = $this->createMachine();
        $category = $this->createCategory();

        $response = $this->actingAs($admin)->post('/schedules/generate', [
            'machine_id' => $machine->id,
            'maintenance_category_id' => $category->id,
            'start_date' => '2026-10-10',
            'end_date' => '2026-10-09',
            'assigned_user_id' => $supervisor->id,
            'scheduled_time' => '25:99',
        ]);

        $response->assertSessionHasErrors([
            'end_date',
            'assigned_user_id' => 'Petugas yang dipilih tidak valid.',
            'scheduled_time',
        ]);
        $this->assertDatabaseCount('maintenance_schedules', 0);
    }

    #[DataProvider('nonAdminRoles')]
    public function test_non_admin_cannot_generate_schedules(UserRole $role): void
    {
        $user = User::factory()->create(['role' => $role]);
        $machine = $this->createMachine();
        $category = $this->createCategory();

        $response = $this->actingAs($user)->post('/schedules/generate', [
            'machine_id' => $machine->id,
            'maintenance_category_id' => $category->id,
            'start_date' => '2026-10-01',
            'end_date' => '2026-10-02',
        ]);

        $response->assertForbidden();
        $this->assertDatabaseCount('maintenance_schedules', 0);
    }

    /** @return array<string, array{UserRole}> */
    public static function nonAdminRoles(): array
    {
        return [
            'petugas' => [UserRole::Petugas],
            'supervisor' => [UserRole::Supervisor],
        ];
    }

    /** @return array<string, array{string, array<string, string|null>}> */
    public static function scheduleSearchTerms(): array
    {
        return [
            'equipment name' => ['Search Alpha', ['equipment_name' => 'X-Ray Search Alpha']],
            'serial number' => ['SERIAL-SEARCH-31', ['serial_number' => 'SERIAL-SEARCH-31']],
            'location' => ['Lokasi Search 31', ['location' => 'Lokasi Search 31']],
        ];
    }

    public function test_admin_can_create_a_single_schedule_and_duplicate_is_rejected(): void
    {
        $admin = User::factory()->create(['role' => UserRole::Admin]);
        $machine = $this->createMachine();
        $category = $this->createCategory();
        $payload = $this->validSchedulePayload($machine, $category);

        $this->actingAs($admin)
            ->post('/schedules', $payload)
            ->assertRedirect('/schedules')
            ->assertSessionHas('success', 'Jadwal berhasil dibuat.');

        $this->actingAs($admin)
            ->post('/schedules', $payload)
            ->assertSessionHasErrors(['scheduled_date' => 'Jadwal untuk mesin, kategori, dan tanggal tersebut sudah tersedia.']);

        $this->assertDatabaseCount('maintenance_schedules', 1);
    }

    public function test_admin_can_update_a_schedule_without_creating_a_duplicate(): void
    {
        $admin = User::factory()->create(['role' => UserRole::Admin]);
        $machine = $this->createMachine();
        $category = $this->createCategory();
        $schedule = $this->createSchedule($machine, $category, ['scheduled_date' => '2026-10-12']);
        $this->createSchedule($machine, $category, ['scheduled_date' => '2026-10-13']);

        $this->actingAs($admin)
            ->put('/schedules/'.$schedule->id, [
                'scheduled_date' => '2026-10-14',
                'scheduled_time' => '09:15',
                'assigned_user_id' => null,
                'status' => 'pending',
                'notes' => 'Jadwal diperbarui.',
            ])
            ->assertRedirect('/schedules')
            ->assertSessionHas('success', 'Jadwal berhasil diperbarui.');

        $this->actingAs($admin)
            ->put('/schedules/'.$schedule->id, [
                'scheduled_date' => '2026-10-13',
                'status' => 'pending',
            ])
            ->assertSessionHasErrors(['scheduled_date']);

        $schedule->refresh();
        $this->assertSame('2026-10-14', $schedule->scheduled_date->toDateString());
        $this->assertSame('pending', $schedule->status);
    }

    public function test_schedule_update_ignores_machine_and_category_fields(): void
    {
        $admin = User::factory()->create(['role' => UserRole::Admin]);
        $originalMachine = $this->createMachine();
        $otherMachine = $this->createMachine();
        $originalCategory = $this->createCategory();
        $otherCategory = $this->createCategory();
        $schedule = $this->createSchedule($originalMachine, $originalCategory);

        $response = $this->actingAs($admin)->put('/schedules/'.$schedule->id, [
            'machine_id' => $otherMachine->id,
            'maintenance_category_id' => $otherCategory->id,
            'scheduled_date' => '2026-10-14',
            'status' => 'pending',
        ]);

        $response
            ->assertRedirect('/schedules')
            ->assertSessionHas('success', 'Jadwal berhasil diperbarui.');
        $schedule->refresh();
        $this->assertTrue($schedule->machine->is($originalMachine));
        $this->assertTrue($schedule->maintenanceCategory->is($originalCategory));
        $this->assertSame('2026-10-14', $schedule->scheduled_date->toDateString());
    }

    public function test_index_marks_past_scheduled_and_pending_records_overdue(): void
    {
        $this->travelTo(CarbonImmutable::parse('2026-10-12 10:00:00'));
        $admin = User::factory()->create(['role' => UserRole::Admin]);
        $machine = $this->createMachine();
        $category = $this->createCategory();
        $scheduled = $this->createSchedule($machine, $category, ['scheduled_date' => '2026-10-11', 'status' => 'scheduled']);
        $pending = $this->createSchedule($machine, $category, ['scheduled_date' => '2026-10-10', 'status' => 'pending']);
        $completed = $this->createSchedule($machine, $category, ['scheduled_date' => '2026-10-09', 'status' => 'completed']);
        $today = $this->createSchedule($machine, $category, ['scheduled_date' => '2026-10-12', 'status' => 'scheduled']);

        $this->actingAs($admin)->get('/schedules')->assertOk();

        $this->assertSame('overdue', $scheduled->fresh()->status);
        $this->assertSame('overdue', $pending->fresh()->status);
        $this->assertSame('completed', $completed->fresh()->status);
        $this->assertSame('scheduled', $today->fresh()->status);
    }

    public function test_admin_filters_database_schedules_on_every_supported_filter(): void
    {
        $admin = User::factory()->create(['role' => UserRole::Admin]);
        $matchingPetugas = User::factory()->create(['role' => UserRole::Petugas]);
        $otherPetugas = User::factory()->create(['role' => UserRole::Petugas]);
        $matchingMachine = $this->createMachine(['equipment_name' => 'X-Ray Filter Match']);
        $otherMachine = $this->createMachine(['equipment_name' => 'X-Ray Other']);
        $dailyCategory = $this->createCategory(['name' => 'Harian Filter', 'frequency' => 'daily']);
        $monthlyCategory = $this->createCategory(['name' => 'Bulanan Filter', 'frequency' => 'monthly']);
        $matching = $this->createSchedule($matchingMachine, $dailyCategory, [
            'scheduled_date' => '2026-10-12',
            'assigned_user_id' => $matchingPetugas->id,
            'status' => 'completed',
        ]);
        $this->createSchedule($otherMachine, $monthlyCategory, [
            'scheduled_date' => '2025-09-10',
            'assigned_user_id' => $otherPetugas->id,
            'status' => 'cancelled',
        ]);

        $response = $this->actingAs($admin)->get('/schedules?'.http_build_query([
            'month' => 10,
            'year' => 2026,
            'machine_id' => $matchingMachine->id,
            'maintenance_category_id' => $dailyCategory->id,
            'assigned_user_id' => $matchingPetugas->id,
            'frequency' => 'daily',
            'status' => 'completed',
        ]));

        $response
            ->assertOk()
            ->assertViewIs('schedules.index')
            ->assertViewHas('schedules', fn ($schedules): bool => $schedules->count() === 1
                && $schedules->first()->is($matching)
                && $schedules->first()->relationLoaded('maintenanceCategory'))
            ->assertSeeText('X-Ray Filter Match')
            ->assertSee('name="month"', false);
    }

    #[DataProvider('scheduleSearchTerms')]
    public function test_admin_can_search_schedules_by_machine_identity(string $search, array $machineAttributes): void
    {
        $admin = User::factory()->create(['role' => UserRole::Admin]);
        $matchingMachine = $this->createMachine($machineAttributes);
        $otherMachine = $this->createMachine(['equipment_name' => 'Mesin Tidak Cocok']);
        $category = $this->createCategory();
        $matching = $this->createSchedule($matchingMachine, $category);
        $this->createSchedule($otherMachine, $category, ['scheduled_date' => '2026-10-13']);

        $response = $this->actingAs($admin)->get('/schedules?'.http_build_query(['search' => $search]));

        $response
            ->assertOk()
            ->assertViewHas('schedules', fn ($schedules): bool => $schedules->count() === 1 && $schedules->first()->is($matching));
    }

    public function test_petugas_only_sees_assigned_schedules_while_supervisor_sees_all(): void
    {
        $petugas = User::factory()->create(['role' => UserRole::Petugas, 'name' => 'Petugas A']);
        $otherPetugas = User::factory()->create(['role' => UserRole::Petugas, 'name' => 'Petugas B']);
        $supervisor = User::factory()->create(['role' => UserRole::Supervisor]);
        $machineA = $this->createMachine(['equipment_name' => 'Mesin Milik Petugas A']);
        $machineB = $this->createMachine(['equipment_name' => 'Mesin Milik Petugas B']);
        $category = $this->createCategory();
        $this->createSchedule($machineA, $category, ['assigned_user_id' => $petugas->id]);
        $this->createSchedule($machineB, $category, ['assigned_user_id' => $otherPetugas->id, 'scheduled_date' => '2026-10-13']);

        $petugasResponse = $this->actingAs($petugas)->get('/schedules');
        $supervisorResponse = $this->actingAs($supervisor)->get('/schedules');

        $petugasResponse->assertOk()->assertSeeText('Mesin Milik Petugas A')->assertDontSeeText('Mesin Milik Petugas B');
        $supervisorResponse->assertOk()->assertSeeText('Mesin Milik Petugas A')->assertSeeText('Mesin Milik Petugas B');
    }

    public function test_petugas_cannot_view_another_officers_schedule_detail(): void
    {
        $petugas = User::factory()->create(['role' => UserRole::Petugas]);
        $otherPetugas = User::factory()->create(['role' => UserRole::Petugas]);
        $schedule = $this->createSchedule(
            $this->createMachine(),
            $this->createCategory(),
            ['assigned_user_id' => $otherPetugas->id],
        );

        $this->actingAs($petugas)->get('/schedules/'.$schedule->id)->assertNotFound();
    }

    public function test_petugas_can_start_own_scheduled_record_but_cannot_set_another_status(): void
    {
        $petugas = User::factory()->create(['role' => UserRole::Petugas]);
        $schedule = $this->createSchedule(
            $this->createMachine(),
            $this->createCategory(),
            ['assigned_user_id' => $petugas->id, 'status' => 'scheduled'],
        );

        $this->actingAs($petugas)
            ->patch('/schedules/'.$schedule->id.'/status', ['status' => 'in_progress', 'context' => 'mobile'])
            ->assertRedirect('/mobile/schedules')
            ->assertSessionHas('success', 'Status jadwal berhasil diubah.');
        $this->assertSame('in_progress', $schedule->fresh()->status);

        $this->actingAs($petugas)
            ->patch('/schedules/'.$schedule->id.'/status', ['status' => 'completed'])
            ->assertForbidden();
        $this->assertSame('in_progress', $schedule->fresh()->status);
    }

    public function test_petugas_cannot_start_another_officers_schedule(): void
    {
        $petugas = User::factory()->create(['role' => UserRole::Petugas]);
        $otherPetugas = User::factory()->create(['role' => UserRole::Petugas]);
        $schedule = $this->createSchedule(
            $this->createMachine(),
            $this->createCategory(),
            ['assigned_user_id' => $otherPetugas->id, 'status' => 'scheduled'],
        );

        $response = $this->actingAs($petugas)->patch('/schedules/'.$schedule->id.'/status', [
            'status' => 'in_progress',
        ]);

        $response->assertForbidden();
        $this->assertSame('scheduled', $schedule->fresh()->status);
    }

    public function test_supervisor_cannot_change_schedule_status(): void
    {
        $supervisor = User::factory()->create(['role' => UserRole::Supervisor]);
        $schedule = $this->createSchedule($this->createMachine(), $this->createCategory());

        $response = $this->actingAs($supervisor)->patch('/schedules/'.$schedule->id.'/status', [
            'status' => 'completed',
        ]);

        $response->assertForbidden();
        $this->assertSame('scheduled', $schedule->fresh()->status);
    }

    public function test_admin_cannot_set_an_unsupported_schedule_status(): void
    {
        $admin = User::factory()->create(['role' => UserRole::Admin]);
        $schedule = $this->createSchedule($this->createMachine(), $this->createCategory());

        $response = $this->actingAs($admin)->patch('/schedules/'.$schedule->id.'/status', [
            'status' => 'unknown',
        ]);

        $response->assertSessionHasErrors(['status' => 'Status jadwal yang dipilih tidak valid.']);
        $this->assertSame('scheduled', $schedule->fresh()->status);
    }

    public function test_admin_can_set_every_supported_status(): void
    {
        $admin = User::factory()->create(['role' => UserRole::Admin]);
        $schedule = $this->createSchedule($this->createMachine(), $this->createCategory());

        foreach (['scheduled', 'pending', 'in_progress', 'completed', 'overdue', 'cancelled'] as $status) {
            $this->actingAs($admin)
                ->patch('/schedules/'.$schedule->id.'/status', ['status' => $status])
                ->assertRedirect('/schedules');
            $this->assertSame($status, $schedule->fresh()->status);
        }
    }

    public function test_admin_can_delete_a_completed_schedule_without_a_check_but_cannot_delete_a_checked_schedule(): void
    {
        $admin = User::factory()->create(['role' => UserRole::Admin]);
        $petugas = User::factory()->create(['role' => UserRole::Petugas]);
        $machine = $this->createMachine();
        $category = $this->createCategory();
        $completed = $this->createSchedule($machine, $category, ['status' => 'completed']);
        $checked = $this->createSchedule($machine, $category, ['scheduled_date' => '2026-10-13', 'status' => 'completed']);
        MaintenanceCheck::query()->create([
            'maintenance_schedule_id' => $checked->id,
            'machine_id' => $machine->id,
            'checked_by' => $petugas->id,
            'check_date' => '2026-10-13',
            'overall_status' => 'normal',
            'verification_status' => 'verified',
            'general_note' => 'Pemeriksaan selesai.',
        ]);

        $this->actingAs($admin)
            ->delete('/schedules/'.$completed->id)
            ->assertRedirect('/schedules')
            ->assertSessionHas('success', 'Jadwal berhasil dihapus.');
        $this->assertModelMissing($completed);

        $this->actingAs($admin)
            ->delete('/schedules/'.$checked->id)
            ->assertSessionHas('error', 'Jadwal yang sudah memiliki pemeriksaan tidak dapat dihapus.');
        $this->assertModelExists($checked);
    }

    public function test_schedule_detail_shows_airport_and_related_check_summary(): void
    {
        $admin = User::factory()->create(['role' => UserRole::Admin]);
        $petugas = User::factory()->create(['role' => UserRole::Petugas, 'name' => 'Petugas Detail']);
        $machine = $this->createMachine(['airport' => 'Bandara Malikussaleh']);
        $schedule = $this->createSchedule($machine, $this->createCategory(), [
            'assigned_user_id' => $petugas->id,
            'status' => 'completed',
        ]);
        MaintenanceCheck::query()->create([
            'maintenance_schedule_id' => $schedule->id,
            'machine_id' => $machine->id,
            'checked_by' => $petugas->id,
            'check_date' => '2026-10-12',
            'overall_status' => 'finding',
            'verification_status' => 'waiting',
            'general_note' => 'Perlu pengecekan lanjutan.',
        ]);

        $response = $this->actingAs($admin)->get('/schedules?schedule_detail='.$schedule->id);

        $response
            ->assertOk()
            ->assertSeeText('Bandara Malikussaleh')
            ->assertSeeText('Informasi Pemeriksaan')
            ->assertSeeText('finding')
            ->assertSeeText('waiting')
            ->assertSeeText('Perlu pengecekan lanjutan.');
    }

    public function test_mobile_schedule_page_uses_database_records_and_real_actions(): void
    {
        $admin = User::factory()->create(['role' => UserRole::Admin]);
        $machine = $this->createMachine(['equipment_name' => 'X-Ray Database Mobile']);
        $category = $this->createCategory(['name' => 'Mingguan Mobile', 'frequency' => 'weekly']);
        $schedule = $this->createSchedule($machine, $category);

        $response = $this->actingAs($admin)->get('/mobile/schedules');

        $response
            ->assertOk()
            ->assertSeeText('X-Ray Database Mobile')
            ->assertSeeText('Mingguan Mobile')
            ->assertSee('action="'.route('schedules.destroy', $schedule).'"', false)
            ->assertDontSeeText('X-Ray Cabin');
    }

    public function test_mobile_schedule_detail_uses_a_bottom_sheet_with_database_data(): void
    {
        $admin = User::factory()->create(['role' => UserRole::Admin]);
        $machine = $this->createMachine([
            'equipment_name' => 'X-Ray Detail Mobile',
            'airport' => 'Bandara Mobile',
        ]);
        $schedule = $this->createSchedule($machine, $this->createCategory());

        $response = $this->actingAs($admin)->get('/mobile/schedules?schedule_detail='.$schedule->id);

        $response
            ->assertOk()
            ->assertSee('role="dialog"', false)
            ->assertSeeText('X-Ray Detail Mobile')
            ->assertSeeText('Bandara Mobile');
    }

    public function test_schedule_content_is_escaped_in_the_index(): void
    {
        $admin = User::factory()->create(['role' => UserRole::Admin]);
        $machine = $this->createMachine(['equipment_name' => '<script>machine()</script>']);
        $category = $this->createCategory(['name' => '<script>category()</script>']);
        $this->createSchedule($machine, $category, ['notes' => '<script>notes()</script>']);

        $response = $this->actingAs($admin)->get('/schedules');

        $response
            ->assertOk()
            ->assertSee('&lt;script&gt;', false)
            ->assertDontSee('<script>machine()</script>', false)
            ->assertDontSee('<script>category()</script>', false)
            ->assertDontSee('<script>notes()</script>', false);
    }

    /** @param array<string, string|null> $attributes */
    private function createMachine(array $attributes = []): Machine
    {
        static $machineNumber = 0;
        $machineNumber++;

        return Machine::query()->create([
            'equipment_name' => 'Mesin X-Ray '.$machineNumber,
            'brand' => 'Smiths Detection',
            'type' => 'HI-SCAN',
            'serial_number' => 'SCHEDULE-'.$machineNumber,
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
            'name' => 'Kategori Jadwal '.$categoryNumber,
            'frequency' => 'daily',
            'description' => null,
            'status' => 'active',
            ...$attributes,
        ]);
    }

    /** @param array<string, int|string|null> $attributes */
    private function createSchedule(Machine $machine, MaintenanceCategory $category, array $attributes = []): MaintenanceSchedule
    {
        return MaintenanceSchedule::query()->create([
            'machine_id' => $machine->id,
            'maintenance_category_id' => $category->id,
            'scheduled_date' => '2026-10-12',
            'scheduled_time' => '08:00',
            'assigned_user_id' => null,
            'status' => 'scheduled',
            'notes' => 'Catatan jadwal.',
            ...$attributes,
        ]);
    }

    /** @return array<string, int|string|null> */
    private function validSchedulePayload(Machine $machine, MaintenanceCategory $category): array
    {
        return [
            'machine_id' => $machine->id,
            'maintenance_category_id' => $category->id,
            'scheduled_date' => '2026-10-12',
            'scheduled_time' => '08:00',
            'assigned_user_id' => null,
            'status' => 'scheduled',
            'notes' => 'Jadwal manual.',
        ];
    }
}
