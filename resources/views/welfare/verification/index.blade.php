@extends('layouts.app')

@section('title', 'Field Verification')

@push('styles')
<link rel="stylesheet" href="{{ asset('css/views/welfare-verification-index-1.css') }}">
@endpush



@section('content')

@php
    $statusLabels = [
        'assigned'    => 'Assigned',
        'in_progress' => 'In Progress',
        'completed'   => 'Completed',
        'overdue'     => 'Overdue',
    ];

    $sortLabels = [
        'newest'     => 'Newest first',
        'visit_asc'  => 'Visit date: soonest',
        'visit_desc' => 'Visit date: latest',
    ];

    $f = [
        'search'     => trim((string) request('search')),
        'status'     => request('status'),
        'priority'   => request('priority'),
        'visit_from' => request('visit_from'),
        'visit_to'   => request('visit_to'),
        'sort'       => request('sort', 'newest'),
    ];

    $hasFilters = $f['search'] !== '' || $f['status'] || $f['priority']
        || $f['visit_from'] || $f['visit_to'] || $f['sort'] !== 'newest';

    // Remove one filter, keep the rest.
    $without = fn (string ...$keys) => route(
        'welfare.verification.index',
        array_filter(
            request()->except([...$keys, 'page']),
            fn ($v) => $v !== null && $v !== ''
        )
    );

    $canAssign = auth()->user()?->hasPermission('welfare.beneficiaries.verify');
@endphp


<div class="page-header">

    <div>
        <h1>Field Verification</h1>
        <p>
            Manage beneficiary and welfare field verification assignments,
            visits, findings and supporting evidence.
        </p>
    </div>

    <div class="actions">

        @if ($canAssign)
            @if(auth()->user()?->hasPermission('welfare.beneficiaries.verify'))<a href="{{ route('welfare.verification.create') }}" class="ui-btn primary">
                <i class="bi bi-plus-lg"></i>
                Assign Verification
            </a>@endif
        @endif

    </div>

</div>


@if (session('success'))
    <div style="margin-bottom:18px;padding:13px 15px;background:#edf7f0;border:1px solid #d4eadb;border-radius:11px;color:#397955;font-size:15px;">
        <i class="bi bi-check-circle"></i>
        {{ session('success') }}
    </div>
@endif

@if ($errors->any())
    <div style="margin-bottom:18px;padding:13px 15px;background:#fff5f4;border:1px solid #f0d6d3;border-radius:11px;color:#a85f58;font-size:15px;">
        <i class="bi bi-exclamation-circle"></i>
        {{ $errors->first() }}
    </div>
@endif


<!-- SUMMARY (click a card to filter) -->

<div class="summary-grid">

    @if(auth()->user()?->hasPermission('welfare.view'))<a
        href="{{ route('welfare.verification.index', ['status' => 'assigned']) }}"
        class="summary-card {{ $f['status'] === 'assigned' ? 'selected' : '' }}"
    >
        <div class="summary-label">Pending Verification</div>
        <div class="summary-value">{{ $pendingVerification }}</div>
        <div class="summary-meta">Awaiting field visit</div>
    </a>@endif

    @if(auth()->user()?->hasPermission('welfare.view'))<a
        href="{{ route('welfare.verification.index', ['status' => 'in_progress']) }}"
        class="summary-card {{ $f['status'] === 'in_progress' ? 'selected' : '' }}"
    >
        <div class="summary-label">In Progress</div>
        <div class="summary-value">{{ $inProgress }}</div>
        <div class="summary-meta">Currently being reviewed</div>
    </a>@endif

    <div class="summary-card">
        <div class="summary-label">Completed This Month</div>
        <div class="summary-value">{{ $completedThisMonth }}</div>
        <div class="summary-meta">Verification reports completed</div>
    </div>

    @if(auth()->user()?->hasPermission('welfare.view'))<a
        href="{{ route('welfare.verification.index', ['status' => 'overdue']) }}"
        class="summary-card {{ $f['status'] === 'overdue' ? 'selected' : '' }}"
    >
        <div class="summary-label">Overdue</div>
        <div class="summary-value">{{ $overdue }}</div>
        <div class="summary-meta">Require immediate attention</div>
    </a>@endif

</div>


