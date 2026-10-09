<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

final class MobileSchedulesPageTest extends TestCase
{
    use RefreshDatabase;

    public function test_authenticated_user_receives_mobile_schedule_content_and_assets(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->get(route('schedules.index'));

        $response
            ->assertOk()
            ->assertSeeText('Jadwal Maintenance')
            ->assertSeeText('Pantau jadwal pemeriksaan mesin X-Ray')
            ->assertSeeText('X-Ray Baggage HI-Scan 100100T')
            ->assertSeeText('X-Ray Cabin')
            ->assertSeeText('X-Ray Cargo')
            ->assertSeeText('Budi Santoso')
            ->assertSeeText('Andi Pratama')
            ->assertSeeText('Siti Rahma')
            ->assertSee(asset('assets/css/mobile-schedules.css'), false)
            ->assertSee(asset('assets/js/mobile-schedules.js'), false);
    }
}
