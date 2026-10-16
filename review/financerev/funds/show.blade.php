@extends('layouts.app')

@section('title', 'Fund Details')

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
    }

    .status::before {
        content:"";
        width:5px;
        height:5px;
        border-radius:50%;
        background:#4c9a6c;
    }

    .restricted {
        display:inline-flex;
        align-items:center;
        padding:5px 8px;
        border-radius:20px;
        background:#f1eef7;
        color:#77668c;
        font-size: 13px;
        font-weight:650;
    }

    /* SUMMARY */

    .summary-grid {
        display:grid;
        grid-template-columns:repeat(4,minmax(0,1fr));
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

    /* FUND HEADER */

    .fund-intro {
        display:flex;
        gap:13px;
        align-items:center;
    }

    .fund-icon {
        width:46px;
        height:46px;
        border-radius:12px;
        background:var(--primary-soft);
        color:var(--primary);
        display:flex;
        align-items:center;
        justify-content:center;
        font-size: 18px;
    }

    .fund-name {
        font-size: 16px;
        font-weight:700;
    }

    .fund-description {
        margin-top:3px;
        color: #6c7470;
        font-size: 13px;
    }

    /* BALANCE */

    .balance-banner {
        padding:18px;
        background:linear-gradient(145deg,#f8faf9,#f2f6f4);
        border:1px solid var(--border);
        border-radius:12px;
    }

    .balance-label {
        color: #6e7772;
        font-size: 14px;
        text-transform:uppercase;
        letter-spacing:.7px;
    }

    .balance-value {
        margin-top:6px;
        color:var(--primary);
        font-size: 30px;
        font-weight:700;
    }

    .balance-sub {
        margin-top:4px;
        color: #6d7570;
        font-size: 13px;
    }

    /* BARS */

    .bar-row {
        margin-bottom:16px;
    }

    .bar-row:last-child {
        margin-bottom:0;
    }

    .bar-top {
        display:flex;
        justify-content:space-between;
        gap:10px;
        font-size: 14px;
    }

    .bar-top span:first-child {
        color: #6b7671;
    }

    .bar-top span:last-child {
        color:#4a5750;
        font-weight:650;
    }

    .bar {
        height:7px;
        margin-top:7px;
        background:#edf1ee;
        border-radius:20px;
        overflow:hidden;
    }

    .bar span {
        display:block;
        height:100%;
        background:var(--primary);
        border-radius:20px;
    }

    /* TABLE */

    .table-wrap {
        overflow-x:auto;
    }

    .transaction-table {
        width:100%;
        min-width:850px;
        border-collapse:collapse;
    }

    .transaction-table th {
        padding:10px 12px;
        background:#fafcfb;
        border-bottom:1px solid #eef1ef;
        color: #6c7470;
        font-size: 13px;
        text-transform:uppercase;
        letter-spacing:.5px;
        text-align:left;
        white-space:nowrap;
    }

    .transaction-table td {
        padding:12px;
        border-bottom:1px solid #f0f3f1;
        font-size: 14px;
        vertical-align:middle;
    }

    .transaction-table tr:last-child td {
        border-bottom:none;
    }

    .credit {
        color:#397955;
        font-weight:650;
    }

    .debit {
        color:#a85f58;
        font-weight:650;
    }

    .neutral {
        color:#6d7973;
        font-weight:650;
    }

    .transaction-type {
        display:inline-flex;
        padding:5px 8px;
        border-radius:20px;
        font-size: 13px;
        font-weight:650;
    }

    .transaction-type.donation {
        background:#edf7f0;
        color:#397955;
    }

    .transaction-type.expense {
        background:#fff0ef;
        color:#a85f58;
    }

    .transaction-type.allocation {
        background:#f1eef7;
        color:#77668c;
    }

    .transaction-type.recovery {
        background:#eef3f8;
        color:#55758d;
    }

    /* TRACE */

    .trace {
        display:flex;
        align-items:center;
        overflow-x:auto;
        padding:4px 0;
    }

    .trace-step {
        min-width:125px;
        text-align:center;
    }

    .trace-icon {
        width:37px;
        height:37px;
        margin:0 auto 7px;
        border-radius:10px;
        background:var(--primary-soft);
        color:var(--primary);
        display:flex;
        align-items:center;
        justify-content:center;
    }

    .trace-step strong {
        display:block;
        font-size: 13px;
    }

    .trace-step span {
        display:block;
        margin-top:3px;
        color: #6c7470;
        font-size: 12px;
    }

    .trace-arrow {
        min-width:25px;
        color: #6e7471;
        text-align:center;
    }

    /* TIMELINE */

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
        color: #6b766f;
        font-size: 13px;
        line-height:1.5;
    }

    /* SIDEBAR */

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

        .summary-grid {
            grid-template-columns:repeat(2,1fr);
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
@php
    $typeLabel = match ($fund->type) {
        'general' => 'General',
        'restricted' => 'Restricted',
        'designated' => 'Designated',
        default => ucfirst($fund->type),
    };

    $purposeLabel = match ($fund->purpose) {
        'general_welfare' => 'General Welfare',
        'small_business' => 'Small Business Assistance',
        'qard' => 'Qard-e-Hasana',
        'education' => 'Education Support',
        'emergency' => 'Emergency Assistance',
        'community_development' => 'Community Development',
        default => ucfirst(
            str_replace('_', ' ', $fund->purpose)
        ),
    };

    $utilization = $received > 0
        ? round(
            (($spent + $allocated) / $received) * 100
        )
        : 0;

    $availablePercentage = max(
        0,
        min(100, 100 - $utilization)
    );
@endphp

<div class="page-header">

    <div class="identity">

        <div class="icon-box">
            <i class="bi bi-wallet2"></i>
        </div>

        <div>

            <div class="title">
                {{ $fund->name }}
            </div>


            <div class="meta">
                {{ $fund->fund_reference }}
                ·
                {{ $purposeLabel }}
                ·
                {{ ucfirst($fund->status) }}
            </div>

        </div>

    </div>

    <div class="actions">
        <form
            method="POST"
            action="{{ route('finance.funds.destroy', $fund) }}"
            onsubmit="return confirm(
                'Are you sure you want to archive this fund? The fund will be removed from the active list but retained in system history.'
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

        <a
            href="{{ route('finance.funds.index') }}"
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
            Print
        </button>

        <a
            href="{{ route('finance.funds.edit', $fund) }}"
            class="ui-btn primary"
        >
            <i class="bi bi-pencil"></i>
            Edit Fund
        </a>

    </div>

</div>


<div class="layout">

    <div>

        <!-- FUND OVERVIEW -->

        <div class="card">

            <div class="card-head">

                <div class="fund-intro">

                    <div class="fund-icon">
                        <i class="bi bi-wallet2"></i>
                    </div>

                    <div>

                        <div class="fund-name">
                            {{ $fund->name }}
                        </div>

                        <div class="fund-description">
                            {{ $fund->description ?: 'No description provided.' }}
                        </div>

                    </div>

                </div>

                <span class="status">
                    {{ ucfirst($fund->status) }}
                </span>

            </div>

            <div class="card-body">

                <div class="summary-grid">

                    <div class="summary-box highlight">
                        <label>Current Balance</label>
                        <strong>
                            PKR {{ number_format($balance, 0) }}
                        </strong>
                    </div>

                    <div class="summary-box">
                        <label>Total Received</label>
                        <strong>
                            PKR {{ number_format($received, 0) }}
                        </strong>
                    </div>

                    <div class="summary-box">
                        <label>Total Spent</label>
                        <strong>
                            PKR {{ number_format($spent, 0) }}
                        </strong>
                    </div>

                    <div class="summary-box">
                        <label>Allocated</label>
                        <strong>
                            PKR {{ number_format($allocated, 0) }}
                        </strong>
                    </div>

                    <div class="summary-box">
                        <label>Recovered</label>
                        <strong>
                            PKR {{ number_format($recovered, 0) }}
                        </strong>
                    </div>

                    <div class="summary-box">
                        <label>Refunded</label>
                        <strong>
                            PKR {{ number_format($refunded, 0) }}
                        </strong>
                    </div>

                    <div class="summary-box">
                        <label>Fund Type</label>
                        <strong>
                            {{ $typeLabel }}
                        </strong>
                    </div>

                    <div class="summary-box">
                        <label>Fund Reference</label>
                        <strong>
                            {{ $fund->fund_reference }}
                        </strong>
                    </div>

                </div>

            </div>

        </div>


        <!-- BALANCE -->

        <div class="card">

            <div class="card-head">

                <div>
                    <strong>Balance Position</strong>

                    <span>
                        Current movement and utilization of fund resources.
                    </span>
                </div>

            </div>

            <div class="card-body">

                <div class="balance-banner">

                    <div class="balance-label">
                        Available Balance
                    </div>

                    <div class="balance-value">
                        PKR {{ number_format($balance, 0) }}
                    </div>

                    <div class="balance-sub">
                        Total received PKR {{ number_format($received, 0) }}
                        ·
                        Total spent PKR {{ number_format($spent, 0) }}
                    </div>

                </div>

                <div style="margin-top:18px;">

                    <div class="bar-row">

                        <div class="bar-top">

                            <span>
                                Fund Utilization
                            </span>

                            <span style="width:{{ $utilization }}%">{{ $utilization }}%</span>

                        </div>

                        <div class="bar">
                            <span style="width:{{ $utilization }}%"></span>
                        </div>

                    </div>


                    <div class="bar-row">

                        <div class="bar-top">

                            <span>
                                Available
                            </span>

                            <span style="width:{{ $availablePercentage }}%">{{ $availablePercentage }}%</span>

                        </div>

                        <div class="bar">
                            <span style="width:{{ $availablePercentage }}%"></span>
                        </div>

                    </div>

                </div>

            </div>

        </div>


        <!-- FUND INFORMATION -->

        <div class="card">

            <div class="card-head">

                <div>
                    <strong>Fund Information</strong>

                    <span>
                        Purpose and administrative configuration.
                    </span>
                </div>

            </div>

            <div class="card-body">

                <div class="summary-grid">

                    <div class="summary-box">
                        <label>Primary Purpose</label>
                        <strong>
                            {{ $purposeLabel }}
                        </strong>
                    </div>

                    <div class="summary-box">
                        <label>Fund Type</label>
                        <strong>
                            {{ $typeLabel }}
                        </strong>
                    </div>

                    <div class="summary-box">
                        <label>Status</label>
                        <strong>
                            {{ ucfirst($fund->status) }}
                        </strong>
                    </div>

                    <div class="summary-box">
                        <label>Responsible Authority</label>
                        <strong>
                            {{ $fund->responsible_authority ?: '—' }}
                        </strong>
                    </div>

                    <div class="summary-box">
                        <label>Created</label>
                        <strong>
                            {{ $fund->created_at->format('d M Y') }}
                        </strong>
                    </div>

                    <div class="summary-box">
                        <label>Last Updated</label>
                        <strong>
                            {{ $fund->updated_at->format('d M Y') }}
                        </strong>
                    </div>

                    <div class="summary-box">
                        <label>Opening Balance</label>
                        <strong>
                            PKR {{ number_format($fund->opening_balance, 0) }}
                        </strong>
                    </div>

                    <div class="summary-box">
                        <label>Current Balance</label>
                        <strong>
                            PKR {{ number_format($balance, 0) }}
                        </strong>
                    </div>

                </div>

            </div>

        </div>


        <!-- FINANCIAL TRANSACTIONS -->

        <div class="card">

            <div class="card-head">

                <div>
                    <strong>Financial Transactions</strong>

                    <span>
                        Transaction history will appear here as the Transactions module is implemented.
                    </span>
                </div>

                <button class="ui-btn">
                    <i class="bi bi-download"></i>
                    Export
                </button>

            </div>

            <div class="card-body" style="padding:0;">

                <div class="table-wrap">

                    <table class="transaction-table">

                        <thead>

                            <tr>
                                <th>Date</th>
                                <th>Reference</th>
                                <th>Type</th>
                                <th>Description</th>
                                <th>Amount</th>
                                <th>Balance</th>
                            </tr>

                        </thead>

                        <tbody>

                            @php
                                $runningBalance = (float) $fund->opening_balance;
                            @endphp

                            {{-- Opening Balance --}}

                            <tr>

                                <td>
                                    {{ $fund->opening_balance_date
                                        ? $fund->opening_balance_date->format('d M Y')
                                        : $fund->created_at->format('d M Y')
                                    }}
                                </td>

                                <td>
                                    {{ $fund->fund_reference }}
                                </td>

                                <td>
                                    <span class="transaction-type donation">
                                        Opening Balance
                                    </span>
                                </td>

                                <td>
                                    Fund opening balance
                                </td>

                                <td class="credit">
                                    +
                                    PKR {{ number_format($fund->opening_balance, 0) }}
                                </td>

                                <td>
                                    PKR {{ number_format($runningBalance, 0) }}
                                </td>

                            </tr>


                            {{-- Donations --}}

                            @forelse($fund->donations as $donation)

                                @php
                                    $runningBalance += (float) $donation->amount;
                                @endphp

                                <tr>

                                    <td>
                                        {{ $donation->donation_date->format('d M Y') }}
                                    </td>

                                    <td>
                                        {{ $donation->donation_number }}

                                        @if($donation->receipt_number)
                                            <div style="
                                                margin-top:3px;
                                                color:#929b96;
                                                font-size:11px;
                                            ">
                                                Receipt: {{ $donation->receipt_number }}
                                            </div>
                                        @endif
                                    </td>

                                    <td>
                                        <span class="transaction-type donation">
                                            Donation
                                        </span>
                                    </td>

                                    <td>
                                        {{ $donation->donor_name }}

                                        <div style="
                                            margin-top:3px;
                                            color:#929b96;
                                            font-size:11px;
                                        ">
                                            {{ ucfirst($donation->payment_method) }}
                                            ·
                                            {{ ucfirst($donation->designation) }}
                                        </div>
                                    </td>

                                    <td class="credit">
                                        +
                                        PKR {{ number_format($donation->amount, 0) }}
                                    </td>

                                    <td>
                                        PKR {{ number_format($runningBalance, 0) }}
                                    </td>

                                </tr>

                            @empty

                                {{-- No donations --}}

                            @endforelse

                        </tbody>

                    </table>

                </div>

            </div>

        </div>


        <!-- FUND TRACE -->

        <div class="card">

            <div class="card-head">

                <div>
                    <strong>Fund Traceability</strong>

                    <span>
                        Financial lifecycle from incoming resources to approved use.
                    </span>
                </div>

            </div>

            <div class="card-body">

                <div class="trace">

                    <div class="trace-step">

                        <div class="trace-icon">
                            <i class="bi bi-person"></i>
                        </div>

                        <strong>
                            Donor
                        </strong>

                        <span>
                            Contribution source
                        </span>

                    </div>


                    <div class="trace-arrow">
                        <i class="bi bi-arrow-right"></i>
                    </div>


                    <div class="trace-step">

                        <div class="trace-icon">
                            <i class="bi bi-cash-coin"></i>
                        </div>

                        <strong>
                            Donation
                        </strong>

                        <span>
                            Recorded income
                        </span>

                    </div>


                    <div class="trace-arrow">
                        <i class="bi bi-arrow-right"></i>
                    </div>


                    <div class="trace-step">

                        <div class="trace-icon">
                            <i class="bi bi-wallet2"></i>
                        </div>

                        <strong>
                            {{ $fund->name }}
                        </strong>

                        <span>
                            Available balance
                        </span>

                    </div>


                    <div class="trace-arrow">
                        <i class="bi bi-arrow-right"></i>
                    </div>


                    <div class="trace-step">

                        <div class="trace-icon">
                            <i class="bi bi-diagram-3"></i>
                        </div>

                        <strong>
                            Allocation
                        </strong>

                        <span>
                            Approved use
                        </span>

                    </div>


                    <div class="trace-arrow">
                        <i class="bi bi-arrow-right"></i>
                    </div>


                    <div class="trace-step">

                        <div class="trace-icon">
                            <i class="bi bi-file-earmark-check"></i>
                        </div>

                        <strong>
                            Record
                        </strong>

                        <span>
                            Audit / reporting
                        </span>

                    </div>

                </div>

            </div>

        </div>


        <!-- ACTIVITY -->

        <div class="card">

            <div class="card-head">

                <div>
                    <strong>Fund Activity</strong>

                    <span>
                        Important fund events and changes.
                    </span>
                </div>

            </div>

            <div class="card-body">

                <div class="timeline-item">

                    <div class="timeline-dot"></div>

                    <div>

                        <div class="timeline-title">
                            Fund created
                        </div>

                        <div class="timeline-meta">
                            {{ $fund->created_at->format('d M Y, h:i A') }}
                        </div>

                        <div class="timeline-note">
                            {{ $fund->name }}
                            was created with an opening balance of
                            PKR {{ number_format($fund->opening_balance, 0) }}.
                        </div>

                    </div>

                </div>


                @if ($fund->updated_at->gt($fund->created_at))

                    <div class="timeline-item">

                        <div class="timeline-dot"></div>

                        <div>

                            <div class="timeline-title">
                                Fund updated
                            </div>

                            <div class="timeline-meta">
                                {{ $fund->updated_at->format('d M Y, h:i A') }}
                            </div>

                        </div>

                    </div>

                @endif

            </div>

        </div>


        <!-- CONTROL -->

        <div class="card">

            <div class="card-head">

                <div>
                    <strong>Fund Controls</strong>

                    <span>
                        Current financial control configuration.
                    </span>
                </div>

            </div>

            <div class="card-body">

                <div class="notice">

                    <i class="bi bi-shield-check"></i>

                    <p>
                        This fund is configured with:

                        @if($fund->prevent_negative_balance)
                            negative-balance protection,
                        @endif

                        @if($fund->require_approval)
                            approval requirements,
                        @endif

                        @if($fund->require_documents)
                            supporting-document requirements,
                        @endif

                        @if($fund->protect_restricted_designation)
                            restricted-designation protection
                        @endif

                        @if(
                            !$fund->prevent_negative_balance &&
                            !$fund->require_approval &&
                            !$fund->require_documents &&
                            !$fund->protect_restricted_designation
                        )
                            no additional financial restrictions
                        @endif
                        .
                    </p>

                </div>

            </div>

        </div>

    </div>


    <!-- SIDEBAR -->

    <aside>

        <div class="card">

            <div class="card-body">

                <div class="side-number">
                    PKR {{ number_format($balance, 0) }}
                </div>

                <div class="side-label">
                    Current Balance
                </div>

                <div style="
                    height:1px;
                    background:#eef1ef;
                    margin:16px 0;
                "></div>

                <div class="side-row">
                    <span>Fund</span>
                    <span>{{ $fund->name }}</span>
                </div>

                <div class="side-row">
                    <span>Type</span>
                    <span>{{ $typeLabel }}</span>
                </div>

                <div class="side-row">
                    <span>Status</span>
                    <span>{{ ucfirst($fund->status) }}</span>
                </div>

                <div class="side-row">
                    <span>Received</span>
                    <span>
                        PKR {{ number_format($received, 0) }}
                    </span>
                </div>

                <div class="side-row">
                    <span>Spent</span>
                    <span>
                        PKR {{ number_format($spent, 0) }}
                    </span>
                </div>

                <div class="side-row">
                    <span>Available</span>
                    <span>
                        PKR {{ number_format($balance, 0) }}
                    </span>
                </div>

            </div>

        </div>


        <div class="card">

            <div class="card-head">
                <strong>Fund Purpose</strong>
            </div>

            <div class="card-body">

                <div class="notice">

                    <i class="bi bi-wallet2"></i>

                    <p>
                        {{ $fund->description ?: 'No fund description has been recorded.' }}
                    </p>

                </div>

            </div>

        </div>

    </aside>

</div>

@endsection