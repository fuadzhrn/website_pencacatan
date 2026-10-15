<?php

namespace App\Http\Controllers;

use App\Http\Requests\FilterInspectionHistoryRequest;
use App\Http\Requests\RejectInspectionRequest;
use App\Http\Requests\VerifyInspectionRequest;
use App\Models\Machine;
use App\Models\MaintenanceCategory;
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
use Symfony\Component\HttpFoundation\StreamedResponse;

class VerificationController extends Controller
{
    public function index(FilterInspectionHistoryRequest $request): View
    {
        $validated = $request->validated();
        $user = $request->user();
        $isVerificationQueue = $request->routeIs('verifications.*');
        $visibleChecks = $this->visibleSubmittedChecks($user);
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
            ])
            ->when(
                $isVerificationQueue && ! isset($validated['verification_status']),
                fn (Builder $query): Builder => $query->where('verification_status', 'waiting'),
            )
            ->when(isset($validated['start_date']), fn (Builder $query): Builder => $query->whereDate('check_date', '>=', $validated['start_date']))
            ->when(isset($validated['end_date']), fn (Builder $query): Builder => $query->whereDate('check_date', '<=', $validated['end_date']))
            ->when(isset($validated['machine_id']), fn (Builder $query): Builder => $query->where('machine_id', $validated['machine_id']))
            ->when(isset($validated['officer_id']), fn (Builder $query): Builder => $query->where('checked_by', $validated['officer_id']))
            ->when(isset($validated['category_id']), function (Builder $query) use ($validated): void {
                $query->whereHas(
                    'schedule',
                    fn (Builder $scheduleQuery): Builder => $scheduleQuery->where('maintenance_category_id', $validated['category_id']),
                );
            })
            ->when(isset($validated['verification_status']), fn (Builder $query): Builder => $query->where('verification_status', $validated['verification_status']))
            ->when(isset($validated['overall_status']), fn (Builder $query): Builder => $query->where('overall_status', $validated['overall_status']))
            ->when(isset($validated['search']), function (Builder $query) use ($validated): void {
                $search = '%'.str_replace(['%', '_'], ['\\%', '\\_'], trim($validated['search'])).'%';
                $query->whereHas('machine', function (Builder $machineQuery) use ($search): void {
                    $machineQuery->where('equipment_name', 'like', $search)
                        ->orWhere('serial_number', 'like', $search);
                });
            })
            ->latest('check_date')
            ->latest('id')
            ->paginate(15)
            ->withQueryString();

        return view('verifications.index', [
            ...$this->layoutData($user, $isVerificationQueue),
            'checks' => $checks,
            'machines' => Machine::query()
                ->whereIn('id', $this->visibleSubmittedChecks($user)->select('machine_id'))
                ->orderBy('equipment_name')
                ->get(),
            'officers' => User::query()
                ->whereIn('id', $this->visibleSubmittedChecks($user)->select('checked_by'))
                ->orderBy('name')
                ->get(),
            'categories' => MaintenanceCategory::query()
                ->whereIn(
                    'id',
                    MaintenanceSchedule::query()
                        ->whereIn('id', $this->visibleSubmittedChecks($user)->select('maintenance_schedule_id'))
                        ->select('maintenance_category_id'),
                )
                ->orderBy('name')
                ->get(),
            'summary' => $summary,
            'isVerificationQueue' => $isVerificationQueue,
            'isHistoryDetail' => false,
            'verificationLabels' => $this->verificationLabels(),
            'overallLabels' => $this->overallLabels(),
            'frequencyLabels' => $this->frequencyLabels(),
        ]);
    }

    public function show(Request $request, MaintenanceCheck $check): View
    {
        $check = $this->submittedCheckFor($request->user(), $check);

        return view('verifications.show', [
            ...$this->layoutData($request->user(), true),
            'check' => $check,
            'isVerificationQueue' => true,
            'isHistoryDetail' => true,
            'verificationLabels' => $this->verificationLabels(),
            'overallLabels' => $this->overallLabels(),
            'frequencyLabels' => $this->frequencyLabels(),
        ]);
    }

    public function historyShow(Request $request, MaintenanceCheck $check): View
    {
        $check = $this->submittedCheckFor($request->user(), $check);

        return view('verifications.show', [
            ...$this->layoutData($request->user(), false),
            'check' => $check,
            'isVerificationQueue' => false,
            'isHistoryDetail' => true,
            'verificationLabels' => $this->verificationLabels(),
            'overallLabels' => $this->overallLabels(),
            'frequencyLabels' => $this->frequencyLabels(),
        ]);
    }

    public function detailPhoto(
        Request $request,
        MaintenanceCheck $check,
        MaintenanceCheckDetail $detail,
    ): StreamedResponse {
        $visibleCheck = $this->submittedCheckFor($request->user(), $check);

        abort_unless($detail->maintenance_check_id === $visibleCheck->id && filled($detail->photo), 404);

        return $this->photoResponse($detail->photo);
    }

    public function findingPhoto(
        Request $request,
        MaintenanceCheck $check,
        MaintenanceFinding $finding,
    ): StreamedResponse {
        $visibleCheck = $this->submittedCheckFor($request->user(), $check);

        abort_unless($finding->maintenance_check_id === $visibleCheck->id && filled($finding->photo), 404);

        return $this->photoResponse($finding->photo);
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
    private function visibleSubmittedChecks(User $user): Builder
    {
        return MaintenanceCheck::query()
            ->whereNotNull('end_time')
            ->when(
                $user->role === UserRole::Petugas,
                fn (Builder $query): Builder => $query->where('checked_by', $user->id),
            );
    }

    private function submittedCheckFor(User $user, MaintenanceCheck $check): MaintenanceCheck
    {
        return $this->visibleSubmittedChecks($user)
            ->with([
                'machine',
                'schedule.maintenanceCategory',
                'schedule.assignedUser',
                'checkedBy',
                'verifiedBy',
                'details.item.category',
                'details.findings',
                'findings.item',
                'findings.reportedBy',
                'findings.reviewedBy',
            ])
            ->whereKey($check->getKey())
            ->firstOrFail();
    }

    private function photoResponse(string $path): StreamedResponse
    {
        $disk = Storage::disk('local');

        abort_unless($disk->exists($path), 404);

        return $disk->response($path);
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
            'semester' => 'Semesteran',
            'yearly' => 'Tahunan',
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
