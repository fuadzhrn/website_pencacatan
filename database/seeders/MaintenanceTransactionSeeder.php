<?php

namespace Database\Seeders;

use App\Models\Machine;
use App\Models\MaintenanceCategory;
use App\Models\MaintenanceSchedule;
use App\Models\User;
use Illuminate\Database\Seeder;

class MaintenanceTransactionSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $petugas = User::query()->where('email', 'petugas@example.com')->firstOrFail();
        $supervisor = User::query()->where('email', 'supervisor@example.com')->firstOrFail();

        foreach ([
            ['serial_number' => 'XRAY-001', 'category' => 'Harian', 'date' => '2026-10-05', 'completed' => true, 'finding' => null],
            ['serial_number' => 'XRAY-002', 'category' => 'Harian', 'date' => '2026-10-10', 'completed' => true, 'finding' => [
                'status' => 'open', 'description' => 'Tepi belt conveyor mulai sobek.',
                'follow_up' => 'Ajukan penggantian belt sebelum kerusakan meluas.', 'reviewed_at' => null, 'resolved_at' => null,
            ]],
            ['serial_number' => 'XRAY-003', 'category' => 'Mingguan', 'date' => '2026-10-08', 'completed' => true, 'finding' => [
                'status' => 'review', 'description' => 'Filter pendingin tertutup debu sehingga aliran udara berkurang.',
                'follow_up' => 'Supervisor meninjau rencana pembersihan dan penggantian filter.',
                'reviewed_at' => '2026-10-09 10:00:00', 'resolved_at' => null,
            ]],
            ['serial_number' => 'XRAY-001', 'category' => 'Bulanan', 'date' => '2026-10-02', 'completed' => true, 'finding' => [
                'status' => 'resolved', 'description' => 'Roller conveyor menimbulkan suara abnormal.',
                'follow_up' => 'Roller diganti dan uji operasi kembali berjalan normal.',
                'reviewed_at' => '2026-10-03 10:00:00', 'resolved_at' => '2026-10-04 11:00:00',
            ]],
            ['serial_number' => 'XRAY-002', 'category' => 'Triwulan', 'date' => '2026-10-15', 'completed' => false, 'finding' => null],
            ['serial_number' => 'XRAY-003', 'category' => 'Semesteran', 'date' => '2026-10-20', 'completed' => false, 'finding' => null],
            ['serial_number' => 'XRAY-001', 'category' => 'Tahunan', 'date' => '2026-10-30', 'completed' => false, 'finding' => null],
        ] as $transaction) {
            $machine = Machine::query()->where('serial_number', $transaction['serial_number'])->firstOrFail();
            $category = MaintenanceCategory::query()->where('name', $transaction['category'])->firstOrFail();
            $schedule = $machine->schedules()->whereDate('scheduled_date', $transaction['date'])->updateOrCreate(
                ['maintenance_category_id' => $category->id, 'scheduled_time' => '08:00:00'],
                [
                    'scheduled_date' => $transaction['date'],
                    'assigned_user_id' => $petugas->id, 'status' => $transaction['completed'] ? 'completed' : 'scheduled',
                    'notes' => 'Maintenance '.$category->name.' Oktober 2026.',
                ],
            );

            if ($transaction['completed']) {
                $this->seedCheck($schedule, $petugas, $supervisor, $transaction['finding']);
            }
        }
    }

    /**
     * @param  array{status: string, description: string, follow_up: string, reviewed_at: ?string, resolved_at: ?string}|null  $finding
     */
    private function seedCheck(MaintenanceSchedule $schedule, User $petugas, User $supervisor, ?array $finding): void
    {
        $isVerified = $finding === null || $finding['reviewed_at'] !== null;
        $checkDate = $schedule->scheduled_date->toDateString();
        $check = $schedule->check()->updateOrCreate([], [
            'machine_id' => $schedule->machine_id, 'checked_by' => $petugas->id,
            'verified_by' => $isVerified ? $supervisor->id : null,
            'check_date' => $checkDate, 'start_time' => '08:05:00', 'end_time' => '08:30:00',
            'overall_status' => $finding === null ? 'normal' : 'has_finding',
            'verification_status' => $isVerified ? 'verified' : 'waiting',
            'general_note' => $finding === null ? 'Seluruh item pemeriksaan sesuai kriteria.' : $finding['description'],
            'supervisor_note' => $isVerified ? 'Hasil pemeriksaan telah ditinjau supervisor.' : null,
            'verified_at' => $isVerified ? ($finding['reviewed_at'] ?? $checkDate.' 10:00:00') : null,
        ]);

        foreach ($schedule->category->items()->orderBy('sort_order')->get() as $index => $item) {
            $hasFinding = $finding !== null && $index === 0;
            $detail = $check->details()->updateOrCreate(
                ['maintenance_item_id' => $item->id],
                [
                    'result_status' => $hasFinding ? 'tidak_sesuai' : 'sesuai',
                    'actual_value' => $item->input_type === 'nilai_aktual' ? '24' : null,
                    'note' => $hasFinding ? $finding['description'] : 'Hasil pemeriksaan sesuai kriteria.',
                    'photo' => null,
                ],
            );

            if ($hasFinding) {
                $check->findings()->whereDate('finding_date', $checkDate)->updateOrCreate(
                    ['maintenance_check_detail_id' => $detail->id],
                    [
                        'finding_date' => $checkDate,
                        'machine_id' => $schedule->machine_id, 'maintenance_item_id' => $item->id,
                        'reported_by' => $petugas->id, 'finding_description' => $finding['description'],
                        'follow_up' => $finding['follow_up'], 'photo' => null, 'status' => $finding['status'],
                        'reviewed_by' => $finding['reviewed_at'] !== null ? $supervisor->id : null,
                        'reviewed_at' => $finding['reviewed_at'], 'resolved_at' => $finding['resolved_at'],
                    ],
                );
            }
        }
    }
}
