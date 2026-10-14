<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreInspectionRequest;
use App\Models\MaintenanceCheck;
use App\Models\MaintenanceCheckDetail;
use App\Models\MaintenanceFinding;
use App\Models\MaintenanceSchedule;
use App\Models\User;
use App\UserRole;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\View\View;

class InspectionController extends Controller
{
    private const ACTIVE_SCHEDULE_STATUSES = ['scheduled', 'pending', 'in_progress', 'overdue'];

    public function today(Request $request): View
    {
        $user = $request->user();
        $schedules = $this->visibleSchedules($user)
            ->whereDate('scheduled_date', now()->toDateString())
            ->whereIn('status', self::ACTIVE_SCHEDULE_STATUSES)
            ->with(['machine', 'maintenanceCategory', 'assignedUser', 'check'])
            ->orderBy('scheduled_time')
            ->get();

        $selectedSchedule = null;
        $checklistItems = collect();
        $checkDetails = collect();

        if ($request->filled('schedule')) {
            $selectedSchedule = $this->visibleSchedules($user)
                ->with([
                    'machine',
                    'maintenanceCategory.items' => fn ($query) => $query
                        ->where('status', 'active')
                        ->orderBy('sort_order')
                        ->orderBy('item_name'),
                    'assignedUser',
                    'check.details.item',
                    'check.details.finding',
                ])
                ->find($request->integer('schedule'));

            if ($selectedSchedule) {
                $checkDetails = $selectedSchedule->check?->details?->keyBy('maintenance_item_id') ?? collect();
                $isHistoricalInspection = $selectedSchedule->check !== null
                    && ($selectedSchedule->status === 'completed'
                        || ! $selectedSchedule->scheduled_date->isToday()
                        || $selectedSchedule->check->verification_status !== 'waiting');
                $checklistItems = $isHistoricalInspection
                    ? $selectedSchedule->check->details
                        ->pluck('item')
                        ->filter()
                        ->sortBy([['sort_order', 'asc'], ['item_name', 'asc']])
                        ->values()
                    : $selectedSchedule->maintenanceCategory->items;
            }
        }

        $canEditInspection = $selectedSchedule !== null
            && $user->role === UserRole::Petugas
            && $selectedSchedule->assigned_user_id === $user->id
            && $selectedSchedule->scheduled_date->isToday()
            && $selectedSchedule->status === 'in_progress'
            && $selectedSchedule->check?->verification_status === 'waiting';

        return view('inspections.today', [
            ...$this->layoutData($user),
            'schedules' => $schedules,
            'selectedSchedule' => $selectedSchedule,
            'checklistItems' => $checklistItems,
            'checkDetails' => $checkDetails,
            'canEditInspection' => $canEditInspection,
            'frequencyLabels' => $this->frequencyLabels(),
            'scheduleStatusLabels' => $this->scheduleStatusLabels(),
            'resultStatusLabels' => $this->resultStatusLabels(),
            'inputTypeLabels' => $this->inputTypeLabels(),
        ]);
    }

    public function showSchedule(Request $request, MaintenanceSchedule $schedule): RedirectResponse
    {
        $this->ensureScheduleIsVisible($request->user(), $schedule);

        return redirect()->route($this->todayRouteName($request), ['schedule' => $schedule->id]);
    }

    public function start(Request $request, MaintenanceSchedule $schedule): RedirectResponse
    {
        $user = $request->user();
        $this->ensureScheduleIsEditable($user, $schedule);

        DB::transaction(function () use ($schedule, $user): void {
            $lockedSchedule = MaintenanceSchedule::query()->lockForUpdate()->findOrFail($schedule->id);
            $this->ensureScheduleIsEditable($user, $lockedSchedule);

            if (! in_array($lockedSchedule->status, self::ACTIVE_SCHEDULE_STATUSES, true)) {
                abort(403);
            }

            $check = MaintenanceCheck::query()
                ->where('maintenance_schedule_id', $lockedSchedule->id)
                ->lockForUpdate()
                ->first();

            if ($check === null) {
                $check = MaintenanceCheck::query()->create([
                    'maintenance_schedule_id' => $lockedSchedule->id,
                    'machine_id' => $lockedSchedule->machine_id,
                    'checked_by' => $user->id,
                    'check_date' => now()->toDateString(),
                    'start_time' => now()->format('H:i:s'),
                    'overall_status' => 'incomplete',
                    'verification_status' => 'waiting',
                ]);
            }

            if ($check->verification_status !== 'waiting') {
                abort(403);
            }

            $lockedSchedule->update(['status' => 'in_progress']);
        });

        return redirect()
            ->route($this->todayRouteName($request), ['schedule' => $schedule->id])
            ->with('success', 'Pemeriksaan dimulai.');
    }

