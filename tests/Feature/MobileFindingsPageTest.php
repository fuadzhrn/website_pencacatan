<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

final class MobileFindingsPageTest extends TestCase
{
    use RefreshDatabase;

    public function test_authenticated_user_receives_mobile_findings_content_and_assets(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->get(route('findings.index'));

        $response
            ->assertOk()
            ->assertSeeText('Pantau temuan hasil pemeriksaan')
            ->assertSeeText('X-Ray Cabin')
            ->assertSeeText('Indicator lamp tidak menyala')
            ->assertSeeText('X-Ray Cargo')
            ->assertSeeText('UPS tidak normal')
            ->assertSeeText('X-Ray Baggage HI-Scan 100100T')
            ->assertSeeText('Conveyor belt tidak stabil')
            ->assertSeeText('Tambah Temuan')
            ->assertSee('data-mobile-finding-detail-field="machine"', false)
            ->assertSee('aria-describedby="mobile-finding-photo-help mobile-finding-photo-error"', false)
            ->assertSee('data-mobile-finding-photo-error', false)
            ->assertSee(asset('assets/css/mobile-findings.css'), false)
            ->assertSee(asset('assets/js/mobile-findings.js'), false);
    }
}
