@extends('layouts.app')

@section('title', 'User Details')

@push('styles')
<style>
    .header {
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
        font-size: 18px;
        font-weight:700;
    }

    .name {
        font-size: 23px;
        font-weight:700;
        letter-spacing:-.4px;
    }

    .meta {
        margin-top:4px;
        color: #6e7773;
        font-size: 16px;
    }

    .actions {
        display:flex;
        gap:8px;
    }

    .btn-ui {
        height:39px;
        padding:0 13px;
        border-radius:9px;
        display:inline-flex;
        align-items:center;
        gap:7px;
        border:1px solid var(--border);
        background:#fff;
        color:#65726c;
        font-size: 16px;
        font-weight:650;
        text-decoration:none;
    }

    .btn-ui.primary {
        background:var(--primary);
        border-color:var(--primary);
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
        padding:16px 19px;
        border-bottom:1px solid #eef1ef;
    }

    .card-head strong {
        font-size: 16px;
    }

    .card-head span {
        display:block;
        margin-top:4px;
        color: #6c7470;
        font-size: 16px;
    }

    .card-body {
        padding:18px 19px;
    }

    .grid {
        display:grid;
        grid-template-columns:repeat(2,minmax(0,1fr));
        gap:18px 24px;
    }

    .item label {
        display:block;
        margin-bottom:5px;
        color: #6d7571;
        font-size: 16px;
        text-transform:uppercase;
        letter-spacing:.5px;
    }

    .item strong {
        display:block;
        color:#3e4b45;
        font-size: 16px;
    }

    .role {
        display:inline-flex;
        padding:5px 8px;
        border-radius:30px;
        background:var(--primary-soft);
        color:var(--primary);
        font-size: 16px;
        font-weight:650;
    }

    .status {
        display:inline-flex;
        align-items:center;
        gap:5px;
        padding:5px 8px;
        border-radius:30px;
        background:#edf7f0;
        color:#397955;
        font-size: 16px;
        font-weight:650;
    }

    .status::before {
        content:"";
        width:5px;
        height:5px;
        background:#4c9a6c;
        border-radius:50%;
    }

    .permission {
        display:flex;
        align-items:flex-start;
        gap:9px;
        padding:11px;
        border:1px solid #edf1ee;
        border-radius:9px;
    }

    .permission i {
        color:var(--primary);
        font-size: 17px;
    }

    .permission span {
        color:#617068;
        font-size: 16px;
        line-height:1.45;
    }

    .permission-grid {
        display:grid;
        grid-template-columns:repeat(2,minmax(0,1fr));
        gap:9px;
    }

    .timeline-item {
        display:flex;
        gap:11px;
        padding-bottom:18px;
        position:relative;
    }

    .timeline-item:last-child {
        padding-bottom:0;
    }

    .timeline-dot {
        width:9px;
        height:9px;
        margin-top:4px;
        border-radius:50%;
        background:var(--primary);
        flex-shrink:0;
        position:relative;
    }

    .timeline-item:not(:last-child) .timeline-dot::after {
        content:"";
        position:absolute;
        width:1px;
        height:29px;
        left:4px;
        top:9px;
        background:#dae3df;
    }

    .timeline-title {
        font-size: 16px;
        font-weight:650;
    }

    .timeline-meta {
        margin-top:3px;
        color: #6c7470;
        font-size: 16px;
    }

    .side-stat {
        text-align:center;
        padding:7px 0 16px;
    }

    .side-number {
        font-size: 28px;
        font-weight:700;
        color:var(--primary);
    }

    .side-label {
        margin-top:3px;
        color: #6e7772;
        font-size: 16px;
    }

    .side-row {
        display:flex;
        justify-content:space-between;
        gap:10px;
        padding:9px 0;
        border-bottom:1px solid #f0f3f1;
        font-size: 16px;
    }

    .side-row:last-child {
        border-bottom:none;
    }

    .side-row span:first-child {
        color: #6d7571;
    }

    .side-row span:last-child {
        color:#4a5750;
        font-weight:650;
        text-align:right;
    }

    .security {
        display:flex;
        gap:9px;
        padding:12px;
        background:#f7f9f8;
        border:1px solid #e5ebe8;
        border-radius:10px;
    }

    .security i {
        color:var(--primary);
    }

    .security p {
        margin:0;
        color: #6b766f;
        font-size: 16px;
        line-height:1.6;
    }

    @media(max-width:900px) {
        .layout {
            grid-template-columns:1fr;
        }
    }

    @media(max-width:650px) {
        .header {
            flex-direction:column;
        }

        .actions {
            width:100%;
        }

        .actions .btn-ui {
            flex:1;
            justify-content:center;
        }

        .grid,
        .permission-grid {
            grid-template-columns:1fr;
        }
    }
</style>
@endpush

@section('content')

@include('shared.action-feedback')

<div class="header">

    <div class="identity">

        <div class="avatar">
            {{ Str::of($user->name)->explode(' ')->map(fn ($part) => Str::substr($part, 0, 1))->take(2)->implode('') ?: '?' }}
        </div>

        <div>

            <div class="name">
                {{ $user->name }}
            </div>

            <div class="meta">
                USER-{{ str_pad($user->id, 5, '0', STR_PAD_LEFT) }}
                @if($user->roles->isNotEmpty())
                    · {{ $user->roles->pluck('name')->join(', ') }}
                @endif
            </div>

        </div>

    </div>

    <div class="actions">

        <a
            href="{{ route('users.index') }}"
            class="btn-ui"
        >
            <i class="bi bi-arrow-left"></i>
            Back
        </a>

        <a
            href="{{ route('users.edit', $user) }}"
            class="btn-ui primary"
        >
            <i class="bi bi-pencil"></i>
            Edit User
        </a>

        @include('shared.archive-user', ['buttonClass' => 'btn-ui'])

    </div>

