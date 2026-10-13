<?php

namespace App\Http\Requests;

use App\Models\MaintenanceSchedule;
use App\UserRole;
use Closure;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreMaintenanceScheduleRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return $this->user()?->role === UserRole::Admin;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'machine_id' => ['required', 'integer', Rule::exists('machines', 'id')],
            'maintenance_category_id' => ['required', 'integer', Rule::exists('maintenance_categories', 'id')],
            'scheduled_date' => ['required', 'date', function (string $attribute, mixed $value, Closure $fail): void {
                $exists = MaintenanceSchedule::query()
                    ->where('machine_id', $this->integer('machine_id'))
                    ->where('maintenance_category_id', $this->integer('maintenance_category_id'))
                    ->whereDate('scheduled_date', $value)
                    ->exists();

                if ($exists) {
                    $fail('Jadwal untuk mesin, kategori, dan tanggal tersebut sudah tersedia.');
                }
            }],
            'scheduled_time' => ['nullable', 'date_format:H:i'],
            'assigned_user_id' => [
                'nullable',
                'integer',
                Rule::exists('users', 'id')->where('role', UserRole::Petugas->value),
            ],
            'status' => ['required', Rule::in(['scheduled', 'pending', 'in_progress', 'completed', 'overdue', 'cancelled'])],
            'notes' => ['nullable', 'string', 'max:5000'],
            'context' => ['nullable', Rule::in(['mobile'])],
            'form_type' => ['nullable', Rule::in(['schedule'])],
        ];
    }

    /** @return array<string, string> */
    public function messages(): array
    {
        return [
            'machine_id.required' => 'Mesin wajib dipilih.',
            'machine_id.exists' => 'Mesin yang dipilih tidak ditemukan.',
            'maintenance_category_id.required' => 'Kategori maintenance wajib dipilih.',
            'maintenance_category_id.exists' => 'Kategori maintenance yang dipilih tidak ditemukan.',
            'scheduled_date.required' => 'Tanggal jadwal wajib diisi.',
            'scheduled_date.date' => 'Tanggal jadwal tidak valid.',
            'scheduled_date.unique' => 'Jadwal untuk mesin, kategori, dan tanggal tersebut sudah tersedia.',
            'scheduled_time.date_format' => 'Jam jadwal harus menggunakan format jam dan menit.',
            'assigned_user_id.exists' => 'Petugas yang dipilih tidak valid.',
            'status.required' => 'Status jadwal wajib dipilih.',
            'status.in' => 'Status jadwal yang dipilih tidak valid.',
        ];
    }

    protected function prepareForValidation(): void
    {
        $this->merge([
            'scheduled_time' => $this->filled('scheduled_time') ? $this->input('scheduled_time') : null,
            'assigned_user_id' => $this->filled('assigned_user_id') ? $this->input('assigned_user_id') : null,
            'notes' => $this->filled('notes') ? trim((string) $this->input('notes')) : null,
        ]);
    }
}
