@extends('layouts.app')

@section('title', 'Welfare Applications')

@push('styles')
<link rel="stylesheet" href="{{ asset('css/views/welfare-applications-index-1.css') }}">
@endpush


@section('content')

<div class="page-header">

    <div>
        <h1>Welfare Applications</h1>

        <p>
            Review and manage applications for welfare and community assistance.
        </p>
    </div>

    <div class="actions">

        <button class="ui-btn">
            <i class="bi bi-download"></i>
            Export
        </button>

        @if(auth()->user()?->hasPermission('welfare.assistance.manage'))<a
            href="{{ route('welfare.applications.create') }}"
            class="ui-btn primary"
        >
            <i class="bi bi-plus-lg"></i>
            New Application
        </a>@endif

    </div>

</div>


<!-- SUMMARY -->

<div class="summary-grid">

    <div class="summary-card">
        <div class="summary-label">Total Applications</div>
        <div class="summary-value">{{ $totalApplications }}</div>
        <div class="summary-meta">This year</div>
    </div>

    <div class="summary-card">
        <div class="summary-label">Under Verification</div>
        <div class="summary-value">{{ $underVerification }}</div>
        <div class="summary-meta">Field review required</div>
    </div>

    <div class="summary-card">
        <div class="summary-label">Awaiting Approval</div>
        <div class="summary-value">{{ $awaitingApproval }}</div>
        <div class="summary-meta">Committee decision pending</div>
    </div>

    <div class="summary-card">
        <div class="summary-label">Approved</div>
        <div class="summary-value">{{ $approved }}</div>
        <div class="summary-meta">Approved applications</div>
    </div>

    <div class="summary-card">
        <div class="summary-label">Rejected</div>
        <div class="summary-value">{{ $rejected }}</div>
        <div class="summary-meta">Closed applications</div>
    </div>

</div>


<!-- APPLICATION TABLE -->

