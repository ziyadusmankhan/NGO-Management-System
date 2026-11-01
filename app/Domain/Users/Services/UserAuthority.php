<?php

namespace App\Domain\Users\Services;

use App\Domain\Users\Models\Role;
use App\Domain\Users\Models\User;

class UserAuthority
{
    public function manage(User $actor, User $target): void
    {
        abort_if($target->hasRole('administrator') && ! $actor->hasRole('administrator'), 403);
    }

    public function assign(User $actor, ?User $target, array $ids): void
    {
        $old = $target?->roles()->pluck('roles.id')->map(fn ($id) => (int) $id)->sort()->values()->all() ?? [];
        $new = array_values(array_unique(array_map('intval', $ids)));
        sort($new);
        if ($old === $new) {
            return;
        }
        abort_if($target && $actor->id === $target->id, 403, 'You cannot change your own roles.');
        abort_unless($actor->hasPermission('users.assign-roles'), 403);
        foreach (Role::with('permissions')->whereIn('id', array_unique(array_merge($old, $new)))->get() as $role) {
            if ($actor->hasRole('administrator')) {
                continue;
            }
            abort_if($role->slug === 'administrator', 403);
            foreach ($role->permissions as $permission) {
                abort_unless($actor->hasPermission($permission->slug), 403, 'This role exceeds your authority.');
            }
        }
    }
}
