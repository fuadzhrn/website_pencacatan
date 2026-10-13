<?php

namespace Tests\Feature;

use App\Models\Machine;
use App\Models\MaintenanceCategory;
use App\Models\MaintenanceSchedule;
use App\Models\User;
use App\UserRole;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

final class MobileSchedulesPageTest extends TestCase
{
    use RefreshDatabase;

    public function test_authenticated_user_receives_mobile_schedule_content_and_assets(): void
    {
        $user = User::factory()->create(['role' => UserRole::Petugas]);
        $machine = Machine::query()->create([
            'equipment_name' => 'X-Ray Database Mobile',
            'brand' => 'Smiths Detection',
            'type' => 'HI-SCAN',
            'serial_number' => 'MOBILE-SCHEDULE-001',
            'location' => 'Terminal 1',
            'airport' => 'UPBU Malikussaleh',
            'status' => 'active',
        ]);
        $category = MaintenanceCategory::query()->create([
            'name' => 'Pemeriksaan Mobile',
            'frequency' => 'daily',
            'status' => 'active',
        ]);
        MaintenanceSchedule::query()->create([
            'machine_id' => $machine->id,
            'maintenance_category_id' => $category->id,
            'scheduled_date' => '2026-10-13',
            'scheduled_time' => '08:00:00',
            'assigned_user_id' => $user->id,
            'status' => 'scheduled',
            'notes' => 'Jadwal dari database.',
        ]);

        $response = $this->actingAs($user)->get(route('mobile.schedules'));

        $response
            ->assertOk()
            ->assertSeeText('Jadwal Maintenance')
            ->assertSeeText('Pantau jadwal pemeriksaan mesin X-Ray')
            ->assertSeeText('X-Ray Database Mobile')
            ->assertSeeText('Pemeriksaan Mobile')
            ->assertSeeText($user->name)
            ->assertDontSeeText('X-Ray Baggage HI-Scan 100100T')
            ->assertSee(asset('assets/css/mobile-schedules.css'), false)
            ->assertSee(asset('assets/js/mobile-schedules.js'), false);
    }
}
