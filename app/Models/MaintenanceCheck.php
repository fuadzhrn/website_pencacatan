<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[Fillable(['maintenance_schedule_id', 'machine_id', 'checked_by', 'verified_by', 'check_date', 'start_time', 'end_time', 'overall_status', 'verification_status', 'general_note', 'supervisor_note', 'verified_at'])]
class MaintenanceCheck extends Model
{
    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'check_date' => 'date',
            'verified_at' => 'datetime',
        ];
    }

    public function schedule(): BelongsTo
    {
        return $this->belongsTo(MaintenanceSchedule::class, 'maintenance_schedule_id');
    }

    public function machine(): BelongsTo
    {
        return $this->belongsTo(Machine::class);
    }

    public function checkedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'checked_by');
    }

    public function verifiedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'verified_by');
    }

    public function details(): HasMany
    {
        return $this->hasMany(MaintenanceCheckDetail::class);
    }

    public function findings(): HasMany
    {
        return $this->hasMany(MaintenanceFinding::class);
    }
}
