@extends('layouts.app')

@section('title', 'Welfare Assistance Details')

@push('styles')
<link rel="stylesheet" href="{{ asset('css/views/welfare-assistance-show-1.css') }}">
@endpush

@section('content')

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
        'approved', 'paid', 'completed' => 'approved',
        'rejected' => 'rejected',
        'closed' => 'closed',
        default => 'pending',
    };
@endphp

<div class="page-header">

    <div>
        <h1>{{ $assistance->assistance_number }}</h1>

        <p>
            {{ $assistance->beneficiary?->full_name ?? 'Unknown beneficiary' }}
            · {{ $typeLabel }}
        </p>
    </div>

    <div class="actions">

    @can('welfare.reviews.view')
    @if(auth()->user()?->hasPermission('welfare.assistance.view') && auth()->user()?->hasPermission('welfare.reviews.view'))<a
        href="{{ route('welfare.assistance.reviews.index') }}"
        class="ui-btn"
    >
        <i class="bi bi-clipboard-check"></i>
        Review Queue
    </a>@endif
    @endcan

        @if(auth()->user()?->hasPermission('welfare.assistance.view'))<a
            href="{{ route('welfare.assistance.index') }}"
            class="ui-btn"
        >
            <i class="bi bi-arrow-left"></i>
            Back
        </a>@endif

        @can('welfare.assistance.manage')
            @if(in_array($assistance->status, ['pending', 'rejected'], true))
                @if(auth()->user()?->hasPermission('welfare.assistance.view') && auth()->user()?->hasPermission('welfare.assistance.manage'))<a
                    href="{{ route('welfare.assistance.edit', $assistance) }}"
                    class="ui-btn primary"
                >
                    <i class="bi bi-pencil"></i>
                    Edit
                </a>@endif
            @endif
        @endcan
        @can('welfare.assistance.manage')
            @if($assistance->status === 'approved')

                @if(auth()->user()?->hasPermission('welfare.assistance.view') && auth()->user()?->hasPermission('welfare.assistance.manage'))<a
                    href="{{ route('welfare.assistance.payments.create', $assistance) }}"
                    class="ui-btn primary"
                >
                    <i class="bi bi-wallet2"></i>
                    Record Payment
                </a>@endif

            @endif
        @endcan

        @if(
            $assistance->status === 'approved'
            && auth()->user()->hasPermission('welfare.reviews.manage')
        )

            @if(auth()->user()?->hasPermission('welfare.assistance.view') && auth()->user()?->hasPermission('welfare.reviews.view') && auth()->user()?->hasPermission('welfare.reviews.manage'))<a
                href="{{ route(
                    'welfare.assistance.reviews.create',
                    $assistance
                ) }}"
                class="ui-btn"
            >
                <i class="bi bi-clipboard-check"></i>
                Record Review
            </a>@endif

        @endif

        @can('welfare.assistance.approve')
            @if($assistance->status === 'pending')

                <form
                    method="POST"
                    action="{{ route('welfare.assistance.approve', $assistance) }}"
                >
                    @csrf

                    <button
                        type="submit"
                        class="ui-btn primary"
                        onclick="return confirm('Approve this assistance request?')"
                    >
                        <i class="bi bi-check-lg"></i>
                        Approve
                    </button>
                </form>

                <form
                    method="POST"
                    action="{{ route('welfare.assistance.reject', $assistance) }}"
                >
                    @csrf
                    <label for="reject-reason" class="form-label">Reason (optional)</label>
                    <input id="reject-reason" name="reason" class="form-control mb-2" maxlength="5000">


                    <button
                        type="submit"
                        class="ui-btn danger"
                        onclick="return confirm('Reject this assistance request?')"
                    >
                        <i class="bi bi-x-lg"></i>
                        Reject
                    </button>
                </form>

            @endif
        @endcan

    </div>

</div>

@if($errors->any())
    <div class="alert alert-danger"><ul class="mb-0">@foreach($errors->all() as $error)<li>{{ $error }}</li>@endforeach</ul></div>
@endif

