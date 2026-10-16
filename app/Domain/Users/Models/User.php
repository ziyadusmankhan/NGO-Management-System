<?php

namespace App\Domain\Users\Models;

use App\Domain\Members\Models\Member;
use App\Domain\Users\Models\Role;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Notifications\Notifiable;

class User extends Authenticatable
{
    use HasFactory, Notifiable;

    protected $fillable = [
        'name',
        'email',
        'password',
        'status',
    ];

    protected $hidden = [
        'password',
        'remember_token',
    ];

    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
        ];
    }

    public function roles(): BelongsToMany
    {
        return $this->belongsToMany(
            Role::class,
            'user_roles'
        )->withTimestamps();
    }

    public function member(): HasOne
    {
        return $this->hasOne(Member::class);
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

    public function hasAnyRole(array $roles): bool
    {
        return $this->roles()
            ->whereIn('slug', $roles)
            ->exists();
    }

    public function hasPermission(string $permission): bool
    {
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