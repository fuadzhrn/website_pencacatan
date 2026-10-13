<?php

namespace App\Http\Requests;

use App\UserRole;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class GenerateMaintenanceScheduleRequest extends FormRequest
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
            'start_date' => ['required', 'date'],
            'end_date' => ['required', 'date', 'after_or_equal:start_date'],
            'scheduled_time' => ['nullable', 'date_format:H:i'],
            'assigned_user_id' => [
                'nullable',
                'integer',
                Rule::exists('users', 'id')->where('role', UserRole::Petugas->value),
            ],
            'notes' => ['nullable', 'string', 'max:5000'],
            'context' => ['nullable', Rule::in(['mobile'])],
            'form_type' => ['nullable', Rule::in(['generate'])],
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
            'start_date.required' => 'Tanggal mulai wajib diisi.',
            'start_date.date' => 'Tanggal mulai tidak valid.',
            'end_date.required' => 'Tanggal akhir wajib diisi.',
            'end_date.date' => 'Tanggal akhir tidak valid.',
            'end_date.after_or_equal' => 'Tanggal akhir harus setelah atau sama dengan tanggal mulai.',
            'scheduled_time.date_format' => 'Jam jadwal harus menggunakan format jam dan menit.',
            'assigned_user_id.exists' => 'Petugas yang dipilih tidak valid.',
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
