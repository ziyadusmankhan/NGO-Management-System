@extends('layouts.app')

@section('title', 'Edit User')

@push('styles')
<link rel="stylesheet" href="{{ asset('css/views/users-edit-1.css') }}">
@endpush


@section('content')
<div class="alert alert-light">Before changing account access, <a href="{{ route('security.confirm') }}">confirm your password</a>. Confirmation lasts 15 minutes.</div>


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

        @if(auth()->user()?->hasPermission('users.view'))<a
            href="{{ route('users.show', $user) }}"
            class="btn-ui secondary"
        >
            <i class="bi bi-arrow-left"></i>
            Back
        </a>@endif

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
                color:var(--legacy-text-a85f58, #a85f58);
                font-size:10px;
                font-weight:650;
                margin-bottom:8px;
            ">
                Please correct the following:
            </div>

            <ul style="
                margin:0;
                padding-left:18px;
                color:var(--legacy-text-a85f58, #a85f58);
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
                                name="unused_password" disabled
                                type="password"
                                class="control"
                                placeholder="Use Password recovery on account details"
                            >

                            <div class="help">
                                Use the Password recovery action on the account details page.
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
                                name="unused_password_confirmation" disabled
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

                        @if (!auth()->user()->hasPermission('users.assign-roles') || auth()->id() === $user->id)
                            <p>Role assignment needs separate authorization. You cannot change your own roles.</p>
                        @endif

                        @forelse ($roles as $role)

                            <div class="role-option">

                                <input
                                    type="radio"
                                    name="roles[]"
                                    @disabled(!auth()->user()->hasPermission('users.assign-roles') || auth()->id() === $user->id)
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
                                color:var(--legacy-text-89948f, #89948f);
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
