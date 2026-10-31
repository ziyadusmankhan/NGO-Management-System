@extends('layouts.app')

@section('title', 'Finance Reports')

@push('styles')
<link rel="stylesheet" href="{{ asset('css/views/finance-reports-index-1.css') }}">
@endpush

@section('content')
@if($errors->any()) @include('shared.action-feedback') @endif

<div class="report-header">

    <div>
        <div class="report-title">
            Finance Reports
        </div>

        <div class="report-subtitle">
            Consolidated financial position, ledger movement, and fund balances.
        </div>
    </div>

    <div class="report-actions">
        @include('shared.finance-welfare-export', ['exportKind'=>'finance-reports'])


        <button
            type="button"
            class="report-btn"
            onclick="window.print()"
        >
            <i class="bi bi-printer"></i>
            Print
        </button>

        @if(auth()->user()?->hasPermission('finance.view'))<a
            href="{{ route('finance.transactions.index') }}"
            class="report-btn primary"
        >
            <i class="bi bi-journal-text"></i>
            View Ledger
        </a>@endif

    </div>

</div>


{{-- FILTERS --}}

<div class="card">

    <div class="card-head">

        <div>
            <strong>Report Filters</strong>

            <span>
                Filter ledger movement by fund and transaction date.
            </span>
        </div>

    </div>

    <div class="card-body">

        <form method="GET" action="{{ route('finance.reports.index') }}">

            <div class="filters">

                <div class="field">

                    <label for="fund_id">
                        Fund
                    </label>

                    <select name="fund_id" id="fund_id">

                        <option value="">
                            All Funds
                        </option>

                        @foreach($funds as $fund)

                            <option
                                value="{{ $fund->id }}"
                                @selected((string) $fundId === (string) $fund->id)
                            >
                                {{ $fund->name }}
                            </option>

                        @endforeach

                    </select>

                </div>


                <div class="field">

                    <label for="date_from">
                        From Date
                    </label>

                    <input
                        type="date"
                        name="date_from"
                        id="date_from"
                        value="{{ $dateFrom }}"
                    >

                </div>


                <div class="field">

                    <label for="date_to">
                        To Date
                    </label>

                    <input
                        type="date"
                        name="date_to"
                        id="date_to"
                        value="{{ $dateTo }}"
                    >

                </div>


                <div class="report-actions">

                    <button
                        type="submit"
                        class="report-btn primary"
                    >
                        <i class="bi bi-funnel"></i>
                        Apply
                    </button>

                    @if(auth()->user()?->hasPermission('finance.reports.view'))<a
                        href="{{ route('finance.reports.index') }}"
                        class="report-btn"
                    >
                        Reset
                    </a>@endif

                </div>

            </div>

        </form>

    </div>

</div>


{{-- SUMMARY --}}

<div class="card">

    <div class="card-head">

        <div>
            <strong>Financial Overview</strong>

            <span>
                Posted ledger movement for the selected report scope.
            </span>
        </div>

    </div>

    <div class="card-body">

        <div class="summary-grid">

            <div class="summary-box highlight">

                <label>
                    Current Balance
                </label>

                <strong>
                    PKR {{ \App\Domain\Finance\Support\Money::format($overview['current_balance']) }}
                </strong>

            </div>


            <div class="summary-box">

                <label>
                    Credits
                </label>

                <strong class="credit">
                    PKR {{ \App\Domain\Finance\Support\Money::format($overview['total_credits']) }}
                </strong>

            </div>


            <div class="summary-box">

                <label>
                    Debits
                </label>

                <strong class="debit">
                    PKR {{ \App\Domain\Finance\Support\Money::format($overview['total_debits']) }}
                </strong>

            </div>


            <div class="summary-box">

                <label>
                    Net Movement
                </label>

                <strong>
                    PKR {{ \App\Domain\Finance\Support\Money::format($overview['net_movement']) }}
                </strong>

            </div>


            <div class="summary-box">

                <label>
                    Restricted Balance
                </label>

                <strong>
                    PKR {{ \App\Domain\Finance\Support\Money::format($overview['restricted_balance']) }}
                </strong>

            </div>

        </div>

        <div
            class="table-note"
            style="margin-top:12px;"
        >
            {{ $overview['transaction_count'] }}
            posted transaction(s) are included in the selected movement period.
            Current Balance represents the fund's present ledger position.
        </div>

    </div>

</div>


{{-- FUND SUMMARY --}}

