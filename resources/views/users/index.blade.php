@extends('layouts.app')

@section('title', 'Users & Roles')

@push('styles')
<style>
    .users-header {
        display: flex;
        justify-content: space-between;
        align-items: flex-end;
        gap: 20px;
        margin-bottom: 24px;
    }

    .users-header h1 {
        margin: 0;
        font-size: 25px;
        font-weight: 700;
        letter-spacing: -.5px;
    }

    .users-header p {
        margin: 7px 0 0;
        color: var(--muted);
        font-size: 16px;
    }

    .users-actions {
        display: flex;
        gap: 9px;
    }

    .users-btn {
        height: 40px;
        padding: 0 14px;
        border-radius: 10px;

        display: inline-flex;
        align-items: center;
        justify-content: center;
        gap: 7px;

        font-size: 16px;
        font-weight: 650;
        text-decoration: none;

        cursor: pointer;
    }

    .users-btn.secondary {
        background: #fff;
        border: 1px solid var(--border);
        color: #637069;
    }

    .users-btn.primary {
        background: var(--primary);
        color: #fff;
        border: 1px solid var(--primary);
    }

    .users-btn.primary:hover {
        background: var(--primary-light);
    }

    /* SUMMARY */

    .user-summary {
        display: grid;
        grid-template-columns: repeat(4, minmax(0, 1fr));
        gap: 14px;
        margin-bottom: 18px;
    }

    .summary-card {
        background: #fff;
        border: 1px solid var(--border);
        border-radius: 13px;
        padding: 16px;
    }

    .summary-label {
        color: var(--muted);
        font-size: 16px;
    }

    .summary-value {
        margin-top: 6px;
        font-size: 22px;
        font-weight: 700;
    }

    .summary-meta {
        margin-top: 4px;
        color: #6d7572;
        font-size: 16px;
    }

    /* TOOLBAR */

    .users-card {
        background: #fff;
        border: 1px solid var(--border);
        border-radius: 14px;
        overflow: hidden;
    }

    .toolbar {
        padding: 15px 17px;
        border-bottom: 1px solid #eef1ef;

        display: flex;
        align-items: center;
        gap: 10px;
        flex-wrap: wrap;
    }

    .search-box {
        position: relative;
        flex: 1;
        min-width: 230px;
    }

    .search-box i {
        position: absolute;
        left: 13px;
        top: 50%;
        transform: translateY(-50%);
        color: #6f7672;
        font-size: 17px;
    }

    .search-box input {
        width: 100%;
        height: 40px;

        border: 1px solid var(--border);
        border-radius: 9px;

        padding: 0 12px 0 36px;

        background: #fafcfa;
        outline: none;

        font-size: 16px;
    }

    .search-box input:focus {
        background: #fff;
        border-color: #6e7772;
        box-shadow: 0 0 0 3px rgba(23,63,53,.06);
    }

    .filter-select {
        height: 40px;
        min-width: 145px;

        border: 1px solid var(--border);
        border-radius: 9px;

        padding: 0 30px 0 12px;

        background: #fff;
        color: #69766f;

        font-size: 16px;
        outline: none;
    }

    /* TABLE */

    .table-container {
        overflow-x: auto;
    }

    .users-table {
        width: 100%;
        min-width: 950px;
        border-collapse: collapse;
    }

    .users-table th {
        padding: 11px 18px;

        background: #fafcfb;
        border-bottom: 1px solid #eef1ef;

        color: #6c7470;

        font-size: 16px;
        font-weight: 700;

        text-transform: uppercase;
        letter-spacing: .6px;

        text-align: left;
    }

    .users-table td {
        padding: 13px 18px;

        border-bottom: 1px solid #f0f3f1;

        font-size: 16px;
        vertical-align: middle;
    }

    .users-table tbody tr:hover {
        background: #fbfcfb;
    }

    .users-table tbody tr:last-child td {
        border-bottom: none;
    }

    .user-profile {
        display: flex;
        align-items: center;
        gap: 10px;
    }

    .user-avatar {
        width: 35px;
        height: 35px;

        border-radius: 10px;

        background: var(--primary-soft);
        color: var(--primary);

        display: flex;
        align-items: center;
        justify-content: center;

        font-size: 16px;
        font-weight: 700;

        flex-shrink: 0;
    }

    .user-name {
        font-size: 16px;
        font-weight: 650;
    }

    .user-email {
        margin-top: 3px;
        color: #6d7571;
        font-size: 16px;
    }

    .role-badge {
        display: inline-flex;
        padding: 5px 8px;
        border-radius: 30px;

        background: #f0f5f2;
        color: var(--primary);

        font-size: 16px;
        font-weight: 650;
    }

    .role-badge.admin {
        background: #f4efe4;
        color: #876a30;
    }

    .role-badge.finance {
        background: #edf6f0;
        color: #397955;
    }

    .role-badge.audit {
        background: #eef3f7;
        color: #52708a;
    }

    .status-badge {
        display: inline-flex;
        align-items: center;
        gap: 5px;

        padding: 5px 8px;
        border-radius: 30px;

        font-size: 16px;
        font-weight: 650;
    }

    .status-badge::before {
        content: "";
        width: 5px;
        height: 5px;
        border-radius: 50%;
    }

    .status-active {
        background: #edf7f0;
        color: #397955;
    }

    .status-active::before {
        background: #4c9a6c;
    }

    .status-inactive {
        background: #f1f3f2;
        color: #6c7771;
    }

    .status-inactive::before {
        background: #9ba49f;
    }

    .last-active {
        color: #6b766f;
    }

    .action-btn {
        width: 31px;
        height: 31px;

        border: 1px solid var(--border);
        background: #fff;
        border-radius: 8px;

        display: inline-flex;
        align-items: center;
        justify-content: center;

        color: #6c7771;
    }

    .action-btn:hover {
        color: var(--primary);
        background: #f7faf8;
    }

    /* ACCESS BANNER */

    .access-banner {
        margin-top: 18px;

        display: flex;
        gap: 11px;

        padding: 15px 17px;

        background: #f7f9f8;
        border: 1px solid #e5ebe8;
        border-radius: 13px;
    }

    .access-banner i {
        color: var(--primary);
        font-size: 18px;
    }

    .access-banner strong {
        display: block;
        font-size: 16px;
    }

    .access-banner p {
        margin: 4px 0 0;
        color: #6b7670;
        font-size: 16px;
        line-height: 1.6;
    }

    /* FOOTER */

    .table-footer {
        display: flex;
        align-items: center;
        justify-content: space-between;

        padding: 14px 18px;

        border-top: 1px solid #eef1ef;
    }

    .result-count {
        color: #6e7672;
        font-size: 16px;
    }

    .pagination {
        display: flex;
        gap: 5px;
    }

    .page-btn {
        min-width: 29px;
        height: 29px;

        padding: 0 7px;

        border: 1px solid var(--border);
        background: #fff;

        border-radius: 7px;

        color: #69756f;
        font-size: 16px;
    }

    .page-btn.active {
        background: var(--primary);
        border-color: var(--primary);
        color: white;
    }

    @media (max-width: 1050px) {
        .user-summary {
            grid-template-columns: repeat(2, 1fr);
        }
    }

    @media (max-width: 650px) {
        .users-header {
            flex-direction: column;
            align-items: flex-start;
        }

        .users-actions {
            width: 100%;
        }

        .users-actions .users-btn {
            flex: 1;
        }

        .user-summary {
            grid-template-columns: 1fr;
        }

        .search-box {
            min-width: 100%;
        }

        .filter-select {
            flex: 1;
            min-width: 0;
        }

        .table-footer {
            align-items: flex-start;
            gap: 10px;
            flex-direction: column;
        }
    }
