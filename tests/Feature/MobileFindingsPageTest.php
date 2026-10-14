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
            ->assertSeeText('Belum ada temuan')
            ->assertDontSeeText('X-Ray Cabin')
            ->assertSee('name="machine_id"', false)
            ->assertSee('name="status"', false)
            ->assertSee(asset('assets/css/mobile-findings.css'), false)
            ->assertSee(asset('assets/js/findings-database.js'), false);
    }
}
