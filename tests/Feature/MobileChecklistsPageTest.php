<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

final class MobileChecklistsPageTest extends TestCase
{
    use RefreshDatabase;

    public function test_authenticated_user_receives_mobile_checklist_content_and_assets(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->get(route('checklists.index'));

        $response
            ->assertOk()
            ->assertSeeText('Master Checklist')
            ->assertSeeText('Kelola kategori dan item pemeriksaan')
            ->assertSeeText('Harian')
            ->assertSeeText('Mingguan')
            ->assertSeeText('Bulanan')
            ->assertSeeText('Triwulan')
            ->assertSeeText('Semesteran')
            ->assertSeeText('Tahunan')
            ->assertSeeText('Lead curtain dalam kondisi baik')
            ->assertSeeText('Conveyor belt berjalan normal')
            ->assertSeeText('UPS dalam kondisi normal')
            ->assertSeeText('Monitor menampilkan gambar jelas')
            ->assertSee('data-mobile-checklist-detail-field="name"', false)
            ->assertSee(asset('assets/css/mobile-checklists.css'), false)
            ->assertSee(asset('assets/js/mobile-checklists.js'), false);
    }
}