<div class="main-card">

    <div class="toolbar">

        <form method="GET" action="{{ route('welfare.verification.index') }}" id="filterForm">

            <div class="search">
                <i class="bi bi-search"></i>
                <input
                    id="searchInput"
                    name="search"
                    type="search"
                    value="{{ $f['search'] }}"
                    maxlength="100"
                    autocomplete="off"
                    placeholder="Search beneficiary, application, officer or VER number..."
                >
            </div>

            <select name="status" class="filter js-auto">
                <option value="">All Statuses</option>
                @foreach ($statusLabels as $value => $label)
                    <option value="{{ $value }}" @selected($f['status'] === $value)>{{ $label }}</option>
                @endforeach
            </select>

            <select name="priority" class="filter js-auto">
                <option value="">All Priorities</option>
                <option value="high" @selected($f['priority'] === 'high')>High</option>
                <option value="normal" @selected($f['priority'] === 'normal')>Normal</option>
            </select>

            <div class="date-filter">
                <span>Visit</span>
                <input type="date" name="visit_from" value="{{ $f['visit_from'] }}" class="js-auto" title="Visit date from">
                <span>to</span>
                <input type="date" name="visit_to" value="{{ $f['visit_to'] }}" class="js-auto" title="Visit date to">
            </div>

            <select name="sort" class="filter js-auto" title="Sort order">
                @foreach ($sortLabels as $value => $label)
                    <option value="{{ $value }}" @selected($f['sort'] === $value)>{{ $label }}</option>
                @endforeach
            </select>

            <button type="submit" class="ui-btn">
                <i class="bi bi-funnel"></i>
                Filter
            </button>

            @if ($hasFilters)
                @if(auth()->user()?->hasPermission('welfare.view'))<a href="{{ route('welfare.verification.index') }}" class="ui-btn">
                    <i class="bi bi-x-lg"></i>
                    Clear
                </a>@endif
            @endif

        </form>

    </div>


    @if ($hasFilters)
        <div class="active-filters">
            <span>Active filters:</span>

            @if ($f['search'] !== '')
                <a class="chip" href="{{ $without('search') }}">“{{ $f['search'] }}” <i class="bi bi-x"></i></a>
            @endif

            @if ($f['status'])
                <a class="chip" href="{{ $without('status') }}">{{ $statusLabels[$f['status']] ?? $f['status'] }} <i class="bi bi-x"></i></a>
            @endif

            @if ($f['priority'])
                <a class="chip" href="{{ $without('priority') }}">{{ ucfirst($f['priority']) }} priority <i class="bi bi-x"></i></a>
            @endif

            @if ($f['visit_from'])
                <a class="chip" href="{{ $without('visit_from') }}">From {{ \Illuminate\Support\Carbon::parse($f['visit_from'])->format('d M Y') }} <i class="bi bi-x"></i></a>
            @endif

            @if ($f['visit_to'])
                <a class="chip" href="{{ $without('visit_to') }}">To {{ \Illuminate\Support\Carbon::parse($f['visit_to'])->format('d M Y') }} <i class="bi bi-x"></i></a>
            @endif

            @if ($f['sort'] !== 'newest')
                <a class="chip" href="{{ $without('sort') }}">{{ $sortLabels[$f['sort']] ?? $f['sort'] }} <i class="bi bi-x"></i></a>
            @endif
        </div>
    @endif


    <div class="table-wrap">

        <table class="verification-table">

            <thead>
                <tr>
                    <th>Beneficiary</th>
                    <th>Application</th>
                    <th>Assigned Officer</th>
                    <th>Visit Date</th>
                    <th>Priority</th>
                    <th>Status</th>
                    <th></th>
                </tr>
            </thead>

            <tbody id="verificationBody">

                @forelse ($verifications as $verification)

                    @php
                        $beneficiaryName = $verification->beneficiary?->full_name ?? 'Unknown Beneficiary';

                        $nameParts = preg_split('/\s+/', trim($beneficiaryName));

                        $initials = strtoupper(
                            mb_substr($nameParts[0] ?? '', 0, 1)
                            . mb_substr($nameParts[count($nameParts) - 1] ?? '', 0, 1)
                        );

                        $isOverdue = in_array($verification->workflow_status, ['assigned', 'in_progress'], true)
                            && $verification->visit_date
                            && $verification->visit_date->isBefore(today());

                        $statusClass = $isOverdue
                            ? 'overdue'
                            : str_replace('_', '-', $verification->workflow_status);

                        $statusLabel = $isOverdue
                            ? 'Overdue'
                            : ($statusLabels[$verification->workflow_status]
                                ?? ucfirst(str_replace('_', ' ', $verification->workflow_status)));

                        $priorityClass = $verification->priority === 'high'
                            ? 'priority-high'
                            : 'priority-normal';
                    @endphp

                    <tr>
                        <td>
                            <div class="person">
                                <div class="avatar">{{ $initials }}</div>
                                <div>
                                    <div class="person-name">{{ $beneficiaryName }}</div>
                                    <div class="person-id">{{ $verification->beneficiary?->beneficiary_number ?? '—' }}</div>
                                </div>
                            </div>
                        </td>

                        <td>{{ $verification->application?->application_number ?? '—' }}</td>

                        <td>{{ $verification->assignedTo?->name ?? 'Unassigned' }}</td>

                        <td>{{ $verification->visit_date?->format('d M Y') ?? '—' }}</td>

                        <td class="{{ $priorityClass }}">{{ ucfirst($verification->priority ?? 'normal') }}</td>

                        <td>
                            <span class="verification-status {{ $statusClass }}">{{ $statusLabel }}</span>
                        </td>

                        <td>
                            @if(auth()->user()?->hasPermission('welfare.view'))<a
                                href="{{ route('welfare.verification.show', $verification) }}"
                                class="action"
                                title="View verification"
                            >
                                <i class="bi bi-eye"></i>
                            </a>@endif
                        </td>
                    </tr>

                @empty

                    <tr>
                        <td colspan="7" style="padding:45px 20px;text-align:center;color:#6d7571;">
                            <div style="font-size:30px;margin-bottom:10px;">
                                <i class="bi bi-clipboard-check"></i>
                            </div>
                            <div style="font-weight:650;">No field verifications found</div>
                            <div style="margin-top:5px;font-size:14px;">
                                @if ($hasFilters)
                                    Nothing matches the current filters.
                                    @if(auth()->user()?->hasPermission('welfare.view'))<a href="{{ route('welfare.verification.index') }}">Clear all filters</a>@endif
                                @else
                                    No verification assignments have been created yet.
                                @endif
                            </div>
                        </td>
                    </tr>

                @endforelse

            </tbody>

        </table>

    </div>


    <div class="table-footer">

        <div class="result">
            @if ($verifications->total() > 0)
                Showing {{ $verifications->firstItem() }}–{{ $verifications->lastItem() }}
                of {{ $verifications->total() }} verification records
            @else
                No verification records
            @endif
        </div>

        @if ($verifications->hasPages())
            <div class="pagination">

                @if ($verifications->onFirstPage())
                    <span class="page-btn" style="display:inline-flex;align-items:center;justify-content:center;opacity:.45;">
                        <i class="bi bi-chevron-left"></i>
                    </span>
                @else
                    <a href="{{ $verifications->previousPageUrl() }}" class="page-btn" style="display:inline-flex;align-items:center;justify-content:center;text-decoration:none;">
                        <i class="bi bi-chevron-left"></i>
                    </a>
                @endif

                @foreach ($verifications->getUrlRange(
                    max(1, $verifications->currentPage() - 2),
                    min($verifications->lastPage(), $verifications->currentPage() + 2)
                ) as $page => $url)
                    <a
                        href="{{ $url }}"
                        class="page-btn {{ $page == $verifications->currentPage() ? 'active' : '' }}"
                        style="display:inline-flex;align-items:center;justify-content:center;text-decoration:none;"
                    >{{ $page }}</a>
                @endforeach

                @if ($verifications->hasMorePages())
                    <a href="{{ $verifications->nextPageUrl() }}" class="page-btn" style="display:inline-flex;align-items:center;justify-content:center;text-decoration:none;">
                        <i class="bi bi-chevron-right"></i>
                    </a>
                @else
                    <span class="page-btn" style="display:inline-flex;align-items:center;justify-content:center;opacity:.45;">
                        <i class="bi bi-chevron-right"></i>
                    </span>
                @endif

            </div>
        @endif

    </div>

