@extends('layouts.app')

@section('title', 'Edit Member')

<style>
    .edit-layout {
        display:grid;
        grid-template-columns:minmax(0,1fr) 300px;
        gap:18px;
    }

    .edit-card {
        background:#fff;
        border:1px solid var(--border);
        border-radius:14px;
        overflow:hidden;
        margin-bottom:18px;
    }

    .edit-head {
        padding:17px 20px;
        border-bottom:1px solid #eef1ef;
    }

    .edit-title {
        font-size:13px;
        font-weight:700;
    }

    .edit-desc {
        margin-top:4px;
        color:#929d97;
        font-size:9px;
    }

    .edit-body {
        padding:20px;
    }

    .edit-grid {
        display:grid;
        grid-template-columns:repeat(2,minmax(0,1fr));
        gap:17px;
    }

    .field {
        min-width:0;
    }

    .field.full {
        grid-column:1/-1;
    }

    .field label {
        display:block;
        margin-bottom:7px;
        font-size:10px;
        font-weight:650;
    }

    .field input,
    .field select,
    .field textarea {
        width:100%;
        border:1px solid var(--border);
        border-radius:9px;
        outline:none;
        background:#fff;
        padding:0 12px;
        font-size:10px;
    }

    .field input,
    .field select {
        height:42px;
    }

    .field textarea {
        min-height:110px;
        padding:11px 12px;
        resize:vertical;
    }

    .field input:focus,
    .field select:focus,
    .field textarea:focus {
        border-color:#6b7771;
        box-shadow:0 0 0 3px rgba(23,63,53,.055);
    }

    .error {
        margin-top:5px;
        color:#a85f58;
        font-size:8px;
    }

    .status-row {
        display:flex;
        flex-wrap:wrap;
        gap:8px;
    }

    .status-row input {
        display:none;
    }

    .status-row label {
        padding:7px 10px;
        border:1px solid var(--border);
        border-radius:20px;
        font-size:9px;
        cursor:pointer;
    }

    .status-row input:checked + label {
        background:var(--primary-soft);
        border-color:#c7d8d0;
        color:var(--primary);
    }

    .side {
        background:#fff;
        border:1px solid var(--border);
        border-radius:14px;
        padding:18px;
        margin-bottom:18px;
    }

    .side h3 {
        margin:0 0 15px;
        font-size:12px;
    }

    .avatar {
        width:64px;
        height:64px;
        margin:0 auto 10px;
        border-radius:17px;
        background:var(--primary-soft);
        color:var(--primary);
        display:flex;
        align-items:center;
        justify-content:center;
        font-size:18px;
        font-weight:700;
    }

    .preview-name {
        text-align:center;
        font-size:12px;
        font-weight:700;
    }

    .preview-id {
        margin-top:4px;
        text-align:center;
        color:#929d97;
        font-size:8px;
    }

    .side-row {
        display:flex;
        justify-content:space-between;
        gap:10px;
        padding:9px 0;
        border-bottom:1px solid #f0f3f1;
        font-size:9px;
    }

    .side-row:last-child {
        border-bottom:none;
    }

    .side-row span:first-child {
        color:#98a29d;
    }

    .side-row span:last-child {
        color:#4a5750;
        font-weight:650;
        text-align:right;
    }

    .actions {
        display:flex;
        justify-content:flex-end;
        gap:8px;
        margin-bottom:18px;
    }

    .btn {
        height:40px;
        padding:0 14px;
        display:inline-flex;
        align-items:center;
        justify-content:center;
        gap:7px;
        border:1px solid var(--border);
        background:#fff;
        border-radius:9px;
        text-decoration:none;
        font-size:10px;
        font-weight:650;
        cursor:pointer;
    }

    .btn.primary {
        color:#fff;
        background:var(--primary);
        border-color:var(--primary);
    }

    @media(max-width:900px) {
        .edit-layout {
            grid-template-columns:1fr;
        }
    }

    @media(max-width:650px) {
        .edit-grid {
            grid-template-columns:1fr;
        }

        .field.full {
            grid-column:auto;
        }

        .actions {
            width:100%;
        }

        .actions .btn {
            flex:1;
        }
    }
</style>


@section('content')

@php
    $fullName = $member->full_name;
@endphp


<div style="
    display:flex;
    justify-content:space-between;
    align-items:flex-start;
    gap:20px;
    margin-bottom:24px;
">

    <div>

        <h1 style="
            margin:0;
            font-size:25px;
            font-weight:700;
        ">
            Edit Member
        </h1>

        <p style="
            margin:7px 0 0;
            color:var(--muted);
            font-size:12px;
        ">
            Update {{ $member->member_number }} — {{ $fullName }}
        </p>

    </div>


    <div class="actions">

        <a
            href="{{ route('members.show', $member) }}"
            class="btn"
        >
            <i class="bi bi-arrow-left"></i>
            Cancel
        </a>

        <button
            type="submit"
            form="editMemberForm"
            class="btn primary"
        >
            <i class="bi bi-check-lg"></i>
            Save Changes
        </button>

    </div>

</div>


<form
    id="editMemberForm"
    method="POST"
    action="{{ route('members.update', $member) }}"
