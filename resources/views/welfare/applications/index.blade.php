@extends('layouts.app')

@section('title', 'Welfare Applications')

@push('styles')
<style>
    .page-header {
        display:flex;
        justify-content:space-between;
        align-items:flex-end;
        gap:20px;
        margin-bottom:24px;
    }

    .page-header h1 {
        margin:0;
        font-size: 25px;
        font-weight:700;
        letter-spacing:-.5px;
    }

    .page-header p {
        margin:7px 0 0;
        color:var(--muted);
        font-size: 16px;
    }

    .actions {
        display:flex;
        gap:8px;
    }

    .ui-btn {
        height:40px;
        padding:0 14px;
        border-radius:10px;
        display:inline-flex;
        align-items:center;
        justify-content:center;
        gap:7px;
        font-size: 16px;
        font-weight:650;
        border:1px solid var(--border);
        background:#fff;
        color:#637069;
        text-decoration:none;
        cursor:pointer;
    }

    .ui-btn.primary {
        background:var(--primary);
        border-color:var(--primary);
        color:#fff;
    }

    /* SUMMARY */

    .summary-grid {
        display:grid;
        grid-template-columns:repeat(5,minmax(0,1fr));
        gap:12px;
        margin-bottom:18px;
    }

    .summary-card {
        background:#fff;
        border:1px solid var(--border);
        border-radius:13px;
        padding:15px;
    }

    .summary-label {
        color:var(--muted);
        font-size: 16px;
    }

    .summary-value {
        margin-top:6px;
        font-size: 21px;
        font-weight:700;
    }

    .summary-meta {
        margin-top:4px;
        color: #6d7572;
        font-size: 16px;
    }

    /* MAIN */

    .main-card {
        background:#fff;
        border:1px solid var(--border);
        border-radius:14px;
        overflow:hidden;
    }

    .toolbar {
        padding:15px 17px;
        border-bottom:1px solid #eef1ef;
        display:flex;
        align-items:center;
        gap:9px;
        flex-wrap:wrap;
    }

    .search {
        position:relative;
        flex:1;
        min-width:230px;
    }

    .search i {
        position:absolute;
        left:13px;
        top:50%;
        transform:translateY(-50%);
        color: #6f7672;
        font-size: 17px;
    }

    .search input {
        width:100%;
        height:40px;
        border:1px solid var(--border);
        border-radius:9px;
        padding:0 12px 0 36px;
        background:#fafcfa;
        outline:none;
        font-size: 16px;
    }

    .search input:focus {
        background:#fff;
        border-color: #6e7772;
        box-shadow:0 0 0 3px rgba(23,63,53,.06);
    }

    .filter {
        height:40px;
        min-width:145px;
        border:1px solid var(--border);
        border-radius:9px;
        padding:0 30px 0 12px;
        background:#fff;
        color:#68756f;
        font-size: 16px;
        outline:none;
    }

    /* TABLE */

    .table-wrap {
        overflow-x:auto;
    }

    .applications-table {
        width:100%;
        min-width:1050px;
        border-collapse:collapse;
    }

    .applications-table th {
        padding:11px 18px;
        background:#fafcfb;
        border-bottom:1px solid #eef1ef;
        color: #6c7470;
        font-size: 16px;
        font-weight:700;
        text-transform:uppercase;
        letter-spacing:.6px;
        text-align:left;
        white-space:nowrap;
    }

    .applications-table td {
        padding:13px 18px;
        border-bottom:1px solid #f0f3f1;
        font-size: 16px;
        vertical-align:middle;
    }

    .applications-table tbody tr:hover {
        background:#fbfcfb;
    }

    .applications-table tbody tr:last-child td {
        border-bottom:none;
    }

    .applicant {
        display:flex;
        align-items:center;
        gap:9px;
    }

    .avatar {
        width:34px;
        height:34px;
        border-radius:9px;
        background:var(--primary-soft);
        color:var(--primary);
        display:flex;
        align-items:center;
        justify-content:center;
        font-size: 16px;
        font-weight:700;
        flex-shrink:0;
    }

    .applicant-name {
        font-size: 16px;
        font-weight:650;
    }

    .applicant-id {
        margin-top:3px;
        color: #6d7571;
        font-size: 16px;
    }

    .category {
        display:inline-flex;
        padding:5px 8px;
        border-radius:20px;
        font-size: 16px;
        font-weight:650;
        background:#fff4ed;
        color: #9e664d;
    }

    .category.education {
        background:#eef3f8;
        color:#55758d;
    }

    .category.emergency {
        background:#fff0ef;
        color:#a85f58;
    }

    .category.livelihood {
        background:#edf7f0;
        color:#397955;
    }

    .amount {
        font-weight:700;
        color:#49564f;
    }

    .stage {
        display:inline-flex;
        align-items:center;
        gap:5px;
        padding:5px 8px;
        border-radius:20px;
        font-size: 16px;
        font-weight:650;
        white-space:nowrap;
    }

    .stage::before {
        content:"";
        width:5px;
        height:5px;
        border-radius:50%;
    }

    .stage.submitted {
        background:#eef3f8;
        color:#55758d;
    }

    .stage.submitted::before {
        background:#6d8ea5;
    }

    .stage.verification {
        background:#fff5e9;
        color: #976d38;
    }

    .stage.verification::before {
        background:#d19a4d;
    }

    .stage.recommendation {
        background:#f1eef7;
        color:#77668c;
    }

    .stage.recommendation::before {
        background:#8c77a3;
    }

    .stage.approved {
        background:#edf7f0;
        color:#397955;
    }

    .stage.approved::before {
        background:#4c9a6c;
    }

    .stage.rejected {
        background:#fff0ef;
        color:#a85f58;
    }

    .stage.rejected::before {
        background:#b96b63;
    }

    .priority {
        display:inline-flex;
        padding:4px 7px;
        border-radius:20px;
        font-size: 16px;
        font-weight:650;
    }

    .priority.high {
        background:#fff0ef;
        color:#a85f58;
    }

    .priority.normal {
        background:#f1f4f2;
        color:#69756f;
    }

    .priority.low {
        background:#edf7f0;
        color:#397955;
    }

    .action {
        width:30px;
        height:30px;
        border:1px solid var(--border);
        background:#fff;
        border-radius:8px;
        display:inline-flex;
        align-items:center;
        justify-content:center;
        color: #6c7771;
        text-decoration:none;
    }

    .action:hover {
        color:var(--primary);
        background:#f7faf8;
    }

    /* FOOTER */

    .table-footer {
        padding:14px 18px;
        border-top:1px solid #eef1ef;
        display:flex;
        justify-content:space-between;
        align-items:center;
        gap:10px;
    }

    .result {
        color: #6e7672;
        font-size: 16px;
    }

    .pagination {
        display:flex;
        gap:5px;
    }

    .page-btn {
        min-width:29px;
        height:29px;
        border:1px solid var(--border);
        background:#fff;
        border-radius:7px;
        font-size: 16px;
        color:#69756f;
        cursor:pointer;
    }

    .page-btn:disabled {
        opacity:.45;
        cursor:default;
    }

    .page-btn.active {
        background:var(--primary);
        border-color:var(--primary);
        color:#fff;
    }

    /* PRIVACY */

    .privacy {
        margin-top:18px;
        padding:14px 16px;
        display:flex;
        gap:10px;
        background:#f7f9f8;
        border:1px solid #e5ebe8;
        border-radius:13px;
    }

    .privacy i {
        color:var(--primary);
        font-size: 17px;
    }

    .privacy strong {
        display:block;
        font-size: 16px;
    }

    .privacy p {
        margin:4px 0 0;
        color: #6b7670;
        font-size: 16px;
        line-height:1.6;
    }

    @media(max-width:1100px) {
        .summary-grid {
            grid-template-columns:repeat(3,1fr);
        }
    }

    @media(max-width:700px) {

        .page-header {
            flex-direction:column;
            align-items:flex-start;
        }

        .actions {
            width:100%;
        }

        .actions .ui-btn {
            flex:1;
        }

        .summary-grid {
            grid-template-columns:repeat(2,1fr);
        }

        .search {
            min-width:100%;
        }

        .filter {
            flex:1;
            min-width:0;
        }

        .table-footer {
            flex-direction:column;
            align-items:flex-start;
        }
    }

    @media(max-width:450px) {
        .summary-grid {
            grid-template-columns:1fr;
        }
    }
</style>
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

        <a
            href="{{ route('welfare.applications.create') }}"
            class="ui-btn primary"
        >
            <i class="bi bi-plus-lg"></i>
            New Application
        </a>

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
                            <a
                                href="{{ route('welfare.applications.show', $application) }}"
                                class="action"
                            >
                                <i class="bi bi-eye"></i>
                            </a>
                        </td>

                    </tr>

                @empty

                    <tr>
                        <td
                            colspan="8"
                            style="text-align:center; padding:28px 18px; color:#6d7571;"
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