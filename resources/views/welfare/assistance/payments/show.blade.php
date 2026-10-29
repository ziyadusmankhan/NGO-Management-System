@extends('layouts.app')

@section('title', $payment->payment_number)

@push('styles')
<link rel="stylesheet" href="{{ asset('css/views/welfare-assistance-payments-show-1.css') }}">
@endpush


@section('content')

@php

    $paymentMethodLabel = match ($payment->payment_method) {
        'cash' => 'Cash',
        'bank_transfer' => 'Bank Transfer',
        'mobile_wallet' => 'Mobile Wallet',
        'cheque' => 'Cheque',
        default => ucfirst(
            str_replace('_', ' ', $payment->payment_method)
        ),
    };

    $assistanceTypeLabel = match ($assistance->assistance_type) {
        'monthly_assistance' => 'Monthly Assistance',
        'food_ration' => 'Food / Ration',
        'medical_assistance' => 'Medical Assistance',
        'education_support' => 'Education Support',
        'emergency_assistance' => 'Emergency Assistance',
        'one_time_support' => 'One-Time Support',
        default => ucfirst(
            str_replace('_', ' ', $assistance->assistance_type)
        ),
    };

    $statusClass = match ($payment->status) {
        'posted' => 'badge-posted',
        'pending' => 'badge-pending',
        'reversed' => 'badge-reversed',
        'void' => 'badge-void',
        default => 'badge-pending',
    };

@endphp


