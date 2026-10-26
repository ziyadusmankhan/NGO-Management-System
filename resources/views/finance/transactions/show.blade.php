@extends('layouts.app')

@section('title', 'Transaction Details')

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
        width:60px;
        height:60px;
        border-radius:16px;
        background:var(--primary-soft);
        color:var(--primary);
        display:flex;
        align-items:center;
        justify-content:center;
        font-size:22px;
    }

    .title {
        font-size:24px;
        font-weight:700;
    }

    .meta {
        margin-top:5px;
        color:#6e7773;
        font-size:14px;
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
        border:1px solid var(--border);
        background:#fff;
        color:#637069;
        font-weight:650;
        text-decoration:none;
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
    }

    .card-head strong {
        font-size:16px;
    }

    .card-body {
        padding:19px;
    }

    .summary-grid {
        display:grid;
        grid-template-columns:repeat(3,1fr);
        gap:10px;
    }

    .summary-box {
        padding:14px;
        background:#fafcfb;
        border:1px solid #edf1ee;
        border-radius:10px;
    }

    .summary-box.highlight {
        background:var(--primary-soft);
    }

    .summary-box label {
        display:block;
        color:#6d7571;
        font-size:12px;
        text-transform:uppercase;
    }

    .summary-box strong {
        display:block;
        margin-top:5px;
        font-size:16px;
    }

    .credit {
        color:#397955;
    }

    .debit {
        color:#a85f58;
    }

    .info-grid {
        display:grid;
        grid-template-columns:repeat(2,1fr);
        gap:18px;
    }

    .info label {
        display:block;
        margin-bottom:5px;
        color:#6d7571;
        font-size:12px;
        text-transform:uppercase;
    }

    .info strong,
    .info p {
        color:#46534c;
        font-size:15px;
    }

    .fund-box {
        padding:15px;
        background:#f7faf8;
        border:1px solid #dce7e1;
        border-radius:11px;
    }

    .fund-name {
        font-size:17px;
        font-weight:700;
        color:var(--primary);
    }

    .fund-reference {
        margin-top:3px;
        color:#7a847f;
        font-size:13px;
    }

    .side-number {
        text-align:center;
        font-size:29px;
        font-weight:700;
        color:var(--primary);
    }

    .side-label {
        text-align:center;
        margin-top:3px;
        color:#6e7772;
        font-size:14px;
    }

    .side-row {
        display:flex;
        justify-content:space-between;
        gap:10px;
        padding:9px 0;
        border-bottom:1px solid #f0f3f1;
        font-size:14px;
    }

    .side-row:last-child {
        border-bottom:none;
    }

    .side-row span:first-child {
        color:#6d7571;
    }

    .side-row span:last-child {
        text-align:right;
        font-weight:650;
        color:#4a5750;
    }

    .source-link {
        display:inline-flex;
        align-items:center;
        gap:7px;
        margin-top:10px;
        padding:8px 11px;
        border-radius:8px;
        background:#f7f9f8;
        border:1px solid #e5ebe8;
        color:var(--primary);
        text-decoration:none;
        font-size:13px;
        font-weight:650;
    }

    @media(max-width:800px) {
        .layout {
            grid-template-columns:1fr;
        }

        .summary-grid,
        .info-grid {
            grid-template-columns:1fr;
        }

        .page-header {
            flex-direction:column;
        }
    }

    @media(max-width:480px) {
        .identity {
            align-items:flex-start;
        }

        .icon-box {
            width:48px;
            height:48px;
            font-size:18px;
        }

        .title {
            font-size:19px;
        }

        .actions {
            width:100%;
        }

        .actions .ui-btn {
            flex:1;
        }
    }
</style>
@endpush

@section('content')

@php
    $isCredit = $transaction->direction === 'credit';

    $sourceUrl = null;

    if ($transaction->source) {
        if ($transaction->source instanceof \App\Domain\Projects\Models\ProjectMovement) {
            $sourceUrl = auth()->user()->hasPermission('projects.view') ? route('projects.show',$transaction->source->project) : null;
        } elseif ($transaction->source instanceof \App\Domain\Business\Models\BusinessMovement) {
            $sourceUrl = auth()->user()->hasPermission('business.view') ? route('business.applications.show', $transaction->source->application) : null;
        } elseif ($transaction->source instanceof \App\Domain\Qard\Models\QardMovement) {
            $sourceUrl = auth()->user()->hasPermission('qard.view') ? route('qard.show', $transaction->source->loan) : null;
        } elseif ($transaction->type === 'donation') {
            $sourceUrl = route(
                'finance.donations.show',
                $transaction->source
            );
        } elseif ($transaction->type === 'expense') {
            $sourceUrl = route(
                'finance.expenses.show',
                $transaction->source
            );
        }
    }
@endphp

<div class="page-header">

    <div class="identity">

        <div class="icon-box">
            <i class="bi bi-receipt"></i>
        </div>

        <div>

            <div class="title">
                {{ $transaction->transaction_number }}
            </div>

            <div class="meta">
                {{ $transaction->transaction_date->format('d M Y') }}
                ·
                {{ ucfirst($transaction->type) }}
                ·
                {{ ucfirst($transaction->status) }}
            </div>

        </div>

    </div>

    <div class="actions">

        <a
            href="{{ route('finance.transactions.index') }}"
            class="ui-btn"
        >
            <i class="bi bi-arrow-left"></i>
            Back
        </a>

        <button
            type="button"
            class="ui-btn"
            onclick="window.print()"
        >
            <i class="bi bi-printer"></i>
            Print
        </button>

    </div>

</div>


