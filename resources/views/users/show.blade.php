@extends('layouts.app')

@section('title', 'User Details')

@push('styles')
<link rel="stylesheet" href="{{ asset('css/views/users-show-1.css') }}">
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

        @if(auth()->user()?->hasPermission('users.view'))<a
            href="{{ route('users.index') }}"
            class="btn-ui"
        >
            <i class="bi bi-arrow-left"></i>
            Back
        </a>@endif

        @if(auth()->user()?->hasPermission('users.manage'))<a
            href="{{ route('users.edit', $user) }}"
            class="btn-ui primary"
        >
            <i class="bi bi-pencil"></i>
            Edit User
        </a>@endif

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