@extends('layouts.app')

@section('title', 'Donation Details')

@push('styles')
<link rel="stylesheet" href="{{ asset('css/views/finance-donations-show-1.css') }}">
@endpush


@section('content')

@php
    $purposeLabels = [
        'general' => 'General Welfare',
        'business' => 'Small Business Assistance',
        'qard' => 'Qard-e-Hasana',
        'education' => 'Education Support',
        'emergency' => 'Emergency Assistance',
        'project' => 'Community Project',
    ];

    $paymentLabels = [
        'cash' => 'Cash',
        'bank' => 'Bank Transfer',
        'mobile' => 'Mobile Payment',
        'online' => 'Online',
    ];

    $fundLabels = [
        'general' => 'General Fund',
        'business' => 'Small Business Fund',
        'qard' => 'Qard-e-Hasana Fund',
        'education' => 'Education Fund',
        'emergency' => 'Emergency Fund',
    ];

    $donorTypeLabels = [
        'individual' => 'Individual Donor',
        'family' => 'Family Donation',
        'organization' => 'Organization',
    ];

    $purpose =
        $purposeLabels[$donation->purpose]
        ?? ucfirst($donation->purpose);

    $payment =
        $paymentLabels[$donation->payment_method]
        ?? ucfirst($donation->payment_method);

    $fund =
        $donation->fund->name
        ?? 'Not assigned';

    $donorType =
        $donorTypeLabels[$donation->donor_type]
        ?? ucfirst($donation->donor_type);

    $initials = collect(
        preg_split(
            '/\s+/',
            trim($donation->donor_name)
        )
    )
    ->take(2)
    ->map(
        fn ($part) =>
            strtoupper(substr($part, 0, 1))
    )
    ->implode('');

    $statusClass = match ($donation->status) {
        'received' => 'received',
        'pending' => 'pending',
        'restricted' => 'restricted',
        default => 'pending',
    };

    $statusLabel =
        ucfirst($donation->status);
@endphp


