<?php

namespace App\Http\Controllers;

use App\Http\Requests\GenerateMaintenanceScheduleRequest;
use App\Http\Requests\StoreMaintenanceScheduleRequest;
use App\Http\Requests\UpdateMaintenanceScheduleRequest;
use App\Http\Requests\UpdateMaintenanceScheduleStatusRequest;
use App\Models\Machine;
use App\Models\MaintenanceCategory;
use App\Models\MaintenanceSchedule;
use App\Models\User;
use App\Services\MaintenanceScheduleService;
use App\UserRole;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\View\View;

class MaintenanceScheduleController extends Controller
{
    public function __construct(public MaintenanceScheduleService $scheduleService) {}

    public function index(Request $request): View
    {
        $this->scheduleService->markOverdue();

        $filters = $this->filters($request);
        $query = $this->visibleScheduleQuery($request)->with(['machine', 'maintenanceCategory', 'assignedUser']);
        $this->applyFilters($query, $filters);

        $summaryQuery = clone $query;
        $schedules = $query->orderBy('scheduled_date')->orderBy('scheduled_time')->paginate(25)->withQueryString();
        $selectedSchedule = null;

        if ($request->filled('schedule_detail')) {
            $selectedSchedule = $this->visibleScheduleQuery($request)
                ->with(['machine', 'maintenanceCategory', 'assignedUser', 'check'])
                ->findOrFail($request->integer('schedule_detail'));
        }

        return view($this->viewName($request), [
            'activeMenu' => 'jadwal-maintenance',
            'pageTitle' => 'Jadwal Maintenance',
            'pageDescription' => 'Pantau jadwal pemeriksaan preventive maintenance mesin X-Ray.',
            'userName' => $request->user()->name,
            'userRole' => $request->user()->role->label(),
            'userInitials' => Str::of($request->user()->name)
                ->trim()
                ->explode(' ')
                ->filter()
                ->take(2)
                ->map(fn (string $name): string => Str::upper(Str::substr($name, 0, 1)))
                ->implode(''),
            'schedules' => $schedules,
            'summary' => [
                'total' => (clone $summaryQuery)->count(),
                'scheduled' => (clone $summaryQuery)->where('status', 'scheduled')->count(),
                'completed' => (clone $summaryQuery)->where('status', 'completed')->count(),
                'overdue' => (clone $summaryQuery)->where('status', 'overdue')->count(),
            ],
            'todaySchedules' => $this->visibleScheduleQuery($request)
                ->with(['machine', 'maintenanceCategory', 'assignedUser'])
                ->whereDate('scheduled_date', today())
                ->orderBy('scheduled_time')
                ->get(),
            'machines' => $this->availableMachines($request),
            'categories' => MaintenanceCategory::query()->orderBy('name')->get(),
            'petugas' => User::query()
                ->where('role', UserRole::Petugas->value)
                ->where('status', 'active')
                ->orderBy('name')
                ->get(),
            'filters' => $filters,
            'statusOptions' => $this->statusOptions(),
            'frequencyOptions' => $this->frequencyOptions(),
            'selectedSchedule' => $selectedSchedule,
            'canManage' => $request->user()->role === UserRole::Admin,
        ]);
    }

    public function create(Request $request): RedirectResponse
    {
        return redirect()->route($this->indexRouteName($request), ['form' => 'create']);
    }

    public function store(StoreMaintenanceScheduleRequest $request): RedirectResponse
    {
        MaintenanceSchedule::query()->create($this->normalizedScheduleAttributes($request->safe()->except(['context', 'form_type'])));

        return redirect()->route($this->indexRouteName($request))->with('success', 'Jadwal berhasil dibuat.');
    }

    public function show(Request $request, MaintenanceSchedule $schedule): RedirectResponse
    {
        $this->ensureScheduleIsVisible($request, $schedule);

        return redirect()->route($this->indexRouteName($request), ['schedule_detail' => $schedule->id]);
    }

    public function edit(Request $request, MaintenanceSchedule $schedule): RedirectResponse
    {
        return redirect()->route($this->indexRouteName($request), [
            'form' => 'edit',
            'schedule_detail' => $schedule->id,
        ]);
    }

    public function update(UpdateMaintenanceScheduleRequest $request, MaintenanceSchedule $schedule): RedirectResponse
    {
        $schedule->update($this->normalizedScheduleAttributes($request->safe()->only([
            'scheduled_date',
            'scheduled_time',
            'assigned_user_id',
            'status',
            'notes',
        ])));

        return redirect()->route($this->indexRouteName($request))->with('success', 'Jadwal berhasil diperbarui.');
    }

    public function destroy(Request $request, MaintenanceSchedule $schedule): RedirectResponse
    {
        if ($schedule->check()->exists()) {
            return back()->with('error', 'Jadwal yang sudah memiliki pemeriksaan tidak dapat dihapus.');
        }

        $schedule->delete();

        return redirect()->route($this->indexRouteName($request))->with('success', 'Jadwal berhasil dihapus.');
    }

