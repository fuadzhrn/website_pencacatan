<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class FilterInspectionHistoryRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user() !== null;
    }

    /** @return array<string, array<mixed>> */
    public function rules(): array
    {
        return [
            'start_date' => ['nullable', 'date'],
            'end_date' => ['nullable', 'date', 'after_or_equal:start_date'],
            'machine_id' => ['nullable', 'integer', 'exists:machines,id'],
            'officer_id' => ['nullable', 'integer', 'exists:users,id'],
            'category_id' => ['nullable', 'integer', 'exists:maintenance_categories,id'],
            'verification_status' => ['nullable', Rule::in(['waiting', 'verified', 'rejected'])],
            'overall_status' => ['nullable', Rule::in(['normal', 'has_finding'])],
            'search' => ['nullable', 'string', 'max:100'],
        ];
    }

    /** @return array<string, string> */
    public function messages(): array
    {
        return [
            'end_date.after_or_equal' => 'Tanggal akhir harus sama dengan atau setelah tanggal mulai.',
            'machine_id.exists' => 'Mesin yang dipilih tidak ditemukan.',
            'officer_id.exists' => 'Petugas yang dipilih tidak ditemukan.',
            'category_id.exists' => 'Kategori yang dipilih tidak ditemukan.',
            'verification_status.in' => 'Status verifikasi yang dipilih tidak valid.',
            'overall_status.in' => 'Hasil pemeriksaan yang dipilih tidak valid.',
            'search.max' => 'Pencarian maksimal 100 karakter.',
        ];
    }
}
