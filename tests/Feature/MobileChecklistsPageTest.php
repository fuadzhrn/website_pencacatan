<?php

namespace Tests\Feature;

use App\Models\MaintenanceCategory;
use App\Models\MaintenanceItem;
use App\Models\User;
use App\UserRole;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

final class MobileChecklistsPageTest extends TestCase
{
    use RefreshDatabase;

    public function test_authenticated_user_receives_mobile_checklist_content_and_assets(): void
    {
        $user = User::factory()->create(['role' => UserRole::Admin]);
        $category = MaintenanceCategory::query()->create([
            'name' => 'Harian Database',
            'frequency' => 'daily',
            'description' => 'Kategori dari database.',
            'status' => 'active',
        ]);
        MaintenanceItem::query()->create([
            'maintenance_category_id' => $category->id,
            'item_name' => 'Periksa lead curtain database',
            'criteria' => 'Tidak robek dan menutup dengan baik.',
            'input_type' => 'sesuai_tidak_sesuai',
            'sort_order' => 1,
            'status' => 'active',
        ]);

        $response = $this->actingAs($user)->get(route('mobile.checklists'));

        $response
            ->assertOk()
            ->assertSeeText('Master Checklist')
            ->assertSeeText('Kelola kategori dan item pemeriksaan')
            ->assertSeeText('Harian Database')
            ->assertSeeText('Periksa lead curtain database')
            ->assertSee('action="'.route('maintenance-items.store').'"', false)
            ->assertSee('action="'.route('maintenance-categories.store').'"', false)
            ->assertDontSeeText('Conveyor belt berjalan normal')
            ->assertSee(asset('assets/css/mobile-checklists.css'), false)
            ->assertSee(asset('assets/js/mobile-checklists.js'), false);
    }
}
