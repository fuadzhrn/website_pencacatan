<?php

namespace App\Http\Requests;

use App\UserRole;
use Illuminate\Foundation\Http\FormRequest;

class VerifyInspectionRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->role === UserRole::Supervisor;
    }

    /** @return array<string, array<mixed>> */
    public function rules(): array
    {
        return [
            'supervisor_note' => ['nullable', 'string', 'max:5000'],
        ];
    }
}
