@extends('layouts.app')

@section('title', 'Application Details')

@push('styles')
<link rel="stylesheet" href="{{ asset('css/views/welfare-applications-show-1.css') }}">
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

@php
    $linkedAssistance = $application->assistanceIncludingArchived;
@endphp
@if($linkedAssistance)
    <div class="alert alert-info">
        @if($linkedAssistance->trashed())
            Assistance {{ $linkedAssistance->assistance_number }} is archived. Another assistance cannot be created for this application.
        @else
            Related assistance: @if(auth()->user()?->hasPermission('welfare.assistance.view'))<a href="{{ route('welfare.assistance.show', $linkedAssistance) }}">{{ $linkedAssistance->assistance_number }} · {{ ucfirst($linkedAssistance->status) }}</a>@endif
        @endif
    </div>
@endif


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

        @if(auth()->user()?->hasPermission('welfare.assistance.view'))<a
            href="{{ route('welfare.applications.index') }}"
            class="ui-btn"
        >
            <i class="bi bi-arrow-left"></i>
            Back
        </a>@endif


    </div>

</div>

@if (session('success'))
    <div style="margin-bottom:16px;padding:12px 16px;background:var(--legacy-surface-edf7f0, #edf7f0);border:1px solid var(--legacy-border-cfe6d8, #cfe6d8);border-radius:10px;color:var(--legacy-text-397955, #397955);font-size:16px;">
        {{ session('success') }}
    </div>
@endif

