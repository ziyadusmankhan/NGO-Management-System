@extends('layouts.app')

@section('title', 'Transactions')

@push('styles')
<style>
    .page-header {
        display:flex;
        justify-content:space-between;
        align-items:flex-end;
        gap:20px;
        margin-bottom:24px;
    }

    .page-header h1 {
        margin:0;
        font-size:25px;
        font-weight:700;
        letter-spacing:-.5px;
    }

    .page-header p {
        margin:7px 0 0;
        color:var(--muted);
        font-size:16px;
    }

    .summary-grid {
        display:grid;
        grid-template-columns:repeat(4,minmax(0,1fr));
        gap:12px;
        margin-bottom:18px;
    }

    .summary-card {
        background:#fff;
        border:1px solid var(--border);
        border-radius:13px;
        padding:15px;
    }

    .summary-label {
        color:var(--muted);
        font-size:14px;
    }

    .summary-value {
        margin-top:6px;
        font-size:20px;
        font-weight:700;
    }

    .summary-meta {
        margin-top:4px;
        color:#6d7572;
        font-size:13px;
    }

    .main-card {
        background:#fff;
        border:1px solid var(--border);
        border-radius:14px;
        overflow:hidden;
    }

    .toolbar {
        padding:15px 17px;
        border-bottom:1px solid #eef1ef;
        display:flex;
        gap:9px;
        flex-wrap:wrap;
    }

    .search {
        position:relative;
        flex:1;
        min-width:230px;
    }

    .search i {
        position:absolute;
        left:13px;
        top:50%;
        transform:translateY(-50%);
        color:#6f7672;
    }

    .search input {
        width:100%;
        height:40px;
        border:1px solid var(--border);
        border-radius:9px;
        padding:0 12px 0 36px;
        background:#fafcfa;
        outline:none;
    }

    .filter {
        height:40px;
        min-width:145px;
        border:1px solid var(--border);
        border-radius:9px;
        padding:0 12px;
        background:#fff;
        color:#68756f;
        font-size:14px;
        outline:none;
    }

    .ui-btn {
        height:40px;
        padding:0 14px;
        border-radius:10px;
        display:inline-flex;
        align-items:center;
        justify-content:center;
        gap:7px;
        font-size:15px;
        font-weight:650;
        border:1px solid var(--border);
        background:#fff;
        color:#637069;
        text-decoration:none;
        cursor:pointer;
    }

    .ui-btn.primary {
        background:var(--primary);
        border-color:var(--primary);
        color:#fff;
    }

    .table-wrap {
        overflow-x:auto;
    }

    .transaction-table {
        width:100%;
        min-width:1100px;
        border-collapse:collapse;
    }

    .transaction-table th {
        padding:11px 16px;
        background:#fafcfb;
        border-bottom:1px solid #eef1ef;
        color:#6c7470;
        font-size:12px;
        text-transform:uppercase;
        letter-spacing:.55px;
        text-align:left;
        white-space:nowrap;
    }

    .transaction-table td {
        padding:13px 16px;
        border-bottom:1px solid #f0f3f1;
        font-size:14px;
        vertical-align:middle;
    }

    .transaction-table tbody tr:hover {
        background:#fbfcfb;
    }

    .transaction-number {
        font-weight:650;
        color:#55635b;
    }

    .subtext {
        margin-top:3px;
        color:#89928e;
        font-size:12px;
    }

    .fund-name {
        font-weight:600;
        color:#58655f;
    }

    .fund-reference {
        margin-top:3px;
        color:#89928e;
        font-size:12px;
    }

    .type-badge,
    .status-badge {
        display:inline-flex;
        align-items:center;
        gap:5px;
        padding:5px 8px;
        border-radius:20px;
        font-size:12px;
        font-weight:650;
        white-space:nowrap;
    }

    .type-badge.donation {
        background:#edf7f0;
        color:#397955;
    }

    .type-badge.expense {
        background:#fff0ef;
        color:#a85f58;
    }

    .type-badge.other {
        background:#eef3f8;
        color:#55758d;
    }

    .status-badge {
        background:#edf7f0;
        color:#397955;
    }

    .credit {
        color:#397955;
        font-weight:700;
    }

    .debit {
        color:#a85f58;
        font-weight:700;
    }

    .balance {
        color:#4a5750;
        font-weight:650;
    }

    .action {
        width:30px;
        height:30px;
        border:1px solid var(--border);
        background:#fff;
        border-radius:8px;
        display:inline-flex;
        align-items:center;
        justify-content:center;
        color:#6c7771;
        text-decoration:none;
    }

    .empty {
        padding:50px;
        text-align:center;
        color:#89948f;
    }

    .table-footer {
        padding:14px 18px;
        border-top:1px solid #eef1ef;
        display:flex;
        justify-content:space-between;
        align-items:center;
        gap:10px;
    }

    .result {
        color:#6e7672;
        font-size:13px;
    }

    @media(max-width:900px) {
        .summary-grid {
            grid-template-columns:repeat(2,1fr);
        }
    }

    @media(max-width:650px) {
        .summary-grid {
            grid-template-columns:1fr;
        }

        .search {
            min-width:100%;
        }

        .filter {
            flex:1;
            min-width:0;
        }

        .table-footer {
            flex-direction:column;
            align-items:flex-start;
        }
    }
</style>
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

            <a
                href="{{ route('finance.transactions.index') }}"
                class="ui-btn"
            >
                Clear
            </a>

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

                            <a
                                href="{{ route('finance.transactions.show', $transaction) }}"
                                class="action"
                                title="View transaction"
                            >
                                <i class="bi bi-receipt"></i>
                            </a>

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
    background:#f7f9f8;
    border:1px solid #e5ebe8;
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
            color:#6b7670;
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