    public function store(StoreInspectionRequest $request, MaintenanceSchedule $schedule): RedirectResponse
    {
        $validated = $request->validated();
        $isFinalSubmission = $validated['submission_type'] === 'final';
        $newPhotoPaths = [];
        $replacedPhotoPaths = [];

        try {
            DB::transaction(function () use ($request, $schedule, $validated, $isFinalSubmission, &$newPhotoPaths, &$replacedPhotoPaths): void {
                $lockedSchedule = MaintenanceSchedule::query()->lockForUpdate()->findOrFail($schedule->id);
                $this->ensureScheduleIsEditable($request->user(), $lockedSchedule);

                if ($lockedSchedule->status !== 'in_progress') {
                    abort(403);
                }

                $check = MaintenanceCheck::query()
                    ->where('maintenance_schedule_id', $lockedSchedule->id)
                    ->lockForUpdate()
                    ->first();

                if ($check === null) {
                    $check = MaintenanceCheck::query()->create([
                        'maintenance_schedule_id' => $lockedSchedule->id,
                        'machine_id' => $lockedSchedule->machine_id,
                        'checked_by' => $request->user()->id,
                        'check_date' => now()->toDateString(),
                        'start_time' => now()->format('H:i:s'),
                        'overall_status' => 'incomplete',
                        'verification_status' => 'waiting',
                    ]);
                }

                if ($check->verification_status !== 'waiting') {
                    abort(403);
                }

                foreach ($validated['items'] as $itemData) {
                    $detail = MaintenanceCheckDetail::query()
                        ->where('maintenance_check_id', $check->id)
                        ->where('maintenance_item_id', $itemData['maintenance_item_id'])
                        ->lockForUpdate()
                        ->first() ?? new MaintenanceCheckDetail([
                            'maintenance_check_id' => $check->id,
                            'maintenance_item_id' => $itemData['maintenance_item_id'],
                        ]);

                    $detail->fill([
                        'result_status' => $itemData['result_status'],
                        'actual_value' => $itemData['actual_value'] ?? null,
                        'note' => $itemData['note'] ?? null,
                    ]);

                    if (isset($itemData['photo'])) {
                        $previousPhotoPath = $detail->photo;
                        $detail->photo = $itemData['photo']->store('inspection-photos', 'public');
                        $newPhotoPaths[] = $detail->photo;

                        if ($previousPhotoPath) {
                            $replacedPhotoPaths[] = $previousPhotoPath;
                        }
                    }

                    $finding = MaintenanceFinding::query()
                        ->where('maintenance_check_detail_id', $detail->id)
                        ->lockForUpdate()
                        ->first();

                    if ($itemData['result_status'] !== 'tidak_sesuai'
                        && $finding
                        && ($finding->status !== 'open' || $finding->reviewed_by !== null)) {
                        abort(409, 'Hasil item yang temuannya sudah ditinjau tidak dapat diubah.');
                    }

                    $detail->save();

                    if ($itemData['result_status'] === 'tidak_sesuai') {
                        $canUpdateFinding = ! $finding
                            || ($finding->status === 'open' && $finding->reviewed_by === null);

                        if ($canUpdateFinding) {
                            $finding ??= new MaintenanceFinding;
                            $finding->fill([
                                'maintenance_check_id' => $check->id,
                                'maintenance_check_detail_id' => $detail->id,
                                'machine_id' => $lockedSchedule->machine_id,
                                'maintenance_item_id' => $itemData['maintenance_item_id'],
                                'reported_by' => $request->user()->id,
                                'finding_date' => now()->toDateString(),
                                'finding_description' => $itemData['finding_description'],
                                'follow_up' => $itemData['follow_up'] ?? null,
                                'status' => 'open',
                            ]);

                            if (isset($itemData['finding_photo'])) {
                                $previousFindingPhoto = $finding->photo;
                                $finding->photo = $itemData['finding_photo']->store('findings', 'public');
                                $newPhotoPaths[] = $finding->photo;

                                if ($previousFindingPhoto) {
                                    $replacedPhotoPaths[] = $previousFindingPhoto;
                                }
                            }

                            $finding->save();
                        }
                    } elseif ($finding && $finding->status === 'open' && $finding->reviewed_by === null) {
                        if ($finding->photo) {
                            $replacedPhotoPaths[] = $finding->photo;
                        }

                        $finding->delete();
                    }
                }

                $submittedResultStatuses = collect($validated['items'])->pluck('result_status');
                $hasStoredNonconformity = $check->details()
                    ->where('result_status', 'tidak_sesuai')
                    ->exists();
                $overallStatus = match (true) {
                    $submittedResultStatuses->contains('belum_diperiksa') => 'incomplete',
                    $hasStoredNonconformity => 'has_finding',
                    default => 'normal',
                };

                $check->update([
                    'checked_by' => $request->user()->id,
                    'general_note' => $validated['general_note'] ?? null,
                    'overall_status' => $overallStatus,
                    'end_time' => $isFinalSubmission ? now()->format('H:i:s') : null,
                ]);
                $lockedSchedule->update(['status' => $isFinalSubmission ? 'completed' : 'in_progress']);
            });
        } catch (\Throwable $exception) {
            Storage::disk('public')->delete($newPhotoPaths);

            throw $exception;
        }

        Storage::disk('public')->delete($replacedPhotoPaths);

        $message = $isFinalSubmission
            ? 'Pemeriksaan berhasil disimpan.'
            : 'Draft pemeriksaan berhasil disimpan.';

        return redirect()
            ->route($this->todayRouteName($request), ['schedule' => $schedule->id])
            ->with('success', $message);
    }

