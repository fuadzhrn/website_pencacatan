<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[Fillable(['equipment_name', 'brand', 'type', 'serial_number', 'location', 'airport', 'status', 'description'])]
class Machine extends Model
{
    public function schedules(): HasMany
    {
        return $this->hasMany(MaintenanceSchedule::class);
    }

    public function checks(): HasMany
    {
        return $this->hasMany(MaintenanceCheck::class);
    }

    public function findings(): HasMany
    {
        return $this->hasMany(MaintenanceFinding::class);
    }
}
