<?php

namespace App\Http\Requests;

use App\Models\MaintenanceFinding;
use App\UserRole;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;

class UpdateFindingStatusRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->role === UserRole::Supervisor;
    }

    /** @return array<string, array<mixed>> */
    public function rules(): array
    {
        return [
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

                $allowedTransitions = [
                    'open' => ['open', 'review'],
                    'review' => ['review', 'resolved'],
                    'resolved' => ['resolved'],
                ];

                if (! in_array($this->input('status'), $allowedTransitions[$finding->status] ?? [], true)) {
                    $validator->errors()->add('status', 'Status temuan tidak dapat dikembalikan ke tahap sebelumnya.');
                }
            },
        ];
    }
}
