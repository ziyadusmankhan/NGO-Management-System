@extends('layouts.app')

@section('title', 'Application Details')

@push('styles')
<style>
    .page-header {
        display: flex;
        justify-content: space-between;
        align-items: flex-start;
        gap: 20px;
        margin-bottom: 22px;
    }

    .identity {
        display: flex;
        align-items: center;
        gap: 14px;
    }

    .app-icon {
        width: 62px;
        height: 62px;
        border-radius: 17px;
        background: var(--primary-soft);
        color: var(--primary);
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 23px;
    }

    .title {
        font-size: 23px;
        font-weight: 700;
        letter-spacing: -.4px;
    }

    .meta {
        margin-top: 4px;
        color: #6e7773;
        font-size: 16px;
    }

    .actions {
        display: flex;
        gap: 8px;
        flex-wrap: wrap;
    }

    .ui-btn {
        height: 39px;
        padding: 0 12px;
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
        cursor: pointer;
    }

    .ui-btn.primary {
        background: var(--primary);
        border-color: var(--primary);
        color: #fff;
    }

    .ui-btn.approve {
        background: #edf7f0;
        border-color: #6a746d;
        color: #397955;
    }

    .ui-btn.reject {
        background: #fff0ef;
        border-color: #7c706f;
        color: #a85f58;
    }

    .layout {
        display: grid;
        grid-template-columns: minmax(0, 1fr) 300px;
        gap: 18px;
    }

    .card {
        background: #fff;
        border: 1px solid var(--border);
        border-radius: 14px;
        overflow: hidden;
        margin-bottom: 18px;
    }

    .card-head {
        padding: 16px 19px;
        border-bottom: 1px solid #eef1ef;
        display: flex;
        align-items: center;
        justify-content: space-between;
        gap: 15px;
    }

    .card-head strong {
        font-size: 16px;
    }

    .card-head span {
        display: block;
        margin-top: 4px;
        color: #6c7470;
        font-size: 16px;
    }

    .card-body {
        padding: 18px 19px;
    }

    /* STATUS */

    .stage {
        display: inline-flex;
        align-items: center;
        gap: 5px;
        padding: 5px 8px;
        border-radius: 20px;
        background: #fff5e9;
        color: #976d38;
        font-size: 16px;
        font-weight: 650;
    }

    .stage::before {
        content: "";
        width: 5px;
        height: 5px;
        border-radius: 50%;
        background: #d19a4d;
    }

    .stage.submitted {
        background: #eef3f8;
        color: #55758d;
    }

    .stage.submitted::before {
        background: #6d8ea5;
    }

    .stage.recommendation {
        background: #f1eef7;
        color: #77668c;
    }

    .stage.recommendation::before {
        background: #8c77a3;
    }

    .stage.approved {
        background: #edf7f0;
        color: #397955;
    }

    .stage.approved::before {
        background: #4c9a6c;
    }

    .stage.rejected {
        background: #fff0ef;
        color: #a85f58;
    }

    .stage.rejected::before {
        background: #b96b63;
    }

    .priority {
        display: inline-flex;
        padding: 5px 8px;
        border-radius: 20px;
        background: #fff0ef;
        color: #a85f58;
        font-size: 16px;
        font-weight: 650;
    }

    .priority.normal {
        background: #f1f4f2;
        color: #69756f;
    }

    .priority.low {
        background: #edf7f0;
        color: #397955;
    }

    /* OVERVIEW */

    .overview-grid {
        display: grid;
        grid-template-columns: repeat(3, minmax(0, 1fr));
        gap: 10px;
    }

    .overview-item {
        padding: 13px;
        border: 1px solid #edf1ee;
        border-radius: 10px;
        background: #fafcfb;
    }

    .overview-item label {
        display: block;
        color: #6d7571;
        font-size: 16px;
        text-transform: uppercase;
        letter-spacing: .5px;
    }

    .overview-item strong {
        display: block;
        margin-top: 5px;
        font-size: 16px;
    }

    /* WORKFLOW */

    .workflow {
        display: flex;
        align-items: flex-start;
        overflow-x: auto;
        padding-bottom: 4px;
    }

    .workflow-stage {
        min-width: 145px;
        text-align: center;
        position: relative;
    }

    .workflow-stage:not(:last-child)::after {
        content: "";
        position: absolute;
        width: 45px;
        height: 1px;
        top: 15px;
        right: -22px;
        background: #d9e2de;
    }

    .workflow-circle {
        width: 30px;
        height: 30px;
        margin: 0 auto 8px;
        border-radius: 9px;
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 16px;
        font-weight: 700;
        position: relative;
        z-index: 1;
    }

    .workflow-circle.done {
        background: var(--primary);
        color: #fff;
    }

    .workflow-circle.current {
        background: #f0c46f;
        color: #624d20;
    }

    .workflow-circle.pending {
        background: #eef1ef;
        color: #6d7671;
    }

    .workflow-stage strong {
        display: block;
        font-size: 16px;
    }

    .workflow-stage span {
        display: block;
        margin-top: 3px;
        color: #6d7571;
        font-size: 16px;
        line-height: 1.4;
    }

    /* APPLICANT */

    .applicant-box {
        display: flex;
        align-items: center;
        gap: 11px;
        padding: 12px;
        background: #fafcfb;
        border: 1px solid #edf1ee;
        border-radius: 10px;
    }

    .avatar {
        width: 39px;
        height: 39px;
        border-radius: 11px;
        background: var(--primary-soft);
        color: var(--primary);
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 16px;
        font-weight: 700;
    }

    .applicant-name {
        font-size: 16px;
        font-weight: 650;
    }

    .applicant-meta {
        margin-top: 3px;
        color: #6d7470;
        font-size: 16px;
    }

    /* REQUEST */

    .request-text {
        color: #647169;
        font-size: 16px;
        line-height: 1.7;
    }

    .financial-row {
        display: grid;
        grid-template-columns: repeat(3, minmax(0, 1fr));
        gap: 10px;
        margin-top: 16px;
    }

    .financial-box {
        padding: 13px;
        border-radius: 10px;
        background: #fafcfb;
        border: 1px solid #edf1ee;
    }

    .financial-box label {
        display: block;
        color: #6d7571;
        font-size: 16px;
        text-transform: uppercase;
    }

    .financial-box strong {
        display: block;
        margin-top: 6px;
        font-size: 17px;
    }

    /* TIMELINE */

    .timeline-item {
        display: flex;
        gap: 11px;
        padding-bottom: 20px;
        position: relative;
    }

    .timeline-item:last-child {
        padding-bottom: 0;
    }

    .timeline-dot {
        width: 10px;
        height: 10px;
        margin-top: 3px;
        border-radius: 50%;
        background: var(--primary);
        flex-shrink: 0;
        position: relative;
    }

    .timeline-item:not(:last-child) .timeline-dot::after {
        content: "";
        position: absolute;
        width: 1px;
        height: 32px;
        left: 4px;
        top: 10px;
        background: #d9e2de;
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

    /* DOCUMENTS */

    .document-row {
        display: flex;
        align-items: center;
        gap: 10px;
        padding: 11px 0;
        border-bottom: 1px solid #f0f3f1;
    }

    .document-row:last-child {
        border-bottom: none;
    }

    .document-icon {
        width: 32px;
        height: 32px;
        border-radius: 9px;
        background: #f4f7f5;
        color: var(--primary);
        display: flex;
        align-items: center;
        justify-content: center;
    }

    .document-info {
        flex: 1;
    }

    .document-info strong {
        display: block;
        font-size: 16px;
        word-break: break-all;
    }

    .document-info span {
        display: block;
        margin-top: 3px;
        color: #6c7470;
        font-size: 16px;
    }

    .doc-action {
        width: 28px;
        height: 28px;
        border: 1px solid var(--border);
        background: #fff;
        border-radius: 7px;
        color: #6b766f;
        display: inline-flex;
        align-items: center;
        justify-content: center;
        text-decoration: none;
        flex-shrink: 0;
    }

    /* SIDE */

    .side-stat {
        text-align: center;
        padding: 7px 0 16px;
    }

    .side-number {
        font-size: 25px;
        font-weight: 700;
        color: var(--primary);
    }

    .side-label {
        margin-top: 3px;
        color: #6e7772;
        font-size: 16px;
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

    .security-note {
        display: flex;
        gap: 9px;
        padding: 12px;
        background: #f7f9f8;
        border: 1px solid #e5ebe8;
        border-radius: 10px;
    }

    .security-note i {
        color: var(--primary);
        font-size: 17px;
    }

    .security-note p {
        margin: 0;
        color: #6b766f;
        font-size: 16px;
        line-height: 1.6;
    }

    /* DECISION BOX */

    .decision-box {
        display: flex;
        gap: 9px;
    }

    .decision-box form {
        flex: 1;
    }

    .decision-btn {
        width: 100%;
        height: 39px;
        border-radius: 9px;
        border: 1px solid var(--border);
        background: #fff;
        font-size: 16px;
        font-weight: 650;
        cursor: pointer;
    }

    .decision-btn.approve {
        color: #397955;
        background: #edf7f0;
        border-color: #6a746d;
    }

    .decision-btn.reject {
        color: #a85f58;
        background: #fff0ef;
        border-color: #7c706f;
    }

    .decision-form label {
        display: block;
        margin: 12px 0 5px;
        color: #56645c;
        font-size: 14px;
        font-weight: 650;
    }

    .decision-form input,
    .decision-form textarea {
        width: 100%;
        max-width: 100%;
        box-sizing: border-box;
        border: 1px solid var(--border);
        border-radius: 9px;
        background: #fff;
        color: #334139;
        padding: 10px 11px;
        font: inherit;
        font-size: 14px;
    }

    .decision-form textarea {
        resize: vertical;
        line-height: 1.55;
    }

    .decision-form .decision-box {
        margin-top: 12px;
        flex-wrap: wrap;
    }

    .decision-form .decision-box button {
        flex: 1 1 140px;
        min-height: 39px;
        padding: 8px 10px;
    }
    .verification-assigned {
    background:#fff5e9;
    color:#976d38;
   }

    .verification-progress {
        background:#eef3f8;
        color:#55758d;
    }

    .verification-completed {
        background:#edf7f0;
        color:#397955;
    }

    .verification-pending {
        background:#f1f4f2;
        color:#69756f;
    }

    @media (max-width: 900px) {
        .layout {
            grid-template-columns: 1fr;
        }
    }

    @media (max-width: 700px) {
        .page-header {
            flex-direction: column;
        }

        .actions {
            width: 100%;
        }

        .actions .ui-btn {
            flex: 1;
            justify-content: center;
        }

        .overview-grid,
        .financial-row {
            grid-template-columns: 1fr;
        }
    }
</style>
@endpush

@php
    $categoryLabel = match ($application->category) {
        'welfare' => 'General Welfare',
        default => ucfirst($application->category ?? ''),
    };

    $stageLabel = match ($application->status) {
        'submitted' => 'Awaiting Field Verification',
        'verification' => 'Field Verification',
        'recommendation' => 'Implementation Committee Review',
        'general_secretary_review' => 'General Secretary Review',
        'committee_review' => 'Decision-Making Committee Review',
        'approved' => 'Approved',
        'rejected' => 'Rejected',
        default => ucfirst(str_replace('_', ' ', $application->status ?? '')),
    };

    $latestVerification = $application->verifications->first();
    $hasCompletedVerification = $latestVerification?->workflow_status === 'completed';

    $canImplementationDecision =
        $application->status === 'recommendation'
        && $hasCompletedVerification
        && auth()->user()?->hasPermission('welfare.applications.recommend');

    $canSecretaryReview =
        $application->status === 'general_secretary_review'
        && auth()->user()?->hasPermission('welfare.applications.review');

    $canCommitteeDecision =
        $application->status === 'committee_review'
        && auth()->user()?->hasPermission('welfare.applications.approve');

    $initials = 'NA';

    if ($application->beneficiary && $application->beneficiary->full_name) {
        $nameParts = preg_split('/\s+/', trim($application->beneficiary->full_name));
        $initials = strtoupper(
            substr($nameParts[0], 0, 1)
            . (isset($nameParts[1]) ? substr($nameParts[1], 0, 1) : '')
        );
    }
@endphp

@section('content')

<div class="page-header">

    <div class="identity">

        <div class="app-icon">
            <i class="bi bi-file-earmark-person"></i>
        </div>

        <div>

            <div class="title">
                {{ $application->application_number }}
            </div>

            <div class="meta">
                {{ optional($application->beneficiary)->full_name ?? 'Unknown beneficiary' }}
                &middot; {{ $categoryLabel }} Assistance
                &middot; Submitted {{ optional($application->submitted_at)->format('d M Y') }}
            </div>

        </div>

    </div>

    <div class="actions">

        <a
            href="{{ route('welfare.applications.index') }}"
            class="ui-btn"
        >
            <i class="bi bi-arrow-left"></i>
            Back
        </a>


    </div>

</div>

@if (session('success'))
    <div style="margin-bottom:16px;padding:12px 16px;background:#edf7f0;border:1px solid #cfe6d8;border-radius:10px;color:#397955;font-size:16px;">
        {{ session('success') }}
    </div>
@endif

@if ($errors->any())
    <div style="margin-bottom:16px;padding:12px 16px;background:#fff0ef;border:1px solid #f0d3d1;border-radius:10px;color:#a85f58;font-size:16px;">
        @foreach ($errors->all() as $error)
            <div>{{ $error }}</div>
        @endforeach
    </div>
@endif


<div class="layout">

    <div>

        <!-- OVERVIEW -->

        <div class="card">

            <div class="card-head">

                <div>
                    <strong>Application Overview</strong>

                    <span>
                        Current application status and summary.
                    </span>
                </div>

                <span class="stage {{ $application->status }}">
                    {{ $stageLabel }}
                </span>

            </div>

            <div class="card-body">

                <div class="overview-grid">

                    <div class="overview-item">
                        <label>Application ID</label>
                        <strong>{{ $application->application_number }}</strong>
                    </div>

                    <div class="overview-item">
                        <label>Category</label>
                        <strong>{{ $categoryLabel }}</strong>
                    </div>

                    <div class="overview-item">
                        <label>Requested Amount</label>
                        <strong>PKR {{ number_format($application->requested_amount) }}</strong>
                    </div>

                    <div class="overview-item">
                        <label>Funding Source</label>
                        <strong>{{ optional($application->fund)->name ?? 'Not specified' }}</strong>
                    </div>

                    <div class="overview-item">
                        <label>Priority</label>
                        <strong>{{ ucfirst($application->priority) }}</strong>
                    </div>

                    <div class="overview-item">
                        <label>Submitted</label>
                        <strong>{{ optional($application->submitted_at)->format('d M Y') }}</strong>
                    </div>

                </div>

            </div>

        </div>
        
        <!-- AGREEMENT -->
                @if (
                    $application->status === 'approved'
                    && auth()->user()?->hasPermission('welfare.agreements.view')
                )
                    <div class="card">

                        <div class="card-head">
                            <div>
                                <strong>Welfare Agreement</strong>
                                <span>Agreement preparation and status for this approved application.</span>
                            </div>
                        </div>

                        <div class="card-body">

                            @if ($application->agreement)
                                <div class="overview-grid">

                                    <div class="overview-item">
                                        <label>Agreement Number</label>
                                        <strong>{{ $application->agreement->agreement_number }}</strong>
                                    </div>

                                    <div class="overview-item">
                                        <label>Status</label>
                                        <strong>{{ ucfirst($application->agreement->status) }}</strong>
                                    </div>

                                    <div class="overview-item">
                                        <label>Approved Amount</label>
                                        <strong>
                                            PKR {{ number_format((float) $application->agreement->approved_amount, 2) }}
                                        </strong>
                                    </div>

                                </div>

                                <p style="margin:16px 0 0;color:#6d7571;">
                                    This agreement has not necessarily been signed. Check its signature
                                    status on the agreement details page.
                                </p>

                                <div style="margin-top:16px;">
                                    <a
                                        href="{{ route('welfare.applications.agreement.show', $application) }}"
                                        class="ui-btn primary"
                                    >
                                        View Agreement
                                    </a>
                                </div>

                            @elseif ($application->application_number === 'APP-00010')
                                <p style="margin:0;color:#6d7571;">
                                    This is a reserved test-only application. Agreement creation is disabled.
                                </p>

                            @elseif ((int) $application->submitted_by === (int) auth()->id())
                                <p style="margin:0;color:#6d7571;">
                                    The application submitter cannot prepare its agreement.
                                </p>

                            @else
                                <p style="margin:0 0 16px;color:#6d7571;">
                                    Final approval is recorded. No agreement has been created yet.
                                </p>

                                @if (auth()->user()?->hasPermission('welfare.agreements.manage'))
                                    <a
                                        href="{{ route('welfare.applications.agreement.create', $application) }}"
                                        class="ui-btn primary"
                                    >
                                        Prepare Draft Agreement
                                    </a>
                                @endif
                            @endif

                        </div>
                    </div>
                @endif



        <!-- WORKFLOW -->

        <div class="card">

            <div class="card-head">

                <div>
                    <strong>Application Workflow</strong>

                    <span>
                        Current stage in the welfare approval process.
                    </span>
                </div>

            </div>

            <div class="card-body">

                @php
                    $stageOrder = [
                        'submitted',
                        'verification',
                        'recommendation',
                        'general_secretary_review',
                        'committee_review',
                        'approved',
                    ];
                    $currentIndex = array_search($application->status, $stageOrder, true);
                    $isRejected = $application->status === 'rejected';

                    $steps = [
                        ['label' => 'Application', 'done_text' => 'Submitted'],
                        ['label' => 'Field Verification', 'done_text' => 'Report completed'],
                        ['label' => 'Implementation Committee', 'done_text' => 'Recommendation recorded'],
                        ['label' => 'General Secretary', 'done_text' => 'Review completed'],
                        ['label' => 'Decision Committee', 'done_text' => 'Decision recorded'],
                        ['label' => 'Final Decision', 'done_text' => 'Approved'],
                    ];
                @endphp

                <div class="workflow">
                    @foreach ($steps as $index => $step)
                        @php
                            if ($isRejected || $currentIndex === false) {
                                $circleClass = 'pending';
                            } elseif ($application->status === 'approved' || $index < $currentIndex) {
                                $circleClass = 'done';
                            } elseif ($index === $currentIndex) {
                                $circleClass = 'current';
                            } else {
                                $circleClass = 'pending';
                            }
                        @endphp

                        <div class="workflow-stage">
                            <div class="workflow-circle {{ $circleClass }}">
                                @if ($circleClass === 'done')
                                    <i class="bi bi-check-lg"></i>
                                @else
                                    {{ $index + 1 }}
                                @endif
                            </div>

                            <strong>{{ $step['label'] }}</strong>
                            <span>
                                @if ($circleClass === 'done')
                                    {{ $step['done_text'] }}
                                @elseif ($circleClass === 'current')
                                    In progress
                                @elseif ($isRejected)
                                    Stopped
                                @else
                                    Pending
                                @endif
                            </span>
                        </div>
                    @endforeach

                    <div class="workflow-stage">
                        <div class="workflow-circle {{ $application->status === 'approved' ? 'current' : 'pending' }}">
                            7
                        </div>
                        <strong>Assistance</strong>
                        <span>
                            @if ($isRejected)
                                Not applicable
                            @elseif ($application->status === 'approved')
                                Ready for disbursement
                            @else
                                Pending
                            @endif
                        </span>
                    </div>
                </div>

                @if ($isRejected)
                    <p style="margin-top:14px;color:#a85f58;font-size:16px;">
                        This application was rejected
                        @if ($application->rejected_at)
                            on {{ $application->rejected_at->format('d M Y') }}
                        @endif
                        and did not proceed through the remaining workflow stages.
                    </p>
                @endif

            </div>

        </div>


        <!-- APPLICANT -->

        <div class="card">

            <div class="card-head">

                <div>
                    <strong>Applicant</strong>

                    <span>
                        Linked beneficiary information.
                    </span>
                </div>

            </div>

            <div class="card-body">

                <div class="applicant-box">

                    <div class="avatar">
                        {{ $initials }}
                    </div>

                    <div style="flex:1">

                        <div class="applicant-name">
                            {{ optional($application->beneficiary)->full_name ?? 'Unknown beneficiary' }}
                        </div>

                        <div class="applicant-meta">
                            {{ optional($application->beneficiary)->beneficiary_number ?? '—' }}
                            @if ($application->beneficiary)
                                &middot; {{ ucfirst($application->beneficiary->verification_status) }}
                            @endif
                        </div>

                    </div>

                    @if ($application->beneficiary)
                        <a
                            href="{{ route('welfare.beneficiaries.show', $application->beneficiary) }}"
                            class="ui-btn"
                        >
                            View Beneficiary
                        </a>
                    @endif

                </div>

            </div>

        </div>


        <!-- REQUEST -->

        <div class="card">

            <div class="card-head">

                <div>
                    <strong>Request Details</strong>

                    <span>
                        Purpose and financial requirement.
                    </span>
                </div>

            </div>

            <div class="card-body">

                <div class="request-text">
                    {{ $application->purpose_details ?? $application->request_type }}
                </div>


                <div class="financial-row">

                    <div class="financial-box">

                        <label>Required Investment</label>

                        <strong>
                            PKR {{ number_format($application->requested_amount) }}
                        </strong>

                    </div>


                    <div class="financial-box">

                        <label>Applicant Contribution</label>

                        <strong>
                            PKR {{ number_format($application->applicant_contribution) }}
                        </strong>

                    </div>


                    <div class="financial-box">

                        <label>Organization Contribution</label>

                        <strong>
                            PKR {{ number_format($application->organization_contribution) }}
                        </strong>

                    </div>

                </div>

            </div>

        </div>


        <!-- VERIFICATION / RECOMMENDATION -->

        @php
            $verification = $application->verifications->first();

            $verificationWorkflow =
                $verification?->workflow_status;

            $verificationStatusLabel = match ($verificationWorkflow) {
                'assigned' => 'Assigned',
                'in_progress' => 'In Progress',
                'completed' => 'Completed',
                default => null,
            };

            $verificationStatusClass = match ($verificationWorkflow) {
                'assigned' => 'verification-assigned',
                'in_progress' => 'verification-progress',
                'completed' => 'verification-completed',
                default => 'verification-pending',
            };
        @endphp


        <div class="card">

            <div class="card-head">

                <div>

                    <strong>
                        Verification &amp; Recommendation
                    </strong>

                    <span>
                        Field assessment and recommendation associated with this application.
                    </span>

                </div>


                @if ($verificationStatusLabel)

                    <span
                        class="stage {{ $verificationStatusClass }}"
                    >
                        {{ $verificationStatusLabel }}
                    </span>

                @endif

            </div>


            <div class="card-body">

                @if (!$verification)

                    <div
                        style="
                            display:flex;
                            align-items:flex-start;
                            gap:10px;
                            padding:13px 15px;
                            background:#f7f9f8;
                            border:1px solid #e5ebe8;
                            border-radius:10px;
                        "
                    >

                        <i
                            class="bi bi-clipboard-check"
                            style="
                                color:var(--primary);
                                font-size:18px;
                                margin-top:2px;
                            "
                        ></i>

                        <div>

                            <strong style="font-size:15px;">
                                Field verification not assigned yet
                            </strong>

                            <div
                                style="
                                    margin-top:4px;
                                    color:#6b7670;
                                    font-size:14px;
                                    line-height:1.6;
                                "
                            >
                                This application is currently waiting for a field
                                verification assignment.
                            </div>

                        </div>

                    </div>


                    @if (
                        $application->status === 'submitted'
                        && auth()->user()?->hasPermission('welfare.beneficiaries.verify')
                    )

                        <div style="margin-top:14px;">

                            <a
                                href="{{ route('welfare.verification.create', [
                                    'application_id' => $application->id
                                ]) }}"
                                class="ui-btn"
                            >
                                <i class="bi bi-clipboard-plus"></i>
                                Assign Field Verification
                            </a>

                        </div>

                    @endif


                @else

                    <!-- VERIFICATION OVERVIEW -->

                    <div
                        style="
                            display:grid;
                            grid-template-columns:repeat(4,minmax(0,1fr));
                            gap:10px;
                        "
                    >

                        <div class="overview-item">

                            <label>
                                Verification
                            </label>

                            <strong>
                                {{ $verification->verification_number ?? '—' }}
                            </strong>

                        </div>


                        <div class="overview-item">

                            <label>
                                Assigned Officer
                            </label>

                            <strong>
                                {{ $verification->assignedTo?->name ?? '—' }}
                            </strong>

                        </div>


                        <div class="overview-item">

                            <label>
                                Visit Date
                            </label>

                            <strong>
                                {{ $verification->visit_date?->format('d M Y') ?? '—' }}
                            </strong>

                        </div>


                        <div class="overview-item">

                            <label>
                                Method
                            </label>

                            <strong>
                                {{ $verification->method
                                    ? ucfirst(str_replace('_', ' ', $verification->method))
                                    : '—'
                                }}
                            </strong>

                        </div>

                    </div>


                    <!-- FINDINGS -->

                    @if ($verification->findings)

                        <div style="margin-top:18px;">

                            <label
                                style="
                                    display:block;
                                    margin-bottom:6px;
                                    color:#6d7571;
                                    font-size:13px;
                                    font-weight:650;
                                    text-transform:uppercase;
                                    letter-spacing:.5px;
                                "
                            >
                                Field Findings
                            </label>

                            <div
                                class="request-text"
                                style="
                                    white-space:pre-line;
                                    line-height:1.7;
                                "
                            >
                                {{ $verification->findings }}
                            </div>

                        </div>

                    @endif


                    <!-- CHECKLIST -->

                    @if (
                        is_array($verification->checklist)
                        && count($verification->checklist)
                    )

                        @php

                            $checklistLabels = [
                                'identity_verified' =>
                                    'Identity Verified',

                                'residence_verified' =>
                                    'Residence Verified',

                                'family_condition_verified' =>
                                    'Family Condition Verified',

                                'financial_condition_verified' =>
                                    'Financial Condition Verified',

                                'documents_reviewed' =>
                                    'Documents Reviewed',

                                'photos_attached' =>
                                    'Photos / Evidence Attached',
                            ];

                        @endphp


                        <div style="margin-top:18px;">

                            <label
                                style="
                                    display:block;
                                    margin-bottom:8px;
                                    color:#6d7571;
                                    font-size:13px;
                                    font-weight:650;
                                    text-transform:uppercase;
                                    letter-spacing:.5px;
                                "
                            >
                                Verification Checklist
                            </label>


                            <div
                                style="
                                    display:grid;
                                    grid-template-columns:repeat(2,minmax(0,1fr));
                                    gap:8px;
                                "
                            >

                                @foreach ($checklistLabels as $key => $label)

                                    <div
                                        style="
                                            padding:9px 11px;
                                            border:1px solid #edf1ee;
                                            border-radius:9px;
                                            background:#fafcfb;
                                            color:#59655f;
                                            font-size:14px;
                                        "
                                    >

                                        <i
                                            class="bi {{ !empty($verification->checklist[$key])
                                                ? 'bi-check-circle'
                                                : 'bi-x-circle' }}"
                                            style="
                                                margin-right:5px;
                                                color:{{ !empty($verification->checklist[$key])
                                                    ? '#397955'
                                                    : '#a85f58' }};
                                            "
                                        ></i>

                                        {{ $label }}

                                    </div>

                                @endforeach

                            </div>

                        </div>

                    @endif


                    <!-- RECOMMENDATION -->

                    @if ($verification->recommendation)

                        <div
                            style="
                                margin-top:18px;
                                padding:13px 15px;
                                background:#f7f9f8;
                                border:1px solid #e5ebe8;
                                border-radius:10px;
                            "
                        >

                            <div
                                style="
                                    color:#6d7571;
                                    font-size:13px;
                                    font-weight:650;
                                    text-transform:uppercase;
                                    letter-spacing:.5px;
                                "
                            >
                                Field Recommendation
                            </div>


                            <div
                                style="
                                    margin-top:5px;
                                    font-size:17px;
                                    font-weight:700;
                                    color:#46534c;
                                "
                            >
                                {{ ucfirst($verification->recommendation) }}
                            </div>


                            @if ($verification->recommendation_notes)

                                <div
                                    style="
                                        margin-top:7px;
                                        color:#69766f;
                                        line-height:1.6;
                                        font-size:14px;
                                    "
                                >
                                    {{ $verification->recommendation_notes }}
                                </div>

                            @endif

                        </div>

                    @endif


                    <!-- VERIFICATION ACTION -->

                    <div style="margin-top:16px;display:flex;gap:8px;flex-wrap:wrap;">

                        <a
                            href="{{ route(
                                'welfare.verification.show',
                                $verification
                            ) }}"
                            class="ui-btn"
                        >
                            <i class="bi bi-clipboard-check"></i>
                            View Field Verification
                        </a>

                        @if (
                            $application->status === 'submitted'
                            && auth()->user()?->hasPermission('welfare.beneficiaries.verify')
                        )
                            <a
                                href="{{ route('welfare.verification.create', ['application_id' => $application->id]) }}"
                                class="ui-btn"
                            >
                                <i class="bi bi-arrow-repeat"></i>
                                Assign New Verification
                            </a>
                        @endif

                    </div>

                @endif

            </div>

        </div>


        <!-- DOCUMENTS -->

        <div class="card">

            <div class="card-head">

                <div>
                    <strong>Supporting Documents</strong>

                    <span>
                        Documents attached to this application.
                    </span>
                </div>

            </div>

            <div class="card-body">

                @forelse ($documents as $document)

                    @php
                        $sizeLabel = match (true) {
                            $document['size'] >= 1048576 => number_format($document['size'] / 1048576, 1) . ' MB',
                            $document['size'] >= 1024 => number_format($document['size'] / 1024, 1) . ' KB',
                            default => $document['size'] . ' B',
                        };

                        $iconClass = match ($document['extension']) {
                            'pdf' => 'bi-file-earmark-pdf',
                            'jpg', 'jpeg', 'png', 'gif', 'webp' => 'bi-file-earmark-image',
                            default => 'bi-file-earmark-text',
                        };
                    @endphp

                    <div class="document-row">

                        <div class="document-icon">
                            <i class="bi {{ $iconClass }}"></i>
                        </div>

                        <div class="document-info">

                            <strong>
                                {{ $document['name'] }}
                            </strong>

                            <span>
                                {{ strtoupper($document['extension']) }} &middot; {{ $sizeLabel }}
                            </span>

                        </div>

                        <a
                            href="{{ $document['url'] }}"
                            target="_blank"
                            rel="noopener"
                            class="doc-action"
                        >
                            <i class="bi bi-eye"></i>
                        </a>

                    </div>

                @empty

                    <p style="margin:0;color:#6d7571;font-size:16px;">
                        No supporting documents have been uploaded for this application.
                    </p>

                @endforelse

            </div>

        </div>


        <!-- ACTIVITY -->

        <div class="card">

            <div class="card-head">

                <div>
                    <strong>Activity History</strong>

                    <span>
                        Important actions and status changes.
                    </span>
                </div>

            </div>

            <div class="card-body">

                @php
                    $verification = $application->verifications->first();

                    $activity = collect([
                        [
                            'title' => 'Application submitted',
                            'time' => $application->submitted_at,
                            'description' => optional($application->submittedBy)->name
                                ? 'Submitted by ' . optional($application->submittedBy)->name
                                : null,
                        ],
                    ]);

                    /*
                    * Field verification assigned.
                    */
                    if ($verification && $verification->assigned_at) {
                        $activity->push([
                            'title' => 'Field verification assigned',
                            'time' => $verification->assigned_at,
                            'description' => $verification->assignedTo?->name
                                ? 'Assigned to ' . $verification->assignedTo->name
                                : null,
                        ]);
                    }

                    /*
                    * Field verification started.
                    */
                    if ($verification && $verification->started_at) {
                        $activity->push([
                            'title' => 'Field verification started',
                            'time' => $verification->started_at,
                            'description' => $verification->assignedTo?->name
                                ? $verification->assignedTo->name . ' started the field review'
                                : 'Field review started',
                        ]);
                    }

                    /*
                    * Field verification completed.
                    */
                    if ($verification && $verification->completed_at) {
                        $activity->push([
                            'title' => 'Field verification completed',
                            'time' => $verification->completed_at,
                            'description' => $verification->verifiedBy?->name
                                ? 'Completed by ' . $verification->verifiedBy->name
                                : 'Verification report completed',
                        ]);
                    }

                    /*
                    * Recommendation submitted.
                    */
                    if ($application->recommended_at) {
                        $recommendationLabel = match ($verification?->recommendation) {
                            'approve' => 'Approve',
                            'reject' => 'Reject',
                            'revise' => 'Revise',
                            default => 'Submitted',
                        };

                        $activity->push([
                            'title' => 'Recommendation submitted',
                            'time' => $application->recommended_at,
                            'description' => (
                                $recommendationLabel . ' recommendation'
                                . (
                                    optional($application->recommendedBy)->name
                                        ? ' by ' . $application->recommendedBy->name
                                        : ''
                                )
                            ),
                        ]);
                    }

                    /*
                    * Application approved.
                    */
                    if ($application->approved_at) {
                        $activity->push([
                            'title' => 'Application approved',
                            'time' => $application->approved_at,
                            'description' => optional($application->approvedBy)->name
                                ? 'Approved by ' . optional($application->approvedBy)->name
                                : null,
                        ]);
                    }

                    /*
                    * Application rejected.
                    */
                    if ($application->rejected_at) {
                        $activity->push([
                            'title' => 'Application rejected',
                            'time' => $application->rejected_at,
                            'description' => optional($application->rejectedBy)->name
                                ? 'Rejected by ' . optional($application->rejectedBy)->name
                                : null,
                        ]);
                    }

                    /*
                     * Auditable Implementation Committee, General Secretary,
                     * and Decision-Making Committee decisions.
                    */
                    foreach ($application->reviews as $review) {
                        $reviewStageLabel = match ($review->stage) {
                            'implementation_committee' => 'Implementation Committee',
                            'general_secretary' => 'General Secretary',
                            'decision_making_committee' => 'Decision-Making Committee',
                            default => ucfirst(str_replace('_', ' ', $review->stage)),
                        };

                        $reviewDecisionLabel = match ($review->decision) {
                            'approve' => match ($review->stage) {
                                'implementation_committee' => 'recommended the application',
                                'general_secretary' => 'forwarded the application',
                                default => 'approved the application',
                            },
                            'return' => 'returned the application for further work',
                            'revise' => 'requested revisions',
                            'reject' => 'rejected the application',
                            default => str_replace('_', ' ', $review->decision),
                        };

                        $reviewDescription = collect([
                            $review->decidedBy?->name,
                            $review->review_number,
                            $review->notes,
                            $review->recommended_amount !== null
                                ? 'Amount: PKR ' . number_format((float) $review->recommended_amount, 2)
                                : null,
                        ])->filter()->implode(' · ');

                        $activity->push([
                            'title' => $reviewStageLabel . ' ' . $reviewDecisionLabel,
                            'time' => $review->decided_at ?? $review->created_at,
                            'description' => $reviewDescription ?: null,
                        ]);
                    }

                    $activity = $activity
                        ->filter(fn ($event) => !empty($event['time']))
                        ->sortBy('time')
                        ->values();
                @endphp

                @foreach ($activity as $event)

                    <div class="timeline-item">

                        <div class="timeline-dot"></div>

                        <div>

                            <div class="timeline-title">
                                {{ $event['title'] }}
                            </div>

                            <div class="timeline-meta">
                                {{ optional($event['time'])->format('d M Y \a\t H:i') }}
                            </div>

                            @if (!empty($event['description']))

                                <div class="timeline-note">
                                    {{ $event['description'] }}
                                </div>

                            @endif

                        </div>

                    </div>

                @endforeach

            </div>

        </div>

    </div>


    <aside>

        <!-- SUMMARY -->

        <div class="card">

            <div class="card-body">

                <div class="side-stat">

                    <div class="side-number">
                        PKR {{ number_format($application->requested_amount) }}
                    </div>

                    <div class="side-label">
                        Requested Assistance
                    </div>

                </div>


                <div class="side-row">
                    <span>Stage</span>
                    <span>{{ $stageLabel }}</span>
                </div>

                <div class="side-row">
                    <span>Priority</span>
                    <span>{{ ucfirst($application->priority) }}</span>
                </div>

                <div class="side-row">
                    <span>Applicant</span>
                    <span>{{ optional($application->beneficiary)->full_name ?? '—' }}</span>
                </div>

                <div class="side-row">
                    <span>Fund</span>
                    <span>{{ optional($application->fund)->name ?? 'Not specified' }}</span>
                </div>

                <div class="side-row">
                    <span>Submitted</span>
                    <span>{{ optional($application->submitted_at)->format('d M Y') }}</span>
                </div>

            </div>

        </div>


        <!-- DECISION -->

        <div class="card">

            <div class="card-head">

                <div>
                    <strong>Current Decision</strong>

                    <span>
                        Available actions for authorized users.
                    </span>
                </div>

            </div>

            <div class="card-body">

                @if ($canImplementationDecision)
                    <form
                        class="decision-form"
                        method="POST"
                        action="{{ route('welfare.applications.implementation-decision', $application) }}"
                        onsubmit="return confirm('Submit the Implementation Committee decision?')"
                    >
                        @csrf

                        <p style="margin:0 0 12px;color:#6d7571;font-size:14px;line-height:1.6;">
                            Record the committee recommendation. A positive recommendation advances the application to the General Secretary.
                        </p>

                        <label for="implementation_recommended_amount">Recommended amount (PKR)</label>
                        <input
                            id="implementation_recommended_amount"
                            type="number"
                            name="recommended_amount"
                            min="0.01"
                            max="9999999999999.99"
                            step="0.01"
                            value="{{ old('recommended_amount', $application->recommended_amount ?? $application->requested_amount) }}"
                        >

                        <label for="implementation_notes">Committee notes / reason</label>
                        <textarea id="implementation_notes" name="notes" rows="4" maxlength="5000" required>{{ old('notes') }}</textarea>

                        <div class="decision-box">
                            <button type="submit" name="decision" value="approve" class="decision-btn approve">Recommend</button>
                            <button type="submit" name="decision" value="return" class="decision-btn">Return for field verification</button>
                            <button type="submit" name="decision" value="reject" class="decision-btn reject">Reject</button>
                        </div>
                    </form>

                @elseif ($canSecretaryReview)
                    <form
                        class="decision-form"
                        method="POST"
                        action="{{ route('welfare.applications.secretary-review', $application) }}"
                        onsubmit="return confirm('Submit the General Secretary review?')"
                    >
                        @csrf

                        <p style="margin:0 0 12px;color:#6d7571;font-size:14px;line-height:1.6;">
                            Review the Implementation Committee recommendation and forward it to the Decision-Making Committee, return it for reconsideration, or reject it with reasons.
                        </p>

                        <label for="secretary_notes">Review notes / reason</label>
                        <textarea id="secretary_notes" name="notes" rows="4" maxlength="5000" required>{{ old('notes') }}</textarea>

                        <div class="decision-box">
                            <button type="submit" name="decision" value="approve" class="decision-btn approve">Forward to Decision Committee</button>
                            <button type="submit" name="decision" value="return" class="decision-btn">Return for reconsideration</button>
                            <button type="submit" name="decision" value="reject" class="decision-btn reject">Reject</button>
                        </div>
                    </form>

                @elseif ($canCommitteeDecision)
                    <form
                        class="decision-form"
                        method="POST"
                        action="{{ route('welfare.applications.committee-decision', $application) }}"
                        onsubmit="return confirm('Submit the Decision-Making Committee decision?')"
                    >
                        @csrf

                        <p style="margin:0 0 12px;color:#6d7571;font-size:14px;line-height:1.6;">
                            Record the final committee decision. Approval makes the application eligible to proceed to agreement and authorized disbursement; it does not itself create a payment.
                        </p>

                        <label for="committee_approved_amount">Final approved amount (PKR)</label>
                        <input
                            id="committee_approved_amount"
                            type="number"
                            name="approved_amount"
                            min="0.01"
                            max="9999999999999.99"
                            step="0.01"
                            value="{{ old('approved_amount', $application->recommended_amount ?? $application->requested_amount) }}"
                        >

                        <label for="committee_notes">Decision notes / reason</label>
                        <textarea id="committee_notes" name="notes" rows="4" maxlength="5000" required>{{ old('notes') }}</textarea>

                        <div class="decision-box">
                            <button type="submit" name="decision" value="approve" class="decision-btn approve">Approve application</button>
                            <button type="submit" name="decision" value="return" class="decision-btn">Return to General Secretary</button>
                            <button type="submit" name="decision" value="reject" class="decision-btn reject">Reject</button>
                        </div>
                    </form>

                @else
                    <p style="margin:0;color:#6d7571;font-size:16px;line-height:1.6;">
                        @if ($application->status === 'approved')
                            Final approval is recorded. The next steps are the required agreement and authorized disbursement; no payment is created by this decision.
                        @elseif ($application->status === 'rejected')
                            This application is rejected. Its stage decisions and reasons remain in the activity history.
                        @else
                            No decision action is available to your account at this stage. The application is waiting for the responsible role.
                        @endif
                    </p>
                @endif

            </div>

        </div>


        <!-- SECURITY -->

        <div class="card">

            <div class="card-head">
                <strong>Confidentiality</strong>
            </div>

            <div class="card-body">

                <div class="security-note">

                    <i class="bi bi-shield-lock"></i>

                    <p>
                        Application and beneficiary information is restricted
                        to authorized welfare and committee personnel.
                    </p>

                </div>

            </div>

        </div>

    </aside>

</div>

@endsection