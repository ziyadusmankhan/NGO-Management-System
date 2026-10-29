@extends('layouts.app')

@section('title', 'Welfare Assistance')

@push('styles')
<link rel="stylesheet" href="{{ asset('css/views/welfare-assistance-index-1.css') }}">
@endpush

@section('content')

<div class="page-header">

    <div>
        <h1>Welfare Assistance</h1>
        <p>Manage welfare assistance requests, approvals and review dates.</p>
        @can('welfare.reviews.view')
        @if(auth()->user()?->hasPermission('welfare.assistance.view') && auth()->user()?->hasPermission('welfare.reviews.view'))<a href="{{ route('welfare.assistance.reviews.index') }}">Review and follow-up queue</a>@endif
        @endcan
    </div>

    @can('welfare.assistance.manage')
        @if(auth()->user()?->hasPermission('welfare.assistance.view') && auth()->user()?->hasPermission('welfare.assistance.manage'))<a
            href="{{ route('welfare.assistance.create') }}"
            class="ui-btn primary"
        >
            <i class="bi bi-plus-lg"></i>
            New Assistance
        </a>@endif
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
            PKR {{ \App\Domain\Finance\Support\Money::format($thisMonthAmount) }}
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
                    @foreach($assistanceTypes as $type)
                        <option value="{{ $type }}" @selected(request('assistance_type') === $type)>{{ ucfirst(str_replace('_', ' ', $type)) }}</option>
                    @endforeach
                </select>

                <select name="status" class="form-select">
                    <option value="">All Statuses</option>
                    <option value="pending" {{ request('status') === 'pending' ? 'selected' : '' }}>Pending</option>
                    <option value="approved" {{ request('status') === 'approved' ? 'selected' : '' }}>Approved</option>
                    <option value="rejected" {{ request('status') === 'rejected' ? 'selected' : '' }}>Rejected</option>
                    <option value="paid" {{ request('status') === 'paid' ? 'selected' : '' }}>Paid</option>
                    <option value="completed" {{ request('status') === 'completed' ? 'selected' : '' }}>Completed</option>
                    <option value="closed" {{ request('status') === 'closed' ? 'selected' : '' }}>Closed</option>
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

                    @if(auth()->user()?->hasPermission('welfare.assistance.view'))<a
                        href="{{ route('welfare.assistance.index') }}"
                        class="ui-btn"
                    >
                        Reset
                    </a>@endif
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
                                'closed' => 'closed',
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
                                PKR {{ \App\Domain\Finance\Support\Money::format($assistance->amount) }}
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
                                @if(auth()->user()?->hasPermission('welfare.assistance.view'))<a
                                    href="{{ route('welfare.assistance.show', $assistance) }}"
                                    class="action"
                                    title="View"
                                >
                                    <i class="bi bi-eye"></i>
                                </a>@endif
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