<div class="layout">

    <div>

        <div class="card">

            <div class="card-head">
                <strong>Transaction Overview</strong>
            </div>

            <div class="card-body">

                <div class="summary-grid">

                    <div class="summary-box highlight">

                        <label>
                            Amount
                        </label>

                        <strong class="{{ $isCredit ? 'credit' : 'debit' }}">

                            {{ $isCredit ? '+' : '-' }}

                            PKR {{ \App\Domain\Finance\Support\Money::format($transaction->amount) }}

                        </strong>

                    </div>

                    <div class="summary-box">

                        <label>
                            Direction
                        </label>

                        <strong>
                            {{ ucfirst($transaction->direction) }}
                        </strong>

                    </div>

                    <div class="summary-box">

                        <label>
                            Running Balance
                        </label>

                        <strong>
                            @if($runningBalance !== null)
                                PKR {{ \App\Domain\Finance\Support\Money::format($runningBalance) }}
                            @else
                                —
                            @endif
                        </strong>

                    </div>

                </div>

            </div>

        </div>


        <div class="card">

            <div class="card-head">
                <strong>Transaction Details</strong>
            </div>

            <div class="card-body">

                <div class="info-grid">

                    <div class="info">

                        <label>
                            Transaction Number
                        </label>

                        <strong>
                            {{ $transaction->transaction_number }}
                        </strong>

                    </div>

                    <div class="info">

                        <label>
                            Reference
                        </label>

                        <strong>
                            {{ $transaction->reference ?: '—' }}
                        </strong>

                    </div>

                    <div class="info">

                        <label>
                            Type
                        </label>

                        <strong>
                            {{ ucfirst($transaction->type) }}
                        </strong>

                    </div>

                    <div class="info">

                        <label>
                            Status
                        </label>

                        <strong>
                            {{ ucfirst($transaction->status) }}
                        </strong>

                    </div>

                    <div class="info">

                        <label>
                            Transaction Date
                        </label>

                        <strong>
                            {{ $transaction->transaction_date->format('d M Y') }}
                        </strong>

                    </div>

                    <div class="info">

                        <label>
                            Recorded By
                        </label>

                        <strong>
                            {{ $transaction->recordedBy?->name ?? 'System' }}
                        </strong>

                    </div>

                    <div class="info"
                         style="grid-column:1/-1;">

                        <label>
                            Description
                        </label>

                        <p>
                            {{ $transaction->description }}
                        </p>

                    </div>

                    @if($transaction->notes)

                        <div class="info"
                             style="grid-column:1/-1;">

                            <label>
                                Notes
                            </label>

                            <p>
                                {{ $transaction->notes }}
                            </p>

                        </div>

                    @endif

                </div>

            </div>

        </div>


        <div class="card">

            <div class="card-head">
                <strong>Fund</strong>
            </div>

            <div class="card-body">

                @if($transaction->fund)

                    <div class="fund-box">

                        <div class="fund-name">
                            {{ $transaction->fund->name }}
                        </div>

                        <div class="fund-reference">
                            {{ $transaction->fund->fund_reference }}
                        </div>

                        <div style="
                            margin-top:12px;
                            color:#68756e;
                            font-size:13px;
                        ">
                            Opening Balance:
                            <strong>
                                PKR {{ \App\Domain\Finance\Support\Money::format($transaction->fund->opening_balance) }}
                            </strong>
                        </div>

                    </div>

                @else

                    <div style="color:#89948f;">
                        No fund is attached to this transaction.
                    </div>

                @endif

            </div>

        </div>


        <div class="card">

            <div class="card-head">
                <strong>Source Record</strong>
            </div>

            <div class="card-body">

                @if($transaction->source)

                    <div>

                        <strong>
                            {{ ucfirst($transaction->type) }}
                        </strong>

                        <div style="
                            margin-top:5px;
                            color:#6d7571;
                            font-size:13px;
                        ">
                            {{ $transaction->reference }}
                        </div>

                    </div>

                    @if($sourceUrl)

                        <a
                            href="{{ $sourceUrl }}"
                            class="source-link"
                        >
                            <i class="bi bi-box-arrow-up-right"></i>
                            View Source Record
                        </a>

                    @endif

                @else

                    <div style="color:#89948f;">
                        Source record is unavailable.
                    </div>

                @endif

            </div>

        </div>

    </div>


    <aside>

        <div class="card">

            <div class="card-body">

                <div class="side-number">

                    {{ $isCredit ? '+' : '-' }}

                    PKR {{ \App\Domain\Finance\Support\Money::format($transaction->amount) }}

                </div>

                <div class="side-label">
                    {{ ucfirst($transaction->direction) }}
                </div>

                <div style="
                    height:1px;
                    background:#eef1ef;
                    margin:16px 0;
                "></div>

                <div class="side-row">
                    <span>Transaction</span>
                    <span>
                        {{ $transaction->transaction_number }}
                    </span>
                </div>

                <div class="side-row">
                    <span>Fund</span>
                    <span>
                        {{ $transaction->fund?->name ?? '—' }}
                    </span>
                </div>

                <div class="side-row">
                    <span>Type</span>
                    <span>
                        {{ ucfirst($transaction->type) }}
                    </span>
                </div>

                <div class="side-row">
                    <span>Status</span>
                    <span>
                        {{ ucfirst($transaction->status) }}
                    </span>
                </div>

                <div class="side-row">
                    <span>Date</span>
                    <span>
                        {{ $transaction->transaction_date->format('d M Y') }}
                    </span>
                </div>

                <div class="side-row">
                    <span>Balance After</span>
                    <span>
                        @if($runningBalance !== null)
                            PKR {{ \App\Domain\Finance\Support\Money::format($runningBalance) }}
                        @else
                            —
                        @endif
                    </span>
                </div>

            </div>

        </div>

    </aside>

</div>

@endsection
