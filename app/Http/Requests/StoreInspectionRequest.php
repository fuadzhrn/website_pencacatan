<?php

namespace App\Http\Requests;

use App\Models\MaintenanceFinding;
use App\Models\MaintenanceSchedule;
use App\UserRole;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;

class StoreInspectionRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        $user = $this->user();
        $schedule = $this->route('schedule');

        if (! $user || $user->role !== UserRole::Petugas || ! $schedule instanceof MaintenanceSchedule) {
            return false;
        }

        return $schedule->assigned_user_id === $user->id
            && $schedule->scheduled_date->isToday()
            && $schedule->status === 'in_progress'
            && ! $schedule->check()->where('verification_status', 'verified')->exists();
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'maintenance_schedule_id' => ['required', 'integer', 'exists:maintenance_schedules,id'],
            'submission_type' => ['required', Rule::in(['draft', 'final'])],
            'general_note' => ['nullable', 'string', 'max:5000'],
            'items' => ['required', 'array', 'min:1'],
            'items.*.maintenance_item_id' => ['required', 'integer', 'distinct', 'exists:maintenance_items,id'],
            'items.*.result_status' => [
                'required',
                Rule::in(['sesuai', 'tidak_sesuai', 'tidak_dijadwalkan', 'belum_diperiksa']),
            ],
            'items.*.actual_value' => ['nullable', 'string', 'max:255'],
            'items.*.note' => ['nullable', 'string', 'max:5000'],
            'items.*.photo' => ['nullable', 'image', 'mimes:jpg,jpeg,png,webp', 'max:2048'],
            'items.*.finding_description' => ['nullable', 'string', 'max:5000'],
            'items.*.follow_up' => ['nullable', 'string', 'max:5000'],
            'items.*.finding_photo' => ['nullable', 'image', 'mimes:jpg,jpeg,png,webp', 'max:2048'],
            'context' => ['nullable', Rule::in(['desktop', 'mobile'])],
        ];
    }

    /** @return array<int, callable> */
    public function after(): array
    {
        return [
            function (Validator $validator): void {
                $schedule = $this->route('schedule');

                if (! $schedule instanceof MaintenanceSchedule) {
                    return;
                }

                if ((int) $this->input('maintenance_schedule_id') !== $schedule->id) {
                    $validator->errors()->add('maintenance_schedule_id', 'Jadwal pemeriksaan tidak sesuai.');
                }

                $expectedItemIds = $schedule->maintenanceCategory
                    ->items()
                    ->where('status', 'active')
                    ->pluck('id')
                    ->map(fn (int $id): int => $id)
                    ->sort()
                    ->values();
                $submittedItems = collect($this->input('items', []));
                $submittedItemIds = $submittedItems
                    ->pluck('maintenance_item_id')
                    ->filter(fn (mixed $id): bool => is_numeric($id))
                    ->map(fn (mixed $id): int => (int) $id)
                    ->sort()
                    ->values();

                if ($submittedItems->count() !== $expectedItemIds->count() || $submittedItemIds->all() !== $expectedItemIds->all()) {
                    $validator->errors()->add('items', 'Item checklist tidak sesuai dengan kategori jadwal.');

                    return;
                }

                if ($this->input('submission_type') === 'final'
                    && $submittedItems->contains(fn (mixed $item): bool => is_array($item)
                        && ($item['result_status'] ?? null) === 'belum_diperiksa')) {
                    $validator->errors()->add('items', 'Masih ada checklist yang belum diisi.');
                }

                $checkId = $schedule->check()->value('id');
                $protectedFindings = $checkId
                    ? MaintenanceFinding::query()
                        ->where('maintenance_check_id', $checkId)
                        ->where(function ($query): void {
                            $query->where('status', '!=', 'open')->orWhereNotNull('reviewed_by');
                        })
                        ->get(['maintenance_item_id'])
                        ->keyBy('maintenance_item_id')
                    : collect();

                foreach ($submittedItems as $key => $item) {
                    if (! is_array($item)) {
                        continue;
                    }

                    $itemId = (int) ($item['maintenance_item_id'] ?? 0);
                    $hasProtectedFinding = $protectedFindings->has($itemId);

                    if (($item['result_status'] ?? null) === 'tidak_sesuai'
                        && blank($item['finding_description'] ?? null)
                        && ! $hasProtectedFinding) {
                        $validator->errors()->add(
                            'items.'.$key.'.finding_description',
                            'Keterangan temuan wajib diisi jika checklist tidak sesuai.',
                        );
                    }

                    if (($item['result_status'] ?? null) !== 'tidak_sesuai'
                        && $hasProtectedFinding) {
                        $validator->errors()->add(
                            'items.'.$key.'.result_status',
                            'Hasil item yang temuannya sudah ditinjau tidak dapat diubah.',
                        );
                    }
                }
            },
        ];
    }

    /** @return array<string, string> */
    public function messages(): array
    {
        return [
            'items.required' => 'Checklist pemeriksaan wajib diisi.',
            'items.*.result_status.required' => 'Hasil setiap item pemeriksaan wajib dipilih.',
            'items.*.photo.image' => 'Foto pemeriksaan harus berupa gambar.',
            'items.*.photo.max' => 'Ukuran foto pemeriksaan maksimal 2 MB.',
            'items.*.finding_photo.image' => 'Foto temuan harus berupa gambar.',
            'items.*.finding_photo.max' => 'Ukuran foto temuan maksimal 2 MB.',
        ];
    }
}
