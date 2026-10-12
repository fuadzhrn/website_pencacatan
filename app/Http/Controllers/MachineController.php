<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreMachineRequest;
use App\Http\Requests\UpdateMachineRequest;
use App\Models\Machine;
use App\Models\User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Str;
use Illuminate\View\View;

class MachineController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request): View
    {
        return view('machines.index', [
            ...$this->layoutData($request),
            ...$this->machineListData($request),
            'mobileMachineMode' => 'index',
        ]);
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create(Request $request): View
    {
        return view('machines.create', [
            ...$this->layoutData($request),
            'statusOptions' => $this->statusOptions(),
            'mobileMachineMode' => 'create',
        ]);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(StoreMachineRequest $request): RedirectResponse
    {
        Machine::query()->create($request->safe()->except(['context']));

        return $this->redirectToIndex($request)
            ->with('success', 'Mesin berhasil ditambahkan.');
    }

    /**
     * Display the specified resource.
     */
    public function show(Request $request, Machine $machine): View
    {
        return view('machines.show', [
            ...$this->layoutData($request),
            'statusOptions' => $this->statusOptions(),
            'machine' => $machine->loadCount(['schedules', 'checks', 'findings']),
            'mobileMachineMode' => 'show',
        ]);
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(Request $request, Machine $machine): View
    {
        return view('machines.edit', [
            ...$this->layoutData($request),
            'statusOptions' => $this->statusOptions(),
            'machine' => $machine,
            'mobileMachineMode' => 'edit',
        ]);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(UpdateMachineRequest $request, Machine $machine): RedirectResponse
    {
        $machine->update($request->safe()->except(['context']));

        return $this->redirectToIndex($request)
            ->with('success', 'Mesin berhasil diperbarui.');
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Request $request, Machine $machine): RedirectResponse
    {
        if ($machine->schedules()->exists() || $machine->checks()->exists() || $machine->findings()->exists()) {
            return $this->redirectToIndex($request)
                ->with('error', 'Mesin tidak dapat dihapus karena memiliki jadwal atau riwayat maintenance.');
        }

        $machine->delete();

        return $this->redirectToIndex($request)
            ->with('success', 'Mesin berhasil dihapus.');
    }

    public function toggleStatus(Request $request, Machine $machine): RedirectResponse
    {
        $machine->update([
            'status' => $machine->status === 'active' ? 'inactive' : 'active',
        ]);

        return $this->redirectToIndex($request)
            ->with('success', 'Status mesin berhasil diubah.');
    }

    /** @return array{activeMenu: string, pageTitle: string, pageDescription: string, userName: string, userRole: string, userInitials: string} */
    private function layoutData(Request $request): array
    {
        /** @var User $authenticatedUser */
        $authenticatedUser = $request->user();
        $initials = Str::of($authenticatedUser->name)
            ->trim()
            ->explode(' ')
            ->filter()
            ->take(2)
            ->map(fn (string $name): string => Str::upper(Str::substr($name, 0, 1)))
            ->implode('');

        return [
            'activeMenu' => 'data-mesin',
            'pageTitle' => 'Data Mesin',
            'pageDescription' => 'Kelola data mesin/peralatan X-Ray yang digunakan untuk preventive maintenance.',
            'userName' => $authenticatedUser->name,
            'userRole' => $authenticatedUser->role->label(),
            'userInitials' => $initials,
        ];
    }

    /** @return array{machines: LengthAwarePaginator<int, Machine>, summary: array{total: int, active: int, inactive: int, maintenance: int}, filters: array{search: string, status: string, airport: string}, airports: list<string>, statusOptions: array<string, string>} */
    private function machineListData(Request $request): array
    {
        $search = $request->string('search')->trim()->toString();
        $status = $request->string('status')->toString();
        $airport = $request->string('airport')->trim()->toString();
        $statusOptions = $this->statusOptions();

        $machines = Machine::query()
            ->when($search !== '', function (Builder $query) use ($search): void {
                $query->where(function (Builder $query) use ($search): void {
                    $query
                        ->where('equipment_name', 'like', '%'.$search.'%')
                        ->orWhere('serial_number', 'like', '%'.$search.'%')
                        ->orWhere('location', 'like', '%'.$search.'%');
                });
            })
            ->when(array_key_exists($status, $statusOptions), fn (Builder $query): Builder => $query->where('status', $status))
            ->when($airport !== '', fn (Builder $query): Builder => $query->where('airport', $airport))
            ->orderBy('equipment_name')
            ->paginate(10)
            ->withQueryString();

        return [
            'machines' => $machines,
            'summary' => [
                'total' => Machine::query()->count(),
                'active' => Machine::query()->where('status', 'active')->count(),
                'inactive' => Machine::query()->where('status', 'inactive')->count(),
                'maintenance' => Machine::query()->where('status', 'maintenance')->count(),
            ],
            'filters' => compact('search', 'status', 'airport'),
            'airports' => Machine::query()
                ->whereNotNull('airport')
                ->where('airport', '!=', '')
                ->distinct()
                ->orderBy('airport')
                ->pluck('airport')
                ->all(),
            'statusOptions' => $statusOptions,
        ];
    }

    /** @return array<string, string> */
    private function statusOptions(): array
    {
        return [
            'active' => 'Aktif',
            'inactive' => 'Nonaktif',
            'maintenance' => 'Maintenance',
        ];
    }

    private function redirectToIndex(Request $request): RedirectResponse
    {
        return redirect()->route($request->input('context') === 'mobile' ? 'mobile.machines' : 'machines.index');
    }
}
