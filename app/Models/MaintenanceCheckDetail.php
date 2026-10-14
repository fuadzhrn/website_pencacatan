<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

#[Fillable(['maintenance_check_id', 'maintenance_item_id', 'result_status', 'actual_value', 'note', 'photo'])]
class MaintenanceCheckDetail extends Model
{
    public function check(): BelongsTo
    {
        return $this->belongsTo(MaintenanceCheck::class, 'maintenance_check_id');
    }

    public function item(): BelongsTo
    {
        return $this->belongsTo(MaintenanceItem::class, 'maintenance_item_id');
    }

    public function findings(): HasMany
    {
        return $this->hasMany(MaintenanceFinding::class);
    }

    public function finding(): HasOne
    {
        return $this->hasOne(MaintenanceFinding::class);
    }
}
