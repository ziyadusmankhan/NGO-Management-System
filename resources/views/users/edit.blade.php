@extends('layouts.app')

@section('title', 'Edit User')

@push('styles')
<style>
    .page-head {
        display:flex;
        justify-content:space-between;
        align-items:flex-start;
        gap:20px;
        margin-bottom:24px;
    }

    .page-head h1 {
        margin:0;
        font-size:25px;
        font-weight:700;
        letter-spacing:-.5px;
    }

    .page-head p {
        margin:7px 0 0;
        color:var(--muted);
        font-size:12px;
    }

    .actions {
        display:flex;
        gap:8px;
    }

    .btn-ui {
        height:40px;
        padding:0 14px;
        border-radius:10px;
        display:inline-flex;
        align-items:center;
        justify-content:center;
        gap:7px;
        font-size:11px;
        font-weight:650;
        text-decoration:none;
        cursor:pointer;
    }

    .btn-ui.secondary {
        background:#fff;
        border:1px solid var(--border);
        color:#637069;
    }

    .btn-ui.primary {
        background:var(--primary);
        border:1px solid var(--primary);
        color:#fff;
    }

    .layout {
        display:grid;
        grid-template-columns:minmax(0,1fr) 300px;
        gap:18px;
    }

    .card {
        background:#fff;
        border:1px solid var(--border);
        border-radius:14px;
        overflow:hidden;
        margin-bottom:18px;
    }

    .card-head {
        padding:17px 20px;
        border-bottom:1px solid #eef1ef;
    }

    .card-head strong {
        font-size:12px;
    }

    .card-head span {
        display:block;
        margin-top:4px;
        color:#929d97;
        font-size:9px;
    }

    .card-body {
        padding:20px;
    }

    .grid {
        display:grid;
        grid-template-columns:repeat(2,minmax(0,1fr));
        gap:17px;
    }

    .group.full {
        grid-column:1/-1;
    }

    .label {
        display:block;
        margin-bottom:7px;
        color:#36433d;
        font-size:10px;
        font-weight:650;
    }

    .required {
        color:#b35b4b;
    }

    .control,
    .select {
        width:100%;
        height:42px;
        border:1px solid var(--border);
        border-radius:9px;
        padding:0 12px;
        background:#fff;
        color:var(--text);
        font-size:10px;
        outline:none;
    }

    .control:focus,
    .select:focus {
        border-color:#6b7670;
        box-shadow:0 0 0 3px rgba(23,63,53,.055);
    }

    .help {
        margin-top:5px;
        color:#6d7571;
        font-size:9px;
        line-height:1.5;
    }

    .error {
        margin-top:5px;
        color:#a85f58;
        font-size:8px;
    }

    .role-grid {
        display:grid;
        grid-template-columns:repeat(2,minmax(0,1fr));
        gap:9px;
    }

    .role-option {
        position:relative;
    }

    .role-option input {
        position:absolute;
        opacity:0;
        pointer-events:none;
    }

    .role-option label {
        min-height:58px;
        padding:10px;
        border:1px solid var(--border);
        border-radius:10px;
        display:flex;
        flex-direction:column;
        justify-content:center;
        cursor:pointer;
        transition:.15s;
    }

    .role-option label strong {
        font-size:10px;
    }

    .role-option label span {
        margin-top:4px;
        color:#6c7470;
        font-size:8px;
        line-height:1.4;
    }

    .role-option input:checked + label {
        background:var(--primary-soft);
        border-color:#6b7570;
        color:var(--primary);
    }

    .preview {
        background:linear-gradient(145deg,#f8faf9,#f2f6f4);
        border:1px solid var(--border);
        border-radius:14px;
        padding:20px;
        margin-bottom:18px;
    }

    .preview-label {
        color:#6d7672;
        font-size:8px;
        text-transform:uppercase;
        letter-spacing:.8px;
    }

    .avatar {
        width:64px;
        height:64px;
        margin:15px 0 11px;
        border-radius:18px;
        background:var(--primary);
        color:#fff;
        display:flex;
        align-items:center;
        justify-content:center;
        font-size:18px;
        font-weight:700;
    }

    .preview-name {
        font-size:13px;
        font-weight:700;
    }

    .preview-email {
        margin-top:4px;
        color:#6c7570;
        font-size:9px;
    }

    .preview-divider {
        height:1px;
        background:#dde5e1;
        margin:16px 0;
    }

    .preview-row {
        display:flex;
        justify-content:space-between;
        gap:12px;
        padding:7px 0;
        font-size:9px;
    }

    .preview-row span:first-child {
        color:#6e7672;
    }

    .preview-row span:last-child {
        font-weight:650;
        color:#4a5750;
        text-align:right;
    }

    .notice {
        display:flex;
        gap:9px;
        padding:12px;
        border-radius:10px;
        background:#f7f9f8;
        border:1px solid #e5ebe8;
    }

    .notice i {
        color:var(--primary);
        font-size:14px;
    }

    .notice p {
        margin:0;
        color:#6b766f;
        font-size:8px;
        line-height:1.6;
    }

    @media(max-width:900px) {
        .layout {
            grid-template-columns:1fr;
        }
    }

    @media(max-width:650px) {
        .page-head {
            flex-direction:column;
        }

        .actions {
            width:100%;
        }

        .actions .btn-ui {
            flex:1;
        }

        .grid,
        .role-grid {
            grid-template-columns:1fr;
        }

        .group.full {
            grid-column:auto;
        }
    }
</style>
@endpush


@section('content')

@php
    $userRoleIds = old('roles', $user->roles->pluck('id')->all());
@endphp

<div class="page-head">

    <div>

        <h1>Edit User</h1>

        <p>
            Update the account details and role for {{ $user->name }}.
        </p>

    </div>


    <div class="actions">

        <a
            href="{{ route('users.show', $user) }}"
            class="btn-ui secondary"
        >
            <i class="bi bi-arrow-left"></i>
            Back
        </a>

        <button
            type="submit"
            form="userForm"
            class="btn-ui primary"
        >
            <i class="bi bi-check-lg"></i>
            Save Changes
        </button>

    </div>

</div>


@if ($errors->any())

    <div class="card">

        <div class="card-body">

            <div style="
                color:#a85f58;
                font-size:10px;
                font-weight:650;
                margin-bottom:8px;
            ">
                Please correct the following:
            </div>

            <ul style="
                margin:0;
                padding-left:18px;
                color:#a85f58;
                font-size:8px;
            ">

                @foreach ($errors->all() as $error)

                    <li>{{ $error }}</li>

                @endforeach

            </ul>

        </div>

    </div>

@endif


<form
    id="userForm"
    method="POST"
    action="{{ route('users.update', $user) }}"
>

    @csrf
    @method('PUT')


    <div class="layout">

        <div>

            <!-- ACCOUNT -->

            <div class="card">

                <div class="card-head">

                    <strong>
                        Account Information
                    </strong>

                    <span>
                        Basic information for the system account.
                    </span>

                </div>


                <div class="card-body">

                    <div class="grid">

                        <div class="group">

                            <label class="label">
                                Full Name
                                <span class="required">*</span>
                            </label>

                            <input
                                id="userName"
                                name="name"
                                type="text"
                                class="control"
                                value="{{ old('name', $user->name) }}"
                                placeholder="Enter full name"
                                required
                            >

                            @error('name')

                                <div class="error">
                                    {{ $message }}
                                </div>

                            @enderror

                        </div>


                        <div class="group">

                            <label class="label">
                                Email Address
                                <span class="required">*</span>
                            </label>

                            <input
                                id="userEmail"
                                name="email"
                                type="email"
                                class="control"
                                value="{{ old('email', $user->email) }}"
                                placeholder="name@organization.org"
                                required
                            >

                            @error('email')

                                <div class="error">
                                    {{ $message }}
                                </div>

                            @enderror

                        </div>


                        <div class="group">

                            <label class="label">
                                Password
                            </label>

                            <input
                                name="password"
                                type="password"
                                class="control"
                                placeholder="Leave blank to keep current password"
                            >

                            <div class="help">
                                Only fill this in if you want to change the password.
                            </div>

                            @error('password')

                                <div class="error">
                                    {{ $message }}
                                </div>

                            @enderror

                        </div>


                        <div class="group">

                            <label class="label">
                                Confirm Password
                            </label>

                            <input
                                name="password_confirmation"
                                type="password"
                                class="control"
                                placeholder="Confirm new password"
                            >

                        </div>


                        <div class="group">

                            <label class="label">
                                Account Status
                            </label>

                            <select
                                name="status"
                                class="select"
                            >

                                <option
                                    value="active"
                                    @selected(
                                        old('status', $user->status) === 'active'
                                    )
                                >
                                    Active
                                </option>

                                <option
                                    value="inactive"
                                    @selected(
                                        old('status', $user->status) === 'inactive'
                                    )
                                >
                                    Inactive
                                </option>

                            </select>

                        </div>

                    </div>

                </div>

            </div>


            <!-- ROLE -->

            <div class="card">

                <div class="card-head">

                    <strong>
                        Role Assignment
                    </strong>

                    <span>
                        Select the role that determines this user's access.
                    </span>

                </div>


                <div class="card-body">

                    <div class="role-grid">

                        @forelse ($roles as $role)

                            <div class="role-option">

                                <input
                                    type="radio"
                                    name="roles[]"
                                    id="role{{ $role->id }}"
                                    value="{{ $role->id }}"
                                    @checked(
                                        in_array(
                                            $role->id,
                                            $userRoleIds
                                        )
                                    )
                                >

                                <label
                                    for="role{{ $role->id }}"
                                >

                                    <strong>
                                        {{ $role->name }}
                                    </strong>

                                    <span>
                                        {{ $role->description }}
                                    </span>

                                </label>

                            </div>

                        @empty

                            <div style="
                                grid-column:1/-1;
                                padding:20px;
                                text-align:center;
                                color:#89948f;
                                font-size:9px;
                            ">

                                No active roles have been configured.

                            </div>

                        @endforelse

                    </div>


                    @error('roles')

                        <div class="error">
                            {{ $message }}
                        </div>

                    @enderror

                </div>

            </div>


            <!-- SECURITY -->

            <div class="card">

                <div class="card-head">

                    <strong>
                        Security
                    </strong>

                    <span>
                        Additional account-management information.
                    </span>

                </div>


                <div class="card-body">

                    <div class="notice">

                        <i class="bi bi-shield-lock"></i>

                        <p>
                            User permissions are inherited from the selected
                            role. Fine-grained permission overrides will be
                            connected after the role management interface is
                            implemented.
                        </p>

                    </div>

                </div>

            </div>

        </div>


        <aside>

            <div class="preview">

                <div class="preview-label">
                    User Preview
                </div>


                <div
                    class="avatar"
                    id="previewAvatar"
                >
                    {{ strtoupper(substr($user->name, 0, 1)) }}
                </div>


                <div
                    class="preview-name"
                    id="previewName"
                >
                    {{ $user->name }}
                </div>


                <div
                    class="preview-email"
                    id="previewEmail"
                >
                    {{ $user->email }}
                </div>


                <div class="preview-divider"></div>


                <div class="preview-row">

                    <span>
                        Role
                    </span>

                    <span id="previewRole">
                        {{ optional($user->roles->first())->name ?? 'No role selected' }}
                    </span>

                </div>


                <div class="preview-row">

                    <span>
                        Status
                    </span>

                    <span id="previewStatus">
                        {{ ucfirst($user->status) }}
                    </span>

                </div>


                <div class="preview-row">

                    <span>
                        Access
                    </span>

                    <span>
                        Role-based
                    </span>

                </div>

            </div>


            <div class="card">

                <div class="card-head">
                    <strong>Security</strong>
                </div>

                <div class="card-body">

                    <div class="notice">

                        <i class="bi bi-shield-lock"></i>

                        <p>
                            Leave the password fields blank to keep the
                            user's current password unchanged.
                        </p>

                    </div>

                </div>

            </div>

        </aside>

    </div>

</form>

@endsection


@push('scripts')
<script>

    const userName =
        document.getElementById('userName');

    const userEmail =
        document.getElementById('userEmail');

    const previewName =
        document.getElementById('previewName');

    const previewEmail =
        document.getElementById('previewEmail');

    const previewAvatar =
        document.getElementById('previewAvatar');

    const previewRole =
        document.getElementById('previewRole');

    const previewStatus =
        document.getElementById('previewStatus');


    userName.addEventListener(
        'input',
        function () {

            const value =
                this.value.trim();

            previewName.textContent =
                value || 'New User';


            if (!value) {

                previewAvatar.textContent =
                    '?';

                return;

            }


            previewAvatar.textContent =
                value
                    .split(/\s+/)
                    .slice(0, 2)
                    .map(
                        x =>
                            x
                                .charAt(0)
                                .toUpperCase()
                    )
                    .join('');

        }
    );


    userEmail.addEventListener(
        'input',
        function () {

            previewEmail.textContent =
                this.value.trim()
                    || 'No email entered';

        }
    );


    document
        .querySelectorAll('input[name="roles[]"]')
        .forEach(input => {

            input.addEventListener(
                'change',
                function () {

                    const label =
                        document.querySelector(
                            `label[for="${this.id}"] strong`
                        );

                    if (label) {

                        previewRole.textContent =
                            label.textContent;

                    }

                }
            );

        });


    document
        .querySelector('select[name="status"]')
        .addEventListener(
            'change',
            function () {

                previewStatus.textContent =
                    this.options[
                        this.selectedIndex
                    ].text;

            }
        );

</script>
@endpush
