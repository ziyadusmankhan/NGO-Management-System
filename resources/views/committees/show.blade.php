@extends('layouts.app')

@section('title', 'Committee Details')

@push('styles')
<style>
    .details-header {
        display: flex;
        align-items: flex-start;
        justify-content: space-between;
        gap: 20px;
        margin-bottom: 22px;
    }

    .committee-identity-large {
        display: flex;
        align-items: center;
        gap: 14px;
    }

    .large-icon {
        width: 62px;
        height: 62px;
        border-radius: 17px;
        background: var(--primary-soft);
        color: var(--primary);
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 24px;
    }

    .details-name {
        font-size: 23px;
        font-weight: 700;
        letter-spacing: -.4px;
    }

    .details-code {
        margin-top: 4px;
        color: #6e7773;
        font-size: 16px;
    }

    .details-actions {
        display: flex;
        gap: 8px;
    }

    .details-btn {
        height: 39px;
        padding: 0 13px;
        border-radius: 9px;
        display: inline-flex;
        align-items: center;
        gap: 7px;
        border: 1px solid var(--border);
        background: #fff;
        color: #65726c;
        font-size: 16px;
        font-weight: 650;
        text-decoration: none;
    }

    .details-btn.primary {
        background: var(--primary);
        border-color: var(--primary);
        color: #fff;
    }

    .details-layout {
        display: grid;
        grid-template-columns: minmax(0, 1fr) 300px;
        gap: 18px;
    }

    .details-card {
        background: #fff;
        border: 1px solid var(--border);
        border-radius: 14px;
        overflow: hidden;
        margin-bottom: 18px;
    }

    .card-header-custom {
        padding: 16px 19px;
        border-bottom: 1px solid #eef1ef;
        display: flex;
        justify-content: space-between;
        gap: 15px;
        align-items: center;
    }

    .card-header-custom strong {
        font-size: 16px;
    }

    .card-header-custom span {
        display: block;
        margin-top: 4px;
        color: #6c7470;
        font-size: 16px;
    }

    .card-body-custom {
        padding: 18px 19px;
    }

    .active-badge {
        display: inline-flex;
        align-items: center;
        gap: 5px;
        padding: 5px 8px;
        border-radius: 30px;
        background: #edf7f0;
        color: #397955;
        font-size: 16px;
        font-weight: 650;
    }

    .active-badge::before {
        content: "";
        width: 5px;
        height: 5px;
        border-radius: 50%;
        background: #4c9a6c;
    }

    /* Overview */

    .overview-grid {
        display: grid;
        grid-template-columns: repeat(3, minmax(0, 1fr));
        gap: 15px;
    }

    .overview-item {
        padding: 13px;
        border: 1px solid #edf1ee;
        border-radius: 10px;
        background: #fafcfb;
    }

    .overview-item label {
        display: block;
        color: #6d7572;
        font-size: 16px;
        text-transform: uppercase;
        letter-spacing: .5px;
    }

    .overview-item strong {
        display: block;
        margin-top: 5px;
        font-size: 16px;
    }

    /* Responsibilities */

    .responsibility-grid {
        display: grid;
        grid-template-columns: repeat(2, minmax(0, 1fr));
        gap: 10px;
    }

    .responsibility {
        display: flex;
        align-items: flex-start;
        gap: 9px;
        padding: 11px;
        border: 1px solid #edf1ee;
        border-radius: 9px;
    }

    .responsibility i {
        color: var(--primary);
        font-size: 17px;
    }

    .responsibility span {
        color: #606d66;
        font-size: 16px;
        line-height: 1.45;
    }

    /* Members */

    .member-row {
        display: flex;
        align-items: center;
        gap: 10px;
        padding: 11px 0;
        border-bottom: 1px solid #f0f3f1;
    }

    .member-row:last-child {
        border-bottom: none;
    }

    .member-avatar {
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

    .member-info {
        flex: 1;
        min-width: 0;
    }

    .member-info strong {
        display: block;
        font-size: 16px;
    }

    .member-info span {
        display: block;
        margin-top: 3px;
        color: #6c7470;
        font-size: 16px;
    }

    .member-role {
        color: var(--primary);
        font-size: 16px;
        font-weight: 650;
        white-space: nowrap;
    }

    /* Reporting */

    .reporting-chain {
        display: flex;
        align-items: stretch;
        gap: 0;
        overflow-x: auto;
        padding-bottom: 3px;
    }

    .chain-item {
        min-width: 160px;
        padding: 13px;
        border: 1px solid var(--border);
        background: #fafcfb;
        border-radius: 10px;
    }

    .chain-item strong {
        display: block;
        font-size: 16px;
    }

    .chain-item span {
        display: block;
        margin-top: 4px;
        color: #6d7571;
        font-size: 16px;
    }

    .chain-arrow {
        width: 35px;
        min-width: 35px;
        display: flex;
        align-items: center;
        justify-content: center;
        color: #6f7672;
        font-size: 16px;
    }

    /* Activity */

    .timeline-item {
        display: flex;
        gap: 11px;
        position: relative;
        padding-bottom: 18px;
    }

    .timeline-item:last-child {
        padding-bottom: 0;
    }

    .timeline-dot {
        width: 9px;
        height: 9px;
        margin-top: 4px;
        border-radius: 50%;
        background: var(--primary);
        flex-shrink: 0;
        position: relative;
    }

    .timeline-item:not(:last-child) .timeline-dot::after {
        content: "";
        position: absolute;
        width: 1px;
        height: 29px;
        left: 4px;
        top: 9px;
        background: #dae3df;
    }

    .timeline-title {
        font-size: 16px;
        font-weight: 650;
    }

    .timeline-meta {
        margin-top: 3px;
        color: #6c7470;
        font-size: 16px;
    }

    /* Sidebar */

    .side-stat {
        text-align: center;
        padding: 7px 0 16px;
    }

    .side-stat-number {
        font-size: 30px;
        font-weight: 700;
        color: var(--primary);
    }

    .side-stat-label {
        margin-top: 3px;
        color: #6e7772;
        font-size: 16px;
    }

    .side-divider {
        height: 1px;
        background: #eef1ef;
        margin: 4px 0 11px;
    }

    .side-row {
        display: flex;
        justify-content: space-between;
        gap: 12px;
        padding: 9px 0;
        border-bottom: 1px solid #f0f3f1;
        font-size: 16px;
    }

    .side-row:last-child {
        border-bottom: none;
    }

    .side-row span:first-child {
        color: #6d7571;
    }

    .side-row span:last-child {
        color: #4a5750;
        font-weight: 650;
        text-align: right;
    }

    .permission-note {
        display: flex;
        gap: 9px;
        padding: 12px;
        background: #f7f9f8;
        border: 1px solid #e5ebe8;
        border-radius: 10px;
    }

    .permission-note i {
        color: var(--primary);
    }

    .permission-note p {
        margin: 0;
        color: #6b766f;
        font-size: 16px;
        line-height: 1.6;
    }

    @media (max-width: 950px) {
        .details-layout {
            grid-template-columns: 1fr;
        }
    }

    @media (max-width: 700px) {
        .details-header {
            flex-direction: column;
        }

        .details-actions {
            width: 100%;
        }

        .details-actions .details-btn {
            flex: 1;
            justify-content: center;
        }

        .overview-grid,
        .responsibility-grid {
            grid-template-columns: 1fr;
        }
    }
</style>
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

        <a
            href="{{ route('committees.index') }}"
            class="details-btn"
        >
            <i class="bi bi-arrow-left"></i>
            Back
        </a>

        <a
            href="{{ route('committees.edit', $committee) }}"
            class="details-btn primary"
        >
            <i class="bi bi-pencil"></i>
            Edit Committee
        </a>

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
                            background:#f1f3f2;
                            color:#6a766f;
                        @endif
                    "
                >
                    {{ ucfirst($committee->status) }}
                </span>

            </div>

            <div class="card-body-custom">

                <p style="
                    margin:0 0 17px;
                    color:#69766f;
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
                        color:#89948f;
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
                        color:#89948f;
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
                                    <div style="color:#b3413c;font-size:14px;margin-top:5px;">
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
                                    <div style="color:#b3413c;font-size:14px;margin-top:5px;">
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