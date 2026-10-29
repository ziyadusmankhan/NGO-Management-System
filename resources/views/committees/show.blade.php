@extends('layouts.app')

@section('title', 'Committee Details')

@push('styles')
<link rel="stylesheet" href="{{ asset('css/views/committees-show-1.css') }}">
@endpush

@section('content')

@include('shared.action-feedback')

<div class="details-header">

    <div class="committee-identity-large">

        <div class="large-icon">
            <i class="bi bi-diagram-3"></i>
        </div>

        <div>

            <div class="details-name">
                {{ $committee->name }}
            </div>

            <div class="details-code">
                {{ $committee->committee_number }}
                &middot;
                {{ ucfirst($committee->type) }}
                &middot;
                Established
                {{
                    $committee->established_at
                        ? $committee->established_at->format('d M Y')
                        : 'Not specified'
                }}
            </div>

        </div>

    </div>


    <div class="details-actions">

        @if(auth()->user()?->hasPermission('committees.view'))<a
            href="{{ route('committees.index') }}"
            class="details-btn"
        >
            <i class="bi bi-arrow-left"></i>
            Back
        </a>@endif

        @if(auth()->user()?->hasPermission('committees.update'))<a
            href="{{ route('committees.edit', $committee) }}"
            class="details-btn primary"
        >
            <i class="bi bi-pencil"></i>
            Edit Committee
        </a>@endif

        @include('shared.archive-committee', ['buttonClass' => 'details-btn'])

    </div>

</div>


