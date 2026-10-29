@extends('layouts.app')

@section('title', 'Users & Roles')

@push('styles')
<link rel="stylesheet" href="{{ asset('css/views/users-index-1.css') }}">
@endpush


@section('content')

@include('shared.action-feedback')

<div class="users-header">

    <div>
        <h1>Users & Roles</h1>

        <p>
            Manage system users, organizational roles and access levels.
        </p>
    </div>

    <div class="users-actions">
        @include('shared.directory-export', ['exportRoute'=>'users.export'])

        @if(auth()->user()?->hasPermission('roles.manage'))<a
            href="{{ route('users.roles') }}"
            class="users-btn secondary"
        >
            <i class="bi bi-shield-check"></i>
            Manage Roles
        </a>@endif

        @if(auth()->user()?->hasPermission('users.manage'))<a
            href="{{ route('users.create') }}"
            class="users-btn primary"
        >
            <i class="bi bi-person-plus"></i>
            Add User
        </a>@endif

    </div>

</div>


<!-- SUMMARY -->

<div class="user-summary">

    <div class="summary-card">
        <div class="summary-label">Total Users</div>
        <div class="summary-value">{{ number_format($totalUsers) }}</div>
        <div class="summary-meta">All registered accounts</div>
    </div>

    <div class="summary-card">
        <div class="summary-label">Active Users</div>
        <div class="summary-value">{{ number_format($activeUsers) }}</div>
        <div class="summary-meta">Currently enabled</div>
    </div>

    <div class="summary-card">
        <div class="summary-label">Roles</div>
        <div class="summary-value">{{ number_format($totalRoles) }}</div>
        <div class="summary-meta">Configured access roles</div>
    </div>

    <div class="summary-card">
        <div class="summary-label">Inactive Users</div>
        <div class="summary-value">{{ number_format($inactiveUsers) }}</div>
        <div class="summary-meta">Sign-in disabled</div>
    </div>

</div>


<div class="users-card">

    <!-- TOOLBAR -->

    <form class="toolbar" method="get" action="{{ route('users.index') }}">

        <div class="search-box">

            <i class="bi bi-search"></i>

            <input
                type="text"
                id="userSearch"
                name="search" value="{{ request('search') }}"
                placeholder="Search users, email or role..."
            >

        </div>


        <select
            class="filter-select"
            id="roleFilter"
            name="role" onchange="this.form.requestSubmit()"
        >

            <option value="">
                All Roles
            </option>

            @foreach (
                \App\Domain\Users\Models\Role::orderBy('name')->get()
                as $role
            )

                <option value="{{ $role->slug }}" @selected(request('role')===$role->slug)>
                    {{ $role->name }}
                </option>

            @endforeach

        </select>


        <select class="filter-select" id="userStatusFilter" name="status" onchange="this.form.requestSubmit()">

            <option value="">All Statuses</option>

            <option value="active" @selected(request('status')==='active')>
                Active
            </option>

            <option value="inactive" @selected(request('status')==='inactive')>
                Inactive
            </option>

        </select>

        <button type="submit" class="users-btn primary"><i class="bi bi-search" aria-hidden="true"></i> Search</button>
    </form>


    <!-- TABLE -->

    <div class="table-container">

        <table class="users-table">

            <thead>

                <tr>
                    <th>User</th>
                    <th>Role</th>
                    <th>Committee</th>
                    <th>Status</th>
                    <th>Last Active</th>
                    <th>Joined</th>
                    <th>Actions</th>
                </tr>

            </thead>


            <tbody id="usersTableBody">

                @forelse ($users as $user)

                    @php

                        $initials = collect(
                            preg_split(
                                '/\s+/',
                                trim($user->name)
                            )
                        )
                        ->take(2)
                        ->map(
                            fn ($part) =>
                                strtoupper(
                                    substr($part, 0, 1)
                                )
                        )
                        ->implode('');

                        $role = $user->roles->first();

                        $roleSlug =
                            $role?->slug ?? 'none';

                        $roleClass =
                            match ($roleSlug) {
                                'administrator' => 'admin',
                                'treasurer' => 'finance',
                                'viewer' => 'audit',
                                default => '',
                            };

                    @endphp


                    <tr
                        data-role="{{ $roleSlug }}"
                        data-status="{{ $user->status }}"
                    >

                        <td>

                            <div class="user-profile">

                                <div class="user-avatar">
                                    {{ $initials ?: '?' }}
                                </div>

                                <div>

                                    <div class="user-name">
                                        {{ $user->name }}
                                    </div>

                                    <div class="user-email">
                                        {{ $user->email }}
                                    </div>

                                </div>

                            </div>

                        </td>


                        <td>

                            @forelse ($user->roles as $assignedRole)
                                <span class="role-badge" style="display:inline-block;margin:2px 0;">{{ $assignedRole->name }}</span>
                            @empty

                                <span class="role-badge">
                                    No Role
                                </span>

                            @endforelse

                        </td>


                        <td>
                            {{ $user->member?->committees->where('pivot.status', 'active')->pluck('name')->join(', ') ?: '—' }}
                        </td>


                        <td>

                            <span
                                class="status-badge
                                {{ $user->status === 'active'
                                    ? 'status-active'
                                    : 'status-inactive'
                                }}"
                            >
                                {{ ucfirst($user->status) }}
                            </span>

                        </td>


                        <td class="last-active">

                            @if ($user->last_active_at)

                                <time datetime="{{ $user->last_active_at->toIso8601String() }}" title="{{ $user->last_active_at->toIso8601String() }}">{{ $user->last_active_at->diffForHumans() }}</time>

                            @else

                                Not recorded

                            @endif

                        </td>


                        <td>
                            {{ $user->created_at->format('d M Y') }}
                        </td>


                        <td>

                            <div style="display:flex;align-items:center;gap:8px;">
                            @if(auth()->user()?->hasPermission('users.view'))<a
                                href="{{ route('users.show', $user) }}"
                                class="action-btn"
                                title="View user"
                            >
                                <i class="bi bi-eye"></i>
                            </a>@endif
                                @include('shared.archive-user', ['buttonClass' => 'users-btn secondary'])
                            </div>

                        </td>

                    </tr>

                @empty

                    <tr>

                        <td
                            colspan="7"
                            style="
                                text-align:center;
                                padding:45px;
                                color:var(--legacy-text-89948f, #89948f);
                            "
                        >

                            <i
                                class="bi bi-people"
                                style="
                                    display:block;
                                    font-size:28px;
                                    margin-bottom:10px;
                                "
                            ></i>

                            No users have been created yet.

                        </td>

                    </tr>

                @endforelse

            </tbody>

        </table>

    </div>


    <!-- FOOTER -->

    <div class="table-footer">
        <div class="result-count">
            Showing {{ $users->firstItem() ?? 0 }}-{{ $users->lastItem() ?? 0 }} of {{ $users->total() }} users
        </div>
        {{ $users->links('pagination::bootstrap-5') }}
    </div>

</div>


<!-- ACCESS NOTICE -->

<div class="access-banner">

    <i class="bi bi-shield-lock"></i>

    <div>

        <strong>
            Role-based access control
        </strong>

        <p>
            User access is determined by assigned roles and configurable
            permissions. Users should only see organization information
            and actions authorized for their role.
        </p>

    </div>

</div>

@endsection