</div>


<div class="layout">

    <div>

        <div class="card">

            <div class="card-head">

                <strong>
                    Account Information
                </strong>

                <span>
                    User identity and account details
                </span>

            </div>

            <div class="card-body">

                <div class="grid">

                    <div class="item">
                        <label>Full Name</label>
                        <strong>{{ $user->name }}</strong>
                    </div>

                    <div class="item">
                        <label>Last active</label>
                        <strong title="{{ $user->last_active_at?->toIso8601String() }}">{{ $user->last_active_at?->diffForHumans() ?? 'Not recorded' }}</strong>
                    </div>

                    <div class="item">
                        <label>Email</label>
                        <strong>{{ $user->email }}</strong>
                    </div>

                    <div class="item">
                        <label>Phone</label>
                        <strong>{{ $user->member->phone ?? 'Not available' }}</strong>
                    </div>

                    <div class="item">
                        <label>Linked Member</label>
                        <strong>{{ $user->member->member_number ?? 'Not linked' }}</strong>
                    </div>

                    <div class="item">
                        <label>Role</label>
                        @forelse($user->roles as $role)
                            <span class="role">
                                {{ $role->name }}
                            </span>
                        @empty
                            <strong>No role assigned</strong>
                        @endforelse
                    </div>

                    <div class="item">
                        <label>Status</label>
                        <span class="status" @if(!$user->isActive()) style="background:#fbeaea;color:#a13d3d;" @endif>
                            {{ ucfirst($user->status) }}
                        </span>
                    </div>

                </div>

            </div>

        </div>


        <div class="card">

            <div class="card-head">

                <strong>
                    Access Permissions
                </strong>

                <span>
                    Permissions inherited from the assigned role
                </span>

            </div>

            <div class="card-body">

                <div class="permission-grid">

                    @forelse($permissions as $permission)
                        <div class="permission">
                            <i class="bi bi-check2-circle"></i>
                            <span>{{ $permission->name }}</span>
                        </div>
                    @empty
                        <div class="permission">
                            <span>No permissions assigned yet.</span>
                        </div>
                    @endforelse

                </div>

            </div>

        </div>


        <div class="card">

            <div class="card-head">

                <strong>
                    User Activity
                </strong>

                <span>
                    Recent account and permission-related activity
                </span>

            </div>

            <div class="card-body">

                @if($user->updated_at && !$user->updated_at->equalTo($user->created_at))
                    <div class="timeline-item">

                        <div class="timeline-dot"></div>

                        <div>
                            <div class="timeline-title">
                                Account details last updated
                            </div>

                            <div class="timeline-meta">
                                {{ $user->updated_at->format('d M Y · H:i') }}
                            </div>
                        </div>

                    </div>
                @endif

                <div class="timeline-item">

                    <div class="timeline-dot"></div>

                    <div>
                        <div class="timeline-title">
                            User account created
                        </div>

                        <div class="timeline-meta">
                            {{ $user->created_at?->format('d M Y · H:i') ?? 'Unknown' }}
                        </div>
                    </div>

                </div>

            </div>

        </div>

    </div>


    <aside>

        <div class="card">

            <div class="card-body">

                <div class="side-stat">

                    <div class="side-number">
                        {{ $permissions->count() }}
                    </div>

                    <div class="side-label">
                        Active Permissions
                    </div>

                </div>

                <div class="side-row">
                    <span>Role</span>
                    <span>{{ $user->roles->pluck('name')->join(', ') ?: 'Not assigned' }}</span>
                </div>

                <div class="side-row">
                    <span>Status</span>
                    <span>{{ ucfirst($user->status) }}</span>
                </div>

                <div class="side-row">
                    <span>Committee</span>
                    <span>{{ $primaryCommittee->name ?? 'Not assigned' }}</span>
                </div>

                <div class="side-row">
                    <span>Joined</span>
                    <span>{{ $user->created_at?->format('d M Y') ?? 'Unknown' }}</span>
                </div>

            </div>

        </div>


        <div class="card">

            <div class="card-head">
                <strong>Security</strong>
            </div>

            <div class="card-body">

                <div class="security">

                    <i class="bi bi-shield-lock"></i>

                    <p>
                        Financial requests cannot be self-approved.
                        Confidential information remains restricted by
                        role and organizational authorization.
                    </p>

                </div>

            </div>

        </div>

    </aside>

</div>


@if(auth()->user()->hasPermission('users.password-reset') && auth()->id() !== $user->id)
<div class="alert alert-light mt-3"><h3>Password recovery</h3><p><a href="{{ route('security.confirm') }}">Confirm your password</a> before generating a temporary password for this account.</p><form method="post" action="{{ route('security.reset',$user) }}">@csrf<button class="btn btn-outline-danger" onclick="return confirm('Reset this account password and sign out its sessions?')">Generate temporary password</button></form></div>
@endif
@endsection