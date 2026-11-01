<?php

namespace App\Http\Controllers\Users;

use App\Domain\Security\Services\AccountSecurity;
use App\Domain\Users\Models\Permission;
use App\Domain\Users\Models\Role;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class RoleController
{
    public function index(Request $request): View
    {
        $roles = Role::query()
            ->where('status', 'active')
            ->withCount('users')
            ->orderBy('name')
            ->get();

        $selectedRole = $request->filled('role')
            ? Role::with('permissions')
                ->where('id', $request->integer('role'))
                ->where('status', 'active')
                ->first()
            : $roles->first();

        if (! $selectedRole && $roles->isNotEmpty()) {
            $selectedRole = $roles->first()->load('permissions');
        }

        $permissions = Permission::query()->where('slug', '!=', 'documents.delete')
            ->orderBy('module')
            ->orderBy('name')
            ->get()
            ->groupBy('module');

        return view(
            'users.roles',
            compact(
                'roles',
                'selectedRole',
                'permissions'
            )
        );
    }

    public function update(
        Request $request,
        Role $role
    ): RedirectResponse {
        AccountSecurity::requireRecent($request);
        $validated = $request->validate([
            'permissions' => [
                'nullable',
                'array',
            ],

            'permissions.*' => [
                'integer',
                'exists:permissions,id',
            ],
        ]);

        if (! $request->user()->hasRole('administrator')) {
            abort_if($role->slug === 'administrator' || $role->users()->whereKey($request->user()->id)->exists(), 403);
            $permissions = Permission::whereIn('id', array_unique(array_merge(
                $validated['permissions'] ?? [], $role->permissions()->pluck('permissions.id')->all()
            )))->get();
            foreach ($permissions as $permission) {
                abort_unless($request->user()->hasPermission($permission->slug), 403);
            }
        }

        $role->permissions()->sync(
            $validated['permissions'] ?? []
        );

        return redirect()
            ->route('users.roles', [
                'role' => $role->id,
            ])
            ->with(
                'success',
                "Permissions for {$role->name} were updated successfully."
            );
    }
}