<div class="page-header">

    <div class="identity">

        <div class="icon-box">
            <i class="bi bi-receipt"></i>
        </div>

        <div>

            <div class="title">
                {{ $donation->donation_number }}
            </div>

            <div class="meta">
                {{ $donation->donor_name }}
                ·
                {{ $purpose }}
                ·
                {{ $donation->donation_date->format('d M Y') }}
            </div>

        </div>

    </div>


    <div class="actions">

            @if(auth()->user()?->hasPermission('finance.view'))<a
                href="{{ route('finance.donations.index') }}"
                class="ui-btn"
            >
                <i class="bi bi-arrow-left"></i>
                Back
            </a>@endif

            <button
                class="ui-btn"
                onclick="window.print()"
            >
                <i class="bi bi-printer"></i>
                Print Receipt
            </button>

            @if(auth()->user()->hasPermission('finance.donations.manage'))

                @if(auth()->user()?->hasPermission('finance.view') && auth()->user()?->hasPermission('finance.donations.manage'))<a
                    href="{{ route('finance.donations.edit', $donation) }}"
                    class="ui-btn primary"
                >
                    <i class="bi bi-pencil"></i>
                    Edit
                </a>@endif

            @endif

        </div>
        @if(auth()->user()->hasPermission('finance.donations.manage'))

            <form
                method="POST"
                action="{{ route('finance.donations.destroy', $donation) }}"
                onsubmit="return confirm(
                    'Are you sure you want to archive this donation? The record will be hidden from the active donation list but retained in the system history.'
                )"
                style="display:inline;"
            >
                @csrf
                @method('DELETE')

                <button
                    type="submit"
                    class="ui-btn"
                    style="
                        color:var(--legacy-text-a85f58, #a85f58);
                        border-color:var(--legacy-border-ead8d5, #ead8d5);
                    "
                >
                    <i class="bi bi-archive"></i>
                    Archive
                </button>
            </form>

        @endif

</div>


@if (session('success'))

    <div
        class="notice"
        style="margin-top:0;margin-bottom:18px;"
    >

        <i class="bi bi-check-circle"></i>

        <div>

            <strong>
                Success
            </strong>

            <p>
                {{ session('success') }}
            </p>

        </div>

    </div>

@endif


<div class="layout">

    <div>

        <!-- OVERVIEW -->

        <div class="card">

            <div class="card-head">

                <div>

                    <strong>
                        Donation Overview
                    </strong>

                    <span>
                        Complete record of the contribution.
                    </span>

                </div>


                <span
                    class="status {{ $statusClass }}"
                >
                    {{ $statusLabel }}
                </span>

            </div>


            <div class="card-body">

                <div class="summary-grid">

                    <div class="summary-box highlight">

                        <label>
                            Donation Amount
                        </label>

                        <strong>
                            PKR {{ \App\Domain\Finance\Support\Money::format($donation->amount) }}
                        </strong>

                    </div>


                    <div class="summary-box">

                        <label>
                            Donation ID
                        </label>

                        <strong>
                            {{ $donation->donation_number }}
                        </strong>

                    </div>


                    <div class="summary-box">

                        <label>
                            Receipt
                        </label>

                        <strong>
                            {{ $donation->receipt_number ?: '—' }}
                        </strong>

                    </div>


                    <div class="summary-box">

                        <label>
                            Date
                        </label>

                        <strong>
                            {{ $donation->donation_date->format('d M Y') }}
                        </strong>

                    </div>


                    <div class="summary-box">

                        <label>
                            Payment
                        </label>

                        <strong>
                            {{ $payment }}
                        </strong>

                    </div>


                    <div class="summary-box">

                        <label>
                            Designation
                        </label>

                        <strong>
                            {{ ucfirst($donation->designation) }}
                        </strong>

                    </div>

                </div>

            </div>

        </div>


        <!-- DONOR -->

        <div class="card">

            <div class="card-head">

                <div>

                    <strong>
                        Donor
                    </strong>

                    <span>
                        Information recorded with the contribution.
                    </span>

                </div>

            </div>


            <div class="card-body">

                <div class="donor-box">

                    <div class="avatar">
                        {{ $initials ?: '?' }}
                    </div>


                    <div style="flex:1">

                        <div class="donor-name">
                            {{ $donation->donor_name }}
                        </div>

                        <div class="donor-meta">
                            {{ $donorType }}
                        </div>

                    </div>

                </div>

            </div>

        </div>


        <!-- DETAILS -->

        <div class="card">

            <div class="card-head">

                <div>

                    <strong>
                        Donation Details
                    </strong>

                    <span>
                        Purpose, payment and contribution information.
                    </span>

                </div>

            </div>


            <div class="card-body">

                <div class="info-grid">


                    <div class="info">

                        <label>
                            Purpose
                        </label>

                        <strong>
                            {{ $purpose }}
                        </strong>

                    </div>


                    <div class="info">

                        <label>
                            Designation
                        </label>

                        <strong>
                            {{ ucfirst($donation->designation) }}
                        </strong>

                    </div>


                    <div class="info">

                        <label>
                            Payment Method
                        </label>

                        <strong>
                            {{ $payment }}
                        </strong>

                    </div>


                    <div class="info">

                        <label>
                            Transaction Reference
                        </label>

                        <strong>
                            {{ $donation->transaction_reference ?: '—' }}
                        </strong>

                    </div>

                    <div class="info">
                        <label>Ledger Transaction</label>

                        <strong>
                            @if($donation->transaction)

                                @if(auth()->user()?->hasPermission('finance.view'))<a
                                    href="{{ route('finance.transactions.show', $donation->transaction) }}"
                                    style="color:var(--primary);text-decoration:none;"
                                >
                                    {{ $donation->transaction->transaction_number }}
                                </a>@endif

                                · {{ ucfirst($donation->transaction->status) }}

                            @else

                                —

                            @endif
                        </strong>
                    </div>


                    <div class="info">

                        <label>
                            Donation Date
                        </label>

                        <strong>
                            {{ $donation->donation_date->format('d M Y') }}
                        </strong>

                    </div>


                    <div class="info">

                        <label>
                            Recorded By
                        </label>

                        <strong>
                            {{ $donation->recordedBy?->name ?? 'System' }}
                        </strong>

                    </div>


                    <div class="info full">

                        <label>
                            Phone
                        </label>

                        <strong>
                            {{ $donation->phone ?: '—' }}
                        </strong>

                    </div>


                    <div class="info full">

                        <label>
                            Email
                        </label>

                        <strong>
                            {{ $donation->email ?: '—' }}
                        </strong>

                    </div>


                    <div class="info full">

                        <label>
                            Purpose Notes
                        </label>

                        <p>
                            {{ $donation->purpose_notes ?: 'No purpose notes recorded.' }}
                        </p>

                    </div>


                    <div class="info full">

                        <label>
                            Internal Notes
                        </label>

                        <p>
                            {{ $donation->internal_notes ?: 'No internal notes recorded.' }}
                        </p>

                    </div>

                </div>

            </div>

        </div>


        <!-- FUND -->

        <div class="card">

            <div class="card-head">

                <div>

                    <strong>
                        Fund Assignment
                    </strong>

                    <span>
                        Financial destination associated with the donation.
                    </span>

                </div>

                <span class="designation">
                    {{ ucfirst($donation->designation) }}
                </span>

            </div>


            <div class="card-body">

                <div class="fund-box">

                    <div class="fund-top">

                        <div class="fund-name">
                            {{ $fund }}
                        </div>

                        <div class="fund-type">
                            {{ ucfirst($donation->designation) }}
                        </div>

                    </div>


                    <div class="fund-meta">

                        <div class="fund-stat">

                            <label>
                                This Donation
                            </label>

                            <strong>
                                PKR {{ \App\Domain\Finance\Support\Money::format($donation->amount) }}
                            </strong>

                        </div>


                        <div class="fund-stat">
                            <label>Fund Status</label>

                            <strong>
                                {{ $donation->fund
                                    ? ucfirst($donation->fund->status)
                                    : 'Not assigned'
                                }}
                            </strong>
                        </div>

                        <div class="fund-stat">
                            <label>Fund Balance</label>

                            <strong>
                                {{ $fundBalance !== null
                                    ? 'PKR ' . \App\Domain\Finance\Support\Money::format($fundBalance)
                                    : '—'
                                }}
                            </strong>
                        </div>

                    </div>

                </div>

            </div>

        </div>


        <!-- RECEIPT -->

        <div class="card">

            <div class="card-head">

                <div>

                    <strong>
                        Receipt & Supporting Record
                    </strong>

                    <span>
                        Receipt information associated with this donation.
                    </span>

                </div>

            </div>


            <div class="card-body">

                @if ($donation->receipt_number)

                    <div class="receipt">

                        <div class="receipt-icon">
                            <i class="bi bi-receipt"></i>
                        </div>

                        <div class="receipt-info">

                            <strong>
                                Donation Receipt —
                                {{ $donation->receipt_number }}
                            </strong>

                            <span>
                                Receipt number recorded with donation.
                            </span>

                        </div>

                    </div>

                @else

                    <div class="notice">

                        <i class="bi bi-info-circle"></i>

                        <p>
                            No receipt number has been recorded for this donation.
                        </p>

                    </div>

                @endif

            </div>

        </div>


        <!-- ACTIVITY -->

        <div class="card">

            <div class="card-head">

                <div>

                    <strong>
                        Donation Activity
                    </strong>

                    <span>
                        Basic record timestamps.
                    </span>

                </div>

            </div>


            <div class="card-body">

                <div class="timeline-item">

                    <div class="timeline-dot"></div>

                    <div>

                        <div class="timeline-title">
                            Donation recorded
                        </div>

                        <div class="timeline-meta">
                            {{ $donation->created_at->format('d M Y, h:i A') }}
                            ·
                            {{ $donation->recordedBy?->name ?? 'System' }}
                        </div>

                        <div class="timeline-note">

                            PKR
                            {{ \App\Domain\Finance\Support\Money::format($donation->amount) }}
                            received from
                            {{ $donation->donor_name }}.

                        </div>

                    </div>

                </div>


                @if ($donation->updated_at->gt($donation->created_at))

                    <div class="timeline-item">

                        <div class="timeline-dot"></div>

                        <div>

                            <div class="timeline-title">
                                Donation record updated
                            </div>

                            <div class="timeline-meta">
                                {{ $donation->updated_at->format('d M Y, h:i A') }}
                            </div>

                        </div>

                    </div>

                @endif

            </div>

        </div>


        <!-- FINANCIAL TRACE -->

        <div class="card">

            <div class="card-head">

                <div>

                    <strong>
                        Financial Trace
                    </strong>

                    <span>
                        Current position in the donation workflow.
                    </span>

                </div>

            </div>


            <div class="card-body">

                <div
                    style="
                        display:flex;
                        align-items:center;
                        gap:0;
                        overflow-x:auto;
                        padding:5px 0;
                    "
                >

                    <div style="
                        min-width:130px;
                        text-align:center;
                    ">

                        <div style="
                            width:36px;
                            height:36px;
                            margin:0 auto 7px;
                            border-radius:10px;
                            background:var(--primary-soft);
                            color:var(--primary);
                            display:flex;
                            align-items:center;
                            justify-content:center;
                        ">
                            <i class="bi bi-person"></i>
                        </div>

                        <strong style="font-size:13px;">
                            Donor
                        </strong>

                    </div>


                    <div style="
                        color:var(--legacy-text-6e7471, #6e7471);
                        min-width:26px;
                        text-align:center;
                    ">
                        <i class="bi bi-arrow-right"></i>
                    </div>


                    <div style="
                        min-width:130px;
                        text-align:center;
                    ">

                        <div style="
                            width:36px;
                            height:36px;
                            margin:0 auto 7px;
                            border-radius:10px;
                            background:var(--primary-soft);
                            color:var(--primary);
                            display:flex;
                            align-items:center;
                            justify-content:center;
                        ">
                            <i class="bi bi-cash-coin"></i>
                        </div>

                        <strong style="font-size:13px;">
                            Donation
                        </strong>

                    </div>


                    <div style="
                        color:var(--legacy-text-6e7471, #6e7471);
                        min-width:26px;
                        text-align:center;
                    ">
                        <i class="bi bi-arrow-right"></i>
                    </div>


                    <div style="
                        min-width:130px;
                        text-align:center;
                    ">

                        <div style="
                            width:36px;
                            height:36px;
                            margin:0 auto 7px;
                            border-radius:10px;
                            background:var(--primary-soft);
                            color:var(--primary);
                            display:flex;
                            align-items:center;
                            justify-content:center;
                        ">
                            <i class="bi bi-wallet2"></i>
                        </div>

                        <strong style="font-size:13px;">
                            {{ $fund }}
                        </strong>

                    </div>


                    <div style="
                        color:var(--legacy-text-6e7471, #6e7471);
                        min-width:26px;
                        text-align:center;
                    ">
                        <i class="bi bi-arrow-right"></i>
                    </div>


                    <div style="
                        min-width:130px;
                        text-align:center;
                    ">

                        <div style="
                            width:36px;
                            height:36px;
                            margin:0 auto 7px;
                            border-radius:10px;
                            background:var(--primary-soft);
                            color:var(--primary);
                            display:flex;
                            align-items:center;
                            justify-content:center;
                        ">
                            <i class="bi bi-diagram-3"></i>
                        </div>

                        <div style="
                            min-width:130px;
                            text-align:center;
                        ">
                            <div style="
                                width:36px;
                                height:36px;
                                margin:0 auto 7px;
                                border-radius:10px;
                                background:var(--primary-soft);
                                color:var(--primary);
                                display:flex;
                                align-items:center;
                                justify-content:center;
                            ">
                                <i class="bi bi-journal-text"></i>
                            </div>

                            <strong style="font-size:13px;">
                                Ledger
                            </strong>

                            <span style="
                                display:block;
                                margin-top:4px;
                                color:var(--legacy-text-6e7772, #6e7772);
                                font-size:12px;
                            ">
                                {{ $donation->transaction?->transaction_number ?? 'No Entry' }}
                            </span>
                        </div>

                    </div>

                </div>

            </div>

        </div>

    </div>


    <!-- SIDEBAR -->

    <aside>

        <div class="card">

            <div class="card-body">

                <div class="side-number">
                    PKR {{ \App\Domain\Finance\Support\Money::format($donation->amount) }}
                </div>

                <div class="side-label">
                    Donation Amount
                </div>


                <div style="
                    height:1px;
                    background:var(--legacy-surface-eef1ef, #eef1ef);
                    margin:16px 0;
                "></div>


                <div class="side-row">

                    <span>
                        Status
                    </span>

                    <span>
                        {{ $statusLabel }}
                    </span>

                </div>


                <div class="side-row">

                    <span>
                        Donor
                    </span>

                    <span>
                        {{ $donation->donor_name }}
                    </span>

                </div>


                <div class="side-row">

                    <span>
                        Purpose
                    </span>

                    <span>
                        {{ $purpose }}
                    </span>

                </div>


                <div class="side-row">

                    <span>
                        Fund
                    </span>

                    <span>
                        {{ $fund }}
                    </span>

                </div>


                <div class="side-row">

                    <span>
                        Payment
                    </span>

                    <span>
                        {{ $payment }}
                    </span>

                </div>


                <div class="side-row">

                    <span>
                        Date
                    </span>

                    <span>
                        {{ $donation->donation_date->format('d M Y') }}
                    </span>

                </div>

            </div>

        </div>


        <div class="card">

            <div class="card-head">
                <strong>
                    Record Control
                </strong>
            </div>

            <div class="card-body">

                <div class="notice">

                    <i class="bi bi-shield-check"></i>

                    <p>
                        Donation records should remain traceable from the
                        original donor contribution through fund allocation,
                        approved use and financial reporting.
                    </p>

                </div>

            </div>

        </div>

    </aside>

</div>

@endsection


