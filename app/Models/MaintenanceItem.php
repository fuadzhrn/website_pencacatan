<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[Fillable(['maintenance_category_id', 'item_name', 'criteria', 'input_type', 'sort_order', 'status'])]
class MaintenanceItem extends Model
{
    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'sort_order' => 'integer',
        ];
    }

    public function category(): BelongsTo
    {
        return $this->belongsTo(MaintenanceCategory::class, 'maintenance_category_id');
    }

    public function checkDetails(): HasMany
    {
        return $this->hasMany(MaintenanceCheckDetail::class);
    }

    public function findings(): HasMany
    {
        return $this->hasMany(MaintenanceFinding::class);
    }
}
