@extends('layouts.app')

@section('title', 'Transactions')

@push('styles')
<link rel="stylesheet" href="{{ asset('css/views/finance-transactions-index-1.css') }}">
@endpush

@section('content')

<div class="page-header">

    <div>
        <h1>Transactions</h1>

        <p>
            Central financial ledger for fund credits, debits and posted movements.
        </p>
    </div>

</div>


<!-- SUMMARY -->

<div class="summary-grid">

    <div class="summary-card">

        <div class="summary-label">
            Transactions
        </div>

        <div class="summary-value">
            {{ number_format($transactionCount) }}
        </div>

        <div class="summary-meta">
            Posted ledger movements
        </div>

    </div>


    <div class="summary-card">

        <div class="summary-label">
            Credits
        </div>

        <div class="summary-value">
            PKR {{ \App\Domain\Finance\Support\Money::format($totalCredits) }}
        </div>

        <div class="summary-meta">
            Incoming fund movements
        </div>

    </div>


    <div class="summary-card">

        <div class="summary-label">
            Debits
        </div>

        <div class="summary-value">
            PKR {{ \App\Domain\Finance\Support\Money::format($totalDebits) }}
        </div>

        <div class="summary-meta">
            Outgoing fund movements
        </div>

    </div>


    <div class="summary-card">

        <div class="summary-label">
            Net Movement
        </div>

        <div class="summary-value">
            PKR {{ \App\Domain\Finance\Support\Money::format($netMovement) }}
        </div>

        <div class="summary-meta">
            Credits minus debits
        </div>

    </div>

</div>


<!-- FILTERS -->

