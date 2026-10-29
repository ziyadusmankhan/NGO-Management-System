@extends('layouts.app')

@section('title', 'Members')

@push('styles')
<link rel="stylesheet" href="{{ asset('css/views/members-index-1.css') }}">
@endpush


@section('content')

<div class="members-header">

    <div>

        <h1>Members</h1>

        <p>
            Manage organization members, committees, positions and membership status.
        </p>

    </div>

    <div class="header-actions">

        <button
            type="button"
            class="btn-secondary-custom"
            onclick="alert('Member export will be connected later.')"
        >
            <i class="bi bi-download"></i>
            Export
        </button>

        @if(auth()->user()?->hasPermission('members.create'))<a
            href="{{ route('members.create') }}"
            class="btn-primary-custom"
        >
            <i class="bi bi-plus-lg"></i>
            Add Member
        </a>@endif

    </div>

</div>


@if (session('success'))

    <div
        style="
            margin-bottom:18px;
            padding:13px 15px;
            border-radius:10px;
            background:#edf7f0;
            border:1px solid #d8e9dd;
            color:#397955;
            font-size:14px;
        "
    >

        <i class="bi bi-check-circle"></i>

        {{ session('success') }}

    </div>

@endif


<div class="member-summary-grid">

    <div class="member-summary-card">

        <div class="summary-label">
            Total Members
        </div>

        <div class="summary-value">
            {{ number_format($totalMembers) }}
        </div>

        <div class="summary-meta">
            All registered members
        </div>

    </div>


    <div class="member-summary-card">

        <div class="summary-label">
            Active
        </div>

        <div class="summary-value">
            {{ number_format($activeMembers) }}
        </div>

        <div class="summary-meta">
            Current active members
        </div>

    </div>


    <div class="member-summary-card">

        <div class="summary-label">
            Suspended
        </div>

        <div class="summary-value">
            {{ number_format($suspendedMembers) }}
        </div>

        <div class="summary-meta">
            Requires review
        </div>

    </div>


    <div class="member-summary-card">

        <div class="summary-label">
            Committees
        </div>

        <div class="summary-value">
            {{ $totalCommittees }}
        </div>

        <div class="summary-meta">
            Active committees
        </div>

            </div>

</div>