</div>


<div class="info-banner">
    <i class="bi bi-clipboard-check"></i>
    <div>
        <strong>Verification evidence</strong>
        <p>
            Verification records should capture field findings,
            supporting documents, photos where appropriate,
            reviewer information and the resulting recommendation.
        </p>
    </div>
</div>

@endsection


@push('scripts')
<script>
    (function () {
        const form   = document.getElementById('filterForm');
        const search = document.getElementById('searchInput');
        let timer;

        // Selects and date pickers apply immediately.
        form.querySelectorAll('.js-auto').forEach(el =>
            el.addEventListener('change', () => form.requestSubmit())
        );

        // Search applies shortly after the user stops typing.
        search.addEventListener('input', () => {
            clearTimeout(timer);
            timer = setTimeout(() => form.requestSubmit(), 500);
        });

        // Don't send empty parameters (keeps URLs clean).
        form.addEventListener('submit', () => {
            form.querySelectorAll('input, select').forEach(el => {
                if (el.value === '' || (el.name === 'sort' && el.value === 'newest')) {
                    el.disabled = true;
                }
            });
        });

        // Keep the caret at the end after a search reload.
        if (search.value && document.activeElement === document.body && location.search.includes('search=')) {
            search.focus();
            search.setSelectionRange(search.value.length, search.value.length);
        }
    })();
</script>
@endpush