<div class="card">

    <div class="card-head">

        <div>
            <strong>Fund Summary</strong>

            <span>
                Current financial position of every active fund.
            </span>
        </div>

    </div>

    <div class="card-body" style="padding:0;">

        <div class="table-wrap">

            <table>

                <thead>

                    <tr>
                        <th>Fund</th>
                        <th>Type</th>
                        <th>Opening</th>
                        <th>Credits</th>
                        <th>Debits</th>
                        <th>Balance</th>
                    </tr>

                </thead>

                <tbody>

                    @forelse($fundRows as $row)

                        @php
                            $fund = $row['fund'];
                        @endphp

                        <tr>

                            <td>
                                @if(auth()->user()?->hasPermission('finance.view'))<a
                                    href="{{ route(
                                        'finance.funds.show',
                                        $fund
                                    ) }}"
                                    class="fund-link"
                                >
                                    {{ $fund->name }}
                                </a>@endif

                                <div class="table-note">
                                    {{ $fund->fund_reference }}
                                </div>
                            </td>

                            <td>
                                {{ ucfirst($fund->type) }}
                            </td>

                            <td>
                                PKR {{ \App\Domain\Finance\Support\Money::format($row['opening_balance']) }}
                            </td>

                            <td class="credit">
                                + PKR {{ \App\Domain\Finance\Support\Money::format($row['credits']) }}
                            </td>

                            <td class="debit">
                                - PKR {{ \App\Domain\Finance\Support\Money::format($row['debits']) }}
                            </td>

                            <td>
                                <strong>
                                    PKR {{ \App\Domain\Finance\Support\Money::format($row['balance']) }}
                                </strong>
                            </td>

                        </tr>

                    @empty

                        <tr>

                            <td colspan="6" class="empty">
                                No active funds found.
                            </td>

                        </tr>

                    @endforelse

                </tbody>

            </table>

        </div>

    </div>

</div>


{{-- RECENT LEDGER ACTIVITY --}}

<div class="card">

    <div class="card-head">

        <div>
            <strong>Ledger Activity</strong>

            <span>
                Recent posted transactions matching the selected report scope.
            </span>
        </div>

        @if(auth()->user()?->hasPermission('finance.view'))<a
            href="{{ route('finance.transactions.index') }}"
            class="report-btn"
        >
            View All
        </a>@endif

    </div>

    <div class="card-body" style="padding:0;">

        <div class="table-wrap">

            <table>

                <thead>

                    <tr>
                        <th>Date</th>
                        <th>Transaction</th>
                        <th>Fund</th>
                        <th>Type</th>
                        <th>Description</th>
                        <th>Amount</th>
                    </tr>

                </thead>

                <tbody>

                    @forelse($transactions as $transaction)

                        <tr>

                            <td>
                                {{ $transaction->transaction_date->format('d M Y') }}
                            </td>

                            <td>
                                @if(auth()->user()?->hasPermission('finance.view'))<a
                                    href="{{ route(
                                        'finance.transactions.show',
                                        $transaction
                                    ) }}"
                                    class="transaction-link"
                                >
                                    {{ $transaction->transaction_number }}
                                </a>@endif

                                @if($transaction->reference)

                                    <div class="table-note">
                                        {{ $transaction->reference }}
                                    </div>

                                @endif

                            </td>

                            <td>

                                @if($transaction->fund)

                                    @if(auth()->user()?->hasPermission('finance.view'))<a
                                        href="{{ route(
                                            'finance.funds.show',
                                            $transaction->fund
                                        ) }}"
                                        class="fund-link"
                                    >
                                        {{ $transaction->fund->name }}
                                    </a>@endif

                                @else
                                    —
                                @endif

                            </td>

                            <td>

                                <span class="badge">
                                    {{ ucfirst($transaction->type) }}
                                </span>

                            </td>

                            <td>
                                {{ $transaction->description }}
                            </td>

                            <td class="
                                {{ $transaction->direction === 'credit'
                                    ? 'credit'
                                    : 'debit'
                                }}
                            ">

                                {{ $transaction->direction === 'credit'
                                    ? '+'
                                    : '-'
                                }}

                                PKR {{ \App\Domain\Finance\Support\Money::format($transaction->amount) }}

                            </td>

                        </tr>

                    @empty

                        <tr>

                            <td colspan="6" class="empty">
                                No posted transactions match the selected filters.
                            </td>

                        </tr>

                    @endforelse

                </tbody>

            </table>

        </div>

    </div>

</div>

@endsection