<div class="card mb-3"><div class="card-body">
    @if($assistance->application)
        <strong>Source application:</strong>
        @if(auth()->user()?->hasPermission('welfare.assistance.view'))<a href="{{ route('welfare.applications.show', $assistance->application) }}">{{ $assistance->application->application_number }}</a>@endif
        @can('welfare.agreements.view')
            @if($assistance->application->agreement)
                · @if(auth()->user()?->hasPermission('welfare.agreements.view'))<a href="{{ route('welfare.applications.agreement.show', $assistance->application) }}">Signed agreement {{ $assistance->application->agreement->agreement_number }}</a>@endif
            @endif
        @endcan
    @elseif($assistance->application_id)
        The source application is archived (reference #{{ $assistance->application_id }}).
    @else
        Legacy assistance: no linked application was recorded.
    @endif
    @can('welfare.assistance.manage')
        @if($assistance->status === 'rejected')
            <form method="POST" action="{{ route('welfare.assistance.resubmit', $assistance) }}" class="mt-3">
                @csrf
                <label for="resubmit-reason" class="form-label">Resubmission note (optional)</label>
                <input id="resubmit-reason" name="reason" class="form-control mb-2" maxlength="5000">
                <button class="ui-btn primary" type="submit">Resubmit for approval</button>
            </form>
        @endif
    @endcan
</div></div>

<div class="card mb-3"><div class="card-body">
    <h2 class="h5">Status history</h2>
    <p>Approval authorizes payments up to the total cap. Full payment completes assistance; an approver may explicitly close approved or completed assistance with a reason.</p>
    <ul class="mb-0">
        @forelse($assistance->statusChanges as $change)
            <li>{{ $change->from_status ? ucfirst($change->from_status) : 'Created' }} → {{ ucfirst($change->to_status) }}
                by {{ $change->changedBy?->name ?? 'Unknown user' }} on {{ $change->changed_at->format('d M Y H:i') }}
                @if($change->reason)<div>{{ $change->reason }}</div>@endif
            </li>
        @empty
            <li>No status history was recorded before this workflow was introduced.</li>
        @endforelse
    </ul>
</div></div>

@if(session('success'))
    <div class="alert alert-success">
        {{ session('success') }}
    </div>
@endif

@can('welfare.reviews.view')
@include('welfare.assistance.partials.monitoring')
@endcan

<div class="layout">

    <div>

        <div class="card">

            <div class="card-head">
                <strong>Assistance Information</strong>

                <span>
                    Request details and approval status.
                </span>
            </div>

            <div class="card-body">

                <div class="grid">

                    <div class="item">
                        <label>Assistance Number</label>
                        <strong>{{ $assistance->assistance_number }}</strong>
                    </div>

                    <div class="item">
                        <label>Status</label>

                        <span class="status {{ $statusClass }}">
                            {{ ucfirst($assistance->status) }}
                        </span>
                    </div>

                    <div class="item">
                        <label>Beneficiary</label>
                        <strong>
                            {{ $assistance->beneficiary?->full_name ?? '—' }}
                        </strong>
                    </div>

                    <div class="item">
                        <label>Beneficiary Number</label>
                        <strong>
                            {{ $assistance->beneficiary?->beneficiary_number ?? '—' }}
                        </strong>
                    </div>

                    <div class="item">
                        <label>Assistance Type</label>
                        <strong>{{ $typeLabel }}</strong>
                    </div>

                    <div class="item">
                        <label>Amount</label>
                        <strong>
                            PKR {{ \App\Domain\Finance\Support\Money::format($assistance->amount) }}
                        </strong>
                    </div>

                    <div class="item">
                        <label>Frequency</label>
                        <strong>
                            {{ $assistance->frequency
                                ? ucfirst(str_replace('_', ' ', $assistance->frequency))
                                : '—'
                            }}
                        </strong>
                    </div>

                    <div class="item">
                        <label>Fund</label>
                        <strong>
                            {{ $assistance->fund?->name ?? 'Unassigned' }}
                        </strong>
                    </div>

                    <div class="item">
                        <label>Start Date</label>
                        <strong>
                            {{ $assistance->start_date?->format('d M Y') ?? '—' }}
                        </strong>
                    </div>

                    <div class="item">
                        <label>End Date</label>
                        <strong>
                            {{ $assistance->end_date?->format('d M Y') ?? '—' }}
                        </strong>
                    </div>

                    <div class="item">
                        <label>Next Review</label>
                        <strong>
                            {{ $assistance->next_review_date?->format('d M Y') ?? '—' }}
                        </strong>
                    </div>

                    <div class="item">
                        <label>Requested At</label>
                        <strong>
                            {{ $assistance->requested_at?->format('d M Y H:i') ?? '—' }}
                        </strong>
                    </div>

                </div>

            </div>

        </div>


        <div class="card">

            <div class="card-head">
                <strong>Purpose & Notes</strong>
            </div>

            <div class="card-body">

                <div class="item">
                    <label>Purpose Notes</label>

                    <strong style="line-height:1.7;">
                        {{ $assistance->purpose_notes ?: 'No purpose notes recorded.' }}
                    </strong>
                </div>

                <div class="item mt-4">
                    <label>Internal Notes</label>

                    <strong style="line-height:1.7;">
                        {{ $assistance->internal_notes ?: 'No internal notes recorded.' }}
                    </strong>
                </div>

                <div class="item mt-4">
                    <label>General Notes</label>

                    <strong style="line-height:1.7;">
                        {{ $assistance->notes ?: 'No additional notes recorded.' }}
                    </strong>
                </div>

            </div>

        </div>


        <div class="card">

            <div class="card-head">

                <div>
                    <strong>
                        Payment History
                    </strong>

                    <span>
                        Financial payments recorded against this assistance.
                    </span>
                </div>

            </div>


            <div class="card-body">

                @forelse($assistance->payments as $payment)

                    <div class="assistance-row">

                        <div class="assistance-icon">
                            <i class="bi bi-wallet2"></i>
                        </div>


                        <div class="assistance-info">

                            <strong>
                                {{ $payment->payment_number }}
                            </strong>

                            <span>
                                {{ $payment->payment_date->format('d M Y') }}

                                ·

                                {{ ucfirst(
                                    str_replace(
                                        '_',
                                        ' ',
                                        $payment->payment_method
                                    )
                                ) }}

                                ·

                                {{ $payment->fund?->name ?: 'No fund' }}
                            </span>

                        </div>


                        <div class="assistance-amount">

                            @if($payment->status === 'void')

                                <span style="
                                    color:var(--legacy-text-a34f4f, #a34f4f);
                                    text-decoration:line-through;
                                ">
                                    PKR {{ \App\Domain\Finance\Support\Money::format($payment->amount) }}
                                </span>

                                <span style="
                                    display:block;
                                    margin-top:3px;
                                    color:var(--legacy-text-a34f4f, #a34f4f);
                                    font-size:12px;
                                    font-weight:650;
                                ">
                                    Voided
                                </span>

                            @else

                                PKR {{ \App\Domain\Finance\Support\Money::format($payment->amount) }}

                            @endif


                            @if(auth()->user()?->hasPermission('welfare.assistance.view'))<a
                                href="{{ route(
                                    'welfare.assistance.payments.show',
                                    [
                                        'assistance' => $assistance,
                                        'payment' => $payment,
                                    ]
                                ) }}"
                                style="
                                    display:block;
                                    margin-top:4px;
                                    color:var(--primary);
                                    font-size:12px;
                                    text-decoration:none;
                                "
                            >
                                View Details
                                <i class="bi bi-arrow-right"></i>
                            </a>@endif

                        </div>

                    </div>

                @empty

                    <div class="notice">

                        <i class="bi bi-wallet2"></i>

                        <p>
                            No payments have been recorded for this assistance yet.
                        </p>

                    </div>

                @endforelse

            </div>

        </div>

        @can('welfare.reviews.view')
        <div class="card">

            <div class="card-head">

                <div>

                    <strong>
                        Review History
                    </strong>

                    <span>
                        Previous reviews, outcomes and scheduled follow-up dates.
                    </span>

                </div>


                @if(
                    $assistance->status === 'approved'
                    && auth()->user()->hasPermission('welfare.reviews.manage')
                )

                    @if(auth()->user()?->hasPermission('welfare.assistance.view') && auth()->user()?->hasPermission('welfare.reviews.view') && auth()->user()?->hasPermission('welfare.reviews.manage'))<a
                        href="{{ route(
                            'welfare.assistance.reviews.create',
                            $assistance
                        ) }}"
                        class="ui-btn"
                    >
                        <i class="bi bi-plus-lg"></i>
                        Record Review
                    </a>@endif

                @endif

            </div>


            <div class="card-body">

                @forelse(
                    $assistance->reviews
                        ->sortByDesc('id')
                    as $review
                )

                    <div style="
                        display:flex;
                        gap:12px;
                        padding:12px 0;
                        border-bottom:1px solid var(--legacy-border-eef1ef, #eef1ef);
                    ">

                        <div style="
                            width:32px;
                            height:32px;
                            border-radius:9px;
                            background:var(--primary-soft);
                            color:var(--primary);
                            display:flex;
                            align-items:center;
                            justify-content:center;
                            flex-shrink:0;
                        ">
                            <i class="bi bi-clipboard-check"></i>
                        </div>


                        <div style="flex:1; min-width:0;">

                            <strong style="
                                display:block;
                                font-size:14px;
                            ">
                                {{ ucfirst(
                                    str_replace(
                                        '_',
                                        ' ',
                                        $review->outcome
                                    )
                                ) }}
                            </strong>


                            <span style="
                                display:block;
                                margin-top:3px;
                                color:var(--legacy-text-6c7570, #6c7570);
                                font-size:12px;
                            ">
                                {{ $review->review_date->format('d M Y') }}

                                ·

                                {{ $review->reviewedBy?->name ?: 'System' }}
                                · Recorded {{ $review->created_at?->format('d M Y H:i') }}
                            </span>


                            @if($review->next_review_date)

                                <span style="
                                    display:block;
                                    margin-top:5px;
                                    color:var(--primary);
                                    font-size:12px;
                                    font-weight:650;
                                ">
                                    Next review:
                                    {{ $review->next_review_date->format('d M Y') }}
                                </span>

                            @endif


                            @if($review->notes)

                                <p style="
                                    margin:7px 0 0;
                                    color:var(--legacy-text-65706a, #65706a);
                                    font-size:13px;
                                    line-height:1.55;
                                ">
                                    {{ $review->notes }}
                                </p>

                            @endif

                            @if($review->follow_up_action)
                                <p class="mt-2"><strong>Follow-up action:</strong> {{ $review->follow_up_action }}</p>
                            @endif

                        </div>

                    </div>

                @empty

                    <div class="notice">

                        <i class="bi bi-clipboard"></i>

                        <p>
                            No review history has been recorded for this assistance yet.
                        </p>

                    </div>

                @endforelse

            </div>

        </div>
        @endcan

        

    </div>


    <aside>

        <div class="card">

            <div class="card-body">

                <div class="side-number">
                    PKR {{ \App\Domain\Finance\Support\Money::format($assistance->amount) }}
                </div>

                <div class="side-label">
                    Assistance Amount
                </div>

                <div style="
                    height:1px;
                    background:var(--legacy-surface-eef1ef, #eef1ef);
                    margin:17px 0;
                "></div>

                <div class="side-row">
                    <span>Status</span>

                    <span>
                        {{ ucfirst($assistance->status) }}
                    </span>
                </div>

                <div class="side-row">
                    <span>Beneficiary</span>

                    <span>
                        {{ $assistance->beneficiary?->full_name ?? '—' }}
                    </span>
                </div>

                <div class="side-row">
                    <span>Fund</span>

                    <span>
                        {{ $assistance->fund?->name ?? 'Unassigned' }}
                    </span>
                </div>

                <div class="side-row">
                    <span>Approved By</span>

                    <span>
                        {{ $assistance->approvedBy?->name ?? 'Not approved' }}
                    </span>
                </div>

                <div class="side-row">
                    <span>Approved At</span>

                    <span>
                        {{ $assistance->approved_at?->format('d M Y H:i') ?? '—' }}
                    </span>
                </div>

            </div>

        </div>


        <div class="card">

            <div class="card-head">
                <strong>Finance Control</strong>
            </div>

            <div class="card-body">

                <div class="notice">
                    <i class="bi bi-shield-check"></i>

                    <div>
                        This assistance request is separate from financial
                        disbursement. A Finance ledger entry will be created
                        only when an actual payment is recorded.
                    </div>
                </div>

            </div>

        </div>

    </aside>

</div>

@endsection
