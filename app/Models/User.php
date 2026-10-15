<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use App\UserRole;
use Database\Factories\UserFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Hidden;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Support\Str;

#[Fillable(['name', 'email', 'password', 'role', 'phone', 'status'])]
#[Hidden(['password', 'remember_token'])]
class User extends Authenticatable
{
    /** @use HasFactory<UserFactory> */
    use HasFactory, Notifiable;

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
            'role' => UserRole::class,
        ];
    }

    /**
     * Get up to two uppercase initials from the user's name for avatars.
     */
    public function initials(): string
    {
        return Str::of($this->name)
            ->trim()
            ->explode(' ')
            ->filter()
            ->take(2)
            ->map(fn (string $name): string => Str::upper(Str::substr($name, 0, 1)))
            ->implode('');
    }

    public function assignedMaintenanceSchedules(): HasMany
    {
        return $this->hasMany(MaintenanceSchedule::class, 'assigned_user_id');
    }

    public function performedMaintenanceChecks(): HasMany
    {
        return $this->hasMany(MaintenanceCheck::class, 'checked_by');
    }

    public function verifiedMaintenanceChecks(): HasMany
    {
        return $this->hasMany(MaintenanceCheck::class, 'verified_by');
    }

    public function reportedMaintenanceFindings(): HasMany
    {
        return $this->hasMany(MaintenanceFinding::class, 'reported_by');
    }

    public function reviewedMaintenanceFindings(): HasMany
    {
        return $this->hasMany(MaintenanceFinding::class, 'reviewed_by');
    }
}
