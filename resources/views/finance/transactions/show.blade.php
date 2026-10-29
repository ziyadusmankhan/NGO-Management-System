@extends('layouts.app')

@section('title', 'Transaction Details')

@push('styles')
<link rel="stylesheet" href="{{ asset('css/views/finance-transactions-show-1.css') }}">
@endpush

@section('content')

@php
    $isCredit = $transaction->direction === 'credit';

    $sourceUrl = null;

    if ($transaction->source) {
        if ($transaction->source instanceof \App\Domain\Procurement\Models\ProcurementMovement) {
            $sourceUrl=auth()->user()->hasPermission('procurement.view')?route('operations.procurement.show',$transaction->source->procurement):null;
        } elseif ($transaction->source instanceof \App\Domain\Projects\Models\ProjectMovement) {
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

        @if(auth()->user()?->hasPermission('finance.view'))<a
            href="{{ route('finance.transactions.index') }}"
            class="ui-btn"
        >
            <i class="bi bi-arrow-left"></i>
            Back
        </a>@endif

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
                            color:var(--legacy-text-68756e, #68756e);
                            font-size:13px;
                        ">
                            Opening Balance:
                            <strong>
                                PKR {{ \App\Domain\Finance\Support\Money::format($transaction->fund->opening_balance) }}
                            </strong>
                        </div>

                    </div>

                @else

                    <div style="color:var(--legacy-text-89948f, #89948f);">
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
                            color:var(--legacy-text-6d7571, #6d7571);
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

                    <div style="color:var(--legacy-text-89948f, #89948f);">
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
                    background:var(--legacy-surface-eef1ef, #eef1ef);
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
