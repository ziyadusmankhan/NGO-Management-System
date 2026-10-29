@extends('layouts.app')

@section('title', 'Beneficiaries')

@push('styles')
<link rel="stylesheet" href="{{ asset('css/views/welfare-beneficiaries-index-1.css') }}">
@endpush

@section('content')

<div class="page-header">

    <div>
        <h1>Beneficiaries</h1>
        <p>Manage welfare beneficiaries, verification and approval workflow.</p>
    </div>

    @can('welfare.beneficiaries.manage')
        @if(auth()->user()?->hasPermission('welfare.view') && auth()->user()?->hasPermission('welfare.beneficiaries.manage'))<a href="{{ route('welfare.beneficiaries.create') }}" class="ui-btn primary">
            <i class="bi bi-person-plus"></i>
            Add Beneficiary
        </a>@endif
    @endcan

</div>


<div class="stats-grid">

    <div class="stat-card">
        <div class="stat-icon">
            <i class="bi bi-people"></i>
        </div>

        <div class="stat-label">Total Beneficiaries</div>
        <div class="stat-value">{{ number_format($totalBeneficiaries) }}</div>
    </div>

    <div class="stat-card">
        <div class="stat-icon">
            <i class="bi bi-patch-check"></i>
        </div>

        <div class="stat-label">Verified</div>
        <div class="stat-value">{{ number_format($verifiedBeneficiaries) }}</div>
    </div>

    <div class="stat-card">
        <div class="stat-icon">
            <i class="bi bi-hourglass-split"></i>
        </div>

        <div class="stat-label">Pending Verification</div>
        <div class="stat-value">{{ number_format($pendingVerification) }}</div>
    </div>

    <div class="stat-card">
        <div class="stat-icon">
            <i class="bi bi-clipboard-check"></i>
        </div>

        <div class="stat-label">Pending Approval</div>
        <div class="stat-value">{{ number_format($pendingApproval) }}</div>
    </div>

</div>


