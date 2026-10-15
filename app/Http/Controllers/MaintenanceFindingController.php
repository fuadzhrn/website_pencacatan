<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreMaintenanceFindingRequest;
use App\Http\Requests\UpdateFindingStatusRequest;
use App\Http\Requests\UpdateMaintenanceFindingRequest;
use App\Models\Machine;
use App\Models\MaintenanceCheck;
use App\Models\MaintenanceCheckDetail;
use App\Models\MaintenanceFinding;
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

class MaintenanceFindingController extends Controller
{
    public function index(Request $request): View
    {
        $user = $request->user();
        $visibleFindings = $this->visibleFindings($user);
        $summary = [
            'total' => (clone $visibleFindings)->count(),
            'open' => (clone $visibleFindings)->where('status', 'open')->count(),
            'review' => (clone $visibleFindings)->where('status', 'review')->count(),
            'resolved' => (clone $visibleFindings)->where('status', 'resolved')->count(),
        ];

        $status = $request->string('status')->toString();
        $findings = $visibleFindings
            ->with(['machine', 'item', 'reportedBy', 'reviewedBy', 'checkDetail'])
            ->when($request->filled('machine_id'), fn (Builder $query): Builder => $query->where('machine_id', $request->integer('machine_id')))
            ->when(in_array($status, ['open', 'review', 'resolved'], true), fn (Builder $query): Builder => $query->where('status', $status))
            ->when($request->filled('date'), fn (Builder $query): Builder => $query->whereDate('finding_date', $request->date('date')))
            ->when($request->filled('search'), function (Builder $query) use ($request): void {
                $search = '%'.str_replace(['%', '_'], ['\\%', '\\_'], $request->string('search')->trim()->toString()).'%';
                $query->where(function (Builder $query) use ($search): void {
                    $query->where('finding_description', 'like', $search)
                        ->orWhere('follow_up', 'like', $search)
                        ->orWhereHas('machine', fn (Builder $machineQuery): Builder => $machineQuery->where('equipment_name', 'like', $search))
                        ->orWhereHas('item', fn (Builder $itemQuery): Builder => $itemQuery->where('item_name', 'like', $search));
                });
            })
            ->latest('finding_date')
            ->latest('id')
            ->get();

        $machines = Machine::query()
            ->whereIn('id', $this->visibleFindings($user)->select('machine_id'))
            ->orderBy('equipment_name')
            ->get();

        $availableDetails = collect();
        if ($user->role === UserRole::Petugas) {
            $availableDetails = MaintenanceCheckDetail::query()
                ->where('result_status', 'tidak_sesuai')
                ->whereHas('check', fn (Builder $query): Builder => $query->where('checked_by', $user->id))
                ->whereDoesntHave('findings')
                ->with(['check.machine', 'item'])
                ->latest('id')
                ->get();
        }

        return view('findings.database-index', [
            ...$this->layoutData($user),
            'findings' => $findings,
            'machines' => $machines,
            'summary' => $summary,
            'findingActiveCount' => $summary['open'] + $summary['review'],
            'availableDetails' => $availableDetails,
            'statusLabels' => $this->statusLabels(),
        ]);
    }

    public function show(Request $request, MaintenanceFinding $finding): View
    {
        $this->ensureVisible($request->user(), $finding);
        $finding->load(['machine', 'item', 'reportedBy', 'reviewedBy', 'checkDetail']);

        return view('findings.show', [
            ...$this->layoutData($request->user()),
            'finding' => $finding,
            'statusLabels' => $this->statusLabels(),
        ]);
    }

    public function photo(Request $request, MaintenanceFinding $finding): StreamedResponse
    {
        $this->ensureVisible($request->user(), $finding);

        abort_unless(filled($finding->photo), 404);

        $disk = Storage::disk('local');
        abort_unless($disk->exists($finding->photo), 404);

        return $disk->response($finding->photo);
    }

    public function store(StoreMaintenanceFindingRequest $request): RedirectResponse
    {
        $validated = $request->validated();
        $newPhotoPath = null;

        try {
            DB::transaction(function () use ($request, $validated, &$newPhotoPath): void {
                $check = MaintenanceCheck::query()->lockForUpdate()->findOrFail($validated['maintenance_check_id']);
                $detail = MaintenanceCheckDetail::query()->lockForUpdate()->findOrFail($validated['maintenance_check_detail_id']);

                if ($check->checked_by !== $request->user()->id
                    || $detail->maintenance_check_id !== $check->id
                    || $check->machine_id !== $validated['machine_id']
                    || $detail->maintenance_item_id !== $validated['maintenance_item_id']
                    || $detail->result_status !== 'tidak_sesuai') {
                    abort(403);
                }

                if ($request->hasFile('photo')) {
                    $newPhotoPath = $request->file('photo')->store('findings', 'local');
                }

                if (MaintenanceFinding::query()->where('maintenance_check_detail_id', $detail->id)->lockForUpdate()->exists()) {
                    abort(409, 'Temuan untuk detail pemeriksaan ini sudah tersedia.');
                }

                MaintenanceFinding::query()->create([
                    'maintenance_check_id' => $check->id,
                    'maintenance_check_detail_id' => $detail->id,
                    'machine_id' => $check->machine_id,
                    'maintenance_item_id' => $detail->maintenance_item_id,
                    'reported_by' => $request->user()->id,
                    'finding_date' => now()->toDateString(),
                    'finding_description' => $validated['finding_description'],
                    'follow_up' => $validated['follow_up'] ?? null,
                    'photo' => $newPhotoPath,
                    'status' => 'open',
                ]);
            });
        } catch (\Throwable $exception) {
            if ($newPhotoPath) {
                Storage::disk('local')->delete($newPhotoPath);
            }

            throw $exception;
        }

        return back()->with('success', 'Temuan berhasil dibuat.');
    }