<div class="main-card">

    <form
        method="GET"
        action="{{ route('welfare.applications.index') }}"
        class="toolbar"
    >

        <div class="search">

            <i class="bi bi-search"></i>

            <input
                id="applicationSearch"
                type="text"
                name="search"
                value="{{ request('search') }}"
                placeholder="Search applicant, application ID or purpose..."
            >

        </div>


        <select
            id="stageFilter"
            name="stage"
            class="filter"
            onchange="this.form.submit()"
        >
            <option value="">All Stages</option>
            <option value="submitted" @selected(request('stage') === 'submitted')>Submitted</option>
            <option value="verification" @selected(request('stage') === 'verification')>Field Verification</option>
            <option value="recommendation" @selected(request('stage') === 'recommendation')>Recommendation</option>
            <option value="approved" @selected(request('stage') === 'approved')>Approved</option>
            <option value="rejected" @selected(request('stage') === 'rejected')>Rejected</option>
        </select>


        <select
            id="categoryFilter"
            name="category"
            class="filter"
            onchange="this.form.submit()"
        >
            <option value="">All Categories</option>
            <option value="livelihood" @selected(request('category') === 'livelihood')>Livelihood</option>
            <option value="education" @selected(request('category') === 'education')>Education</option>
            <option value="emergency" @selected(request('category') === 'emergency')>Emergency</option>
            <option value="welfare" @selected(request('category') === 'welfare')>General Welfare</option>
        </select>


        <select
            id="priorityFilter"
            name="priority"
            class="filter"
            onchange="this.form.submit()"
        >
            <option value="">All Priorities</option>
            <option value="high" @selected(request('priority') === 'high')>High</option>
            <option value="normal" @selected(request('priority') === 'normal')>Normal</option>
            <option value="low" @selected(request('priority') === 'low')>Low</option>
        </select>

    </form>


    <div class="table-wrap">

        <table class="applications-table">

            <thead>
                <tr>
                    <th>Applicant</th>
                    <th>Category</th>
                    <th>Request</th>
                    <th>Amount</th>
                    <th>Stage</th>
                    <th>Priority</th>
                    <th>Submitted</th>
                    <th></th>
                </tr>
            </thead>


            <tbody id="applicationsBody">

                @forelse ($applications as $application)

                    @php
                        $initials = 'NA';

                        if ($application->beneficiary && $application->beneficiary->full_name) {
                            $nameParts = preg_split('/\s+/', trim($application->beneficiary->full_name));
                            $initials = strtoupper(
                                substr($nameParts[0], 0, 1)
                                . (isset($nameParts[1]) ? substr($nameParts[1], 0, 1) : '')
                            );
                        }

                        $categoryLabel = match ($application->category) {
                            'welfare' => 'General Welfare',
                            default => ucfirst($application->category ?? ''),
                        };

                        $stageLabel = match ($application->status) {
                            'submitted' => 'Submitted',
                            'verification' => 'Field Verification',
                            'recommendation' => 'Recommendation',
                            'approved' => 'Approved',
                            'rejected' => 'Rejected',
                            default => ucfirst($application->status ?? ''),
                        };
                    @endphp

                    <tr
                        data-stage="{{ $application->status }}"
                        data-category="{{ $application->category }}"
                        data-priority="{{ $application->priority }}"
                    >

                        <td>
                            <div class="applicant">

                                <div class="avatar">{{ $initials }}</div>

                                <div>
                                    <div class="applicant-name">
                                        {{ $application->beneficiary->full_name ?? 'Unknown beneficiary' }}
                                    </div>

                                    <div class="applicant-id">
                                        {{ $application->application_number }}
                                        @if ($application->beneficiary)
                                            &middot; {{ $application->beneficiary->beneficiary_number }}
                                        @endif
                                    </div>
                                </div>

                            </div>
                        </td>

                        <td>
                            <span class="category {{ $application->category }}">
                                {{ $categoryLabel }}
                            </span>
                        </td>

                        <td>
                            {{ $application->purpose_details ?? $application->request_type }}
                        </td>

                        <td class="amount">
                            PKR {{ \App\Domain\Finance\Support\Money::format($application->requested_amount) }}
                        </td>

                        <td>
                            <span class="stage {{ $application->status }}">
                                {{ $stageLabel }}
                            </span>
                        </td>

                        <td>
                            <span class="priority {{ $application->priority }}">
                                {{ ucfirst($application->priority) }}
                            </span>
                        </td>

                        <td>
                            {{ optional($application->submitted_at)->format('d M Y') }}
                        </td>

                        <td>
                            @if(auth()->user()?->hasPermission('welfare.assistance.view'))<a
                                href="{{ route('welfare.applications.show', $application) }}"
                                class="action"
                            >
                                <i class="bi bi-eye"></i>
                            </a>@endif
                        </td>

                    </tr>

                @empty

                    <tr>
                        <td
                            colspan="8"
                            style="text-align:center; padding:28px 18px; color:var(--legacy-text-6d7571, #6d7571);"
                        >
                            No applications found.
                        </td>
                    </tr>

                @endforelse

            </tbody>

        </table>

    </div>


    <div class="table-footer">

        <div class="result">
            Showing {{ $applications->firstItem() ?? 0 }}&ndash;{{ $applications->lastItem() ?? 0 }}
            of {{ $applications->total() }} applications
        </div>

        @if ($applications->hasPages())

            <div class="pagination">

                <button
                    type="button"
                    class="page-btn"
                    @if ($applications->onFirstPage()) disabled @else onclick="window.location='{{ $applications->previousPageUrl() }}'" @endif
                >
                    <i class="bi bi-chevron-left"></i>
                </button>

                @for ($page = 1; $page <= $applications->lastPage(); $page++)
                    <button
                        type="button"
                        class="page-btn {{ $page === $applications->currentPage() ? 'active' : '' }}"
                        onclick="window.location='{{ $applications->url($page) }}'"
                    >{{ $page }}</button>
                @endfor

                <button
                    type="button"
                    class="page-btn"
                    @if ($applications->hasMorePages()) onclick="window.location='{{ $applications->nextPageUrl() }}'" @else disabled @endif
                >
                    <i class="bi bi-chevron-right"></i>
                </button>

            </div>

        @endif

    </div>

</div>


<div class="privacy">

    <i class="bi bi-shield-lock"></i>

    <div>

        <strong>
            Application confidentiality
        </strong>

        <p>
            Welfare applications may contain sensitive personal and financial
            information. Access should be limited to authorized welfare and
            committee personnel.
        </p>

    </div>

</div>

@endsection


@push('scripts')
<script>

    const applicationSearch =
        document.getElementById('applicationSearch');

    let searchDebounce;

    applicationSearch?.addEventListener('input', () => {
        clearTimeout(searchDebounce);

        searchDebounce = setTimeout(() => {
            applicationSearch.form.submit();
        }, 500);
    });

</script>
@endpush