    public function show(Request $request, MaintenanceCheck $check): View
    {
        $user = $request->user();

        if ($user->role === UserRole::Petugas && $check->checked_by !== $user->id) {
            abort(404);
        }

        $check->load([
            'schedule.machine',
            'schedule.maintenanceCategory',
            'schedule.assignedUser',
            'details.item',
            'details.finding',
        ]);
        $selectedSchedule = $check->schedule;
        $selectedSchedule->setRelation('check', $check);

        return view('inspections.today', [
            ...$this->layoutData($user),
            'activeMenu' => 'riwayat-pemeriksaan',
            'pageTitle' => 'Detail Riwayat Pemeriksaan',
            'pageDescription' => 'Lihat hasil pemeriksaan preventive maintenance yang telah dilakukan.',
            'schedules' => collect(),
            'selectedSchedule' => $selectedSchedule,
            'check' => $check,
            'checklistItems' => $check->details->pluck('item')->filter()->values(),
            'checkDetails' => $check->details->keyBy('maintenance_item_id'),
            'canEditInspection' => false,
            'frequencyLabels' => $this->frequencyLabels(),
            'scheduleStatusLabels' => $this->scheduleStatusLabels(),
            'resultStatusLabels' => $this->resultStatusLabels(),
            'inputTypeLabels' => $this->inputTypeLabels(),
        ]);
    }

    /** @return Builder<MaintenanceSchedule> */
    private function visibleSchedules(User $user): Builder
    {
        return MaintenanceSchedule::query()
            ->when(
                $user->role === UserRole::Petugas,
                fn (Builder $query): Builder => $query->where('assigned_user_id', $user->id),
            );
    }

    private function ensureScheduleIsVisible(User $user, MaintenanceSchedule $schedule): void
    {
        if ($user->role === UserRole::Petugas && $schedule->assigned_user_id !== $user->id) {
            abort(404);
        }
    }

    private function ensureScheduleIsEditable(User $user, MaintenanceSchedule $schedule): void
    {
        if ($user->role !== UserRole::Petugas) {
            abort(403);
        }

        if ($schedule->assigned_user_id !== $user->id) {
            abort(404);
        }

        if (! $schedule->scheduled_date->isToday()) {
            abort(403);
        }

        if ($schedule->check()->where('verification_status', '!=', 'waiting')->exists()) {
            abort(403);
        }
    }

    private function todayRouteName(Request $request): string
    {
        return $request->input('context') === 'mobile' || $request->routeIs('mobile.*')
            ? 'mobile.inspection-today'
            : 'inspections.today';
    }

    /** @return array<string, string> */
    private function layoutData(User $user): array
    {
        $initials = Str::of($user->name)
            ->trim()
            ->explode(' ')
            ->filter()
            ->take(2)
            ->map(fn (string $name): string => Str::upper(Str::substr($name, 0, 1)))
            ->implode('');

        return [
            'activeMenu' => 'pemeriksaan-hari-ini',
            'pageTitle' => 'Pemeriksaan Hari Ini',
            'pageDescription' => 'Isi checklist preventive maintenance berdasarkan jadwal hari ini.',
            'userName' => $user->name,
            'userRole' => $user->role->label(),
            'userInitials' => $initials,
        ];
    }

    /** @return array<string, string> */
    private function frequencyLabels(): array
    {
        return [
            'daily' => 'Harian',
            'weekly' => 'Mingguan',
            'monthly' => 'Bulanan',
            'quarterly' => 'Triwulan',
            'semiannual' => 'Semesteran',
            'annual' => 'Tahunan',
        ];
    }

    /** @return array<string, string> */
    private function scheduleStatusLabels(): array
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
    private function resultStatusLabels(): array
    {
        return [
            'sesuai' => 'Sesuai',
            'tidak_sesuai' => 'Tidak Sesuai',
            'tidak_dijadwalkan' => 'Tidak Dijadwalkan',
            'belum_diperiksa' => 'Belum Diperiksa',
        ];
    }

    /** @return array<string, string> */
    private function inputTypeLabels(): array
    {
        return [
            'sesuai_tidak_sesuai' => 'Sesuai / Tidak Sesuai',
            'nilai_aktual' => 'Nilai Aktual',
            'catatan' => 'Catatan',
        ];
    }
}
