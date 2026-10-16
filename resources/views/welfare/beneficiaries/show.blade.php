@extends('layouts.app')

@section('title', 'Beneficiary Details')

@push('styles')
<style>
    .page-header {
        display:flex;
        justify-content:space-between;
        align-items:flex-start;
        gap:20px;
        margin-bottom:22px;
    }

    .identity {
        display:flex;
        align-items:center;
        gap:14px;
    }

    .avatar {
        width:62px;
        height:62px;
        border-radius:17px;
        background:var(--primary-soft);
        color:var(--primary);
        display:flex;
        align-items:center;
        justify-content:center;
        font-size: 18px;
        font-weight:700;
    }

    .name {
        font-size: 23px;
        font-weight:700;
        letter-spacing:-.4px;
    }

    .meta {
        margin-top:4px;
        color: #6e7773;
        font-size: 16px;
    }

    .actions {
        display:flex;
        gap:8px;
    }

    .btn-ui {
        height:39px;
        padding:0 13px;
        border-radius:9px;
        display:inline-flex;
        align-items:center;
        gap:7px;
        border:1px solid var(--border);
        background:#fff;
        color:#65726c;
        font-size: 16px;
        font-weight:650;
        text-decoration:none;
    }

    .btn-ui.primary {
        background:var(--primary);
        border-color:var(--primary);
        color:#fff;
    }

    .layout {
        display:grid;
        grid-template-columns:minmax(0,1fr) 300px;
        gap:18px;
    }

    .card {
        background:#fff;
        border:1px solid var(--border);
        border-radius:14px;
        overflow:hidden;
        margin-bottom:18px;
    }

    .card-head {
        padding:16px 19px;
        border-bottom:1px solid #eef1ef;
        display:flex;
        align-items:center;
        justify-content:space-between;
        gap:15px;
    }

    .card-head strong {
        font-size: 16px;
    }

    .card-head span {
        display:block;
        margin-top:4px;
        color: #6c7470;
        font-size: 16px;
    }

    .card-body {
        padding:18px 19px;
    }

    .grid {
        display:grid;
        grid-template-columns:repeat(2,minmax(0,1fr));
        gap:18px 24px;
    }

    .item label {
        display:block;
        margin-bottom:5px;
        color: #6d7571;
        font-size: 16px;
        text-transform:uppercase;
        letter-spacing:.5px;
    }

    .item strong {
        display:block;
        color:#3e4b45;
        font-size: 16px;
    }

    .category {
        display:inline-flex;
        padding:5px 8px;
        border-radius:20px;
        background:#fff4ed;
        color: #9e664d;
        font-size: 16px;
        font-weight:650;
    }

    .verified {
        display:inline-flex;
        align-items:center;
        gap:5px;
        padding:5px 8px;
        border-radius:20px;
        background:#edf7f0;
        color:#397955;
        font-size: 16px;
        font-weight:650;
    }

    .verified i {
        font-size: 16px;
    }

    .financial-summary {
        display:grid;
        grid-template-columns:repeat(3,minmax(0,1fr));
        gap:10px;
    }

    .financial-box {
        padding:13px;
        background:#fafcfb;
        border:1px solid #edf1ee;
        border-radius:10px;
    }

    .financial-box label {
        display:block;
        color: #6d7571;
        font-size: 16px;
        text-transform:uppercase;
        letter-spacing:.5px;
    }

    .financial-box strong {
        display:block;
        margin-top:6px;
        font-size: 17px;
    }

    .assistance-row {
        display:flex;
        align-items:center;
        gap:10px;
        padding:11px 0;
        border-bottom:1px solid #f0f3f1;
    }

    .assistance-row:last-child {
        border-bottom:none;
    }

    .assistance-icon {
        width:33px;
        height:33px;
        border-radius:9px;
        background:var(--primary-soft);
        color:var(--primary);
        display:flex;
        align-items:center;
        justify-content:center;
        flex-shrink:0;
    }

    .assistance-info {
        flex:1;
    }

    .assistance-info strong {
        display:block;
        font-size: 16px;
    }

    .assistance-info span {
        display:block;
        margin-top:3px;
        color: #6c7470;
        font-size: 16px;
    }

    .assistance-amount {
        font-size: 16px;
        font-weight:700;
    }

    .timeline-item {
        display:flex;
        gap:11px;
        padding-bottom:18px;
        position:relative;
    }

    .timeline-item:last-child {
        padding-bottom:0;
    }

    .timeline-dot {
        width:9px;
        height:9px;
        margin-top:4px;
        border-radius:50%;
        background:var(--primary);
        flex-shrink:0;
        position:relative;
    }

    .timeline-item:not(:last-child) .timeline-dot::after {
        content:"";
        position:absolute;
        width:1px;
        height:29px;
        left:4px;
        top:9px;
        background:#dae3df;
    }

    .timeline-title {
        font-size: 16px;
        font-weight:650;
    }

    .timeline-meta {
        margin-top:3px;
        color: #6c7470;
        font-size: 16px;
    }

    .document-row {
        display:flex;
        align-items:center;
        gap:10px;
        padding:11px 0;
        border-bottom:1px solid #f0f3f1;
    }

    .document-row:last-child {
        border-bottom:none;
    }

    .document-icon {
        width:32px;
        height:32px;
        border-radius:9px;
        background:#f4f7f5;
        color:var(--primary);
        display:flex;
        align-items:center;
        justify-content:center;
    }

    .document-info {
        flex:1;
    }

    .document-info strong {
        display:block;
        font-size: 16px;
    }

    .document-info span {
        display:block;
        margin-top:3px;
        color: #6c7470;
        font-size: 16px;
    }

    .side-stat {
        text-align:center;
        padding:8px 0 16px;
    }

    .side-number {
        font-size: 28px;
        font-weight:700;
        color:var(--primary);
    }

    .side-label {
        margin-top:3px;
        color: #6e7772;
        font-size: 16px;
    }

    .side-row {
        display:flex;
        justify-content:space-between;
        gap:10px;
        padding:9px 0;
        border-bottom:1px solid #f0f3f1;
        font-size: 16px;
    }

    .side-row:last-child {
        border-bottom:none;
    }

    .side-row span:first-child {
        color: #6d7571;
    }

    .side-row span:last-child {
        color:#4a5750;
        font-weight:650;
        text-align:right;
    }

    .privacy-note {
        display:flex;
        gap:9px;
        padding:12px;
        border-radius:10px;
        background:#f7f9f8;
        border:1px solid #e5ebe8;
    }

    .privacy-note i {
        color:var(--primary);
        font-size: 17px;
    }

    .privacy-note p {
        margin:0;
        color: #6b766f;
        font-size: 16px;
        line-height:1.6;
    }

    @media(max-width:900px) {
        .layout {
            grid-template-columns:1fr;
        }
    }

    @media(max-width:650px) {

        .page-header {
            flex-direction:column;
        }

        .actions {
            width:100%;
        }

        .actions .btn-ui {
            flex:1;
            justify-content:center;
        }

        .grid,
        .financial-summary {
            grid-template-columns:1fr;
        }
    }
