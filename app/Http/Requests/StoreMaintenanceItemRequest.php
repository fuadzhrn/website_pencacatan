<?php

namespace App\Http\Requests;

use App\UserRole;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreMaintenanceItemRequest extends FormRequest
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
            'maintenance_category_id' => ['required', 'integer', Rule::exists('maintenance_categories', 'id')],
            'item_name' => [
                'required',
                'string',
                'max:255',
                Rule::unique('maintenance_items', 'item_name')
                    ->where(fn ($query) => $query->where('maintenance_category_id', $this->integer('maintenance_category_id'))),
            ],
            'criteria' => ['required', 'string', 'max:5000'],
            'input_type' => ['required', Rule::in(['sesuai_tidak_sesuai', 'nilai_aktual', 'catatan'])],
            'sort_order' => ['nullable', 'integer', 'min:0', 'max:65535'],
            'status' => ['required', Rule::in(['active', 'inactive'])],
            'context' => ['nullable', Rule::in(['mobile'])],
            'form_type' => ['nullable', Rule::in(['item'])],
        ];
    }

    /** @return array<string, string> */
    public function messages(): array
    {
        return [
            'maintenance_category_id.required' => 'Kategori wajib dipilih.',
            'maintenance_category_id.exists' => 'Kategori yang dipilih tidak ditemukan.',
            'item_name.required' => 'Nama item wajib diisi.',
            'item_name.unique' => 'Nama item sudah digunakan pada kategori tersebut.',
            'criteria.required' => 'Kriteria pemeriksaan wajib diisi.',
            'input_type.required' => 'Tipe input wajib dipilih.',
            'input_type.in' => 'Tipe input yang dipilih tidak valid.',
            'sort_order.integer' => 'Urutan harus berupa bilangan bulat.',
            'sort_order.min' => 'Urutan tidak boleh bernilai negatif.',
            'status.required' => 'Status wajib dipilih.',
            'status.in' => 'Status yang dipilih tidak valid.',
        ];
    }

    protected function prepareForValidation(): void
    {
        $this->merge([
            'item_name' => trim((string) $this->input('item_name')),
            'criteria' => trim((string) $this->input('criteria')),
            'sort_order' => $this->filled('sort_order') ? $this->input('sort_order') : null,
        ]);
    }
}
