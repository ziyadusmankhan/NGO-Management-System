<?php

namespace App\Http\Controllers\Users;

use App\Domain\Security\Services\AccountSecurity;
use App\Domain\Users\Models\Role;
use App\Domain\Users\Models\User;
use App\Http\Requests\Users\StoreUserRequest;
use App\Http\Requests\Users\UpdateUserRequest;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

class UserController
{
    public function index(): View
    {
        $users = User::query()
            ->with(['roles', 'member.committees'])
            ->latest('id')
            ->paginate(15)
            ->withQueryString();

        $totalUsers = User::count();
        $totalRoles = Role::count();

        $activeUsers = User::where(
            'status',
            'active'
        )->count();

        $inactiveUsers = User::where(
            'status',
            'inactive'
        )->count();

        return view(
            'users.index',
            compact(
                'users',
                'totalUsers',
                'activeUsers',
                'inactiveUsers',
                'totalRoles'
            )
        );
    }

    public function create(): View
    {
        $roles = Role::query()
            ->where('status', 'active')
            ->orderBy('name')
            ->get();

        return view(
            'users.create',
            compact('roles')
        );
    }

    public function store(
        StoreUserRequest $request
    ): RedirectResponse {

        AccountSecurity::requireRecent($request);
        $validated = $request->validated();

        $user = DB::transaction(function () use ($validated) {

            $user = User::create([
                'name' => $validated['name'],

                'email' => $validated['email'],

                'password' => $validated['password'],

                'status' => $validated['status'],
            ]);

            $user->roles()->sync(
                $validated['roles'] ?? []
            );

            return $user;
        });

        return redirect()
            ->route(
                'users.show',
                $user
            )
            ->with(
                'success',
                'User created successfully.'
            );
    }

    public function show(User $user): View
    {
        $user->load([
            'roles.permissions',
            'member.committees',
        ]);

        $permissions = $user->roles
            ->flatMap(fn ($role) => $role->permissions)
            ->unique('id')
            ->values();

        $primaryCommittee = $user->member?->committees->first();

        return view(
            'users.show',
            compact(
                'user',
                'permissions',
                'primaryCommittee'
            )
        );
    }

    public function edit(User $user): View
    {
        $roles = Role::query()
            ->where('status', 'active')
            ->orderBy('name')
            ->get();

        $user->load('roles');

        return view(
            'users.edit',
            compact(
                'user',
                'roles'
            )
        );
    }

    public function update(
        UpdateUserRequest $request,
        User $user
    ): RedirectResponse {

        AccountSecurity::requireRecent($request);
        $validated = $request->validated();

        DB::transaction(function () use (
            $validated,
            $user
        ) {

            $data = [
                'name' => $validated['name'],

                'email' => $validated['email'],

                'status' => $validated['status'],
            ];

            if (! empty($validated['password'])) {
                throw ValidationException::withMessages(['password' => 'Use Account Security or the administrator password recovery action.']);
            }

            $user->update($data);

            $user->roles()->sync(
                $validated['roles'] ?? []
            );
        });

        return redirect()
            ->route(
                'users.show',
                $user
            )
            ->with(
                'success',
                'User updated successfully.'
            );
    }

    public function destroy(
        User $user
    ): RedirectResponse {

        /*
         * Prevent accidentally deleting the currently
         * logged-in account.
         */
        if (
            auth()->check() &&
            auth()->id() === $user->id
        ) {
            return back()->withErrors([
                'user' => 'You cannot archive your own account.',
            ]);
        }

        $user->delete();

        return redirect()
            ->route('users.index')
            ->with(
                'success',
                'User archived successfully. They can no longer sign in.'
            );
    }
}
