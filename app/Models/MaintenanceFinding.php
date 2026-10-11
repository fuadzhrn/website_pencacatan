<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable(['maintenance_check_id', 'maintenance_check_detail_id', 'machine_id', 'maintenance_item_id', 'reported_by', 'finding_date', 'finding_description', 'follow_up', 'photo', 'status', 'reviewed_by', 'reviewed_at', 'resolved_at'])]
class MaintenanceFinding extends Model
{
    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'finding_date' => 'date',
            'reviewed_at' => 'datetime',
            'resolved_at' => 'datetime',
        ];
    }

    public function check(): BelongsTo
    {
        return $this->belongsTo(MaintenanceCheck::class, 'maintenance_check_id');
    }

    public function checkDetail(): BelongsTo
    {
        return $this->belongsTo(MaintenanceCheckDetail::class, 'maintenance_check_detail_id');
    }

    public function machine(): BelongsTo
    {
        return $this->belongsTo(Machine::class);
    }

    public function item(): BelongsTo
    {
        return $this->belongsTo(MaintenanceItem::class, 'maintenance_item_id');
    }

    public function reportedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'reported_by');
    }

    public function reviewedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'reviewed_by');
    }
}
