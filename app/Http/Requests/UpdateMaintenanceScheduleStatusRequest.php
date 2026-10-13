<?php

namespace App\Http\Requests;

use App\Models\MaintenanceSchedule;
use App\UserRole;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateMaintenanceScheduleStatusRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        $user = $this->user();

        if ($user?->role === UserRole::Admin) {
            return true;
        }

        $schedule = $this->route('schedule');

        return $user?->role === UserRole::Petugas
            && $schedule instanceof MaintenanceSchedule
            && $schedule->assigned_user_id === $user->id
            && in_array($schedule->status, ['scheduled', 'pending'], true)
            && $this->input('status') === 'in_progress';
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'status' => ['required', Rule::in(['scheduled', 'pending', 'in_progress', 'completed', 'overdue', 'cancelled'])],
            'context' => ['nullable', Rule::in(['mobile'])],
        ];
    }

    /** @return array<string, string> */
    public function messages(): array
    {
        return [
            'status.required' => 'Status jadwal wajib dipilih.',
            'status.in' => 'Status jadwal yang dipilih tidak valid.',
        ];
    }
}