</style>
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

        <a
            href="{{ route('users.roles') }}"
            class="users-btn secondary"
        >
            <i class="bi bi-shield-check"></i>
            Manage Roles
        </a>

        <a
            href="{{ route('users.create') }}"
            class="users-btn primary"
        >
            <i class="bi bi-person-plus"></i>
            Add User
        </a>

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

    <div class="toolbar">

        <div class="search-box">

            <i class="bi bi-search"></i>

            <input
                type="text"
                id="userSearch"
                placeholder="Search users, email or role..."
            >

        </div>


        <select
            class="filter-select"
            id="roleFilter"
        >

            <option value="">
                All Roles
            </option>

            @foreach (
                \App\Domain\Users\Models\Role::orderBy('name')->get()
                as $role
            )

                <option value="{{ $role->slug }}">
                    {{ $role->name }}
                </option>

            @endforeach

        </select>


        <select class="filter-select" id="userStatusFilter">

            <option value="">All Statuses</option>

            <option value="active">
                Active
            </option>

            <option value="inactive">
                Inactive
            </option>

        </select>

    </div>


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

                            @if ($role)

                                <span class="role-badge {{ $roleClass }}">
                                    {{ $role->name }}
                                </span>

                            @else

                                <span class="role-badge">
                                    No Role
                                </span>

                            @endif

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
                            <a
                                href="{{ route('users.show', $user) }}"
                                class="action-btn"
                                title="View user"
                            >
                                <i class="bi bi-eye"></i>
                            </a>
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
                                color:#89948f;
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


@push('scripts')

<script>

    const userSearch =
        document.getElementById('userSearch');

    const roleFilter =
        document.getElementById('roleFilter');

    const userStatusFilter =
        document.getElementById('userStatusFilter');

    const userRows =
        document.querySelectorAll('#usersTableBody tr');


    function filterUsers() {

        const search =
            userSearch.value.toLowerCase().trim();

        const role =
            roleFilter.value;

        const status =
            userStatusFilter.value;


        userRows.forEach(row => {

            const text =
                row.textContent.toLowerCase();

            const rowRole =
                row.dataset.role;

            const rowStatus =
                row.dataset.status;


            const matchesSearch =
                !search || text.includes(search);

            const matchesRole =
                !role || rowRole === role;

            const matchesStatus =
                !status || rowStatus === status;


            row.style.display =
                matchesSearch &&
                matchesRole &&
                matchesStatus
                    ? ''
                    : 'none';

        });

    }


    userSearch.addEventListener(
        'input',
        filterUsers
    );

    roleFilter.addEventListener(
        'change',
        filterUsers
    );

    userStatusFilter.addEventListener(
        'change',
        filterUsers
    );

</script>

@endpush
