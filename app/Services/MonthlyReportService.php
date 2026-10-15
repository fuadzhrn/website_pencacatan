<?php

namespace App\Services;

use App\Models\Machine;
use App\Models\MaintenanceCheck;
use App\Models\MaintenanceCheckDetail;
use App\Models\MaintenanceItem;
use App\Models\MaintenanceSchedule;
use App\Models\User;
use App\UserRole;
use Carbon\CarbonImmutable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;

class MonthlyReportService
{
    /**
     * @return Builder<Machine>
     */
    public function visibleMachinesQuery(User $user): Builder
    {
        return Machine::query()
            ->when(
                $user->role === UserRole::Petugas,
                fn (Builder $query): Builder => $query->whereHas(
                    'schedules',
                    fn (Builder $scheduleQuery): Builder => $this->applyPetugasScheduleScope($scheduleQuery, $user),
                ),
            )
            ->orderBy('equipment_name')
            ->orderBy('id');
    }

    /**
     * Hasil final terbaru dipilih berdasarkan waktu selesai, lalu ID pemeriksaan.
     *
     * @return array<string, mixed>
     */
    public function build(User $user, Machine $machine, int $month, int $year): array
    {
        $period = CarbonImmutable::create($year, $month, 1)->startOfMonth();
        $schedules = MaintenanceSchedule::query()
            ->whereBelongsTo($machine)
            ->whereBetween('scheduled_date', [$period->toDateString(), $period->endOfMonth()->toDateString()])
            ->when(
                $user->role === UserRole::Petugas,
                fn (Builder $query): Builder => $this->applyPetugasScheduleScope($query, $user),
            )
            ->with([
                'maintenanceCategory.items' => fn ($query) => $query
                    ->orderByRaw('CASE WHEN sort_order IS NULL THEN 1 ELSE 0 END')
                    ->orderBy('sort_order')
                    ->orderBy('id'),
                'assignedUser:id,name',
                'check.checkedBy:id,name',
                'check.verifiedBy:id,name',
                'check.details.findings.reviewedBy:id,name',
            ])
            ->orderBy('scheduled_date')
            ->orderBy('scheduled_time')
            ->orderBy('id')
            ->get();

        $frequencyOrder = [
            'daily' => 1,
            'weekly' => 2,
            'monthly' => 3,
            'quarterly' => 4,
            'semiannual' => 5,
            'annual' => 6,
        ];
        $groups = $schedules
            ->groupBy('maintenance_category_id')
            ->map(function (Collection $categorySchedules) use ($period): array {
                $category = $categorySchedules->firstOrFail()->maintenanceCategory;

                return [
                    'category' => $category,
                    'frequency' => $category->frequency,
                    'frequency_label' => $this->frequencyLabel($category->frequency),
                    'rows' => $category->items->map(fn (MaintenanceItem $item): array => $this->buildRow(
                        $item,
                        $categorySchedules,
                        $period->daysInMonth,
                    ))->values()->all(),
                ];
            })
            ->sortBy(fn (array $group): string => sprintf(
                '%02d-%s-%010d',
                $frequencyOrder[$group['frequency']] ?? 99,
                mb_strtolower($group['category']->name),
                $group['category']->id,
            ))
            ->values();

        $rows = $groups->flatMap(fn (array $group): array => $group['rows']);
        $scheduledCells = $rows->flatMap(fn (array $row): Collection => collect($row['cells']))
            ->whereIn('state', ['conform', 'nonconform', 'pending']);
        $completedCells = $scheduledCells->whereIn('state', ['conform', 'nonconform']);
        $selectedChecks = $scheduledCells
            ->pluck('check')
            ->filter()
            ->unique('id')
            ->values();

        return [
            'machine' => $machine,
            'period' => [
                'month' => $month,
                'year' => $year,
                'month_name' => $period->locale('id')->translatedFormat('F'),
                'label' => $period->locale('id')->translatedFormat('F Y'),
                'days_in_month' => $period->daysInMonth,
            ],
            'days' => range(1, 31),
            'groups' => $groups->all(),
            'summary' => [
                'total_items' => $rows->count(),
                'scheduled' => $scheduledCells->count(),
                'conform' => $scheduledCells->where('state', 'conform')->count(),
                'nonconform' => $scheduledCells->where('state', 'nonconform')->count(),
                'pending' => $scheduledCells->where('state', 'pending')->count(),
                'findings' => $scheduledCells->flatMap(fn (array $cell): array => $cell['finding_ids'])->unique()->count(),
                'completion_percentage' => $scheduledCells->isNotEmpty()
                    ? (int) round(($completedCells->count() / $scheduledCells->count()) * 100)
                    : 0,
            ],
            'officers' => $selectedChecks->pluck('checkedBy.name')->filter()->unique()->values()->all(),
            'verifications' => $selectedChecks
                ->map(fn (MaintenanceCheck $check): array => [
                    'status' => $check->verification_status,
                    'status_label' => $this->verificationLabel($check->verification_status),
                    'supervisor' => $check->verifiedBy?->name,
                    'verified_at' => $check->verified_at,
                    'note' => $check->supervisor_note,
                ])
                ->values()
                ->all(),
            'selection_rule' => 'Jika terdapat beberapa pemeriksaan final pada item dan tanggal yang sama, digunakan pemeriksaan dengan waktu selesai terbaru, lalu ID terbesar.',
        ];
    }

