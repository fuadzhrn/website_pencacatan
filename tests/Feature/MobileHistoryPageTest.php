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

    public function test_authenticated_user_receives_database_backed_mobile_history_content(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->get('/inspections/history');

        $response
            ->assertOk()
            ->assertSeeText('Lihat pemeriksaan yang sudah dilakukan')
            ->assertSeeText('Filter Riwayat')
            ->assertSeeText('Menunggu Verifikasi')
            ->assertSeeText('Riwayat tidak ditemukan')
            ->assertDontSeeText('X-Ray Baggage HI-Scan 100100T')
            ->assertSee(asset('assets/css/verifications.css'), false)
            ->assertDontSee(asset('assets/js/mobile-history.js'), false);
    }
}
