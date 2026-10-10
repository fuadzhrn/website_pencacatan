<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

final class MobileHistoryPageTest extends TestCase
{
    use RefreshDatabase;

    public function test_unauthenticated_user_is_redirected_to_login(): void
    {
        $response = $this->get('/inspections/history');

        $response->assertRedirect(route('login'));
    }

    public function test_authenticated_user_receives_mobile_history_content_and_assets(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->get('/inspections/history');

        $response
            ->assertOk()
            ->assertSeeText('Lihat pemeriksaan yang sudah dilakukan')
            ->assertSeeText('X-Ray Baggage HI-Scan 100100T')
            ->assertSeeText('X-Ray Cabin')
            ->assertSeeText('X-Ray Cargo')
            ->assertSeeText('Menunggu Verifikasi')
            ->assertSeeText('Detail Checklist')
            ->assertSee('data-mobile-history-detail="machineName"', false)
            ->assertSee('data-mobile-history-result-count role="status" aria-live="polite"', false)
            ->assertSee(asset('assets/css/mobile-history.css'), false)
            ->assertSee(asset('assets/js/mobile-history.js'), false);
    }
}