>

    @csrf
    @method('PUT')


    @if ($errors->any())

        <div class="edit-card">

            <div class="edit-body">

                <div style="
                    color:#a85f58;
                    font-size:10px;
                    font-weight:650;
                ">
                    Please correct the highlighted information.
                </div>

                <ul style="
                    margin:8px 0 0;
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


    <div class="edit-layout">

        <div>

            <div class="edit-card">

                <div class="edit-head">

                    <div class="edit-title">
                        Personal Information
                    </div>

                    <div class="edit-desc">
                        Update the member's personal and contact information.
                    </div>

                </div>

                <div class="edit-body">

                    <div class="edit-grid">

                        <div class="field">

                            <label>
                                Member ID
                            </label>

                            <input
                                value="{{ $member->member_number }}"
                                readonly
                            >

                        </div>


                        <div class="field">

                            <label>
                                Full Name *
                            </label>

                            <input
                                name="full_name"
                                value="{{ old('full_name', $member->full_name) }}"
                                required
                            >

                            @error('full_name')
                                <div class="error">
                                    {{ $message }}
                                </div>
                            @enderror

                        </div>


                        <div class="field">

                            <label>
                                Father / Husband Name
                            </label>

                            <input
                                name="father_name"
                                value="{{ old('father_name', $member->father_name) }}"
                            >

                        </div>


                        <div class="field">

                            <label>
                                CNIC
                            </label>

                            <input
                                name="cnic"
                                value="{{ old('cnic', $member->cnic) }}"
                            >

                            @error('cnic')
                                <div class="error">
                                    {{ $message }}
                                </div>
                            @enderror

                        </div>


                        <div class="field">

                            <label>
                                Phone *
                            </label>

                            <input
                                name="phone"
                                value="{{ old('phone', $member->phone) }}"
                                required
                            >

                            @error('phone')
                                <div class="error">
                                    {{ $message }}
                                </div>
                            @enderror

                        </div>


                        <div class="field">

                            <label>
                                Email
                            </label>

                            <input
                                type="email"
                                name="email"
                                value="{{ old('email', $member->email) }}"
                            >

                            @error('email')
                                <div class="error">
                                    {{ $message }}
                                </div>
                            @enderror

                        </div>


                        <div class="field full">

                            <label>
                                Address
                            </label>

                            <input
                                name="address"
                                value="{{ old('address', $member->address) }}"
                            >

                        </div>


                    </div>

                </div>

            </div>


            <div class="edit-card">

                <div class="edit-head">

                    <div class="edit-title">
                        Membership Information
                    </div>

                    <div class="edit-desc">
                        Update membership dates and current status.
                    </div>

                </div>

                <div class="edit-body">

                    <div class="edit-grid">

                        <div class="field">

                            <label>
                                Joining Date
                            </label>

                            <input
                                type="date"
                                name="joining_date"
                                value="{{
                                    old(
                                        'joining_date',
                                        optional($member->joining_date)->format('Y-m-d')
                                    )
                                }}"
                            >

                        </div>


                        <div class="field">

                            <label>
                                Status
                            </label>

                            <div class="status-row">

                                @foreach([
                                    'active' => 'Active',
                                    'inactive' => 'Inactive',
                                    'suspended' => 'Suspended',
                                    'resigned' => 'Resigned'
                                ] as $value => $label)

                                    <div>

                                        <input
                                            type="radio"
                                            id="status_{{ $value }}"
                                            name="status"
                                            value="{{ $value }}"
                                            @checked(
                                                old(
                                                    'status',
                                                    $member->status
                                                ) === $value
                                            )
                                        >

                                        <label for="status_{{ $value }}">
                                            {{ $label }}
                                        </label>

                                    </div>

                                @endforeach

                            </div>

                        </div>


                        <div class="field full">

                            <label>
                                Notes
                            </label>

                            <textarea
                                name="notes"
                            >{{ old('notes', $member->notes) }}</textarea>

                        </div>

                    </div>

                </div>

            </div>


            <div class="edit-card">

                <div class="edit-head">

                    <div class="edit-title">
                        Committee Assignment
                    </div>

                    <div class="edit-desc">
                        Committee relationships will be connected in the next backend step.
                    </div>

                </div>

                <div class="edit-body">

                    <div style="
                        padding:13px;
                        background:#f7f9f8;
                        border:1px solid #e5ebe8;
                        border-radius:10px;
                        color:#748079;
                        font-size:8px;
                        line-height:1.6;
                    ">

                        Committee assignment is intentionally not stored
                        directly on the member record. We will create the
                        proper committee-membership relationship next.

                    </div>

                </div>

            </div>

        </div>


        <aside>

            <div class="side">

                <h3>
                    Member Preview
                </h3>

                <div class="avatar">

                    {{
                        collect(
                            preg_split(
                                '/\s+/',
                                trim($member->full_name)
                            )
                        )
                        ->take(2)
                        ->map(
                            fn ($part) =>
                                strtoupper(
                                    substr($part, 0, 1)
                                )
                        )
                        ->implode('')
                    }}

                </div>

                <div class="preview-name">
                    {{ $member->full_name }}
                </div>

                <div class="preview-id">
                    {{ $member->member_number }}
                </div>

                <div style="
                    height:1px;
                    background:#eef1ef;
                    margin:16px 0;
                "></div>

                <div class="side-row">
                    <span>Status</span>
                    <span>{{ ucfirst($member->status) }}</span>
                </div>

                <div class="side-row">
                    <span>Phone</span>
                    <span>{{ $member->phone }}</span>
                </div>

                <div class="side-row">
                    <span>Committee</span>
                    <span>Not assigned</span>
                </div>

            </div>

        </aside>

    </div>

</form>

@endsection