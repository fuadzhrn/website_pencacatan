<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreMaintenanceCategoryRequest;
use App\Http\Requests\UpdateMaintenanceCategoryRequest;
use App\Models\MaintenanceCategory;
use App\Models\MaintenanceItem;
use App\Models\User;
use App\Support\MaintenanceLabels;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\View\View;

class MaintenanceCategoryController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request): View
    {
        return view('checklists.index', [
            ...$this->layoutData($request),
            ...$this->checklistData($request),
        ]);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(StoreMaintenanceCategoryRequest $request): RedirectResponse
    {
        MaintenanceCategory::query()->create($request->safe()->only([
            'name',
            'frequency',
            'description',
            'status',
        ]));

        return $this->redirectToIndex($request)
            ->with('success', 'Kategori berhasil ditambahkan.');
    }

    /**
     * Display the specified resource.
     */
    public function show(Request $request, MaintenanceCategory $category): RedirectResponse
    {
        return redirect()->route(
            $request->input('context') === 'mobile' ? 'mobile.checklists' : 'checklists.index',
            ['category_detail' => $category->id],
        );
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(UpdateMaintenanceCategoryRequest $request, MaintenanceCategory $category): RedirectResponse
    {
        $category->update($request->safe()->only([
            'name',
            'frequency',
            'description',
            'status',
        ]));

        return $this->redirectToIndex($request)
            ->with('success', 'Kategori berhasil diperbarui.');
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Request $request, MaintenanceCategory $category): RedirectResponse
    {
        if ($category->items()->exists() || $category->schedules()->exists()) {
            return $this->redirectToIndex($request)
                ->with('error', 'Kategori tidak dapat dihapus karena memiliki item atau jadwal maintenance.');
        }

        $category->delete();

        return $this->redirectToIndex($request)
            ->with('success', 'Kategori berhasil dihapus.');
    }

    public function toggleStatus(Request $request, MaintenanceCategory $category): RedirectResponse
    {
        $category->update([
            'status' => $category->status === 'active' ? 'inactive' : 'active',
        ]);

        return $this->redirectToIndex($request)
            ->with('success', 'Status kategori berhasil diubah.');
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
            'activeMenu' => 'master-checklist',
            'pageTitle' => 'Master Checklist',
            'pageDescription' => 'Kelola kategori dan item pemeriksaan preventive maintenance.',
            'userName' => $authenticatedUser->name,
            'userRole' => $authenticatedUser->role->label(),
            'userInitials' => $initials,
        ];
    }

    /** @return array<string, mixed> */
    private function checklistData(Request $request): array
    {
        $search = $request->string('search')->trim()->toString();
        $category = $request->string('category')->toString();
        $frequency = $request->string('frequency')->toString();
        $inputType = $request->string('input_type')->toString();
        $status = $request->string('status')->toString();
        $frequencyOptions = $this->frequencyOptions();
        $inputTypeOptions = $this->inputTypeOptions();
        $statusOptions = $this->statusOptions();

        $items = MaintenanceItem::query()
            ->with('category')
            ->when($search !== '', function (Builder $query) use ($search): void {
                $query->where(function (Builder $query) use ($search): void {
                    $query
                        ->where('item_name', 'like', '%'.$search.'%')
                        ->orWhere('criteria', 'like', '%'.$search.'%');
                });
            })
            ->when(ctype_digit($category), fn (Builder $query): Builder => $query->where('maintenance_category_id', (int) $category))
            ->when(array_key_exists($frequency, $frequencyOptions), function (Builder $query) use ($frequency): void {
                $query->whereHas('category', fn (Builder $categoryQuery): Builder => $categoryQuery->where('frequency', $frequency));
            })
            ->when(array_key_exists($inputType, $inputTypeOptions), fn (Builder $query): Builder => $query->where('input_type', $inputType))
            ->when(array_key_exists($status, $statusOptions), fn (Builder $query): Builder => $query->where('status', $status))
            ->orderBy('maintenance_category_id')
            ->orderBy('sort_order')
            ->orderBy('item_name')
            ->get();

        return [
            'categories' => MaintenanceCategory::query()
                ->withCount('items')
                ->orderBy('id')
                ->get(),
            'items' => $items,
            'summary' => [
                'categories' => MaintenanceCategory::query()->count(),
                'total' => MaintenanceItem::query()->count(),
                'active' => MaintenanceItem::query()->where('status', 'active')->count(),
                'inactive' => MaintenanceItem::query()->where('status', 'inactive')->count(),
            ],
            'filters' => compact('search', 'category', 'frequency', 'inputType', 'status'),
            'frequencyOptions' => $frequencyOptions,
            'inputTypeOptions' => $inputTypeOptions,
            'statusOptions' => $statusOptions,
            'selectedCategory' => MaintenanceCategory::query()
                ->withCount('items')
                ->find($request->integer('category_detail')),
            'selectedItem' => MaintenanceItem::query()
                ->with('category')
                ->find($request->integer('item_detail')),
            'isMobileChecklist' => $request->routeIs('mobile.*'),
        ];
    }

    /** @return array<string, string> */
    private function frequencyOptions(): array
    {
        return MaintenanceLabels::frequencies();
    }

    /** @return array<string, string> */
    private function inputTypeOptions(): array
    {
        return [
            'sesuai_tidak_sesuai' => 'Sesuai / Tidak Sesuai',
            'nilai_aktual' => 'Nilai Aktual',
            'catatan' => 'Catatan',
        ];
    }

    /** @return array<string, string> */
    private function statusOptions(): array
    {
        return [
            'active' => 'Aktif',
            'inactive' => 'Nonaktif',
        ];
    }

    private function redirectToIndex(Request $request): RedirectResponse
    {
        return redirect()->route($request->input('context') === 'mobile' ? 'mobile.checklists' : 'checklists.index');
    }
}
