<?php

namespace App\Http\Requests;

use App\Models\MaintenanceCheck;
use App\UserRole;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;

class StoreMaintenanceFindingRequest extends FormRequest
{
    public function authorize(): bool
    {
        $user = $this->user();

        if (! $user || $user->role !== UserRole::Petugas) {
            return false;
        }

        return MaintenanceCheck::query()
            ->whereKey($this->integer('maintenance_check_id'))
            ->where('checked_by', $user->id)
            ->exists();
    }

    /** @return array<string, array<mixed>> */
    public function rules(): array
    {
        return [
            'maintenance_check_id' => ['required', 'integer', 'exists:maintenance_checks,id'],
            'maintenance_check_detail_id' => ['required', 'integer', 'exists:maintenance_check_details,id'],
            'machine_id' => ['required', 'integer', 'exists:machines,id'],
            'maintenance_item_id' => ['required', 'integer', 'exists:maintenance_items,id'],
            'finding_description' => ['required', 'string', 'max:5000'],
            'follow_up' => ['nullable', 'string', 'max:5000'],
            'photo' => ['nullable', 'image', 'mimes:jpg,jpeg,png,webp', 'max:2048'],
            'status' => ['required', Rule::in(['open', 'review', 'resolved'])],
            'context' => ['nullable', Rule::in(['desktop', 'mobile'])],
        ];
    }

    /** @return array<int, callable> */
    public function after(): array
    {
        return [
            function (Validator $validator): void {
                $check = MaintenanceCheck::query()->find($this->integer('maintenance_check_id'));
                $detail = $check?->details()->find($this->integer('maintenance_check_detail_id'));

                if (! $check || ! $detail
                    || $check->machine_id !== $this->integer('machine_id')
                    || $detail->maintenance_item_id !== $this->integer('maintenance_item_id')) {
                    $validator->errors()->add('maintenance_check_detail_id', 'Detail pemeriksaan tidak sesuai.');
                }

                if ($detail && $detail->result_status !== 'tidak_sesuai') {
                    $validator->errors()->add('maintenance_check_detail_id', 'Temuan hanya dapat dibuat untuk item tidak sesuai.');
                }

                if ($this->input('status') !== 'open') {
                    $validator->errors()->add('status', 'Petugas hanya dapat membuat temuan berstatus Open.');
                }
            },
        ];
    }
}