</style>

    


@endpush

@section('content')

@php
    $categoryLabels = [
        'elderly_poor' => 'Elderly / Extremely Poor',
        'disabled_vulnerable' => 'Disabled / Vulnerable',
        'poor_employable' => 'Poor but Employable',
        'student' => 'Students / Education Support',
        'emergency' => 'Emergency Assistance',
    ];

    $maritalLabels = [
        'single' => 'Single',
        'married' => 'Married',
        'widowed' => 'Widowed',
        'divorced' => 'Divorced',
        'separated' => 'Separated',
    ];

    $categoryLabel = $categoryLabels[$beneficiary->category]
        ?? ucfirst(str_replace('_', ' ', $beneficiary->category));

    $maritalLabel = $maritalLabels[$beneficiary->marital_status]
        ?? ($beneficiary->marital_status
            ? ucfirst(str_replace('_', ' ', $beneficiary->marital_status))
            : '—');

    $verificationStatus = ucfirst($beneficiary->verification_status ?? 'pending');
    $approvalStatus = ucfirst($beneficiary->approval_status ?? 'pending');

    $verificationClass = match ($beneficiary->verification_status) {
        'verified' => 'status-approved',
        'rejected' => 'status-rejected',
        default => 'status-pending',
    };

    $approvalClass = match ($beneficiary->approval_status) {
        'approved' => 'status-approved',
        'rejected' => 'status-rejected',
        default => 'status-pending',
    };

    $nameParts = preg_split('/\s+/', trim($beneficiary->full_name));
    $initials = collect($nameParts)
        ->filter()
        ->take(2)
        ->map(fn ($part) => strtoupper(substr($part, 0, 1)))
        ->implode('');

    $maskedCnic = $beneficiary->cnic
        ? substr($beneficiary->cnic, 0, 5) . '-*******-' . substr($beneficiary->cnic, -1)
        : '—';

    $assistanceThisYear = $beneficiary->assistance
        ->filter(function ($assistance) {
            $date = $assistance->start_date ?? $assistance->created_at;
            return $date && $date->year === now()->year;
        })
        ->sum(fn ($assistance) => (float) $assistance->amount);

    $verifications = $beneficiary->verifications
        ->sortByDesc(fn ($verification) => $verification->verification_date ?? $verification->created_at);

    $hasWorkflowAction = false;