<div class="card">

    <div class="card-head">

        <form method="GET" action="{{ route('welfare.beneficiaries.index') }}">

            <div class="filter-grid">

                <div>
                    <label class="form-label">Search</label>

                    <input
                        type="text"
                        name="search"
                        class="form-control"
                        value="{{ request('search') }}"
                        placeholder="Name, CNIC, phone or beneficiary number"
                    >
                </div>

                <div>
                    <label class="form-label">Category</label>

                    <select name="category" class="form-select">
                        <option value="">All categories</option>
                        <option value="elderly_poor" {{ request('category') === 'elderly_poor' ? 'selected' : '' }}>
                            Elderly / Extremely Poor
                        </option>
                        <option value="disabled_vulnerable" {{ request('category') === 'disabled_vulnerable' ? 'selected' : '' }}>
                            Disabled / Vulnerable
                        </option>
                        <option value="poor_employable" {{ request('category') === 'poor_employable' ? 'selected' : '' }}>
                            Poor but Employable
                        </option>
                        <option value="student" {{ request('category') === 'student' ? 'selected' : '' }}>
                            Students / Education Support
                        </option>
                        <option value="emergency" {{ request('category') === 'emergency' ? 'selected' : '' }}>
                            Emergency Assistance
                        </option>
                    </select>
                </div>

                <div>
                    <label class="form-label">Verification</label>

                    <select name="verification_status" class="form-select">
                        <option value="">All</option>
                        <option value="pending" {{ request('verification_status') === 'pending' ? 'selected' : '' }}>
                            Pending
                        </option>
                        <option value="verified" {{ request('verification_status') === 'verified' ? 'selected' : '' }}>
                            Verified
                        </option>
                        <option value="rejected" {{ request('verification_status') === 'rejected' ? 'selected' : '' }}>
                            Rejected
                        </option>
                    </select>
                </div>

                <div>
                    <label class="form-label">Approval</label>

                    <select name="approval_status" class="form-select">
                        <option value="">All</option>
                        <option value="pending" {{ request('approval_status') === 'pending' ? 'selected' : '' }}>
                            Pending
                        </option>
                        <option value="approved" {{ request('approval_status') === 'approved' ? 'selected' : '' }}>
                            Approved
                        </option>
                        <option value="rejected" {{ request('approval_status') === 'rejected' ? 'selected' : '' }}>
                            Rejected
                        </option>
                    </select>
                </div>

                <div class="d-flex gap-2">
                    <button type="submit" class="ui-btn primary">
                        <i class="bi bi-search"></i>
                        Filter
                    </button>

                    @if(auth()->user()?->hasPermission('welfare.view'))<a href="{{ route('welfare.beneficiaries.index') }}" class="ui-btn">
                        Reset
                    </a>@endif
                </div>

            </div>

        </form>

    </div>


    @if($beneficiaries->count())

        <div class="table-responsive">

            <table class="table table-hover mb-0">

                <thead>
                    <tr>
                        <th>Beneficiary</th>
                        <th>Category</th>
                        <th>Verification</th>
                        <th>Approval</th>
                        <th>Next Review</th>
                        <th class="text-end">Actions</th>
                    </tr>
                </thead>

                <tbody>

                    @foreach($beneficiaries as $beneficiary)

                        @php
                            $categoryLabels = [
                                'elderly_poor' => 'Elderly / Extremely Poor',
                                'disabled_vulnerable' => 'Disabled / Vulnerable',
                                'poor_employable' => 'Poor but Employable',
                                'student' => 'Students / Education',
                                'emergency' => 'Emergency Assistance',
                            ];

                            $verificationClass = match($beneficiary->verification_status) {
                                'verified' => 'badge-verified',
                                'rejected' => 'badge-rejected',
                                default => 'badge-pending',
                            };

                            $approvalClass = match($beneficiary->approval_status) {
                                'approved' => 'badge-approved',
                                'rejected' => 'badge-rejected',
                                default => 'badge-pending',
                            };
                        @endphp

                        <tr>

                            <td>
                                @if(auth()->user()?->hasPermission('welfare.view'))<a
                                    href="{{ route('welfare.beneficiaries.show', $beneficiary) }}"
                                    class="beneficiary-name"
                                >
                                    {{ $beneficiary->full_name }}
                                </a>@endif

                                <div class="beneficiary-number">
                                    {{ $beneficiary->beneficiary_number }}
                                </div>
                            </td>

                            <td>
                                {{ $categoryLabels[$beneficiary->category] ?? ucfirst(str_replace('_', ' ', $beneficiary->category)) }}
                            </td>

                            <td>
                                <span class="badge-soft {{ $verificationClass }}">
                                    {{ ucfirst($beneficiary->verification_status) }}
                                </span>
                            </td>

                            <td>
                                <span class="badge-soft {{ $approvalClass }}">
                                    {{ ucfirst($beneficiary->approval_status) }}
                                </span>
                            </td>

                            <td>
                                {{ $beneficiary->next_review_date
                                    ? $beneficiary->next_review_date->format('d M Y')
                                    : '—'
                                }}
                            </td>

                            <td>
                                <div class="action-row justify-content-end">

                                    @if(auth()->user()?->hasPermission('welfare.view'))<a
                                        href="{{ route('welfare.beneficiaries.show', $beneficiary) }}"
                                        class="icon-btn"
                                        title="View"
                                    >
                                        <i class="bi bi-eye"></i>
                                    </a>@endif

                                    @can('welfare.beneficiaries.manage')
                                        @if(auth()->user()?->hasPermission('welfare.view') && auth()->user()?->hasPermission('welfare.beneficiaries.manage'))<a
                                            href="{{ route('welfare.beneficiaries.edit', $beneficiary) }}"
                                            class="icon-btn"
                                            title="Edit"
                                        >
                                            <i class="bi bi-pencil"></i>
                                        </a>@endif
                                    @endcan

                                    @can('welfare.beneficiaries.verify')
                                        @if($beneficiary->verification_status === 'pending')

                                            <form
                                                method="POST"
                                                action="{{ route('welfare.beneficiaries.verify', $beneficiary) }}"
                                                class="d-inline"
                                            >
                                                @csrf

                                                <button
                                                    type="submit"
                                                    class="icon-btn"
                                                    title="Verify"
                                                    onclick="return confirm('Verify this beneficiary?')"
                                                >
                                                    <i class="bi bi-check2-circle"></i>
                                                </button>
                                            </form>

                                            <form
                                                method="POST"
                                                action="{{ route('welfare.beneficiaries.reject-verification', $beneficiary) }}"
                                                class="d-inline"
                                            >
                                                @csrf

                                                <button
                                                    type="submit"
                                                    class="icon-btn"
                                                    title="Reject verification"
                                                    onclick="return confirm('Reject verification for this beneficiary?')"
                                                >
                                                    <i class="bi bi-x-circle"></i>
                                                </button>
                                            </form>

                                        @endif
                                    @endcan

                                    @can('welfare.beneficiaries.approve')
                                        @if(
                                            $beneficiary->verification_status === 'verified' &&
                                            $beneficiary->approval_status === 'pending'
                                        )

                                            <form
                                                method="POST"
                                                action="{{ route('welfare.beneficiaries.approve', $beneficiary) }}"
                                                class="d-inline"
                                            >
                                                @csrf

                                                <button
                                                    type="submit"
                                                    class="icon-btn"
                                                    title="Approve"
                                                    onclick="return confirm('Approve this beneficiary?')"
                                                >
                                                    <i class="bi bi-check-lg"></i>
                                                </button>
                                            </form>

                                            <form
                                                method="POST"
                                                action="{{ route('welfare.beneficiaries.reject-approval', $beneficiary) }}"
                                                class="d-inline"
                                            >
                                                @csrf

                                                <button
                                                    type="submit"
                                                    class="icon-btn"
                                                    title="Reject approval"
                                                    onclick="return confirm('Reject approval for this beneficiary?')"
                                                >
                                                    <i class="bi bi-slash-circle"></i>
                                                </button>
                                            </form>

                                        @endif
                                    @endcan

                                </div>
                            </td>

                        </tr>

                    @endforeach

                </tbody>

            </table>

        </div>

        <div class="p-3 border-top">
            {{ $beneficiaries->withQueryString()->links() }}
        </div>

    @else

        <div class="empty-state">
            <i class="bi bi-people" style="font-size:38px;"></i>

            <h5 class="mt-3 mb-2">No beneficiaries found</h5>

            <p class="mb-3">
                There are no beneficiary records matching the current filters.
            </p>

            @can('welfare.beneficiaries.manage')
                @if(auth()->user()?->hasPermission('welfare.view') && auth()->user()?->hasPermission('welfare.beneficiaries.manage'))<a href="{{ route('welfare.beneficiaries.create') }}" class="ui-btn primary">
                    <i class="bi bi-person-plus"></i>
                    Add First Beneficiary
                </a>@endif
            @endcan
        </div>

    @endif

</div>

@endsection