<div class="page-header">

    <div class="identity">

        <div class="icon-box">
            <i class="bi bi-wallet2"></i>
        </div>

        <div>

            <div class="title">
                {{ $payment->payment_number }}
            </div>

            <div class="meta">
                {{ $assistance->beneficiary->full_name }}
                ·
                {{ $assistance->assistance_number }}
                ·
                {{ $payment->payment_date->format('d M Y') }}
            </div>

        </div>

    </div>


    <div class="actions">

        @if(
            $payment->status === 'posted'
            && auth()->user()->hasPermission('welfare.assistance.manage')
        )

            <form
                method="POST"
                action="{{ route(
                    'welfare.assistance.payments.void',
                    [
                        'assistance' => $assistance,
                        'payment' => $payment,
                    ]
                ) }}"
                onsubmit="return confirm(
                    'Void {{ $payment->payment_number }}? This will also void the linked finance transaction and restore the amount to the fund.'
                );"
                style="display:inline;"
            >

                @csrf

                <div style="display:flex; gap:8px; align-items:center;">

                    <input
                        type="text"
                        name="void_reason"
                        value="{{ old('void_reason') }}"
                        maxlength="2000"
                        placeholder="Reason for voiding"
                        required
                        minlength="5"
                        style="
                            height:40px;
                            width:220px;
                            padding:0 11px;
                            border:1px solid var(--border);
                            border-radius:9px;
                            background:var(--legacy-surface-fff, #fff);
                            font-size:13px;
                            outline:none;
                        "
                    >

                    <button
                        type="submit"
                        class="ui-btn"
                        style="
                            color:var(--legacy-text-a34f4f, #a34f4f);
                            border-color:var(--legacy-border-e5cfcf, #e5cfcf);
                            background:var(--legacy-surface-fffafa, #fffafa);
                        "
                    >
                        <i class="bi bi-x-circle"></i>
                        Void Payment
                    </button>

                </div>

            </form>

        @endif


        @if(auth()->user()?->hasPermission('welfare.assistance.view'))<a
            href="{{ route('welfare.assistance.show', $assistance) }}"
            class="ui-btn"
        >
            <i class="bi bi-arrow-left"></i>
            Back to Assistance
        </a>@endif

    </div>

</div>


@if(session('success'))
    <div class="alert alert-success" role="status">{{ session('success') }}</div>
@endif
@if($errors->any())
    <div class="alert alert-danger" role="alert">
        <strong>The payment could not be updated.</strong>
        <ul class="mb-0">
            @foreach($errors->all() as $error)
                <li>{{ $error }}</li>
            @endforeach
        </ul>
    </div>
@endif

<div class="layout">

    <main>


        <!-- PAYMENT SUMMARY -->

        <div class="card">

            <div class="card-head">

                <strong>
                    Payment Summary
                </strong>

                <span>
                    Financial event recorded against the approved welfare assistance.
                </span>

            </div>


            <div class="card-body">

                <div class="amount-box">

                    <div class="amount-label">
                        Payment Amount
                    </div>

                    <div class="amount-value">
                        PKR {{ \App\Domain\Finance\Support\Money::format($payment->amount) }}
                    </div>

                </div>


                <div class="info-grid">


                    <div class="info-item">

                        <label>
                            Payment Number
                        </label>

                        <strong>
                            {{ $payment->payment_number }}
                        </strong>

                    </div>


                    <div class="info-item">

                        <label>
                            Status
                        </label>

                        <strong>

                            <span class="badge {{ $statusClass }}">
                                {{ ucfirst($payment->status) }}
                            </span>

                        </strong>

                    </div>
                    @if($payment->status === 'void')

                        <div class="info-item">

                            <label>
                                Voided By
                            </label>

                            <strong>
                                {{ $payment->voidedBy?->name ?: '—' }}
                            </strong>

                        </div>


                        <div class="info-item">

                            <label>
                                Voided At
                            </label>

                            <strong>
                                {{ $payment->voided_at
                                    ? $payment->voided_at->format('d M Y, h:i A')
                                    : '—'
                                }}
                            </strong>

                        </div>


                        <div class="info-item">

                            <label>
                                Void Reason
                            </label>

                            <strong>
                                {{ $payment->void_reason ?: '—' }}
                            </strong>

                        </div>

                    @endif


                    <div class="info-item">

                        <label>
                            Payment Date
                        </label>

                        <strong>
                            {{ $payment->payment_date->format('d M Y') }}
                        </strong>

                    </div>


                    <div class="info-item">

                        <label>
                            Payment Method
                        </label>

                        <strong>
                            {{ $paymentMethodLabel }}
                        </strong>

                    </div>


                    <div class="info-item">

                        <label>
                            Paid To
                        </label>

                        <strong>
                            {{ $payment->paid_to ?: '—' }}
                        </strong>

                    </div>


                    <div class="info-item">

                        <label>
                            Receipt Number
                        </label>

                        <strong>
                            {{ $payment->receipt_number ?: '—' }}
                        </strong>

                    </div>


                    <div class="info-item">

                        <label>
                            Transaction Reference
                        </label>

                        <strong>
                            {{ $payment->transaction_reference ?: '—' }}
                        </strong>

                    </div>


                    <div class="info-item">

                        <label>
                            Recorded By
                        </label>

                        <strong>
                            {{ $payment->recordedBy?->name ?: '—' }}
                        </strong>

                    </div>


                    <div class="info-item">

                        <label>
                            Created
                        </label>

                        <strong>
                            {{ $payment->created_at->format('d M Y, h:i A') }}
                        </strong>

                    </div>


                    <div class="info-item">

                        <label>
                            Last Updated
                        </label>

                        <strong>
                            {{ $payment->updated_at->format('d M Y, h:i A') }}
                        </strong>

                    </div>

                </div>

            </div>

        </div>


        <!-- ASSISTANCE -->

        <div class="card">

            <div class="card-head">

                <strong>
                    Welfare Assistance
                </strong>

                <span>
                    The approved assistance record from which this payment originated.
                </span>

            </div>


            <div class="card-body">

                <div class="link-card">

                    <div class="link-info">

                        <strong>
                            {{ $assistance->assistance_number }}
                        </strong>

                        <span>
                            {{ $assistanceTypeLabel }}
                            ·
                            PKR {{ \App\Domain\Finance\Support\Money::format($assistance->amount) }}
                            ·
                            {{ $assistance->beneficiary->full_name }}
                        </span>

                    </div>


                    @if(auth()->user()?->hasPermission('welfare.assistance.view'))<a
                        href="{{ route('welfare.assistance.show', $assistance) }}"
                        class="detail-link"
                    >
                        View Assistance
                        <i class="bi bi-arrow-right"></i>
                    </a>@endif

                </div>

            </div>

        </div>


        <!-- FUND -->

        <div class="card">

            <div class="card-head">

                <strong>
                    Fund Allocation
                </strong>

                <span>
                    Fund from which this payment was financially posted.
                </span>

            </div>


            <div class="card-body">

                <div class="info-grid">

                    <div class="info-item">

                        <label>
                            Fund
                        </label>

                        <strong>
                            {{ $payment->fund?->name ?: '—' }}
                        </strong>

                    </div>


                    <div class="info-item">

                        <label>
                            Fund Reference
                        </label>

                        <strong>
                            {{ $payment->fund?->fund_reference ?: '—' }}
                        </strong>

                    </div>


                    <div class="info-item">

                        <label>
                            Fund Type
                        </label>

                        <strong>
                            {{ $payment->fund?->type
                                ? ucfirst($payment->fund->type)
                                : '—'
                            }}
                        </strong>

                    </div>


                    <div class="info-item">

                        <label>
                            Payment Status
                        </label>

                        <strong>
                            {{ ucfirst($payment->status) }}
                        </strong>

                    </div>

                </div>

            </div>

        </div>


        <!-- FINANCE TRANSACTION -->

        <div class="card">

            <div class="card-head">

                <strong>
                    Finance Ledger Link
                </strong>

                <span>
                    The corresponding financial transaction created for this payment.
                </span>

            </div>


            <div class="card-body">

                @if($payment->transaction)

                    <div class="link-card">

                        <div class="link-info">

                            <strong>
                                {{ $payment->transaction->transaction_number }}
                            </strong>

                            <span>
                                {{ $payment->transaction->reference }}
                                ·
                                {{ ucfirst($payment->transaction->direction) }}
                                ·
                                PKR {{ \App\Domain\Finance\Support\Money::format($payment->transaction->amount) }}
                            </span>

                        </div>


                        @if(auth()->user()?->hasPermission('finance.view'))<a
                            href="{{ route(
                                'finance.transactions.show',
                                $payment->transaction
                            ) }}"
                            class="detail-link"
                        >
                            View Transaction
                            <i class="bi bi-arrow-right"></i>
                        </a>@endif

                    </div>

                @else

                    <div class="notice">

                        <i class="bi bi-exclamation-circle"></i>

                        <p>
                            No finance transaction is currently linked to this payment.
                            This should normally only occur for a non-posted or legacy
                            payment record.
                        </p>

                    </div>

                @endif

            </div>

        </div>


        <!-- NOTES -->

        <div class="card">

            <div class="card-head">

                <strong>
                    Payment Notes
                </strong>

                <span>
                    Additional information recorded with this financial event.
                </span>

            </div>


            <div class="card-body">

                <div class="notice">

                    <i class="bi bi-journal-text"></i>

                    <p>
                        {{ $payment->notes ?: 'No additional notes have been recorded.' }}
                    </p>

                </div>

            </div>

        </div>

    </main>


    <!-- SIDEBAR -->

    <aside>


        <div class="card">

            <div class="card-body">

                <div class="side-number">
                    PKR {{ \App\Domain\Finance\Support\Money::format($payment->amount) }}
                </div>

                <div class="side-label">
                    {{ $payment->status === 'void'
                        ? 'Voided Payment'
                        : 'Posted Payment'
                    }}
                </div>


                <div style="
                    height:1px;
                    background:var(--legacy-surface-eef1ef, #eef1ef);
                    margin:16px 0;
                "></div>


                <div class="side-row">

                    <span>
                        Payment
                    </span>

                    <span>
                        {{ $payment->payment_number }}
                    </span>

                </div>


                <div class="side-row">

                    <span>
                        Assistance
                    </span>

                    <span>
                        {{ $assistance->assistance_number }}
                    </span>

                </div>


                <div class="side-row">

                    <span>
                        Beneficiary
                    </span>

                    <span>
                        {{ $assistance->beneficiary->full_name }}
                    </span>

                </div>


                <div class="side-row">

                    <span>
                        Fund
                    </span>

                    <span>
                        {{ $payment->fund?->name ?: '—' }}
                    </span>

                </div>


                <div class="side-row">

                    <span>
                        Method
                    </span>

                    <span>
                        {{ $paymentMethodLabel }}
                    </span>

                </div>


                <div class="side-row">

                    <span>
                        Date
                    </span>

                    <span>
                        {{ $payment->payment_date->format('d M Y') }}
                    </span>

                </div>

            </div>

        </div>


        <div class="card">

            <div class="card-head">

                <strong>
                    Financial Control
                </strong>

            </div>


            <div class="card-body">

                <div class="notice">

                    @if($payment->status === 'void')

                        <i class="bi bi-arrow-counterclockwise"></i>

                        <p>
                            This payment has been voided. Its linked finance transaction
                            is also voided, so the payment no longer reduces the fund balance.
                        </p>

                    @else

                        <i class="bi bi-shield-check"></i>

                        <p>
                            This payment is recorded as a distinct financial event.
                            The corresponding ledger transaction provides the audit
                            trail for the fund deduction.
                        </p>

                    @endif

                </div>

            </div>

        </div>


    </aside>

</div>

@endsection