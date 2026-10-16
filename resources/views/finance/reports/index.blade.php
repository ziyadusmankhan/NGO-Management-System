@extends('layouts.app')

@section('title', 'Finance Reports')

@push('styles')
<style>
    .report-header {
        display:flex;
        justify-content:space-between;
        align-items:flex-start;
        gap:20px;
        margin-bottom:22px;
    }

    .report-title {
        font-size:24px;
        font-weight:700;
        color:#26312c;
    }

    .report-subtitle {
        margin-top:5px;
        color:#6d7671;
        font-size:14px;
    }

    .report-actions {
        display:flex;
        gap:8px;
        flex-wrap:wrap;
    }

    .report-btn {
        display:inline-flex;
        align-items:center;
        justify-content:center;
        gap:7px;
        height:39px;
        padding:0 13px;
        border:1px solid var(--border);
        border-radius:9px;
        background:#fff;
        color:#5f6c66;
        text-decoration:none;
        font-size:14px;
        font-weight:650;
        cursor:pointer;
    }

    .report-btn.primary {
        background:var(--primary);
        border-color:var(--primary);
        color:#fff;
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
        font-size:16px;
    }

    .card-head span {
        display:block;
        margin-top:4px;
        color:#6c7470;
        font-size:13px;
    }

    .card-body {
        padding:18px 19px;
    }

    .summary-grid {
        display:grid;
        grid-template-columns:repeat(5,minmax(0,1fr));
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
        letter-spacing:.5px;
    }

    .summary-box strong {
        display:block;
        margin-top:6px;
        font-size:17px;
        color:#334039;
    }

    .filters {
        display:grid;
        grid-template-columns:1.2fr 1fr 1fr auto;
        gap:10px;
        align-items:end;
    }

    .field label {
        display:block;
        margin-bottom:6px;
        color:#66716b;
        font-size:13px;
        font-weight:650;
    }

    .field select,
    .field input {
        width:100%;
        height:40px;
        padding:0 10px;
        border:1px solid #dfe6e2;
        border-radius:8px;
        background:#fff;
        color:#39453f;
        outline:none;
    }

    .table-wrap {
        overflow-x:auto;
    }

    table {
        width:100%;
        min-width:850px;
        border-collapse:collapse;
    }

    th {
        padding:11px 12px;
        background:#fafcfb;
        border-bottom:1px solid #eef1ef;
        color:#6c7470;
        font-size:12px;
        text-transform:uppercase;
        letter-spacing:.5px;
        text-align:left;
        white-space:nowrap;
    }

    td {
        padding:12px;
        border-bottom:1px solid #f0f3f1;
        font-size:14px;
        vertical-align:middle;
    }

    tr:last-child td {
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

    .badge {
        display:inline-flex;
        padding:5px 8px;
        border-radius:20px;
        background:#edf7f0;
        color:#397955;
        font-size:12px;
        font-weight:650;
    }

    .fund-link,
    .transaction-link {
        color:var(--primary);
        text-decoration:none;
        font-weight:650;
    }

    .empty {
        padding:30px;
        text-align:center;
        color:#7a847f;
    }

    .table-note {
        color:#707b75;
        font-size:12px;
    }

    @media(max-width:1000px) {
        .summary-grid {
            grid-template-columns:repeat(2,1fr);
        }

        .filters {
            grid-template-columns:1fr 1fr;
        }
    }

    @media(max-width:700px) {
        .report-header {
            flex-direction:column;
        }

        .report-actions {
            width:100%;
        }

        .report-btn {
            flex:1;
        }

        .summary-grid,
        .filters {
            grid-template-columns:1fr;
        }
    }
</style>
@endpush

@section('content')

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

        <button
            type="button"
            class="report-btn"
            onclick="window.print()"
        >
            <i class="bi bi-printer"></i>
            Print
        </button>

        <a
            href="{{ route('finance.transactions.index') }}"
            class="report-btn primary"
        >
            <i class="bi bi-journal-text"></i>
            View Ledger
        </a>

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

                    <a
                        href="{{ route('finance.reports.index') }}"
                        class="report-btn"
                    >
                        Reset
                    </a>

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
                    PKR {{ number_format($overview['current_balance'], 2) }}
                </strong>

            </div>


            <div class="summary-box">

                <label>
                    Credits
                </label>

                <strong class="credit">
                    PKR {{ number_format($overview['total_credits'], 2) }}
                </strong>

            </div>


            <div class="summary-box">

                <label>
                    Debits
                </label>

                <strong class="debit">
                    PKR {{ number_format($overview['total_debits'], 2) }}
                </strong>

            </div>


            <div class="summary-box">

                <label>
                    Net Movement
                </label>

                <strong>
                    PKR {{ number_format($overview['net_movement'], 2) }}
                </strong>

            </div>


            <div class="summary-box">

                <label>
                    Restricted Balance
                </label>

                <strong>
                    PKR {{ number_format($overview['restricted_balance'], 2) }}
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
                                <a
                                    href="{{ route(
                                        'finance.funds.show',
                                        $fund
                                    ) }}"
                                    class="fund-link"
                                >
                                    {{ $fund->name }}
                                </a>

                                <div class="table-note">
                                    {{ $fund->fund_reference }}
                                </div>
                            </td>

                            <td>
                                {{ ucfirst($fund->type) }}
                            </td>

                            <td>
                                PKR {{ number_format(
                                    $row['opening_balance'],
                                    2
                                ) }}
                            </td>

                            <td class="credit">
                                + PKR {{ number_format(
                                    $row['credits'],
                                    2
                                ) }}
                            </td>

                            <td class="debit">
                                - PKR {{ number_format(
                                    $row['debits'],
                                    2
                                ) }}
                            </td>

                            <td>
                                <strong>
                                    PKR {{ number_format(
                                        $row['balance'],
                                        2
                                    ) }}
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

        <a
            href="{{ route('finance.transactions.index') }}"
            class="report-btn"
        >
            View All
        </a>

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
                                <a
                                    href="{{ route(
                                        'finance.transactions.show',
                                        $transaction
                                    ) }}"
                                    class="transaction-link"
                                >
                                    {{ $transaction->transaction_number }}
                                </a>

                                @if($transaction->reference)

                                    <div class="table-note">
                                        {{ $transaction->reference }}
                                    </div>

                                @endif

                            </td>

                            <td>

                                @if($transaction->fund)

                                    <a
                                        href="{{ route(
                                            'finance.funds.show',
                                            $transaction->fund
                                        ) }}"
                                        class="fund-link"
                                    >
                                        {{ $transaction->fund->name }}
                                    </a>

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

                                PKR {{ number_format(
                                    $transaction->amount,
                                    2
                                ) }}

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