<div class="details-layout">

    <div>

        <!-- Overview -->

        <div class="details-card">

            <div class="card-header-custom">

                <div>
                    <strong>Committee Overview</strong>

                    <span>
                        Purpose, status and organizational scope
                    </span>
                </div>

                <span
                    class="active-badge"
                    style="
                        @if ($committee->status !== 'active')
                            background:var(--legacy-surface-f1f3f2, #f1f3f2);
                            color:var(--legacy-text-6a766f, #6a766f);
                        @endif
                    "
                >
                    {{ ucfirst($committee->status) }}
                </span>

            </div>

            <div class="card-body-custom">

                <p style="
                    margin:0 0 17px;
                    color:var(--legacy-text-69766f, #69766f);
                    font-size: 16px;
                    line-height:1.7;
                ">
                    {{ $committee->description ?: 'No description has been recorded for this committee.' }}
                </p>


                <div class="overview-grid">

                    <div class="overview-item">
                        <label>Type</label>
                        <strong>{{ ucfirst($committee->type) }}</strong>
                    </div>

                    <div class="overview-item">
                        <label>Status</label>
                        <strong>{{ ucfirst($committee->status) }}</strong>
                    </div>

                    <div class="overview-item">
                        <label>Members</label>
                        <strong>{{ $committee->members->count() }}</strong>
                    </div>

                    <div class="overview-item">
                        <label>Committee Number</label>
                        <strong>{{ $committee->committee_number }}</strong>
                    </div>

                    <div class="overview-item">
                        <label>Established</label>
                        <strong>
                            {{
                                $committee->established_at
                                    ? $committee->established_at->format('d M Y')
                                    : '—'
                            }}
                        </strong>
                    </div>

                    <div class="overview-item">
                        <label>Active Members</label>
                        <strong>{{ $committee->members->where('pivot.status', 'active')->count() }}</strong>
                    </div>

                </div>

            </div>

        </div>


        <!-- Responsibilities -->

        <div class="details-card">

            <div class="card-header-custom">

                <div>

                    <strong>Responsibilities</strong>

                    <span>
                        Configured responsibilities for this committee
                    </span>

                </div>

                <button class="details-btn">
                    <i class="bi bi-sliders"></i>
                    Configure
                </button>

            </div>


            <div class="card-body-custom">

                <div class="responsibility-grid">

                    <div class="responsibility">
                        <i class="bi bi-check2-circle"></i>
                        <span>Review and approve projects</span>
                    </div>

                    <div class="responsibility">
                        <i class="bi bi-check2-circle"></i>
                        <span>Approve fund allocations</span>
                    </div>

                    <div class="responsibility">
                        <i class="bi bi-check2-circle"></i>
                        <span>Review beneficiary applications</span>
                    </div>

                    <div class="responsibility">
                        <i class="bi bi-check2-circle"></i>
                        <span>Review exceptional expenses</span>
                    </div>

                    <div class="responsibility">
                        <i class="bi bi-check2-circle"></i>
                        <span>Monitor approved projects</span>
                    </div>

                    <div class="responsibility">
                        <i class="bi bi-check2-circle"></i>
                        <span>Review financial reports</span>
                    </div>

                </div>

            </div>

        </div>


        <!-- Members -->

        <div class="details-card">

            <div class="card-header-custom">

                <div>

                    <strong>Committee Members</strong>

                    <span>
                        Current members and assigned positions
                    </span>

                </div>

                <button
                    type="button"
                    class="details-btn"
                    onclick="document.getElementById('assignMemberPanel').scrollIntoView({ behavior: 'smooth' })"
                >
                    <i class="bi bi-person-plus"></i>
                    Manage Members
                </button>

            </div>


            <div class="card-body-custom">

                @forelse ($committee->members as $member)

                    <div class="member-row">

                        <div class="member-avatar">
                            {{ strtoupper(substr($member->first_name, 0, 1) . substr($member->last_name ?? '', 0, 1)) }}
                        </div>

                        <div class="member-info">

                            <strong>
                                {{ $member->full_name }}
                            </strong>

                            <span>
                                {{ $member->member_number }}
                            </span>

                        </div>

                        <div class="member-role">
                            {{ $member->pivot->position }}
                        </div>

                        @if ($member->pivot->status === 'active')

                            <form
                                method="POST"
                                action="{{
                                    route(
                                        'committees.members.remove',
                                        [
                                            'committee' => $committee,
                                            'member' => $member,
                                        ]
                                    )
                                }}"
                                onsubmit="return confirm('End this member assignment?')"
                                style="margin-left:10px;"
                            >
                                @csrf
                                @method('DELETE')

                                <button
                                    type="submit"
                                    class="details-btn"
                                    title="Remove from committee"
                                >
                                    <i class="bi bi-person-dash"></i>
                                </button>
                            </form>

                        @endif

                    </div>

                @empty

                    <div style="
                        padding:30px;
                        text-align:center;
                        color:var(--legacy-text-89948f, #89948f);
                        font-size:16px;
                    ">
                        No members are currently assigned to this committee.
                    </div>

                @endforelse

            </div>

        </div>


        <!-- Assign Member -->

        <div class="details-card" id="assignMemberPanel">

            <div class="card-header-custom">

                <div>

                    <strong>Assign Member</strong>

                    <span>
                        Add an existing member to this committee
                    </span>

                </div>

            </div>

            <div class="card-body-custom">

                @if ($availableMembers->isEmpty())

                    <div style="
                        padding:10px 0;
                        color:var(--legacy-text-89948f, #89948f);
                        font-size:16px;
                    ">
                        There are no active members available to assign.
                    </div>

                @else

                    <form
                        method="POST"
                        action="{{ route('committees.members.assign', $committee) }}"
                    >

                        @csrf

                        <div style="
                            display:grid;
                            grid-template-columns: repeat(3, minmax(0, 1fr));
                            gap:14px;
                        ">

                            <div>

                                <label style="
                                    display:block;
                                    margin-bottom:7px;
                                    font-size:16px;
                                    font-weight:650;
                                ">
                                    Member
                                </label>

                                <select
                                    name="member_id"
                                    required
                                    style="
                                        width:100%;
                                        height:40px;
                                        border:1px solid var(--border);
                                        border-radius:9px;
                                        padding:0 10px;
                                    "
                                >
                                    <option value="">Select member</option>

                                    @foreach ($availableMembers as $availableMember)
                                        <option
                                            value="{{ $availableMember->id }}"
                                            @selected(old('member_id') == $availableMember->id)
                                        >
                                            {{ $availableMember->full_name }}
                                            ({{ $availableMember->member_number }})
                                        </option>
                                    @endforeach
                                </select>

                                @error('member_id')
                                    <div style="color:var(--legacy-text-b3413c, #b3413c);font-size:14px;margin-top:5px;">
                                        {{ $message }}
                                    </div>
                                @enderror

                            </div>

                            <div>

                                <label style="
                                    display:block;
                                    margin-bottom:7px;
                                    font-size:16px;
                                    font-weight:650;
                                ">
                                    Position
                                </label>

                                <select
                                    name="position"
                                    required
                                    style="
                                        width:100%;
                                        height:40px;
                                        border:1px solid var(--border);
                                        border-radius:9px;
                                        padding:0 10px;
                                    "
                                >
                                    <option value="Member">Member</option>
                                    <option value="Chairman">Chairman</option>
                                    <option value="President">President</option>
                                    <option value="General Secretary">General Secretary</option>
                                    <option value="Treasurer">Treasurer</option>
                                    <option value="Coordinator">Coordinator</option>
                                </select>

                                @error('position')
                                    <div style="color:var(--legacy-text-b3413c, #b3413c);font-size:14px;margin-top:5px;">
                                        {{ $message }}
                                    </div>
                                @enderror

                            </div>

                            <div>

                                <label style="
                                    display:block;
                                    margin-bottom:7px;
                                    font-size:16px;
                                    font-weight:650;
                                ">
                                    Joined Date
                                </label>

                                <input
                                    type="date"
                                    name="joined_at"
                                    value="{{ old('joined_at', date('Y-m-d')) }}"
                                    style="
                                        width:100%;
                                        height:40px;
                                        border:1px solid var(--border);
                                        border-radius:9px;
                                        padding:0 10px;
                                    "
                                >

                            </div>

                        </div>

                        <div style="margin-top:16px;">

                            <button
                                type="submit"
                                class="details-btn primary"
                            >
                                <i class="bi bi-person-plus"></i>
                                Assign Member
                            </button>

                        </div>

                    </form>

                @endif

            </div>

        </div>


        <!-- Reporting Structure -->

        <div class="details-card">

            <div class="card-header-custom">

                <div>

                    <strong>Reporting Structure</strong>

                    <span>
                        Current decision and reporting chain
                    </span>

                </div>

            </div>


            <div class="card-body-custom">

                <div class="reporting-chain">

                    <div class="chain-item">

                        <strong>
                            Implementation Committee
                        </strong>

                        <span>
                            Submits proposals and field recommendations
                        </span>

                    </div>


                    <div class="chain-arrow">
                        <i class="bi bi-arrow-right"></i>
                    </div>


                    <div class="chain-item">

                        <strong>
                            General Secretary
                        </strong>

                        <span>
                            Coordinates and submits matters
                        </span>

                    </div>


                    <div class="chain-arrow">
                        <i class="bi bi-arrow-right"></i>
                    </div>


                    <div class="chain-item">

                        <strong>
                            Decision-Making Committee
                        </strong>

                        <span>
                            Decision / approval
                        </span>

                    </div>


                    <div class="chain-arrow">
                        <i class="bi bi-arrow-right"></i>
                    </div>


                    <div class="chain-item">

                        <strong>
                            General Secretary
                        </strong>

                        <span>
                            Returns approved decision for implementation
                        </span>

                    </div>

                </div>

            </div>

        </div>


        <!-- Activity -->

        <div class="details-card">

            <div class="card-header-custom">

                <div>
                    <strong>Committee Activity</strong>

                    <span>
                        Recent configuration and organizational events
                    </span>
                </div>

            </div>

            <div class="card-body-custom">

                <div class="timeline-item">

                    <div class="timeline-dot"></div>

                    <div>

                        <div class="timeline-title">
                            Responsibility configuration updated
                        </div>

                        <div class="timeline-meta">
                            13 Aug 2026 · By General Secretary
                        </div>

                    </div>

                </div>


                <div class="timeline-item">

                    <div class="timeline-dot"></div>

                    <div>

                        <div class="timeline-title">
                            New member assigned
                        </div>

                        <div class="timeline-meta">
                            09 Aug 2026 · By Organization Admin
                        </div>

                    </div>

                </div>


                <div class="timeline-item">

                    <div class="timeline-dot"></div>

                    <div>

                        <div class="timeline-title">
                            Committee structure established
                        </div>

                        <div class="timeline-meta">
                            12 Jan 2024 · System
                        </div>

                    </div>

                </div>

            </div>

        </div>

    </div>


    <!-- SIDE PANEL -->

    <aside>

        <div class="details-card">

            <div class="card-body-custom">

                <div class="side-stat">

                    <div class="side-stat-number">
                        {{ $committee->members->count() }}
                    </div>

                    <div class="side-stat-label">
                        Committee Members
                    </div>

                </div>


                <div class="side-divider"></div>


                <div class="side-row">
                    <span>Status</span>
                    <span>{{ ucfirst($committee->status) }}</span>
                </div>

                <div class="side-row">
                    <span>Type</span>
                    <span>{{ ucfirst($committee->type) }}</span>
                </div>

                <div class="side-row">
                    <span>Positions</span>
                    <span>&mdash;</span>
                </div>

                <div class="side-row">
                    <span>Responsibilities</span>
                    <span>&mdash;</span>
                </div>

                <div class="side-row">
                    <span>Region</span>
                    <span>&mdash;</span>
                </div>

                <div class="side-row">
                    <span>Coordinator</span>
                    <span>&mdash;</span>
                </div>

            </div>

        </div>


        <div class="details-card">

            <div class="card-header-custom">

                <div>
                    <strong>Permission Model</strong>
                </div>

            </div>

            <div class="card-body-custom">

                <div class="permission-note">

                    <i class="bi bi-shield-check"></i>

                    <p>
                        Committee permissions should be configurable.
                        Members may only perform actions allowed by their
                        role and assigned responsibilities.
                    </p>

                </div>

            </div>

        </div>

    </aside>

</div>

@endsection