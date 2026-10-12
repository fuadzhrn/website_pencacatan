<?php

namespace App\Http\Requests;

use App\UserRole;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateMachineRequest extends FormRequest
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
            'equipment_name' => ['required', 'string', 'max:255'],
            'brand' => ['required', 'string', 'max:255'],
            'type' => ['required', 'string', 'max:255'],
            'serial_number' => [
                'required',
                'string',
                'max:255',
                Rule::unique('machines', 'serial_number')->ignore($this->route('machine')),
            ],
            'location' => ['required', 'string', 'max:255'],
            'airport' => ['required', 'string', 'max:255'],
            'status' => ['required', Rule::in(['active', 'inactive', 'maintenance'])],
            'description' => ['nullable', 'string', 'max:5000'],
            'context' => ['nullable', Rule::in(['mobile'])],
        ];
    }

    /** @return array<string, string> */
    public function messages(): array
    {
        return [
            'equipment_name.required' => 'Nama peralatan wajib diisi.',
            'brand.required' => 'Merk wajib diisi.',
            'type.required' => 'Tipe wajib diisi.',
            'serial_number.required' => 'Serial number wajib diisi.',
            'serial_number.unique' => 'Serial number sudah digunakan oleh mesin lain.',
            'location.required' => 'Lokasi wajib diisi.',
            'airport.required' => 'Bandar udara wajib diisi.',
            'status.required' => 'Status wajib dipilih.',
            'status.in' => 'Status yang dipilih tidak valid.',
        ];
    }

    protected function prepareForValidation(): void
    {
        $this->merge([
            'equipment_name' => trim((string) $this->input('equipment_name')),
            'brand' => trim((string) $this->input('brand')),
            'type' => trim((string) $this->input('type')),
            'serial_number' => trim((string) $this->input('serial_number')),
            'location' => trim((string) $this->input('location')),
            'airport' => trim((string) $this->input('airport')),
            'description' => $this->filled('description') ? trim((string) $this->input('description')) : null,
        ]);
    }
}
