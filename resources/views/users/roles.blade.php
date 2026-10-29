@extends('layouts.app')

@section('title', 'Roles & Permissions')

@push('styles')
<link rel="stylesheet" href="{{ asset('css/views/users-roles-1.css') }}">
@endpush

@section('content')
<div class="alert alert-light">Before changing account access, <a href="{{ route('security.confirm') }}">confirm your password</a>. Confirmation lasts 15 minutes.</div>


<div class="header">

    <div>
        <h1>Roles & Permissions</h1>

        <p>
            Configure what each organizational role can view and manage.
        </p>
    </div>

</div>


<div class="role-layout">

    <!-- ROLE LIST -->

    <div class="card">

        <div style="
            padding:16px;
            border-bottom:1px solid #eef1ef;
        ">
            <strong style="font-size: 16px;">
                Organization Roles
            </strong>

            <div style="
                margin-top:4px;
                color: #6c7470;
                font-size: 16px;
            ">
                {{ $roles->count() }} configured {{ $roles->count() === 1 ? 'role' : 'roles' }}
            </div>
        </div>


        <div class="role-list">

            @foreach ($roles as $role)

                @php
                    $icon = match ($role->slug) {
                        'administrator' => 'bi-shield-lock',
                        'general-secretary' => 'bi-person-badge',
                        'treasurer' => 'bi-wallet2',
                        'committee-coordinator' => 'bi-diagram-3',
                        'welfare-officer' => 'bi-heart-pulse',
                        'project-officer' => 'bi-gear',
                        'viewer' => 'bi-eye',
                        default => 'bi-person',
                    };
                @endphp

                @if(auth()->user()?->hasPermission('roles.manage'))<a
                    href="{{ route('users.roles', ['role' => $role->id]) }}"
                    class="role-item {{ $selectedRole?->id === $role->id ? 'active' : '' }}"
                    style="text-decoration:none;color:inherit;"
                >

                    <div class="role-icon">
                        <i class="bi {{ $icon }}"></i>
                    </div>

                    <div class="role-info">

                        <strong>
                            {{ $role->name }}
                        </strong>

                        <span>
                            {{ $role->users_count }}
                            {{ $role->users_count === 1 ? 'user' : 'users' }}
                        </span>

                    </div>

                </a>@endif

            @endforeach

        </div>

    </div>


    <!-- PERMISSIONS -->

    <div class="card">

        <div class="permission-head">

            <div>
                <strong>
                    {{ $selectedRole?->name ?? 'Select a role' }}
                </strong>

                <span>
                    Configure permissions for this role.
                </span>
            </div>

            <div class="permission-actions">

                <button
                    type="button"
                    class="small-btn"
                    id="resetPermissions"
                >
                    Reset
                </button>

                <button
                    type="submit"
                    form="rolePermissionsForm"
                    class="small-btn primary"
                >
                    Save Changes
                </button>

            </div>

        </div>


        <div class="role-overview">

            <div class="overview-item">
                <label>Users</label>
                <strong>{{ $selectedRole->users_count ?? 0 }} assigned</strong>
            </div>

            <div class="overview-item">
                <label>Access Level</label>
                <strong>
                    {{ $selectedRole?->slug === 'administrator'
                        ? 'Full access'
                        : 'Role-based' }}
                </strong>
            </div>

            <div class="overview-item">
                <label>Restrictions</label>
                <strong>Configurable</strong>
            </div>

        </div>

        @if ($selectedRole)

        <form
            id="rolePermissionsForm"
            method="POST"
            action="{{ route('users.roles.update', $selectedRole) }}"
        >
            @csrf
            @method('PUT')

            <div class="permissions">

                @foreach ($permissions as $module => $modulePermissions)

                    <div class="permission-section">

                        <div class="section-title">
                            {{ ucfirst($module) }}
                        </div>

                        @foreach ($modulePermissions as $permission)

                            <div class="permission-row">

                                <div class="permission-name">
                                    <strong>{{ $permission->name }}</strong>
                                    <span>{{ $permission->description ?: $permission->slug }}</span>
                                </div>

                                <div
                                    class="permission-column"
                                    style="grid-column:span 3;text-align:right;"
                                >
                                    <label
                                        style="
                                            display:inline-flex;
                                            align-items:center;
                                            gap:7px;
                                            cursor:pointer;
                                        "
                                    >
                                        <input
                                            type="checkbox"
                                            class="check"
                                            name="permissions[]"
                                            value="{{ $permission->id }}"
                                            @checked($selectedRole->permissions->contains('id', $permission->id))
                                        >

                                        <span style="font-size:8px;color:#77837d;">
                                            Allowed
                                        </span>
                                    </label>
                                </div>

                            </div>

                        @endforeach

                    </div>

                @endforeach

                @if (session('success'))

                    <div class="notice">
                        <i class="bi bi-check-circle"></i>
                        <p>{{ session('success') }}</p>
                    </div>

                @endif

                <div class="notice">

                    <i class="bi bi-shield-check"></i>

                    <p>
                        Changes will update the permissions assigned to
                        <strong>{{ $selectedRole->name }}</strong>.
                        Permissions should be configured according to the
                        organization's approval hierarchy. A user must never
                        be allowed to approve their own financial request.
                    </p>

                </div>

            </div>

        </form>

        @else

            <div class="notice">
                <i class="bi bi-shield-check"></i>
                <p>No role selected. Choose a role from the list to configure its permissions.</p>
            </div>

        @endif

    </div>

</div>


@endsection


@push('scripts')

<script>

    document
        .getElementById('resetPermissions')
        ?.addEventListener('click', function () {

            document
                .querySelectorAll(
                    '#rolePermissionsForm input[type="checkbox"]'
                )
                .forEach(function (checkbox) {

                    checkbox.checked = checkbox.defaultChecked;

                });

        });

</script>

@endpush