    private function applyPetugasScheduleScope(Builder $query, User $user): Builder
    {
        return $query->where(function (Builder $visibilityQuery) use ($user): void {
            $visibilityQuery
                ->where(function (Builder $pendingQuery) use ($user): void {
                    $pendingQuery
                        ->where('assigned_user_id', $user->id)
                        ->whereDoesntHave('check', fn (Builder $checkQuery): Builder => $this->finalCheckQuery($checkQuery));
                })
                ->orWhereHas('check', fn (Builder $checkQuery): Builder => $this->finalCheckQuery($checkQuery)
                    ->where('checked_by', $user->id));
        });
    }

    private function finalCheckQuery(Builder $query): Builder
    {
        return $query
            ->whereNotNull('end_time')
            ->where('overall_status', '!=', 'incomplete');
    }

    /**
     * @param  Collection<int, MaintenanceSchedule>  $categorySchedules
     * @return array<string, mixed>
     */
    private function buildRow(MaintenanceItem $item, Collection $categorySchedules, int $daysInMonth): array
    {
        $schedulesByDay = $categorySchedules->groupBy(fn (MaintenanceSchedule $schedule): int => $schedule->scheduled_date->day);
        $cells = collect(range(1, 31))->mapWithKeys(function (int $day) use ($item, $schedulesByDay, $daysInMonth): array {
            if ($day > $daysInMonth) {
                return [$day => $this->emptyCell('invalid')];
            }

            /** @var Collection<int, MaintenanceSchedule> $daySchedules */
            $daySchedules = $schedulesByDay->get($day, collect());

            if ($daySchedules->isEmpty()) {
                return [$day => $this->emptyCell('not_scheduled')];
            }

            $selectedSchedule = $daySchedules
                ->filter(fn (MaintenanceSchedule $schedule): bool => $this->isFinalCheck($schedule->check))
                ->sortByDesc(fn (MaintenanceSchedule $schedule): string => sprintf(
                    '%s-%012d',
                    $schedule->check?->end_time ?? '',
                    $schedule->check?->id ?? 0,
                ))
                ->first();

            if (! $selectedSchedule) {
                return [$day => $this->emptyCell('pending')];
            }

            $check = $selectedSchedule->check;
            $detail = $check?->details->firstWhere('maintenance_item_id', $item->id);

            if (! $detail || $detail->result_status === 'belum_diperiksa') {
                return [$day => $this->pendingCell($check)];
            }

            if ($detail->result_status === 'tidak_dijadwalkan') {
                return [$day => $this->emptyCell('not_scheduled')];
            }

            $state = $detail->result_status === 'tidak_sesuai' ? 'nonconform' : 'conform';

            return [$day => $this->resultCell($state, $check, $detail)];
        });

        return [
            'item' => $item,
            'cells' => $cells->all(),
            'notes' => $cells
                ->filter(fn (array $cell): bool => $cell['note'] !== null)
                ->map(fn (array $cell, int $day): string => sprintf('%02d: %s', $day, $cell['note']))
                ->values()
                ->all(),
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function emptyCell(string $state): array
    {
        return [
            'state' => $state,
            'symbol' => $state === 'pending' ? '—' : '',
            'label' => match ($state) {
                'pending' => 'Dijadwalkan, belum diperiksa',
                'invalid' => 'Tanggal tidak ada dalam bulan',
                default => 'Tidak dijadwalkan',
            },
            'check_id' => null,
            'check' => null,
            'detail' => null,
            'finding_ids' => [],
            'note' => null,
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function pendingCell(?MaintenanceCheck $check): array
    {
        $cell = $this->emptyCell('pending');
        $cell['check_id'] = $check?->id;
        $cell['check'] = $check;

        return $cell;
    }

    /**
     * @return array<string, mixed>
     */
    private function resultCell(string $state, MaintenanceCheck $check, MaintenanceCheckDetail $detail): array
    {
        $findings = $detail->findings;
        $noteParts = collect([$detail->actual_value, $detail->note, $check->general_note])
            ->filter()
            ->map(fn (string $value): string => trim($value));

        foreach ($findings as $finding) {
            $findingText = 'Temuan: '.$finding->finding_description;

            if ($finding->follow_up) {
                $findingText .= '; Tindak lanjut: '.$finding->follow_up;
            }

            $findingText .= '; Status: '.ucfirst($finding->status);
            $noteParts->push($findingText);
        }

        $verification = $this->verificationLabel($check->verification_status);

        if ($check->verifiedBy) {
            $verification .= ' oleh '.$check->verifiedBy->name;
        }

        if ($check->verified_at) {
            $verification .= ' pada '.$check->verified_at->format('d-m-Y H:i');
        }

        if ($check->supervisor_note) {
            $verification .= '; Catatan: '.$check->supervisor_note;
        }

        $noteParts->push($verification);

        return [
            'state' => $state,
            'symbol' => $state === 'conform' ? '✓' : '✕',
            'label' => $state === 'conform' ? 'Sesuai' : 'Tidak sesuai',
            'check_id' => $check->id,
            'check' => $check,
            'detail' => $detail,
            'finding_ids' => $findings->modelKeys(),
            'note' => $noteParts->unique()->implode('; '),
        ];
    }

    private function isFinalCheck(?MaintenanceCheck $check): bool
    {
        return $check !== null
            && $check->end_time !== null
            && $check->overall_status !== 'incomplete';
    }

    private function frequencyLabel(string $frequency): string
    {
        return match ($frequency) {
            'daily' => 'Harian',
            'weekly' => 'Mingguan',
            'monthly' => 'Bulanan',
            'quarterly' => 'Triwulan',
            'semiannual' => 'Semesteran',
            'annual' => 'Tahunan',
            default => ucfirst($frequency),
        };
    }

    private function verificationLabel(string $status): string
    {
        return match ($status) {
            'verified' => 'Terverifikasi',
            'rejected' => 'Ditolak',
            default => 'Menunggu verifikasi',
        };
    }
}
