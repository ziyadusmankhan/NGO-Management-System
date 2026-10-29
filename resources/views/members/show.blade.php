@extends('layouts.app')

@section('title', 'Member Details')

@push('styles')
<link rel="stylesheet" href="{{ asset('css/views/members-show-1.css') }}">
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

        @if(auth()->user()?->hasPermission('members.view'))<a
            href="{{ route('members.index') }}"
            class="btn"
        >
            <i class="bi bi-arrow-left"></i>
            Back
        </a>@endif

        @if(auth()->user()?->hasPermission('members.update'))<a
            href="{{ route('members.edit', $member) }}"
            class="btn primary"
        >
            <i class="bi bi-pencil"></i>
            Edit
        </a>@endif

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