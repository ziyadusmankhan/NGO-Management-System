@extends('layouts.app')

@section('title', 'Welfare Assistance')

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
    }

    .ui-btn.primary {
        background:var(--primary);
        border-color:var(--primary);
        color:#fff;
    }

    .stats {
        display:grid;
        grid-template-columns:repeat(4,minmax(0,1fr));
        gap:15px;
        margin-bottom:18px;
    }

    .stat {
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
        margin-top:7px;
        font-size:26px;
        font-weight:700;
    }

    .card {
        background:#fff;
        border:1px solid var(--border);
        border-radius:14px;
        overflow:hidden;
        margin-bottom:18px;
    }

    .toolbar {
        padding:17px 18px;
        border-bottom:1px solid var(--border);
    }

    .filters {
        display:grid;
        grid-template-columns:2fr 1fr 1fr 1fr auto;
        gap:10px;
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

    .assistance-number {
        font-weight:700;
    }

    .sub {
        color:var(--muted);
        font-size:12px;
        margin-top:2px;
    }

    .badge-soft {
        display:inline-flex;
        padding:5px 9px;
        border-radius:999px;
        font-size:12px;
        font-weight:650;
    }

    .pending {
        background:#fff7df;
        color:#87671d;
    }

    .approved {
        background:#eaf6ef;
        color:#28704a;
    }

    .rejected {
        background:#fbeceb;
        color:#9a4037;
    }

    .neutral {
        background:#eef1ef;
        color:#68736e;
    }

    .action {
        width:35px;
        height:35px;
        display:inline-flex;
        align-items:center;
        justify-content:center;
        border:1px solid var(--border);
        border-radius:8px;
        background:#fff;
        color:#5d6963;
        text-decoration:none;
    }

    .empty {
        text-align:center;
        padding:55px 20px;
        color:var(--muted);
    }

    @media(max-width:1100px) {
        .stats {
            grid-template-columns:repeat(2,1fr);
        }

        .filters {
            grid-template-columns:1fr 1fr;
        }
    }

    @media(max-width:700px) {
        .page-header {
            flex-direction:column;
        }

        .stats {
            grid-template-columns:1fr;
        }

        .filters {
            grid-template-columns:1fr;
        }
    }
</style>
@endpush

@section('content')

<div class="page-header">

    <div>
        <h1>Welfare Assistance</h1>
        <p>Manage welfare assistance requests, approvals and review dates.</p>
    </div>

    @can('welfare.assistance.manage')
        <a
            href="{{ route('welfare.assistance.create') }}"
            class="ui-btn primary"
        >
            <i class="bi bi-plus-lg"></i>
            New Assistance
        </a>
    @endcan

</div>

<div class="stats">

    <div class="stat">
        <div class="stat-label">Total Requests</div>
        <div class="stat-value">{{ number_format($totalAssistance) }}</div>
    </div>

    <div class="stat">
        <div class="stat-label">Pending Approval</div>
        <div class="stat-value">{{ number_format($pendingAssistance) }}</div>
    </div>

    <div class="stat">
        <div class="stat-label">Approved</div>
        <div class="stat-value">{{ number_format($approvedAssistance) }}</div>
    </div>

    <div class="stat">
        <div class="stat-label">This Month</div>
        <div class="stat-value">
            PKR {{ number_format($thisMonthAmount, 0) }}
        </div>
    </div>

</div>

<div class="card">

    <div class="toolbar">

        <form method="GET" action="{{ route('welfare.assistance.index') }}">

            <div class="filters">

                <input
                    type="text"
                    name="search"
                    class="form-control"
                    value="{{ request('search') }}"
                    placeholder="Search assistance or beneficiary..."
                >

                <select name="assistance_type" class="form-select">
                    <option value="">All Types</option>
                    <option value="monthly_assistance" {{ request('assistance_type') === 'monthly_assistance' ? 'selected' : '' }}>Monthly Assistance</option>
                    <option value="food_ration" {{ request('assistance_type') === 'food_ration' ? 'selected' : '' }}>Food / Ration</option>
                    <option value="medical_assistance" {{ request('assistance_type') === 'medical_assistance' ? 'selected' : '' }}>Medical Assistance</option>
                    <option value="education_support" {{ request('assistance_type') === 'education_support' ? 'selected' : '' }}>Education Support</option>
                    <option value="emergency_assistance" {{ request('assistance_type') === 'emergency_assistance' ? 'selected' : '' }}>Emergency Assistance</option>
                    <option value="one_time_support" {{ request('assistance_type') === 'one_time_support' ? 'selected' : '' }}>One-Time Support</option>
                </select>

                <select name="status" class="form-select">
                    <option value="">All Statuses</option>
                    <option value="pending" {{ request('status') === 'pending' ? 'selected' : '' }}>Pending</option>
                    <option value="approved" {{ request('status') === 'approved' ? 'selected' : '' }}>Approved</option>
                    <option value="rejected" {{ request('status') === 'rejected' ? 'selected' : '' }}>Rejected</option>
                    <option value="paid" {{ request('status') === 'paid' ? 'selected' : '' }}>Paid</option>
                    <option value="completed" {{ request('status') === 'completed' ? 'selected' : '' }}>Completed</option>
                </select>

                <select name="fund_id" class="form-select">
                    <option value="">All Funds</option>

                    @foreach($funds as $fund)
                        <option
                            value="{{ $fund->id }}"
                            {{ request('fund_id') == $fund->id ? 'selected' : '' }}
                        >
                            {{ $fund->name }}
                        </option>
                    @endforeach
                </select>

                <div class="d-flex gap-2">
                    <button type="submit" class="ui-btn primary">
                        <i class="bi bi-search"></i>
                        Filter
                    </button>

                    <a
                        href="{{ route('welfare.assistance.index') }}"
                        class="ui-btn"
                    >
                        Reset
                    </a>
                </div>

            </div>

        </form>

    </div>

    @if($assistances->count())

        <div class="table-responsive">

            <table class="table table-hover mb-0">

                <thead>
                    <tr>
                        <th>Assistance</th>
                        <th>Beneficiary</th>
                        <th>Type</th>
                        <th>Fund</th>
                        <th>Amount</th>
                        <th>Status</th>
                        <th>Review</th>
                        <th></th>
                    </tr>
                </thead>

                <tbody>

                    @foreach($assistances as $assistance)

                        @php
                            $typeLabel = match ($assistance->assistance_type) {
                                'monthly_assistance' => 'Monthly Assistance',
                                'food_ration' => 'Food / Ration',
                                'medical_assistance' => 'Medical Assistance',
                                'education_support' => 'Education Support',
                                'emergency_assistance' => 'Emergency Assistance',
                                'one_time_support' => 'One-Time Support',
                                default => ucfirst(str_replace('_', ' ', $assistance->assistance_type)),
                            };

                            $statusClass = match ($assistance->status) {
                                'approved' => 'approved',
                                'rejected' => 'rejected',
                                'paid', 'completed' => 'approved',
                                default => 'pending',
                            };
                        @endphp

                        <tr>

                            <td>
                                <div class="assistance-number">
                                    {{ $assistance->assistance_number }}
                                </div>

                                <div class="sub">
                                    {{ $assistance->requested_at?->format('d M Y') ?? $assistance->created_at->format('d M Y') }}
                                </div>
                            </td>

                            <td>
                                <div class="assistance-number">
                                    {{ $assistance->beneficiary?->full_name ?? 'Unknown' }}
                                </div>

                                <div class="sub">
                                    {{ $assistance->beneficiary?->beneficiary_number ?? '—' }}
                                </div>
                            </td>

                            <td>{{ $typeLabel }}</td>

                            <td>{{ $assistance->fund?->name ?? 'Unassigned' }}</td>

                            <td>
                                PKR {{ number_format((float) $assistance->amount, 0) }}
                            </td>

                            <td>
                                <span class="badge-soft {{ $statusClass }}">
                                    {{ ucfirst($assistance->status) }}
                                </span>
                            </td>

                            <td>
                                {{ $assistance->next_review_date?->format('d M Y') ?? '—' }}
                            </td>

                            <td>
                                <a
                                    href="{{ route('welfare.assistance.show', $assistance) }}"
                                    class="action"
                                    title="View"
                                >
                                    <i class="bi bi-eye"></i>
                                </a>
                            </td>

                        </tr>

                    @endforeach

                </tbody>

            </table>

        </div>

        <div class="p-3 border-top">
            {{ $assistances->links() }}
        </div>

    @else

        <div class="empty">
            <i class="bi bi-hand-heart" style="font-size:38px;"></i>

            <h5 class="mt-3">No assistance records found</h5>

            <p>
                No welfare assistance requests match the current filters.
            </p>
        </div>

    @endif

</div>

@endsection