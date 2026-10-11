<?php

namespace Database\Seeders;

use App\Models\Machine;
use App\Models\MaintenanceCategory;
use Illuminate\Database\Seeder;

class MaintenanceMasterSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        foreach ([
            ['serial_number' => 'XRAY-001', 'type' => 'HI-SCAN 6040', 'location' => 'Terminal 1 - Jalur A'],
            ['serial_number' => 'XRAY-002', 'type' => 'HI-SCAN 6040', 'location' => 'Terminal 1 - Jalur B'],
            ['serial_number' => 'XRAY-003', 'type' => 'HI-SCAN 100100', 'location' => 'Terminal Kargo'],
        ] as $machine) {
            Machine::query()->updateOrCreate(
                ['serial_number' => $machine['serial_number']],
                [
                    'equipment_name' => 'X-Ray Inspection System', 'brand' => 'Smiths Detection',
                    'type' => $machine['type'], 'location' => $machine['location'],
                    'airport' => 'Soekarno-Hatta', 'status' => 'active',
                    'description' => 'Mesin pemeriksaan bagasi untuk dataset maintenance Oktober 2026.',
                ],
            );
        }

        foreach ([
            ['name' => 'Harian', 'frequency' => 'daily', 'items' => [
                ['item_name' => 'Kondisi conveyor', 'criteria' => 'Belt berjalan lancar tanpa sobekan.', 'input_type' => 'sesuai_tidak_sesuai'],
                ['item_name' => 'Tombol emergency stop', 'criteria' => 'Tombol menghentikan conveyor saat ditekan.', 'input_type' => 'sesuai_tidak_sesuai'],
            ]],
            ['name' => 'Mingguan', 'frequency' => 'weekly', 'items' => [
                ['item_name' => 'Kebersihan filter pendingin', 'criteria' => 'Filter bersih dan aliran udara tidak terhambat.', 'input_type' => 'sesuai_tidak_sesuai'],
                ['item_name' => 'Suhu ruang mesin', 'criteria' => 'Suhu ruang mesin antara 18 dan 30 derajat Celsius.', 'input_type' => 'nilai_aktual'],
            ]],
            ['name' => 'Bulanan', 'frequency' => 'monthly', 'items' => [
                ['item_name' => 'Kondisi roller conveyor', 'criteria' => 'Roller berputar lancar tanpa suara abnormal.', 'input_type' => 'sesuai_tidak_sesuai'],
                ['item_name' => 'Kondisi kabel dan konektor', 'criteria' => 'Kabel utuh dan konektor terpasang kuat.', 'input_type' => 'sesuai_tidak_sesuai'],
            ]],
            ['name' => 'Triwulan', 'frequency' => 'quarterly', 'items' => [
                ['item_name' => 'Kualitas citra test piece', 'criteria' => 'Objek test piece terlihat jelas pada monitor.', 'input_type' => 'sesuai_tidak_sesuai'],
                ['item_name' => 'Catatan kalibrasi', 'criteria' => 'Catat hasil pemeriksaan kalibrasi perangkat.', 'input_type' => 'catatan'],
            ]],
            ['name' => 'Semesteran', 'frequency' => 'semester', 'items' => [
                ['item_name' => 'Kondisi shielding', 'criteria' => 'Pelindung terpasang utuh tanpa celah.', 'input_type' => 'sesuai_tidak_sesuai'],
                ['item_name' => 'Kondisi interlock', 'criteria' => 'Interlock menghentikan operasi saat panel dibuka.', 'input_type' => 'sesuai_tidak_sesuai'],
            ]],
            ['name' => 'Tahunan', 'frequency' => 'yearly', 'items' => [
                ['item_name' => 'Pemeriksaan generator X-Ray', 'criteria' => 'Generator lulus pemeriksaan teknisi sesuai panduan pabrikan.', 'input_type' => 'sesuai_tidak_sesuai'],
                ['item_name' => 'Catatan servis tahunan', 'criteria' => 'Catat hasil servis dan rekomendasi teknisi.', 'input_type' => 'catatan'],
            ]],
        ] as $categoryData) {
            $category = MaintenanceCategory::query()->updateOrCreate(
                ['name' => $categoryData['name']],
                ['frequency' => $categoryData['frequency'], 'status' => 'active', 'description' => 'Checklist maintenance '.$categoryData['name'].'.'],
            );

            foreach ($categoryData['items'] as $index => $item) {
                $category->items()->updateOrCreate(
                    ['item_name' => $item['item_name']],
                    ['criteria' => $item['criteria'], 'input_type' => $item['input_type'], 'sort_order' => $index + 1, 'status' => 'active'],
                );
            }
        }
    }
}
