<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreMaintenanceItemRequest;
use App\Http\Requests\UpdateMaintenanceItemRequest;
use App\Models\MaintenanceItem;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class MaintenanceItemController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(): RedirectResponse
    {
        return redirect()->route('checklists.index');
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(StoreMaintenanceItemRequest $request): RedirectResponse
    {
        MaintenanceItem::query()->create($request->safe()->only([
            'maintenance_category_id',
            'item_name',
            'criteria',
            'input_type',
            'sort_order',
            'status',
        ]));

        return $this->redirectToIndex($request)
            ->with('success', 'Item checklist berhasil ditambahkan.');
    }

    /**
     * Display the specified resource.
     */
    public function show(Request $request, MaintenanceItem $item): RedirectResponse
    {
        return redirect()->route(
            $request->input('context') === 'mobile' ? 'mobile.checklists' : 'checklists.index',
            ['item_detail' => $item->id],
        );
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(UpdateMaintenanceItemRequest $request, MaintenanceItem $item): RedirectResponse
    {
        $item->update($request->safe()->only([
            'maintenance_category_id',
            'item_name',
            'criteria',
            'input_type',
            'sort_order',
            'status',
        ]));

        return $this->redirectToIndex($request)
            ->with('success', 'Item checklist berhasil diperbarui.');
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Request $request, MaintenanceItem $item): RedirectResponse
    {
        if ($item->checkDetails()->exists() || $item->findings()->exists()) {
            return $this->redirectToIndex($request)
                ->with('error', 'Item checklist tidak dapat dihapus karena sudah digunakan pada pemeriksaan atau temuan.');
        }

        $item->delete();

        return $this->redirectToIndex($request)
            ->with('success', 'Item checklist berhasil dihapus.');
    }

    public function toggleStatus(Request $request, MaintenanceItem $item): RedirectResponse
    {
        $item->update([
            'status' => $item->status === 'active' ? 'inactive' : 'active',
        ]);

        return $this->redirectToIndex($request)
            ->with('success', 'Status item checklist berhasil diubah.');
    }

    private function redirectToIndex(Request $request): RedirectResponse
    {
        return redirect()->route($request->input('context') === 'mobile' ? 'mobile.checklists' : 'checklists.index');
    }
}
