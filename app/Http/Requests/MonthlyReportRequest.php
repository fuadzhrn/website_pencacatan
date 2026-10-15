<?php

namespace App\Http\Requests;

use App\UserRole;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

class MonthlyReportRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return in_array($this->user()?->role, UserRole::cases(), true);
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'machine_id' => ['nullable', 'integer', 'exists:machines,id'],
            'month' => ['required', 'integer', 'between:1,12'],
            'year' => ['required', 'integer', 'between:2000,2100'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'machine_id.integer' => 'Pilihan mesin tidak valid.',
            'machine_id.exists' => 'Mesin yang dipilih tidak ditemukan.',
            'month.required' => 'Bulan laporan wajib dipilih.',
            'month.integer' => 'Bulan laporan tidak valid.',
            'month.between' => 'Bulan laporan harus antara Januari dan Desember.',
            'year.required' => 'Tahun laporan wajib dipilih.',
            'year.integer' => 'Tahun laporan tidak valid.',
            'year.between' => 'Tahun laporan harus antara 2000 dan 2100.',
        ];
    }

    protected function prepareForValidation(): void
    {
        $defaults = [];

        if (! $this->filled('month')) {
            $defaults['month'] = now()->month;
        }

        if (! $this->filled('year')) {
            $defaults['year'] = now()->year;
        }

        $this->merge($defaults);
    }
}
