@extends('layouts.app')

@section('title', 'Member Details')

@push('styles')
<style>
    .page-header {
        display:flex;
        justify-content:space-between;
        align-items:flex-start;
        gap:20px;
        margin-bottom:22px;
    }

    .identity {
        display:flex;
        align-items:center;
        gap:14px;
    }

    .avatar {
        width:62px;
        height:62px;
        border-radius:17px;
        background:var(--primary-soft);
        color:var(--primary);
        display:flex;
        align-items:center;
        justify-content:center;
        font-size:18px;
        font-weight:700;
    }

    .title {
        font-size:24px;
        font-weight:700;
    }

    .meta {
        margin-top:4px;
        color:#8d9993;
        font-size:10px;
    }

    .actions {
        display:flex;
        gap:8px;
        flex-wrap:wrap;
    }

    .btn {
        height:39px;
        padding:0 13px;
        border-radius:9px;
        display:inline-flex;
        align-items:center;
        justify-content:center;
        gap:7px;
        border:1px solid var(--border);
        background:#fff;
        color:#65726c;
        font-size:10px;
        font-weight:650;
        text-decoration:none;
        cursor:pointer;
    }

    .btn.primary {
        background:var(--primary);
        color:#fff;
        border-color:var(--primary);
    }

    .btn.danger {
        color:#a85f58;
        border-color:#ead8d5;
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
        padding:16px 19px;
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
        padding:18px 19px;
    }

    .status {
        display:inline-flex;
        align-items:center;
        gap:5px;
        padding:5px 8px;
        border-radius:20px;
        font-size:8px;
        font-weight:650;
    }

    .status::before {
        content:"";
        width:5px;
        height:5px;
        border-radius:50%;
    }

    .status-active {
        background:#edf7f0;
        color:#397955;
    }

    .status-active::before {
        background:#4c9a6c;
    }

    .status-inactive {
        background:#f1f3f2;
        color:#6a766f;
    }

    .status-inactive::before {
        background:#9aa49f;
    }

    .status-suspended {
        background:#fff4ed;
        color:#9d674b;
    }

    .status-suspended::before {
        background:#c98561;
    }

    .status-resigned {
        background:#f3f1f7;
        color:#786d8f;
    }

    .status-resigned::before {
        background:#9586a8;
    }

    .info-grid {
        display:grid;
        grid-template-columns:repeat(2,minmax(0,1fr));
        gap:17px;
    }

    .info label {
        display:block;
        margin-bottom:5px;
        color:#98a29d;
        font-size:8px;
        text-transform:uppercase;
    }

    .info strong,
    .info p {
        display:block;
        margin:0;
        color:#4a5750;
        font-size:10px;
        line-height:1.6;
    }

    .full {
        grid-column:1/-1;
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

    .notice {
        display:flex;
        gap:9px;
        padding:12px;
        background:#f7f9f8;
        border:1px solid #e5ebe8;
        border-radius:10px;
    }

    .notice i {
        color:var(--primary);
        font-size:14px;
    }

    .notice p {
        margin:0;
        color:#748079;
        font-size:8px;
        line-height:1.6;
    }

    @media(max-width:900px) {
        .layout {
            grid-template-columns:1fr;
        }
    }

    @media(max-width:650px) {
        .page-header {
            flex-direction:column;
        }

        .actions {
            width:100%;
        }

        .actions .btn {
            flex:1;
        }

        .info-grid {
            grid-template-columns:1fr;
        }

        .full {
            grid-column:auto;
        }
    }
</style>
@endpush


@section('content')

@php
    $initials = collect(
        preg_split('/\s+/', trim($member->full_name))
    )
    ->take(2)
    ->map(fn ($part) => strtoupper(substr($part, 0, 1)))
    ->implode('');

    $statusClass = match ($member->status) {
        'active' => 'status-active',
        'inactive' => 'status-inactive',
        'suspended' => 'status-suspended',
        'resigned' => 'status-resigned',
        default => 'status-inactive',
    };
@endphp


<div class="page-header">

    <div class="identity">

        <div class="avatar">
            {{ $initials ?: '?' }}
        </div>

        <div>

            <div class="title">
                {{ $member->full_name }}
            </div>

            <div class="meta">
                {{ $member->member_number }}
            </div>

        </div>

    </div>


    <div class="actions">

        <a
            href="{{ route('members.index') }}"
            class="btn"
        >
            <i class="bi bi-arrow-left"></i>
            Back
        </a>

        <a
            href="{{ route('members.edit', $member) }}"
            class="btn primary"
        >
            <i class="bi bi-pencil"></i>
            Edit
        </a>

        <form
            method="POST"
            action="{{ route('members.destroy', $member) }}"
            onsubmit="return confirm('Archive this member?')"
        >

            @csrf
            @method('DELETE')

            <button
                type="submit"
                class="btn danger"
            >
                <i class="bi bi-archive"></i>
                Archive
            </button>

        </form>

    </div>

</div>


@if (session('success'))

    <div class="notice" style="margin-bottom:18px;">

        <i class="bi bi-check-circle"></i>

        <p>
            {{ session('success') }}
        </p>

    </div>

@endif


<div class="layout">

    <div>

        <div class="card">

            <div class="card-head">

                <strong>Member Overview</strong>

                <span>
                    Current membership status and basic information.
                </span>

            </div>

            <div class="card-body">

                <div class="info-grid">

                    <div class="info">

                        <label>
                            Status
                        </label>

                        <strong>
                            <span class="status {{ $statusClass }}">
                                {{ ucfirst($member->status) }}
                            </span>
                        </strong>

                    </div>


                    <div class="info">

                        <label>
                            Member ID
                        </label>

                        <strong>
                            {{ $member->member_number }}
                        </strong>

                    </div>


                    <div class="info">

                        <label>
                            Full Name
                        </label>

                        <strong>
                            {{ $member->full_name }}
                        </strong>

                    </div>


                    <div class="info">

                        <label>
                            Father / Husband
                        </label>

                        <strong>
                            {{ $member->father_name ?: '—' }}
                        </strong>

                    </div>


                    <div class="info">

                        <label>
                            CNIC
                        </label>

                        <strong>
                            {{ $member->cnic ?: '—' }}
                        </strong>

                    </div>


                    <div class="info">

                        <label>
                            Phone
                        </label>

                        <strong>
                            {{ $member->phone }}
                        </strong>

                    </div>


                    <div class="info">

                        <label>
                            Email
                        </label>

                        <strong>
                            {{ $member->email ?: '—' }}
                        </strong>

                    </div>


                    <div class="info">

                        <label>
                            Joining Date
                        </label>

                        <strong>
                            {{ $member->joining_date
                                ? $member->joining_date->format('d M Y')
                                : '—'
                            }}
                        </strong>

                    </div>


                    <div class="info full">

                        <label>
                            Address
                        </label>

                        <p>
                            {{ $member->address ?: '—' }}
                        </p>

                    </div>


                    <div class="info full">

                        <label>
                            Notes
                        </label>

                        <p>
                            {{ $member->notes ?: 'No notes recorded.' }}
                        </p>

                    </div>

                </div>

            </div>

        </div>


        <div class="card">

            <div class="card-head">

                <strong>Committee Assignment</strong>

                <span>
                    Committee relationships will be connected in the next backend module.
                </span>

            </div>

            <div class="card-body">

                @forelse ($member->committees as $committee)

                    <div
                        style="
                            display:flex;
                            align-items:center;
                            gap:11px;
                            padding:11px 0;
                            border-bottom:1px solid #f0f3f1;
                        "
                    >

                        <div
                            style="
                                width:38px;
                                height:38px;
                                border-radius:10px;
                                background:var(--primary-soft);
                                color:var(--primary);
                                display:flex;
                                align-items:center;
                                justify-content:center;
                            "
                        >
                            <i class="bi bi-diagram-3"></i>
                        </div>

                        <div style="flex:1;">

                            <div style="
                                font-size:10px;
                                font-weight:650;
                            ">
                                {{ $committee->name }}
                            </div>

                            <div style="
                                margin-top:3px;
                                color:#98a29d;
                                font-size:8px;
                            ">
                                {{ $committee->committee_number }}
                            </div>

                        </div>

                        <div style="
                            color:var(--primary);
                            font-size:9px;
                            font-weight:650;
                        ">
                            {{ $committee->pivot->position }}
                        </div>

                    </div>

                @empty

                    <div class="notice">

                        <i class="bi bi-people"></i>

                        <p>
                            This member is not currently assigned to any active committee.
                        </p>

                    </div>

                @endforelse

            </div>

        </div>


        <div class="card">

            <div class="card-head">

                <strong>Record History</strong>

                <span>
                    Basic member record timestamps.
                </span>

            </div>

            <div class="card-body">

                <div class="side-row">
                    <span>Created</span>
                    <span>{{ $member->created_at->format('d M Y, h:i A') }}</span>
                </div>

                <div class="side-row">
                    <span>Last Updated</span>
                    <span>{{ $member->updated_at->format('d M Y, h:i A') }}</span>
                </div>

            </div>

        </div>

    </div>


    <aside>

        <div class="card">

            <div class="card-body">

                <div
                    style="
                        text-align:center;
                        font-size:28px;
                        font-weight:700;
                        color:var(--primary);
                    "
                >
                    {{ $member->member_number }}
                </div>

                <div
                    style="
                        text-align:center;
                        margin-top:4px;
                        color:#8d9892;
                        font-size:9px;
                    "
                >
                    Member Reference
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

                <div class="side-row">
                    <span>Position</span>
                    <span>Not assigned</span>
                </div>

            </div>

        </div>


        <div class="card">

            <div class="card-head">
                <strong>Privacy</strong>
            </div>

            <div class="card-body">

                <div class="notice">

                    <i class="bi bi-shield-lock"></i>

                    <p>
                        Identity information should only be visible to
                        authorized organization users.
                    </p>

                </div>

            </div>

        </div>

    </aside>

</div>

@endsection