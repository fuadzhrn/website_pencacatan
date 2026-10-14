<?php

namespace App\Http\Controllers;

use App\Http\Requests\RejectInspectionRequest;
use App\Http\Requests\VerifyInspectionRequest;
use App\Models\Machine;
use App\Models\MaintenanceCheck;
use App\Models\MaintenanceSchedule;
use App\Models\User;
use App\UserRole;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class VerificationController extends Controller
{
    public function index(Request $request): View
    {
        $validated = $request->validate([
            'start_date' => ['nullable', 'date'],
            'end_date' => ['nullable', 'date', 'after_or_equal:start_date'],
            'machine_id' => ['nullable', 'integer', 'exists:machines,id'],
            'officer_id' => ['nullable', 'integer', 'exists:users,id'],
            'verification_status' => ['nullable', Rule::in(['waiting', 'verified', 'rejected'])],
            'overall_status' => ['nullable', Rule::in(['normal', 'has_finding', 'incomplete'])],
            'search' => ['nullable', 'string', 'max:100'],
        ]);

        $user = $request->user();
        $isVerificationQueue = $request->routeIs('verifications.*');
        $visibleChecks = $this->visibleCompletedChecks($user);
        $summary = [
            'total' => (clone $visibleChecks)->count(),
            'waiting' => (clone $visibleChecks)->where('verification_status', 'waiting')->count(),
            'verified' => (clone $visibleChecks)->where('verification_status', 'verified')->count(),
            'rejected' => (clone $visibleChecks)->where('verification_status', 'rejected')->count(),
        ];

        $checks = $visibleChecks
            ->with([
                'machine',
                'schedule.maintenanceCategory',
                'checkedBy',
                'verifiedBy',
                'details.item',
                'findings.item',
            ])
            ->when(
                $isVerificationQueue && ! isset($validated['verification_status']),
                fn (Builder $query): Builder => $query->where('verification_status', 'waiting'),
            )
            ->when(isset($validated['start_date']), fn (Builder $query): Builder => $query->whereDate('check_date', '>=', $validated['start_date']))
            ->when(isset($validated['end_date']), fn (Builder $query): Builder => $query->whereDate('check_date', '<=', $validated['end_date']))
            ->when(isset($validated['machine_id']), fn (Builder $query): Builder => $query->where('machine_id', $validated['machine_id']))
            ->when(isset($validated['officer_id']), fn (Builder $query): Builder => $query->where('checked_by', $validated['officer_id']))
            ->when(isset($validated['verification_status']), fn (Builder $query): Builder => $query->where('verification_status', $validated['verification_status']))
            ->when(isset($validated['overall_status']), fn (Builder $query): Builder => $query->where('overall_status', $validated['overall_status']))
            ->when(isset($validated['search']), function (Builder $query) use ($validated): void {
                $search = '%'.str_replace(['%', '_'], ['\\%', '\\_'], trim($validated['search'])).'%';
                $query->where(function (Builder $query) use ($search): void {
                    $query->whereHas('machine', function (Builder $machineQuery) use ($search): void {
                        $machineQuery->where('equipment_name', 'like', $search)
                            ->orWhere('serial_number', 'like', $search);
                    })->orWhereHas('checkedBy', fn (Builder $officerQuery): Builder => $officerQuery->where('name', 'like', $search));
                });
            })
            ->latest('check_date')
            ->latest('id')
            ->get();

        return view('verifications.index', [
            ...$this->layoutData($user, $isVerificationQueue),
            'checks' => $checks,
            'machines' => Machine::query()
                ->whereIn('id', $this->visibleCompletedChecks($user)->select('machine_id'))
                ->orderBy('equipment_name')
                ->get(),
            'officers' => User::query()
                ->whereIn('id', $this->visibleCompletedChecks($user)->select('checked_by'))
                ->orderBy('name')
                ->get(),
            'summary' => $summary,
            'isVerificationQueue' => $isVerificationQueue,
            'verificationLabels' => $this->verificationLabels(),
            'overallLabels' => $this->overallLabels(),
        ]);
    }

    public function show(Request $request, MaintenanceCheck $check): View
    {
        $check->load([
            'machine',
            'schedule.maintenanceCategory',
            'checkedBy',
            'verifiedBy',
            'details.item.category',
            'details.finding',
            'findings.item',
            'findings.reportedBy',
        ]);

        return view('verifications.show', [
            ...$this->layoutData($request->user(), true),
            'check' => $check,
            'isVerificationQueue' => true,
            'verificationLabels' => $this->verificationLabels(),
            'overallLabels' => $this->overallLabels(),
        ]);
    }

    public function verify(VerifyInspectionRequest $request, MaintenanceCheck $check): RedirectResponse
    {
        return $this->recordDecision($request, $check, 'verified', 'Pemeriksaan berhasil diverifikasi.');
    }

    public function reject(RejectInspectionRequest $request, MaintenanceCheck $check): RedirectResponse
    {
        return $this->recordDecision($request, $check, 'rejected', 'Pemeriksaan berhasil ditolak/revisi.');
    }

    private function recordDecision(
        VerifyInspectionRequest|RejectInspectionRequest $request,
        MaintenanceCheck $check,
        string $status,
        string $successMessage,
    ): RedirectResponse {
        return DB::transaction(function () use ($request, $check, $status, $successMessage): RedirectResponse {
            $lockedSchedule = MaintenanceSchedule::query()
                ->lockForUpdate()
                ->findOrFail($check->maintenance_schedule_id);
            $lockedCheck = MaintenanceCheck::query()->lockForUpdate()->findOrFail($check->id);

            if ($lockedCheck->verification_status !== 'waiting') {
                $message = $lockedCheck->verification_status === 'verified'
                    ? 'Pemeriksaan sudah diverifikasi sebelumnya.'
                    : 'Pemeriksaan sudah diproses sebelumnya.';

                return back()->with('error', $message);
            }

            if ($lockedSchedule->status !== 'completed'
                || $lockedCheck->end_time === null
                || $lockedCheck->overall_status === 'incomplete') {
                return back()->with('error', 'Pemeriksaan belum selesai dan tidak dapat diverifikasi.');
            }

            $lockedCheck->update([
                'verification_status' => $status,
                'verified_by' => $request->user()->id,
                'verified_at' => now(),
                'supervisor_note' => $request->validated('supervisor_note'),
            ]);

            return back()->with('success', $successMessage);
        });
    }

    /** @return Builder<MaintenanceCheck> */
    private function visibleCompletedChecks(User $user): Builder
    {
        return MaintenanceCheck::query()
            ->whereNotNull('end_time')
            ->when(
                $user->role === UserRole::Petugas,
                fn (Builder $query): Builder => $query->where('checked_by', $user->id),
            );
    }

    /** @return array<string, string> */
    private function verificationLabels(): array
    {
        return [
            'waiting' => 'Menunggu Verifikasi',
            'verified' => 'Terverifikasi',
            'rejected' => 'Ditolak/Revisi',
        ];
    }

    /** @return array<string, string> */
    private function overallLabels(): array
    {
        return [
            'normal' => 'Normal',
            'has_finding' => 'Terdapat Temuan',
            'incomplete' => 'Belum Lengkap',
        ];
    }

    /** @return array<string, string> */
    private function layoutData(User $user, bool $isVerificationQueue): array
    {
        $initials = Str::of($user->name)->trim()->explode(' ')->filter()->take(2)
            ->map(fn (string $name): string => Str::upper(Str::substr($name, 0, 1)))->implode('');

        return [
            'activeMenu' => $isVerificationQueue ? 'verifikasi-pemeriksaan' : 'riwayat-pemeriksaan',
            'pageTitle' => $isVerificationQueue ? 'Verifikasi Pemeriksaan' : 'Riwayat Pemeriksaan',
            'pageDescription' => $isVerificationQueue
                ? 'Tinjau dan verifikasi hasil preventive maintenance mesin X-Ray.'
                : 'Lihat pemeriksaan preventive maintenance yang sudah dilakukan.',
            'userName' => $user->name,
            'userRole' => $user->role->label(),
            'userInitials' => $initials,
        ];
    }
}
