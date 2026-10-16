@extends('layouts.app')

@section('title', 'Beneficiaries')

@push('styles')
<style>
    .page-header {
        display:flex;
        justify-content:space-between;
        align-items:flex-start;
        gap:20px;
        margin-bottom:22px;
    }

    .page-header h1 {
        margin:0;
        font-size:25px;
        font-weight:700;
    }

    .page-header p {
        margin:6px 0 0;
        color:var(--muted);
    }

    .ui-btn {
        display:inline-flex;
        align-items:center;
        justify-content:center;
        gap:7px;
        height:40px;
        padding:0 14px;
        border:1px solid var(--border);
        border-radius:9px;
        background:#fff;
        color:#596760;
        text-decoration:none;
        font-weight:650;
        cursor:pointer;
    }

    .ui-btn.primary {
        background:var(--primary);
        border-color:var(--primary);
        color:#fff;
    }

    .stats-grid {
        display:grid;
        grid-template-columns:repeat(4,minmax(0,1fr));
        gap:15px;
        margin-bottom:18px;
    }

    .stat-card {
        background:#fff;
        border:1px solid var(--border);
        border-radius:14px;
        padding:18px;
    }

    .stat-label {
        color:var(--muted);
        font-size:14px;
    }

    .stat-value {
        margin-top:5px;
        font-size:26px;
        font-weight:700;
    }

    .stat-icon {
        width:40px;
        height:40px;
        border-radius:10px;
        display:flex;
        align-items:center;
        justify-content:center;
        background:var(--primary-soft);
        color:var(--primary);
        margin-bottom:14px;
    }

    .card {
        background:#fff;
        border:1px solid var(--border);
        border-radius:14px;
        overflow:hidden;
        margin-bottom:18px;
    }

    .card-head {
        padding:17px 20px;
        border-bottom:1px solid var(--border);
    }

    .filter-grid {
        display:grid;
        grid-template-columns:2fr 1fr 1fr 1fr auto;
        gap:10px;
        align-items:end;
    }

    .form-label {
        font-size:13px;
        font-weight:650;
        color:#435049;
    }

    .table th {
        font-size:12px;
        text-transform:uppercase;
        letter-spacing:.4px;
        color:#718078;
        white-space:nowrap;
    }

    .table td {
        vertical-align:middle;
    }

    .beneficiary-name {
        font-weight:650;
        color:var(--text);
    }

    .beneficiary-number {
        color:var(--muted);
        font-size:12px;
        margin-top:2px;
    }

    .badge-soft {
        display:inline-flex;
        align-items:center;
        padding:5px 9px;
        border-radius:999px;
        font-size:12px;
        font-weight:650;
    }

    .badge-pending {
        background:#fff7df;
        color:#87671d;
    }

    .badge-verified,
    .badge-approved {
        background:#eaf6ef;
        color:#28704a;
    }

    .badge-rejected {
        background:#fbeceb;
        color:#9a4037;
    }

    .badge-neutral {
        background:#eef1ef;
        color:#68736e;
    }

    .action-row {
        display:flex;
        gap:6px;
        flex-wrap:wrap;
    }

    .icon-btn {
        width:34px;
        height:34px;
        display:inline-flex;
        align-items:center;
        justify-content:center;
        border:1px solid var(--border);
        border-radius:8px;
        background:#fff;
        color:#5d6963;
    }

    .icon-btn:hover {
        color:var(--primary);
        background:var(--primary-soft);
    }

    .empty-state {
        padding:55px 20px;
        text-align:center;
        color:var(--muted);
    }

    @media(max-width:1100px) {
        .stats-grid {
            grid-template-columns:repeat(2,minmax(0,1fr));
        }

        .filter-grid {
            grid-template-columns:1fr 1fr;
        }
    }

    @media(max-width:700px) {
        .stats-grid {
            grid-template-columns:1fr;
        }

        .filter-grid {
            grid-template-columns:1fr;
        }
    }
</style>
@endpush

@section('content')

<div class="page-header">

    <div>
        <h1>Beneficiaries</h1>
        <p>Manage welfare beneficiaries, verification and approval workflow.</p>
    </div>

    @can('welfare.beneficiaries.manage')
        <a href="{{ route('welfare.beneficiaries.create') }}" class="ui-btn primary">
            <i class="bi bi-person-plus"></i>
            Add Beneficiary
        </a>
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

                    <a href="{{ route('welfare.beneficiaries.index') }}" class="ui-btn">
                        Reset
                    </a>
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
                                <a
                                    href="{{ route('welfare.beneficiaries.show', $beneficiary) }}"
                                    class="beneficiary-name"
                                >
                                    {{ $beneficiary->full_name }}
                                </a>

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

                                    <a
                                        href="{{ route('welfare.beneficiaries.show', $beneficiary) }}"
                                        class="icon-btn"
                                        title="View"
                                    >
                                        <i class="bi bi-eye"></i>
                                    </a>

                                    @can('welfare.beneficiaries.manage')
                                        <a
                                            href="{{ route('welfare.beneficiaries.edit', $beneficiary) }}"
                                            class="icon-btn"
                                            title="Edit"
                                        >
                                            <i class="bi bi-pencil"></i>
                                        </a>
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
                <a href="{{ route('welfare.beneficiaries.create') }}" class="ui-btn primary">
                    <i class="bi bi-person-plus"></i>
                    Add First Beneficiary
                </a>
            @endcan
        </div>

    @endif

</div>

@endsection