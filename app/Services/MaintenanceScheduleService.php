<?php

namespace App\Services;

use App\Models\MaintenanceCategory;
use App\Models\MaintenanceSchedule;
use Carbon\CarbonImmutable;
use Illuminate\Support\Facades\DB;

class MaintenanceScheduleService
{
    /**
     * @param  array{
     *     machine_id: int,
     *     maintenance_category_id: int,
     *     start_date: string,
     *     end_date: string,
     *     scheduled_time?: string|null,
     *     assigned_user_id?: int|null,
     *     notes?: string|null
     * }  $attributes
     * @return array{created: int, skipped: int}
     */
    public function generate(array $attributes): array
    {
        $category = MaintenanceCategory::query()->findOrFail($attributes['maintenance_category_id']);
        $startDate = CarbonImmutable::parse($attributes['start_date'])->startOfDay();
        $endDate = CarbonImmutable::parse($attributes['end_date'])->startOfDay();

        return DB::transaction(function () use ($attributes, $category, $startDate, $endDate): array {
            $created = 0;
            $skipped = 0;
            $occurrence = 0;

            while (true) {
                $scheduledDate = $this->dateForOccurrence($startDate, $category->frequency, $occurrence);

                if ($scheduledDate->greaterThan($endDate)) {
                    break;
                }

                $alreadyExists = MaintenanceSchedule::query()
                    ->where('machine_id', $attributes['machine_id'])
                    ->where('maintenance_category_id', $category->id)
                    ->whereDate('scheduled_date', $scheduledDate)
                    ->exists();

                if ($alreadyExists) {
                    $skipped++;
                } else {
                    MaintenanceSchedule::query()->create([
                        'machine_id' => $attributes['machine_id'],
                        'maintenance_category_id' => $category->id,
                        'scheduled_date' => $scheduledDate->toDateString(),
                        'scheduled_time' => $this->normalizeTime($attributes['scheduled_time'] ?? null),
                        'assigned_user_id' => $attributes['assigned_user_id'] ?? null,
                        'status' => 'scheduled',
                        'notes' => $attributes['notes'] ?? null,
                    ]);
                    $created++;
                }

                $occurrence++;
            }

            return compact('created', 'skipped');
        });
    }

    public function markOverdue(): int
    {
        return MaintenanceSchedule::query()
            ->whereDate('scheduled_date', '<', CarbonImmutable::today())
            ->whereIn('status', ['scheduled', 'pending'])
            ->update(['status' => 'overdue']);
    }

    private function dateForOccurrence(CarbonImmutable $startDate, string $frequency, int $occurrence): CarbonImmutable
    {
        return match ($frequency) {
            'daily' => $startDate->addDays($occurrence),
            'weekly' => $startDate->addWeeks($occurrence),
            'monthly' => $startDate->addMonthsNoOverflow($occurrence),
            'quarterly' => $startDate->addMonthsNoOverflow($occurrence * 3),
            'semester' => $startDate->addMonthsNoOverflow($occurrence * 6),
            'yearly' => $startDate->addYearsNoOverflow($occurrence),
            default => throw new \InvalidArgumentException('Frekuensi maintenance tidak didukung.'),
        };
    }

    private function normalizeTime(?string $time): ?string
    {
        return $time !== null && strlen($time) === 5 ? $time.':00' : $time;
    }
}