    public function update(UpdateMaintenanceFindingRequest $request, MaintenanceFinding $finding): RedirectResponse
    {
        $validated = $request->validated();
        $newPhotoPath = null;
        $oldPhotoPath = null;

        try {
            DB::transaction(function () use ($request, $finding, $validated, &$newPhotoPath, &$oldPhotoPath): void {
                $lockedFinding = MaintenanceFinding::query()->lockForUpdate()->findOrFail($finding->id);

                if ($lockedFinding->reported_by !== $request->user()->id
                    || $lockedFinding->status !== 'open'
                    || $lockedFinding->reviewed_by !== null) {
                    abort(403);
                }

                $attributes = [
                    'finding_description' => $validated['finding_description'],
                    'follow_up' => $validated['follow_up'] ?? null,
                ];

                if ($request->hasFile('photo')) {
                    $oldPhotoPath = $lockedFinding->photo;
                    $newPhotoPath = $request->file('photo')->store('findings', 'local');
                    $attributes['photo'] = $newPhotoPath;
                }

                $lockedFinding->update($attributes);
            });
        } catch (\Throwable $exception) {
            if ($newPhotoPath) {
                Storage::disk('local')->delete($newPhotoPath);
            }

            throw $exception;
        }

        if ($oldPhotoPath) {
            Storage::disk('local')->delete($oldPhotoPath);
        }

        return back()->with('success', 'Temuan berhasil diperbarui.');
    }

    public function updateStatus(UpdateFindingStatusRequest $request, MaintenanceFinding $finding): RedirectResponse
    {
        $validated = $request->validated();

        DB::transaction(function () use ($request, $finding, $validated): void {
            $lockedFinding = MaintenanceFinding::query()->lockForUpdate()->findOrFail($finding->id);
            $status = $validated['status'];
            $allowedTransitions = [
                'open' => ['open', 'review'],
                'review' => ['review', 'resolved'],
                'resolved' => ['resolved'],
            ];

            if (! in_array($status, $allowedTransitions[$lockedFinding->status] ?? [], true)) {
                abort(409, 'Status temuan sudah berubah. Muat ulang halaman sebelum mencoba kembali.');
            }

            $attributes = ['status' => $status];
            if (in_array($status, ['review', 'resolved'], true)) {
                $attributes['reviewed_by'] = $lockedFinding->reviewed_by ?? $request->user()->id;
                $attributes['reviewed_at'] = $lockedFinding->reviewed_at ?? now();
            }
            if ($status === 'resolved') {
                $attributes['resolved_at'] = $lockedFinding->resolved_at ?? now();
            }

            $lockedFinding->update($attributes);
        });

        return back()->with('success', 'Status temuan berhasil diubah.');
    }

    /** @return Builder<MaintenanceFinding> */
    private function visibleFindings(User $user): Builder
    {
        return MaintenanceFinding::query()->when(
            $user->role === UserRole::Petugas,
            fn (Builder $query): Builder => $query->where('reported_by', $user->id),
        );
    }

    private function ensureVisible(User $user, MaintenanceFinding $finding): void
    {
        if ($user->role === UserRole::Petugas && $finding->reported_by !== $user->id) {
            abort(404);
        }
    }

    /** @return array<string, string> */
    private function statusLabels(): array
    {
        return ['open' => 'Open', 'review' => 'Review', 'resolved' => 'Resolved'];
    }

    /** @return array<string, string> */
    private function layoutData(User $user): array
    {
        $initials = Str::of($user->name)->trim()->explode(' ')->filter()->take(2)
            ->map(fn (string $name): string => Str::upper(Str::substr($name, 0, 1)))->implode('');

        return [
            'activeMenu' => 'temuan',
            'pageTitle' => 'Temuan Pemeriksaan',
            'pageDescription' => 'Kelola temuan dari hasil preventive maintenance mesin X-Ray.',
            'userName' => $user->name,
            'userRole' => $user->role->label(),
            'userInitials' => $initials,
        ];
    }
}
