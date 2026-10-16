@extends('layouts.app')

@section('title', 'Donation Details')

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

    .icon-box {
        width:62px;
        height:62px;
        border-radius:17px;
        background:var(--primary-soft);
        color:var(--primary);
        display:flex;
        align-items:center;
        justify-content:center;
        font-size: 22px;
    }

    .title {
        font-size: 23px;
        font-weight:700;
        letter-spacing:-.4px;
    }

    .meta {
        margin-top:4px;
        color: #6e7773;
        font-size: 15px;
    }

    .actions {
        display:flex;
        gap:8px;
        flex-wrap:wrap;
    }

    .ui-btn {
        height:39px;
        padding:0 12px;
        border-radius:9px;
        display:inline-flex;
        align-items:center;
        justify-content:center;
        gap:7px;
        border:1px solid var(--border);
        background:#fff;
        color:#65726c;
        font-size: 15px;
        font-weight:650;
        text-decoration:none;
        cursor:pointer;
    }

    .ui-btn.primary {
        background:var(--primary);
        border-color:var(--primary);
        color:#fff;
    }

    .ui-btn.success {
        background:#edf7f0;
        border-color: #6a746d;
        color:#397955;
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
        justify-content:space-between;
        align-items:center;
        gap:15px;
    }

    .card-head strong {
        font-size: 16px;
    }

    .card-head span {
        display:block;
        margin-top:4px;
        color: #6c7470;
        font-size: 14px;
    }

    .card-body {
        padding:18px 19px;
    }

    .status {
        display:inline-flex;
        align-items:center;
        gap:5px;
        padding:5px 8px;
        border-radius:20px;
        background:#edf7f0;
        color:#397955;
        font-size: 13px;
        font-weight:650;
        white-space:nowrap;
    }

    .status::before {
        content:"";
        width:5px;
        height:5px;
        border-radius:50%;
        background:#4c9a6c;
    }

    .designation {
        display:inline-flex;
        align-items:center;
        gap:5px;
        padding:5px 8px;
        border-radius:20px;
        background:#f1eef7;
        color:#77668c;
        font-size: 13px;
        font-weight:650;
    }

    .summary-grid {
        display:grid;
        grid-template-columns:repeat(3,minmax(0,1fr));
        gap:10px;
    }

    .summary-box {
        padding:13px;
        background:#fafcfb;
        border:1px solid #edf1ee;
        border-radius:10px;
    }

    .summary-box.highlight {
        background:var(--primary-soft);
        border-color: #6f7673;
    }

    .summary-box label {
        display:block;
        color: #6d7571;
        font-size: 13px;
        text-transform:uppercase;
        letter-spacing:.5px;
    }

    .summary-box strong {
        display:block;
        margin-top:5px;
        font-size: 16px;
    }

    .summary-box.highlight strong {
        color:var(--primary);
    }

    .donor-box {
        display:flex;
        align-items:center;
        gap:11px;
    }

    .avatar {
        width:42px;
        height:42px;
        border-radius:11px;
        background:var(--primary-soft);
        color:var(--primary);
        display:flex;
        align-items:center;
        justify-content:center;
        font-size: 15px;
        font-weight:700;
    }

    .donor-name {
        font-size: 15px;
        font-weight:650;
    }

    .donor-meta {
        margin-top:3px;
        color: #6c7470;
        font-size: 13px;
    }

    .info-grid {
        display:grid;
        grid-template-columns:repeat(2,minmax(0,1fr));
        gap:16px;
    }

    .info label {
        display:block;
        margin-bottom:5px;
        color: #6d7571;
        font-size: 13px;
        text-transform:uppercase;
        letter-spacing:.5px;
    }

    .info strong {
        display:block;
        color:#46534c;
        font-size: 15px;
    }

    .info p {
        margin:0;
        color:#68756e;
        font-size: 14px;
        line-height:1.65;
    }

    .full {
        grid-column:1/-1;
    }

    .fund-box {
        padding:15px;
        border:1px solid #dce7e1;
        background:#f7faf8;
        border-radius:11px;
    }

    .fund-top {
        display:flex;
        justify-content:space-between;
        align-items:center;
        gap:10px;
    }

    .fund-name {
        font-size: 16px;
        font-weight:700;
        color:var(--primary);
    }

    .fund-type {
        padding:5px 8px;
        border-radius:20px;
        background:#f1eef7;
        color:#77668c;
        font-size: 13px;
        font-weight:650;
    }

    .fund-meta {
        margin-top:12px;
        display:grid;
        grid-template-columns:repeat(3,1fr);
        gap:10px;
    }

    .fund-stat {
        padding:10px;
        background:#fff;
        border:1px solid #e7edea;
        border-radius:8px;
    }

    .fund-stat label {
        display:block;
        color: #6d7571;
        font-size: 12px;
        text-transform:uppercase;
    }

    .fund-stat strong {
        display:block;
        margin-top:4px;
        font-size: 15px;
    }

    .receipt {
        padding:16px;
        border:1px dashed #cad7d0;
        border-radius:11px;
        background:#fbfcfb;
        display:flex;
        align-items:center;
        gap:12px;
    }

    .receipt-icon {
        width:38px;
        height:38px;
        border-radius:10px;
        background:var(--primary-soft);
        color:var(--primary);
        display:flex;
        align-items:center;
        justify-content:center;
    }

    .receipt-info {
        flex:1;
    }

    .receipt-info strong {
        display:block;
        font-size: 15px;
    }

    .receipt-info span {
        display:block;
        margin-top:3px;
        color: #6c7470;
        font-size: 13px;
    }

    .small-btn {
        width:30px;
        height:30px;
        border:1px solid var(--border);
        background:#fff;
        border-radius:8px;
        color: #6c7771;
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
        font-size: 15px;
        font-weight:650;
    }

    .timeline-meta {
        margin-top:3px;
        color: #6c7470;
        font-size: 13px;
    }

    .timeline-note {
        margin-top:6px;
        padding:8px 10px;
        background:#fafcfb;
        border:1px solid #edf1ee;
        border-radius:8px;
        color:#6d7972;
        font-size: 13px;
        line-height:1.5;
    }

    .side-number {
        text-align:center;
        font-size: 30px;
        font-weight:700;
        color:var(--primary);
    }

    .side-label {
        text-align:center;
        margin-top:3px;
        color: #6e7772;
        font-size: 14px;
    }

    .side-row {
        display:flex;
        justify-content:space-between;
        gap:10px;
        padding:9px 0;
        border-bottom:1px solid #f0f3f1;
        font-size: 14px;
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

    .notice {
        display:flex;
        gap:9px;
        padding:12px;
        background:#f7f9f8;
        border:1px solid #e5ebe8;
        border-radius:10px;
    }

    .notice i {
        color:var(--primary);
        font-size: 16px;
    }

    .notice p {
        margin:0;
        color: #6b766f;
        font-size: 13px;
        line-height:1.6;
    }

    @media(max-width:950px) {
        .layout {
            grid-template-columns:1fr;
        }
    }

    @media(max-width:700px) {
        .page-header {
            flex-direction:column;
        }

        .actions {
            width:100%;
        }

        .actions .ui-btn {
            flex:1;
        }

        .summary-grid,
        .info-grid,
        .fund-meta {
            grid-template-columns:1fr;
        }

        .full {
            grid-column:auto;
        }
    }
</style>
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

            <a
                href="{{ route('finance.donations.index') }}"
                class="ui-btn"
            >
                <i class="bi bi-arrow-left"></i>
                Back
            </a>

            <button
                class="ui-btn"
                onclick="window.print()"
            >
                <i class="bi bi-printer"></i>
                Print Receipt
            </button>

            @if(auth()->user()->hasPermission('finance.donations.manage'))

                <a
                    href="{{ route('finance.donations.edit', $donation) }}"
                    class="ui-btn primary"
                >
                    <i class="bi bi-pencil"></i>
                    Edit
                </a>

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
                        color:#a85f58;
                        border-color:#ead8d5;
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
                            PKR {{ number_format($donation->amount, 0) }}
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

                                <a
                                    href="{{ route('finance.transactions.show', $donation->transaction) }}"
                                    style="color:var(--primary);text-decoration:none;"
                                >
                                    {{ $donation->transaction->transaction_number }}
                                </a>

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
                                PKR {{ number_format($donation->amount, 0) }}
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
                                    ? 'PKR ' . number_format($fundBalance, 0)
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
                            {{ number_format($donation->amount, 0) }}
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
                        color:#6e7471;
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
                        color:#6e7471;
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
                        color:#6e7471;
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
                                color:#6e7772;
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
                    PKR {{ number_format($donation->amount, 0) }}
                </div>

                <div class="side-label">
                    Donation Amount
                </div>


                <div style="
                    height:1px;
                    background:#eef1ef;
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