@if ($errors->any())
    <div style="margin-bottom:16px;padding:12px 16px;background:var(--legacy-surface-fff0ef, #fff0ef);border:1px solid var(--legacy-border-f0d3d1, #f0d3d1);border-radius:10px;color:var(--legacy-text-a85f58, #a85f58);font-size:16px;">
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
                        <strong>PKR {{ \App\Domain\Finance\Support\Money::format($application->requested_amount) }}</strong>
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
                                            PKR {{ \App\Domain\Finance\Support\Money::format($application->agreement->approved_amount) }}
                                        </strong>
                                    </div>

                                </div>

                                <p style="margin:16px 0 0;color:var(--legacy-text-6d7571, #6d7571);">
                                    This agreement has not necessarily been signed. Check its signature
                                    status on the agreement details page.
                                </p>

                                <div style="margin-top:16px;">
                                    @if(auth()->user()?->hasPermission('welfare.agreements.view'))<a
                                        href="{{ route('welfare.applications.agreement.show', $application) }}"
                                        class="ui-btn primary"
                                    >
                                        View Agreement
                                    </a>@endif
                                </div>

                            @elseif ($application->isTestApplication())
                                <p style="margin:0;color:var(--legacy-text-6d7571, #6d7571);">
                                    This is a reserved test-only application. Agreement creation is disabled.
                                </p>

                            @elseif ((int) $application->submitted_by === (int) auth()->id())
                                <p style="margin:0;color:var(--legacy-text-6d7571, #6d7571);">
                                    The application submitter cannot prepare its agreement.
                                </p>

                            @else
                                <p style="margin:0 0 16px;color:var(--legacy-text-6d7571, #6d7571);">
                                    Final approval is recorded. No agreement has been created yet.
                                </p>

                                @if (auth()->user()?->hasPermission('welfare.agreements.manage'))
                                    @if(auth()->user()?->hasPermission('welfare.agreements.manage'))<a
                                        href="{{ route('welfare.applications.agreement.create', $application) }}"
                                        class="ui-btn primary"
                                    >
                                        Prepare Draft Agreement
                                    </a>@endif
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
                    <p style="margin-top:14px;color:var(--legacy-text-a85f58, #a85f58);font-size:16px;">
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
                        @if(auth()->user()?->hasPermission('welfare.view'))<a
                            href="{{ route('welfare.beneficiaries.show', $application->beneficiary) }}"
                            class="ui-btn"
                        >
                            View Beneficiary
                        </a>@endif
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
                            PKR {{ \App\Domain\Finance\Support\Money::format($application->requested_amount) }}
                        </strong>

                    </div>


                    <div class="financial-box">

                        <label>Applicant Contribution</label>

                        <strong>
                            PKR {{ \App\Domain\Finance\Support\Money::format($application->applicant_contribution) }}
                        </strong>

                    </div>


                    <div class="financial-box">

                        <label>Organization Contribution</label>

                        <strong>
                            PKR {{ \App\Domain\Finance\Support\Money::format($application->organization_contribution) }}
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
                            background:var(--legacy-surface-f7f9f8, #f7f9f8);
                            border:1px solid var(--legacy-border-e5ebe8, #e5ebe8);
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
                                    color:var(--legacy-text-6b7670, #6b7670);
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

                            @if(auth()->user()?->hasPermission('welfare.beneficiaries.verify'))<a
                                href="{{ route('welfare.verification.create', [
                                    'application_id' => $application->id
                                ]) }}"
                                class="ui-btn"
                            >
                                <i class="bi bi-clipboard-plus"></i>
                                Assign Field Verification
                            </a>@endif

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
                                    color:var(--legacy-text-6d7571, #6d7571);
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
                                    color:var(--legacy-text-6d7571, #6d7571);
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
                                            border:1px solid var(--legacy-border-edf1ee, #edf1ee);
                                            border-radius:9px;
                                            background:var(--legacy-surface-fafcfb, #fafcfb);
                                            color:var(--legacy-text-59655f, #59655f);
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
                                background:var(--legacy-surface-f7f9f8, #f7f9f8);
                                border:1px solid var(--legacy-border-e5ebe8, #e5ebe8);
                                border-radius:10px;
                            "
                        >

                            <div
                                style="
                                    color:var(--legacy-text-6d7571, #6d7571);
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
                                    color:var(--legacy-text-46534c, #46534c);
                                "
                            >
                                {{ ucfirst($verification->recommendation) }}
                            </div>


                            @if ($verification->recommendation_notes)

                                <div
                                    style="
                                        margin-top:7px;
                                        color:var(--legacy-text-69766f, #69766f);
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

                        @if(auth()->user()?->hasPermission('welfare.view'))<a
                            href="{{ route(
                                'welfare.verification.show',
                                $verification
                            ) }}"
                            class="ui-btn"
                        >
                            <i class="bi bi-clipboard-check"></i>
                            View Field Verification
                        </a>@endif

                        @if (
                            $application->status === 'submitted'
                            && auth()->user()?->hasPermission('welfare.beneficiaries.verify')
                        )
                            @if(auth()->user()?->hasPermission('welfare.beneficiaries.verify'))<a
                                href="{{ route('welfare.verification.create', ['application_id' => $application->id]) }}"
                                class="ui-btn"
                            >
                                <i class="bi bi-arrow-repeat"></i>
                                Assign New Verification
                            </a>@endif
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

                    <p style="margin:0;color:var(--legacy-text-6d7571, #6d7571);font-size:16px;">
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
                                ? 'Amount: PKR ' . \App\Domain\Finance\Support\Money::format($review->recommended_amount)
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
                        PKR {{ \App\Domain\Finance\Support\Money::format($application->requested_amount) }}
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

                        <p style="margin:0 0 12px;color:var(--legacy-text-6d7571, #6d7571);font-size:14px;line-height:1.6;">
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

                        <p style="margin:0 0 12px;color:var(--legacy-text-6d7571, #6d7571);font-size:14px;line-height:1.6;">
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

                        <p style="margin:0 0 12px;color:var(--legacy-text-6d7571, #6d7571);font-size:14px;line-height:1.6;">
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
                    <p style="margin:0;color:var(--legacy-text-6d7571, #6d7571);font-size:16px;line-height:1.6;">
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
