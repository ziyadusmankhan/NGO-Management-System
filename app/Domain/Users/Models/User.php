<?php

namespace App\Domain\Users\Models;

use App\Domain\Audit\Concerns\RecordsAudit;
use App\Domain\Audit\Services\AuditedRelations;
use App\Domain\Members\Models\Member;
use App\Domain\Notifications\Models\NotificationState;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Fortify\TwoFactorAuthenticatable;

class User extends Authenticatable
{
    use HasFactory, Notifiable, SoftDeletes;
    use RecordsAudit;
    use TwoFactorAuthenticatable;

    protected $fillable = [
        'name',
        'email',
        'password',
        'status',
    ];

    protected $hidden = [
        'password',
        'remember_token',
        'two_factor_secret', 'two_factor_recovery_codes',
    ];

    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'last_active_at' => 'datetime',
            'password' => 'hashed',
            'two_factor_confirmed_at' => 'datetime', 'temporary_password_expires_at' => 'datetime', 'session_version' => 'integer', 'must_change_password' => 'boolean',
        ];
    }

    public function roles(): BelongsToMany
    {
        return AuditedRelations::make($this, Role::class, 'user_roles', 'user_id', 'role_id');
    }

    public function member(): HasOne
    {
        return $this->hasOne(Member::class);
    }

    public function notificationStates()
    {
        return $this->hasMany(NotificationState::class);
    }

    public function hasRole(string $role): bool
    {
        return $this->roles()
            ->where('slug', $role)
            ->exists();
    }

    public function isActive(): bool
    {
        return $this->status === 'active';
    }

    public function recordActivity(bool $force = false): void
    {
        if (! $this->isActive() || $this->trashed()) {
            return;
        }

        $now = now();
        $cutoff = $now->copy()->subMinute();

        if (! $force && $this->last_active_at?->greaterThan($cutoff)) {
            return;
        }

        // Activity is independent of profile edits. Throttle writes across sessions.
        $updated = static::query()
            ->whereKey($this->getKey())
            ->where('status', 'active')
            ->when(! $force, fn ($query) => $query->where(function ($query) use ($cutoff) {
                $query->whereNull('last_active_at')->orWhere('last_active_at', '<=', $cutoff);
            }))
            ->toBase()
            ->update(['last_active_at' => $now]);

        if ($updated) {
            $this->last_active_at = $now;
            $this->syncOriginalAttribute('last_active_at');
        }
    }

    public function hasAnyRole(array $roles): bool
    {
        return $this->roles()
            ->whereIn('slug', $roles)
            ->exists();
    }

    public function hasPermission(string $permission): bool
    {
        if (! $this->isActive()) {
            return false;
        }

        /*
         * Administrators always have full access. Without this,
         * an administrator can lose access to a feature whenever a
         * new permission slug is introduced (via a seeder or
         * migration) after roles were already synced, since the
         * permission row/pivot entry may not exist yet for anyone,
         * including the "administrator" role.
         */
        if ($this->hasRole('administrator')) {
            return true;
        }

        return $this->roles()
            ->whereHas('permissions', function ($query) use ($permission) {
                $query->where('slug', $permission);
            })
            ->exists();
    }
}