@endphp

<div class="page-header">

    <div class="identity">

        <div class="avatar">
            {{ $initials ?: 'B' }}
        </div>

        <div>

            <div class="name">
                {{ $beneficiary->full_name }}
            </div>

            <div class="meta">
                {{ $beneficiary->beneficiary_number }}
                @if($beneficiary->location)
                    · {{ $beneficiary->location }}
                @endif
                · {{ $categoryLabel }}
            </div>

        </div>

    </div>

    <div class="actions">

        <a
            href="{{ route('welfare.beneficiaries.index') }}"
            class="btn-ui"
        >
            <i class="bi bi-arrow-left"></i>
            Back
        </a>

        @can('welfare.beneficiaries.manage')
            <a
                href="{{ route('welfare.beneficiaries.edit', $beneficiary) }}"
                class="btn-ui primary"
            >
                <i class="bi bi-pencil"></i>
                Edit
            </a>
        @endcan

    </div>

</div>


<div class="layout">

    <div>

        <!-- PERSONAL INFORMATION -->

        <div class="card">

            <div class="card-head">

                <div>
                    <strong>Personal Information</strong>

                    <span>
                        Identity and contact details
                    </span>
                </div>

                <span class="status-badge {{ $verificationClass }}">
                    <i class="bi bi-patch-check"></i>
                    {{ $verificationStatus }}
                </span>

            </div>

            <div class="card-body">

                <div class="grid">

                    <div class="item">
                        <label>Full Name</label>
                        <strong>{{ $beneficiary->full_name }}</strong>
                    </div>

                    <div class="item">
                        <label>Father / Husband Name</label>
                        <strong>{{ $beneficiary->father_husband_name ?: '—' }}</strong>
                    </div>

                    <div class="item">
                        <label>CNIC</label>
                        <strong>{{ $maskedCnic }}</strong>
                    </div>

                    <div class="item">
                        <label>Phone</label>
                        <strong>{{ $beneficiary->phone ?: '—' }}</strong>
                    </div>

                    <div class="item">
                        <label>Alternate Phone</label>
                        <strong>{{ $beneficiary->alternate_phone ?: '—' }}</strong>
                    </div>

                    <div class="item">
                        <label>Location / Area</label>
                        <strong>{{ $beneficiary->location ?: '—' }}</strong>
                    </div>

                    <div class="item">
                        <label>Marital Status</label>
                        <strong>{{ $maritalLabel }}</strong>
                    </div>

                    <div class="item">
                        <label>Dependents</label>
                        <strong>{{ number_format($beneficiary->dependents_count) }}</strong>
                    </div>

                    <div class="item">
                        <label>Category</label>
                        <span class="category">
                            {{ $categoryLabel }}
                        </span>
                    </div>

                    <div class="item">
                        <label>Address</label>
                        <strong>{{ $beneficiary->address ?: '—' }}</strong>
                    </div>

                </div>

            </div>

        </div>


        <!-- FAMILY / FINANCIAL -->

        <div class="card">

            <div class="card-head">

                <div>
                    <strong>Family & Financial Condition</strong>

                    <span>
                        Current assessment information
                    </span>
                </div>

            </div>

            <div class="card-body">

                <div class="financial-summary">

                    <div class="financial-box">
                        <label>Occupation</label>
                        <strong>{{ $beneficiary->occupation ?: '—' }}</strong>
                    </div>

                    <div class="financial-box">
                        <label>Monthly Income</label>
                        <strong>
                            {{ $beneficiary->monthly_income !== null
                                ? 'PKR ' . number_format((float) $beneficiary->monthly_income, 2)
                                : '—'
                            }}
                        </strong>
                    </div>

                    <div class="financial-box">
                        <label>Dependents</label>
                        <strong>{{ number_format($beneficiary->dependents_count) }}</strong>
                    </div>

                </div>

                <div class="grid" style="margin-top:18px;">

                    <div class="item">
                        <label>Assets</label>
                        <strong style="font-weight:500;line-height:1.6;">
                            {{ $beneficiary->assets_description ?: 'No asset information recorded.' }}
                        </strong>
                    </div>

                    <div class="item">
                        <label>Debts / Liabilities</label>
                        <strong style="font-weight:500;line-height:1.6;">
                            {{ $beneficiary->debts_description ?: 'No debt information recorded.' }}
                        </strong>
                    </div>

                    <div class="item" style="grid-column:1/-1;">
                        <label>Household Condition</label>
                        <strong style="font-weight:500;line-height:1.6;">
                            {{ $beneficiary->household_condition ?: 'No household condition assessment recorded.' }}
                        </strong>
                    </div>

                </div>

            </div>

        </div>


        <!-- ASSISTANCE HISTORY -->

        <div class="card">

            <div class="card-head">

                <div>
                    <strong>Assistance History</strong>

                    <span>
                        Previous and current welfare assistance recorded for this beneficiary
                    </span>
                </div>

            </div>

            <div class="card-body">

                @forelse($beneficiary->assistance->sortByDesc('created_at') as $assistance)

                    @php
                        $assistanceDate = $assistance->start_date ?? $assistance->created_at;
                    @endphp

                    <div class="assistance-row">

                        <div class="assistance-icon">
                            <i class="bi bi-heart"></i>
                        </div>

                        <div class="assistance-info">

                            <strong>
                                {{ ucfirst(str_replace('_', ' ', $assistance->assistance_type)) }}
                            </strong>

                            <span>
                                {{ $assistanceDate?->format('d M Y') ?: 'Date not recorded' }}

                                @if($assistance->fund)
                                    · {{ $assistance->fund->name }}
                                @else
                                    · No fund assigned
                                @endif

                                · {{ ucfirst($assistance->status) }}
                            </span>

                        </div>

                        <div class="assistance-amount">
                            {{ $assistance->amount !== null
                                ? 'PKR ' . number_format((float) $assistance->amount, 2)
                                : '—'
                            }}
                        </div>

                    </div>

                @empty

                    <div style="padding:22px 0;text-align:center;color:#6c7470;">
                        <i class="bi bi-heart" style="font-size:28px;"></i>
                        <div style="margin-top:8px;font-weight:650;color:#46534d;">
                            No assistance recorded
                        </div>
                        <div style="margin-top:4px;">
                            This beneficiary has no welfare assistance records yet.
                        </div>
                    </div>

                @endforelse

            </div>

        </div>


        <!-- VERIFICATION -->

        <div class="card">

            <div class="card-head">

                <div>
                    <strong>Verification</strong>

                    <span>
                        Verification history and current review status
                    </span>
                </div>

                <span class="status-badge {{ $verificationClass }}">
                    {{ $verificationStatus }}
                </span>

            </div>

            <div class="card-body">

                @if($beneficiary->verification_status === 'pending')

                    <div class="privacy-note" style="margin-bottom:18px;">

                        <i class="bi bi-hourglass-split"></i>

                        <p>
                            This beneficiary is awaiting verification.
                            An authorized Welfare Officer can record the verification result below.
                        </p>

                    </div>

                @endif

                @forelse($verifications as $verification)

                    @php
                        $verificationDate = $verification->verification_date ?? $verification->created_at;
                        $verificationTitle = match ($verification->status) {
                            'verified' => 'Beneficiary verification completed',
                            'rejected' => 'Beneficiary verification rejected',
                            default => 'Verification record created',
                        };
                    @endphp

                    <div class="timeline-item">

                        <div class="timeline-dot"></div>

                        <div>

                            <div class="timeline-title">
                                {{ $verificationTitle }}
                            </div>

                            <div class="timeline-meta">
                                {{ $verificationDate?->format('d M Y') ?: 'Date not recorded' }}

                                @if($verification->verifiedBy)
                                    · {{ $verification->verifiedBy->name }}
                                @endif

                                @if($verification->method)
                                    · {{ ucfirst(str_replace('_', ' ', $verification->method)) }}
                                @endif
                            </div>

                            @if($verification->findings)
                                <div style="margin-top:7px;color:#5f6c65;line-height:1.6;">
                                    {{ $verification->findings }}
                                </div>
                            @endif

                            @if($verification->notes)
                                <div style="margin-top:5px;color:#6c7470;line-height:1.6;">
                                    {{ $verification->notes }}
                                </div>
                            @endif

                        </div>

                    </div>

                @empty

                    <div style="padding:18px 0;text-align:center;color:#6c7470;">
                        No verification history has been recorded yet.
                    </div>

                @endforelse

            </div>

        </div>


        <!-- APPROVAL -->

        <div class="card">

            <div class="card-head">

                <div>
                    <strong>Approval Workflow</strong>

                    <span>
                        Current approval status and available workflow actions
                    </span>
                </div>

                <span class="status-badge {{ $approvalClass }}">
                    {{ $approvalStatus }}
                </span>

            </div>

            <div class="card-body">

                <div class="grid">

                    <div class="item">
                        <label>Approval Status</label>
                        <strong>{{ $approvalStatus }}</strong>
                    </div>

                    <div class="item">
                        <label>Approved By</label>
                        <strong>{{ $beneficiary->approvedBy?->name ?: '—' }}</strong>
                    </div>

                    <div class="item">
                        <label>Approved At</label>
                        <strong>
                            {{ $beneficiary->approved_at?->format('d M Y H:i') ?: '—' }}
                        </strong>
                    </div>

                    <div class="item">
                        <label>Next Review</label>
                        <strong>
                            {{ $beneficiary->next_review_date?->format('d M Y') ?: '—' }}
                        </strong>
                    </div>

                </div>

                <div class="workflow-actions">

                    @can('welfare.beneficiaries.verify')

                        @if($beneficiary->verification_status === 'pending')

                            @php $hasWorkflowAction = true; @endphp

                            <form
                                method="POST"
                                action="{{ route('welfare.beneficiaries.verify', $beneficiary) }}"
                            >
                                @csrf

                                <button
                                    type="submit"
                                    class="btn-ui primary"
                                    onclick="return confirm('Verify this beneficiary?')"
                                >
                                    <i class="bi bi-check-circle"></i>
                                    Verify Beneficiary
                                </button>
                            </form>

                            <form
                                method="POST"
                                action="{{ route('welfare.beneficiaries.reject-verification', $beneficiary) }}"
                            >
                                @csrf

                                <button
                                    type="submit"
                                    class="btn-ui danger"
                                    onclick="return confirm('Reject verification for this beneficiary?')"
                                >
                                    <i class="bi bi-x-circle"></i>
                                    Reject Verification
                                </button>
                            </form>

                        @endif

                    @endcan


                    @can('welfare.beneficiaries.approve')

                        @if(
                            $beneficiary->verification_status === 'verified' &&
                            $beneficiary->approval_status === 'pending'
                        )

                            @php $hasWorkflowAction = true; @endphp

                            <form
                                method="POST"
                                action="{{ route('welfare.beneficiaries.approve', $beneficiary) }}"
                            >
                                @csrf

                                <button
                                    type="submit"
                                    class="btn-ui primary"
                                    onclick="return confirm('Approve this beneficiary?')"
                                >
                                    <i class="bi bi-check2-circle"></i>
                                    Approve Beneficiary
                                </button>
                            </form>

                            <form
                                method="POST"
                                action="{{ route('welfare.beneficiaries.reject-approval', $beneficiary) }}"
                            >
                                @csrf

                                <button
                                    type="submit"
                                    class="btn-ui danger"
                                    onclick="return confirm('Reject approval for this beneficiary?')"
                                >
                                    <i class="bi bi-x-circle"></i>
                                    Reject Approval
                                </button>
                            </form>

                        @endif

                    @endcan

                </div>

                @if(!$hasWorkflowAction)

                    <div style="margin-top:16px;color:#6c7470;">
                        No workflow action is currently available for this beneficiary.
                    </div>

                @endif

            </div>

        </div>


        <!-- NOTES -->

        @if($beneficiary->notes)

            <div class="card">

                <div class="card-head">

                    <div>
                        <strong>Notes</strong>

                        <span>
                            Additional beneficiary record notes
                        </span>
                    </div>

                </div>

                <div class="card-body">

                    <div style="color:#59665f;line-height:1.7;">
                        {{ $beneficiary->notes }}
                    </div>

                </div>

            </div>

        @endif


        <!-- DOCUMENTS -->

        <div class="card">

            <div class="card-head">

                <div>
                    <strong>Documents</strong>

                    <span>
                        Authorized supporting records
                    </span>
                </div>

            </div>

            <div class="card-body">

                <div class="privacy-note">

                    <i class="bi bi-folder2-open"></i>

                    <p>
                        No supporting documents are currently linked to this beneficiary record.
                        Document management will be connected here when the Welfare Documents workflow is implemented.
                    </p>

                </div>

            </div>

        </div>

    </div>


    <aside>

        <div class="card">

            <div class="card-body">

                <div class="side-stat">

                    <div class="side-number">
                        PKR {{ number_format((float) $assistanceThisYear, 2) }}
                    </div>

                    <div class="side-label">
                        Assistance Recorded This Year
                    </div>

                </div>

                <div class="side-row">
                    <span>Approval</span>
                    <span>{{ $approvalStatus }}</span>
                </div>

                <div class="side-row">
                    <span>Category</span>
                    <span>{{ $categoryLabel }}</span>
                </div>

                <div class="side-row">
                    <span>Verification</span>
                    <span>{{ $verificationStatus }}</span>
                </div>

                <div class="side-row">
                    <span>Next Review</span>
                    <span>
                        {{ $beneficiary->next_review_date?->format('d M Y') ?: '—' }}
                    </span>
                </div>

                <div class="side-row">
                    <span>Assigned Area</span>
                    <span>{{ $beneficiary->location ?: '—' }}</span>
                </div>

                <div class="side-row">
                    <span>Registered</span>
                    <span>
                        {{ $beneficiary->created_at?->format('d M Y') ?: '—' }}
                    </span>
                </div>

                @if($beneficiary->verifiedBy)

                    <div class="side-row">
                        <span>Verified By</span>
                        <span>{{ $beneficiary->verifiedBy->name }}</span>
                    </div>

                @endif

                @if($beneficiary->approvedBy)

                    <div class="side-row">
                        <span>Approved By</span>
                        <span>{{ $beneficiary->approvedBy->name }}</span>
                    </div>

                @endif

            </div>

        </div>


        <div class="card">

            <div class="card-head">
                <strong>Privacy & Access</strong>
            </div>

            <div class="card-body">

                <div class="privacy-note">

                    <i class="bi bi-shield-lock"></i>

                    <p>
                        This record contains sensitive beneficiary information.
                        Access should be limited to authorized welfare and committee personnel.
                    </p>

                </div>

            </div>

        </div>

    </aside>

</div>

@endsection
