<?php

namespace App\Http\Requests;

use App\UserRole;
use Illuminate\Foundation\Http\FormRequest;

class RejectInspectionRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->role === UserRole::Supervisor;
    }

    /** @return array<string, array<mixed>> */
    public function rules(): array
    {
        return [
            'supervisor_note' => ['required', 'string', 'max:5000'],
        ];
    }

    /** @return array<string, string> */
    public function messages(): array
    {
        return [
            'supervisor_note.required' => 'Catatan supervisor wajib diisi saat pemeriksaan ditolak.',
        ];
    }
}
