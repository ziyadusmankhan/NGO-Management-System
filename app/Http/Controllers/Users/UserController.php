<?php

namespace App\Http\Controllers\Users;

use App\Domain\Organization\Services\DirectoryQuery;
use App\Domain\Security\Services\AccountSecurity;
use App\Domain\Users\Models\Role;
use App\Domain\Users\Models\User;
use App\Domain\Users\Services\UserAuthority;
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
        $directory = app(DirectoryQuery::class);
        $users = $directory->query('users', $directory->filters(request(), 'users'))
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

        app(UserAuthority::class)->assign($request->user(), null, $validated['roles'] ?? []);

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

        $authority = app(UserAuthority::class);
        $authority->manage($request->user(), $user);
        if (array_key_exists('roles', $validated)) {
            $authority->assign($request->user(), $user, $validated['roles'] ?? []);
        }

        DB::transaction(function () use (
            $validated,
            $user
        ) {

            $user = User::whereKey($user->id)->lockForUpdate()->firstOrFail();
            $authority = app(UserAuthority::class);
            $authority->manage(auth()->user(), $user);
            if (array_key_exists('roles', $validated)) {
                $authority->assign(auth()->user(), $user, $validated['roles'] ?? []);
            }

            $data = [
                'name' => $validated['name'],

                'email' => $validated['email'],

                'status' => $validated['status'],
            ];

            if (! empty($validated['password'])) {
                throw ValidationException::withMessages(['password' => 'Use Account Security or the administrator password recovery action.']);
            }

            $user->update($data);

            if (array_key_exists('roles', $validated)) {
                $user->roles()->sync($validated['roles'] ?? []);
            }
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

        app(UserAuthority::class)->manage(auth()->user(), $user);
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
