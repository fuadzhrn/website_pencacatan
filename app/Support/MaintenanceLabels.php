<?php

namespace App\Support;

/**
 * Single source of display labels for maintenance statuses and frequencies,
 * so every desktop and mobile page uses the same terms.
 */
class MaintenanceLabels
{
    /** @return array<string, string> */
    public static function frequencies(): array
    {
        return [
            'daily' => 'Harian',
            'weekly' => 'Mingguan',
            'monthly' => 'Bulanan',
            'quarterly' => 'Triwulan',
            'semester' => 'Semesteran',
            'yearly' => 'Tahunan',
        ];
    }

    /** @return array<string, string> */
    public static function scheduleStatuses(): array
    {
        return [
            'scheduled' => 'Terjadwal',
            'pending' => 'Belum Dikerjakan',
            'in_progress' => 'Sedang Dikerjakan',
            'completed' => 'Selesai',
            'overdue' => 'Terlambat',
            'cancelled' => 'Dibatalkan',
        ];
    }

    /** @return array<string, string> */
    public static function checkResults(): array
    {
        return [
            'normal' => 'Normal',
            'has_finding' => 'Terdapat Temuan',
            'incomplete' => 'Belum Selesai',
        ];
    }

    /** @return array<string, string> */
    public static function itemResults(): array
    {
        return [
            'sesuai' => 'Sesuai',
            'tidak_sesuai' => 'Tidak Sesuai',
            'tidak_dijadwalkan' => 'Tidak Dijadwalkan',
            'belum_diperiksa' => 'Belum Diperiksa',
        ];
    }

    /** @return array<string, string> */
    public static function verificationStatuses(): array
    {
        return [
            'waiting' => 'Menunggu Verifikasi',
            'verified' => 'Terverifikasi',
            'rejected' => 'Ditolak/Revisi',
        ];
    }

    /** @return array<string, string> */
    public static function findingStatuses(): array
    {
        return [
            'open' => 'Open',
            'review' => 'Review',
            'resolved' => 'Resolved',
        ];
    }
}