    public function generate(GenerateMaintenanceScheduleRequest $request): RedirectResponse
    {
        $result = $this->scheduleService->generate($request->safe()->except(['context', 'form_type']));
        $message = "Jadwal otomatis berhasil digenerate: {$result['created']} dibuat";

        if ($result['skipped'] > 0) {
            $message .= ", {$result['skipped']} jadwal duplikat dilewati";
        }

        return redirect()->route($this->indexRouteName($request))->with('success', $message.'.');
    }

    public function updateStatus(UpdateMaintenanceScheduleStatusRequest $request, MaintenanceSchedule $schedule): RedirectResponse
    {
        $schedule->update(['status' => $request->validated('status')]);

        return redirect()->route($this->indexRouteName($request))->with('success', 'Status jadwal berhasil diubah.');
    }

    /** @return array<string, string|null> */
    private function filters(Request $request): array
    {
        return [
            'month' => $request->string('month')->toString() ?: null,
            'year' => $request->string('year')->toString() ?: null,
            'machine_id' => $request->string('machine_id')->toString() ?: ($request->string('machine')->toString() ?: null),
            'maintenance_category_id' => $request->string('maintenance_category_id')->toString() ?: null,
            'assigned_user_id' => $request->string('assigned_user_id')->toString() ?: null,
            'frequency' => $request->string('frequency')->toString() ?: null,
            'status' => $request->string('status')->toString() ?: null,
            'search' => Str::limit(trim($request->string('search')->toString()), 100, ''),
        ];
    }

    /** @param array<string, string|null> $filters */
    private function applyFilters(Builder $query, array $filters): void
    {
        $query
            ->when($filters['month'], fn (Builder $query, string $month): Builder => $query->whereMonth('scheduled_date', $month))
            ->when($filters['year'], fn (Builder $query, string $year): Builder => $query->whereYear('scheduled_date', $year))
            ->when($filters['machine_id'], fn (Builder $query, string $machineId): Builder => $query->where('machine_id', $machineId))
            ->when($filters['maintenance_category_id'], fn (Builder $query, string $categoryId): Builder => $query->where('maintenance_category_id', $categoryId))
            ->when($filters['assigned_user_id'], fn (Builder $query, string $assignedUserId): Builder => $query->where('assigned_user_id', $assignedUserId))
            ->when($filters['frequency'], fn (Builder $query, string $frequency): Builder => $query->whereHas('maintenanceCategory', fn (Builder $categoryQuery): Builder => $categoryQuery->where('frequency', $frequency)))
            ->when($filters['status'], fn (Builder $query, string $status): Builder => $query->where('status', $status))
            ->when($filters['search'], function (Builder $query, string $search): Builder {
                return $query->where(function (Builder $searchQuery) use ($search): void {
                    $searchQuery
                        ->whereHas('machine', function (Builder $machineQuery) use ($search): void {
                            $machineQuery
                                ->where('equipment_name', 'like', "%{$search}%")
                                ->orWhere('serial_number', 'like', "%{$search}%")
                                ->orWhere('location', 'like', "%{$search}%");
                        })
                        ->orWhereHas('assignedUser', fn (Builder $userQuery): Builder => $userQuery->where('name', 'like', "%{$search}%"));
                });
            });
    }

    private function visibleScheduleQuery(Request $request): Builder
    {
        return MaintenanceSchedule::query()->when(
            $request->user()->role === UserRole::Petugas,
            fn (Builder $query): Builder => $query->where('assigned_user_id', $request->user()->id),
        );
    }

    /** @return Collection<int, Machine> */
    private function availableMachines(Request $request): Collection
    {
        return Machine::query()
            ->when(
                $request->user()->role === UserRole::Petugas,
                fn (Builder $query): Builder => $query->whereHas(
                    'schedules',
                    fn (Builder $scheduleQuery): Builder => $scheduleQuery->where('assigned_user_id', $request->user()->id),
                ),
            )
            ->orderBy('equipment_name')
            ->get();
    }

    private function ensureScheduleIsVisible(Request $request, MaintenanceSchedule $schedule): void
    {
        if ($request->user()->role === UserRole::Petugas && $schedule->assigned_user_id !== $request->user()->id) {
            abort(404);
        }
    }

    private function viewName(Request $request): string
    {
        return 'schedules.index';
    }

    private function indexRouteName(Request $request): string
    {
        return $request->routeIs('mobile.*') || $request->input('context') === 'mobile' ? 'mobile.schedules' : 'schedules.index';
    }

    /** @param array<string, mixed> $attributes
     * @return array<string, mixed>
     */
    private function normalizedScheduleAttributes(array $attributes): array
    {
        if (isset($attributes['scheduled_time']) && strlen($attributes['scheduled_time']) === 5) {
            $attributes['scheduled_time'] .= ':00';
        }

        return $attributes;
    }

    /** @return array<string, string> */
    private function statusOptions(): array
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
    private function frequencyOptions(): array
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
}