<div class="members-card">

    <div class="filter-bar">

        <form
            method="GET"
            action="{{ route('members.index') }}"
            id="memberFilterForm"
            style="
                padding:16px 18px;
                display:flex;
                align-items:center;
                gap:10px;
                flex-wrap:wrap;
                border-bottom:1px solid #eef1ef;
            "
        >

            <div class="member-search">

                <i class="bi bi-search"></i>

                <input
                    type="text"
                    name="search"
                    value="{{ request('search') }}"
                    placeholder="Search by name, Member ID or contact..."
                >

            </div>


            <select
                class="filter-select"
                name="status"
                onchange="this.form.submit()"
            >

                <option value="">
                    All Statuses
                </option>

                <option
                    value="active"
                    @selected(request('status') === 'active')
                >
                    Active
                </option>

                <option
                    value="inactive"
                    @selected(request('status') === 'inactive')
                >
                    Inactive
                </option>

                <option
                    value="suspended"
                    @selected(request('status') === 'suspended')
                >
                    Suspended
                </option>

                <option
                    value="resigned"
                    @selected(request('status') === 'resigned')
                >
                    Resigned
                </option>

            </select>


            <button
                type="submit"
                class="btn-primary-custom"
            >
                <i class="bi bi-search"></i>
                Search
            </button>

        </form>
        

    </div>


    <div class="table-container">

        <table class="members-table">

            <thead>

                <tr>

                    <th>Member</th>
                    <th>Contact</th>
                    <th>Location</th>
                    <th>Committee / Position</th>
                    <th>Joined</th>
                    <th>Status</th>
                    <th></th>

                </tr>

            </thead>


            <tbody id="membersTableBody">

                @forelse ($members as $member)

                    @php

                        $initials =
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
                                        substr(
                                            $part,
                                            0,
                                            1
                                        )
                                    )
                            )
                            ->implode('');

                        $statusClass =
                            match ($member->status) {

                                'active' =>
                                    'status-active',

                                'inactive' =>
                                    'status-inactive',

                                'suspended' =>
                                    'status-suspended',

                                'resigned' =>
                                    'status-resigned',

                                default =>
                                    'status-inactive',

                            };

                    @endphp


                    <tr
                        data-status="{{ $member->status }}"
                    >

                        <td>

                            <div class="member-profile">

                                <div class="member-avatar">
                                    {{ $initials ?: '?' }}
                                </div>

                                <div>

                                    <div class="member-name">
                                        {{ $member->full_name }}
                                    </div>

                                    <div class="member-id">
                                        {{ $member->member_number }}
                                    </div>

                                </div>

                            </div>

                        </td>


                        <td class="member-contact">

                            {{ $member->phone }}

                            @if ($member->email)
                                <br>
                                {{ $member->email }}
                            @endif

                        </td>


                        <td class="member-location">

                            {{ $member->address ?: '—' }}

                        </td>


                        <td>

                            @if ($member->committees->isNotEmpty())

                                @foreach ($member->committees as $committee)

                                    <div class="committee-name">
                                        {{ $committee->name }}
                                    </div>

                                    <div class="position-name">
                                        {{ $committee->pivot->position }}
                                    </div>

                                    @if (!$loop->last)
                                        <div style="
                                            height:1px;
                                            margin:5px 0;
                                            background:#eef1ef;
                                        "></div>
                                    @endif

                                @endforeach

                            @else

                                <div class="committee-name">
                                    —
                                </div>

                                <div class="position-name">
                                    Not assigned
                                </div>

                            @endif

                        </td>


                        <td>

                            {{ $member->joining_date
                                ? $member->joining_date->format('d M Y')
                                : '—'
                            }}

                        </td>


                        <td>

                            <span
                                class="status-badge {{ $statusClass }}"
                            >
                                {{ ucfirst($member->status) }}
                            </span>

                        </td>


                        <td>

                            @if(auth()->user()?->hasPermission('members.view'))<a
                                href="{{ route(
                                    'members.show',
                                    ['member' => $member->id]
                                ) }}"
                                class="member-action"
                                title="View member"
                            >
                                <i class="bi bi-eye"></i>
                            </a>@endif

                        </td>

                    </tr>

                @empty

                    <tr>

                        <td
                            colspan="7"
                            style="
                                padding:45px;
                                text-align:center;
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

                            No members have been added yet.

                            <div style="margin-top:12px;">

                                @if(auth()->user()?->hasPermission('members.create'))<a
                                    href="{{ route('members.create') }}"
                                    class="btn-primary-custom"
                                >
                                    <i class="bi bi-plus-lg"></i>
                                    Add First Member
                                </a>@endif

                            </div>

                        </td>

                    </tr>

                @endforelse

            </tbody>

        </table>

    </div>


    <div class="table-footer">

        <div class="results-count">

            Showing
            {{ $members->firstItem() ?? 0 }}
            –
            {{ $members->lastItem() ?? 0 }}
            of
            {{ $members->total() }}
            members

        </div>


        <div class="pagination">

            {{ $members->links() }}

        </div>

    </div>

</div>

@endsection

@push('scripts')

<script>

    const searchInput =
        document.getElementById('memberSearch');

    const statusFilter =
        document.getElementById('statusFilter');

    const rows =
        document.querySelectorAll(
            '#membersTableBody tr[data-status]'
        );


    function filterMembers() {

        const search =
            searchInput.value
                .toLowerCase()
                .trim();

        const status =
            statusFilter.value;


        rows.forEach(row => {

            const text =
                row.textContent
                    .toLowerCase();

            const rowStatus =
                row.dataset.status;


            const matchesSearch =
                !search ||
                text.includes(search);


            const matchesStatus =
                !status ||
                rowStatus === status;


            row.style.display =
                matchesSearch &&
                matchesStatus
                    ? ''
                    : 'none';

        });

    }


    searchInput?.addEventListener(
        'input',
        filterMembers
    );


    statusFilter?.addEventListener(
        'change',
        filterMembers
    );

</script>

@endpush