<div class="main-card">

    <form
        method="GET"
        action="{{ route('finance.transactions.index') }}"
        class="toolbar"
    >

        <div class="search">

            <i class="bi bi-search"></i>

            <input
                name="search"
                value="{{ request('search') }}"
                placeholder="Search transaction, reference, fund or description..."
            >

        </div>


        <select
            name="fund_id"
            class="filter"
        >

            <option value="">
                All Funds
            </option>

            @foreach($funds as $fund)

                <option
                    value="{{ $fund->id }}"
                    @selected(
                        (string) request('fund_id')
                        ===
                        (string) $fund->id
                    )
                >
                    {{ $fund->name }}
                </option>

            @endforeach

        </select>


        <select
            name="type"
            class="filter"
        >

            <option value="">
                All Types
            </option>

            <option
                value="donation"
                @selected(request('type') === 'donation')
            >
                Donation
            </option>

            <option
                value="expense"
                @selected(request('type') === 'expense')
            >
                Expense
            </option>

            <option value="qard_disbursement" @selected(request('type') === 'qard_disbursement')>Qard Disbursement</option>
            <option value="business_disbursement" @selected(request('type') === 'business_disbursement')>Business Disbursement</option>
            <option value="recovery" @selected(request('type') === 'recovery')>Principal Recovery</option>
            <option value="welfare_assistance" @selected(request('type') === 'welfare_assistance')>Welfare Assistance</option>

        </select>


        <select
            name="direction"
            class="filter"
        >

            <option value="">
                Credit / Debit
            </option>

            <option
                value="credit"
                @selected(request('direction') === 'credit')
            >
                Credit
            </option>

            <option
                value="debit"
                @selected(request('direction') === 'debit')
            >
                Debit
            </option>

        </select>


        <input
            type="date"
            name="date_from"
            class="filter"
            value="{{ request('date_from') }}"
        >

        <input
            type="date"
            name="date_to"
            class="filter"
            value="{{ request('date_to') }}"
        >


        <button
            type="submit"
            class="ui-btn primary"
        >
            <i class="bi bi-search"></i>
            Filter
        </button>


        @if(request()->hasAny([
            'search',
            'fund_id',
            'type',
            'direction',
            'status',
            'date_from',
            'date_to'
        ]))

            @if(auth()->user()?->hasPermission('finance.view'))<a
                href="{{ route('finance.transactions.index') }}"
                class="ui-btn"
            >
                Clear
            </a>@endif

        @endif

    </form>


    <!-- TABLE -->

    <div class="table-wrap">

        <table class="transaction-table">

            <thead>

                <tr>
                    <th>Transaction</th>
                    <th>Date</th>
                    <th>Fund</th>
                    <th>Type</th>
                    <th>Description</th>
                    <th>Direction</th>
                    <th>Amount</th>
                    <th>Balance</th>
                    <th>Status</th>
                    <th></th>
                </tr>

            </thead>


            <tbody>

                @forelse($transactions as $transaction)

                    @php

                        $typeClass = match ($transaction->type) {
                            'donation' => 'donation',
                            'expense' => 'expense',
                            default => 'other',
                        };

                        $typeLabel = match ($transaction->type) {
                            'donation' => 'Donation',
                            'expense' => 'Expense',
                            default => ucfirst($transaction->type),
                        };

                    @endphp

                    <tr>

                        <td>

                            <div class="transaction-number">
                                {{ $transaction->transaction_number }}
                            </div>

                            @if($transaction->reference)

                                <div class="subtext">
                                    {{ $transaction->reference }}
                                </div>

                            @endif

                        </td>


                        <td>
                            {{ $transaction->transaction_date->format('d M Y') }}
                        </td>


                        <td>

                            @if($transaction->fund)

                                <div class="fund-name">
                                    {{ $transaction->fund->name }}
                                </div>

                                <div class="fund-reference">
                                    {{ $transaction->fund->fund_reference }}
                                </div>

                            @else
                                —
                            @endif

                        </td>


                        <td>

                            <span class="type-badge {{ $typeClass }}">
                                {{ $typeLabel }}
                            </span>

                        </td>


                        <td>
                            {{ $transaction->description }}
                        </td>


                        <td>

                            @if($transaction->direction === 'credit')

                                <span class="credit">
                                    Credit
                                </span>

                            @else

                                <span class="debit">
                                    Debit
                                </span>

                            @endif

                        </td>


                        <td>

                            <span class="{{ $transaction->direction === 'credit' ? 'credit' : 'debit' }}">

                                {{ $transaction->direction === 'credit' ? '+' : '-' }}

                                PKR {{ \App\Domain\Finance\Support\Money::format($transaction->amount) }}

                            </span>

                        </td>


                        <td class="balance">

                            @if($transaction->running_balance !== null)

                                PKR {{ \App\Domain\Finance\Support\Money::format($transaction->running_balance) }}

                            @else
                                —
                            @endif

                        </td>


                        <td>

                            <span class="status-badge">
                                {{ ucfirst($transaction->status) }}
                            </span>

                        </td>


                        <td>

                            @if(auth()->user()?->hasPermission('finance.view'))<a
                                href="{{ route('finance.transactions.show', $transaction) }}"
                                class="action"
                                title="View transaction"
                            >
                                <i class="bi bi-receipt"></i>
                            </a>@endif

                        </td>

                    </tr>

                @empty

                    <tr>

                        <td
                            colspan="10"
                            class="empty"
                        >

                            <i
                                class="bi bi-journal-x"
                                style="
                                    display:block;
                                    font-size:32px;
                                    margin-bottom:12px;
                                "
                            ></i>

                            <strong>
                                No transactions found.
                            </strong>

                            <div style="
                                margin-top:6px;
                                font-size:13px;
                            ">
                                Transactions will appear here when financial movements are posted.
                            </div>

                        </td>

                    </tr>

                @endforelse

            </tbody>

        </table>

    </div>


    <div class="table-footer">

        <div class="result">

            Showing
            {{ $transactions->firstItem() ?? 0 }}
            –
            {{ $transactions->lastItem() ?? 0 }}
            of
            {{ $transactions->total() }}
            transactions

        </div>


        <div>
            {{ $transactions->links() }}
        </div>

    </div>

</div>

<div style="
    margin-top:18px;
    display:flex;
    gap:10px;
    padding:14px 16px;
    background:var(--legacy-surface-f7f9f8, #f7f9f8);
    border:1px solid var(--legacy-border-e5ebe8, #e5ebe8);
    border-radius:13px;
">

    <i
        class="bi bi-shield-check"
        style="color:var(--primary);font-size:17px;"
    ></i>

    <div>

        <strong style="font-size:14px;">
            Transaction integrity
        </strong>

        <p style="
            margin:4px 0 0;
            color:var(--legacy-text-6b7670, #6b7670);
            font-size:13px;
            line-height:1.6;
        ">
            Transactions are generated from financial source records such as
            donations and approved expenses. This ledger should remain the
            central audit trail for fund movement.
        </p>

    </div>

</div>

@endsection
