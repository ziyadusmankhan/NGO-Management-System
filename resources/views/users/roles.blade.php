@extends('layouts.app')

@section('title', 'Roles & Permissions')

@push('styles')
<style>
    .header {
        display:flex;
        justify-content:space-between;
        align-items:flex-end;
        gap:20px;
        margin-bottom:24px;
    }

    .header h1 {
        margin:0;
        font-size: 25px;
        font-weight:700;
        letter-spacing:-.5px;
    }

    .header p {
        margin:7px 0 0;
        color:var(--muted);
        font-size: 16px;
    }

    .role-layout {
        display:grid;
        grid-template-columns:250px minmax(0,1fr);
        gap:18px;
    }

    .card {
        background:#fff;
        border:1px solid var(--border);
        border-radius:14px;
        overflow:hidden;
    }

    .role-list {
        padding:9px;
    }

    .role-item {
        display:flex;
        align-items:center;
        gap:10px;
        padding:11px;
        border-radius:10px;
        cursor:pointer;
        margin-bottom:3px;
    }

    .role-item:hover {
        background:#f7faf8;
    }

    .role-item.active {
        background:var(--primary-soft);
        color:var(--primary);
    }

    .role-icon {
        width:34px;
        height:34px;
        border-radius:9px;
        background:#f3f6f4;
        display:flex;
        align-items:center;
        justify-content:center;
        color:var(--primary);
        flex-shrink:0;
    }

    .role-item.active .role-icon {
        background:#dfece6;
    }

    .role-info strong {
        display:block;
        font-size: 16px;
    }

    .role-info span {
        display:block;
        margin-top:3px;
        color: #6c7470;
        font-size: 16px;
    }

    .permission-head {
        padding:18px 20px;
        border-bottom:1px solid #eef1ef;
        display:flex;
        justify-content:space-between;
        align-items:center;
        gap:15px;
    }

    .permission-head strong {
        font-size: 16px;
    }

    .permission-head span {
        display:block;
        margin-top:4px;
        color: #6c7470;
        font-size: 16px;
    }

    .permission-actions {
        display:flex;
        gap:7px;
    }

    .small-btn {
        height:34px;
        padding:0 11px;
        border:1px solid var(--border);
        border-radius:8px;
        background:#fff;
        color:#637069;
        font-size: 16px;
        font-weight:650;
        cursor:pointer;
    }

    .small-btn.primary {
        background:var(--primary);
        border-color:var(--primary);
        color:#fff;
    }

    .role-overview {
        padding:17px 20px;
        background:#fafcfb;
        border-bottom:1px solid #eef1ef;
        display:grid;
        grid-template-columns:repeat(3,minmax(0,1fr));
        gap:12px;
    }

    .overview-item label {
        display:block;
        color: #6d7571;
        font-size: 16px;
        text-transform:uppercase;
        letter-spacing:.5px;
    }

    .overview-item strong {
        display:block;
        margin-top:4px;
        font-size: 16px;
    }

    .permissions {
        padding:18px 20px;
    }

    .permission-section {
        margin-bottom:20px;
    }

    .permission-section:last-child {
        margin-bottom:0;
    }

    .section-title {
        margin-bottom:9px;
        font-size: 16px;
        font-weight:700;
        text-transform:uppercase;
        letter-spacing:.7px;
        color: #6c7671;
    }

    .permission-row {
        display:grid;
        grid-template-columns:minmax(0,1fr) 65px 65px 65px;
        align-items:center;
        gap:8px;
        padding:11px 0;
        border-bottom:1px solid #f0f3f1;
    }

    .permission-row:last-child {
        border-bottom:none;
    }

    .permission-name strong {
        display:block;
        font-size: 16px;
    }

    .permission-name span {
        display:block;
        margin-top:3px;
        color: #6f7672;
        font-size: 16px;
    }

    .permission-column {
        text-align:center;
        color: #6d7571;
        font-size: 16px;
        text-transform:uppercase;
    }

    .check {
        width:16px;
        height:16px;
        margin:auto;
        accent-color:var(--primary);
        cursor:pointer;
    }

    .locked {
        opacity:.55;
    }

    .notice {
        margin-top:18px;
        padding:13px;
        display:flex;
        gap:9px;
        background:#f7f9f8;
        border:1px solid #e5ebe8;
        border-radius:10px;
    }

    .notice i {
        color:var(--primary);
        font-size: 17px;
    }

    .notice p {
        margin:0;
        color: #6b766f;
        font-size: 16px;
        line-height:1.6;
    }

    @media(max-width:850px) {
        .role-layout {
            grid-template-columns:1fr;
        }
    }

    @media(max-width:650px) {
        .header {
            flex-direction:column;
            align-items:flex-start;
        }

        .role-overview {
            grid-template-columns:1fr;
        }

        .permission-row {
            grid-template-columns:minmax(0,1fr) 45px 45px 45px;
        }
    }
</style>
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

                <a
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

                </a>

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