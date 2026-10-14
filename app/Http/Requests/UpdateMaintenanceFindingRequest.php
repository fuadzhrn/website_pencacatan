<?php

namespace App\Http\Requests;

use App\Models\MaintenanceFinding;
use App\UserRole;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;

class UpdateMaintenanceFindingRequest extends FormRequest
{
    public function authorize(): bool
    {
        $user = $this->user();
        $finding = $this->route('finding');

        return $user
            && $user->role === UserRole::Petugas
            && $finding instanceof MaintenanceFinding
            && $finding->reported_by === $user->id
            && $finding->status === 'open'
            && $finding->reviewed_by === null;
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
                $finding = $this->route('finding');

                if (! $finding instanceof MaintenanceFinding) {
                    return;
                }

                $expected = [
                    'maintenance_check_id' => $finding->maintenance_check_id,
                    'maintenance_check_detail_id' => $finding->maintenance_check_detail_id,
                    'machine_id' => $finding->machine_id,
                    'maintenance_item_id' => $finding->maintenance_item_id,
                ];

                foreach ($expected as $field => $value) {
                    if ($this->integer($field) !== $value) {
                        $validator->errors()->add($field, 'Relasi temuan tidak boleh diubah.');
                    }
                }

                if ($this->input('status') !== 'open') {
                    $validator->errors()->add('status', 'Petugas tidak dapat mengubah status temuan.');
                }
            },
        ];
    }
}
