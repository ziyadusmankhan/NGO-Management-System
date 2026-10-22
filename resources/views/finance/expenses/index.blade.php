@extends('layouts.app')

@section('title', 'Expenses')

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
        font-size: 25px;
        font-weight:700;
        letter-spacing:-.5px;
    }

    .page-header p {
        margin:7px 0 0;
        color:var(--muted);
        font-size: 16px;
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
        font-size: 15px;
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

    .summary-grid {
        display:grid;
        grid-template-columns:repeat(5,minmax(0,1fr));
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
        font-size: 14px;
    }

    .summary-value {
        margin-top:6px;
        font-size: 20px;
        font-weight:700;
    }

    .summary-meta {
        margin-top:4px;
        color: #6d7572;
        font-size: 13px;
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
        color: #6f7672;
        font-size: 16px;
    }

    .search input {
        width:100%;
        height:40px;
        border:1px solid var(--border);
        border-radius:9px;
        padding:0 12px 0 36px;
        background:#fafcfa;
        outline:none;
        font-size: 15px;
    }

    .search input:focus {
        background:#fff;
        border-color: #6e7772;
        box-shadow:0 0 0 3px rgba(23,63,53,.06);
    }

    .filter {
        height:40px;
        min-width:145px;
        border:1px solid var(--border);
        border-radius:9px;
        padding:0 28px 0 12px;
        background:#fff;
        color:#68756f;
        font-size: 15px;
        outline:none;
    }

    .table-wrap {
        overflow-x:auto;
    }

    .expense-table {
        width:100%;
        min-width:1180px;
        border-collapse:collapse;
    }

    .expense-table th {
        padding:11px 18px;
        background:#fafcfb;
        border-bottom:1px solid #eef1ef;
        color: #6c7470;
        font-size: 13px;
        font-weight:700;
        text-transform:uppercase;
        letter-spacing:.6px;
        text-align:left;
        white-space:nowrap;
    }

    .expense-table td {
        padding:13px 18px;
        border-bottom:1px solid #f0f3f1;
        font-size: 14px;
        vertical-align:middle;
    }

    .expense-table tbody tr:hover {
        background:#fbfcfb;
    }

    .expense-table tbody tr:last-child td {
        border-bottom:none;
    }

    .expense-id {
        font-size: 14px;
        font-weight:650;
        color:#55635b;
    }
    .ledger-status {
    display:inline-flex;
    align-items:center;
    gap:5px;
    padding:5px 8px;
    border-radius:20px;
    font-size:13px;
    font-weight:650;
    white-space:nowrap;
    }

    .ledger-status.posted {
        background:#edf7f0;
        color:#397955;
    }

    .ledger-status.void {
        background:#f1eef7;
        color:#77668c;
    }

    .ledger-status.none {
        background:#f5f6f5;
        color:#7a8580;
    }

    .ledger-reference {
        margin-top:4px;
        color:#6e7772;
        font-size:12px;
    }

    .category {
        display:inline-flex;
        padding:5px 8px;
        border-radius:20px;
        background:#eef3f8;
        color:#55758d;
        font-size: 13px;
        font-weight:650;
        white-space:nowrap;
    }

    .category.welfare {
        background:#edf7f0;
        color:#397955;
    }

    .category.project {
        background:#f1eef7;
        color:#77668c;
    }

    .category.admin {
        background:#fff5e9;
        color: #976d38;
    }

    .amount {
        font-weight:700;
        color:#a85f58;
    }

    .fund {
        color:#68756f;
    }

    .payee {
        font-size: 14px;
        font-weight:600;
    }

    .reference {
        color: #6e7772;
        font-size: 13px;
    }

    .status {
        display:inline-flex;
        align-items:center;
        gap:5px;
        padding:5px 8px;
        border-radius:20px;
        font-size: 13px;
        font-weight:650;
        white-space:nowrap;
    }

    .status::before {
        content:"";
        width:5px;
        height:5px;
        border-radius:50%;
    }

    .status.paid {
        background:#edf7f0;
        color:#397955;
    }

    .status.paid::before {
        background:#4c9a6c;
    }

    .status.pending {
        background:#fff5e9;
        color: #976d38;
    }

    .status.pending::before {
        background:#d19a4d;
    }

    .status.review {
        background:#f1eef7;
        color:#77668c;
    }

    .status.review::before {
        background:#8c77a3;
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
        color: #6c7771;
        text-decoration:none;
    }

    .action:hover {
        color:var(--primary);
        background:#f7faf8;
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
        color: #6e7672;
        font-size: 13px;
    }

    .pagination {
        display:flex;
        gap:5px;
    }

    .page-btn {
        min-width:29px;
        height:29px;
        border:1px solid var(--border);
        background:#fff;
        border-radius:7px;
        color:#69756f;
        font-size: 13px;
    }

    .page-btn.active {
        background:var(--primary);
        border-color:var(--primary);
        color:#fff;
    }

    .notice {
        margin-top:18px;
        display:flex;
        gap:10px;
        padding:14px 16px;
        background:#f7f9f8;
        border:1px solid #e5ebe8;
        border-radius:13px;
    }

    .notice i {
        color:var(--primary);
        font-size: 17px;
    }

    .notice strong {
        display:block;
        font-size: 14px;
    }

    .notice p {
        margin:4px 0 0;
        color: #6b7670;
        font-size: 13px;
        line-height:1.6;
    }

    @media(max-width:1100px) {
        .summary-grid {
            grid-template-columns:repeat(3,1fr);
        }
    }

    @media(max-width:700px) {
        .page-header {
            flex-direction:column;
            align-items:flex-start;
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

    @media(max-width:450px) {
        .summary-grid {
            grid-template-columns:1fr;
        }
    }
</style>
@endpush


@section('content')

<div class="page-header">

    <div>
        <h1>Expenses</h1>

        <p>
            Record, review and monitor organizational spending across funds and approved activities.
        </p>
    </div>

    <div class="actions">

        <button class="ui-btn">
            <i class="bi bi-download"></i>
            Export
        </button>

        @if(auth()->user()->hasPermission('finance.expenses.manage'))

            <a
                href="{{ route('finance.expenses.create') }}"
                class="ui-btn primary"
            >
                <i class="bi bi-plus-lg"></i>
                New Expense
            </a>

        @endif

    </div>

</div>


<!-- SUMMARY -->

<!-- SUMMARY -->

<div class="summary-grid">

    <div class="summary-card">
        <div class="summary-label">This Month</div>

        <div class="summary-value">
            PKR {{ \App\Domain\Finance\Support\Money::format($thisMonth) }}
        </div>

        <div class="summary-meta">
            {{ now()->format('F Y') }}
        </div>
    </div>


    <div class="summary-card">
        <div class="summary-label">This Year</div>

        <div class="summary-value">
            PKR {{ \App\Domain\Finance\Support\Money::format($thisYear) }}
        </div>

        <div class="summary-meta">
            Posted ledger expenses
        </div>
    </div>


    <div class="summary-card">
        <div class="summary-label">Welfare</div>

        <div class="summary-value">
            PKR {{ \App\Domain\Finance\Support\Money::format($welfare) }}
        </div>

        <div class="summary-meta">
            Welfare expenses
        </div>
    </div>


    <div class="summary-card">
        <div class="summary-label">Projects</div>

        <div class="summary-value">
            PKR {{ \App\Domain\Finance\Support\Money::format($projects) }}
        </div>

        <div class="summary-meta">
            Community projects
        </div>
    </div>


    <div class="summary-card">
        <div class="summary-label">Pending</div>

        <div class="summary-value">
            {{ number_format($pending) }}
        </div>

        <div class="summary-meta">
            Awaiting review / approval
        </div>
    </div>

</div>


<div class="main-card">

    <div class="toolbar">

        <form
            method="GET"
            action="{{ route('finance.expenses.index') }}"
            style="
                display:flex;
                gap:9px;
                flex-wrap:wrap;
                width:100%;
            "
        >

            <div class="search">

                <i class="bi bi-search"></i>

                <input
                    id="expenseSearch"
                    name="search"
                    type="text"
                    value="{{ request('search') }}"
                    placeholder="Search expense ID, payee, fund or description..."
                >

            </div>


            <select
                id="categoryFilter"
                name="category"
                class="filter"
            >

                <option value="">
                    All Categories
                </option>

                <option
                    value="welfare"
                    @selected(request('category') === 'welfare')
                >
                    Welfare
                </option>

                <option
                    value="project"
                    @selected(request('category') === 'project')
                >
                    Community Project
                </option>

                <option
                    value="admin"
                    @selected(request('category') === 'admin')
                >
                    Administration
                </option>

            </select>


            <select
                id="fundFilter"
                name="fund_id"
                class="filter"
            >

                <option value="">
                    All Funds
                </option>

                @foreach($funds as $fund)
                

                    <option
                        value="{{ $fund->id }}"
                        @selected((string) request('fund_id') === (string) $fund->id)
                    >
                        {{ $fund->name }}
                    </option>

                @endforeach

            </select>


            <select
                id="statusFilter"
                name="status"
                class="filter"
            >

                <option value="">
                    All Statuses
                </option>

                <option
                    value="pending"
                    @selected(request('status') === 'pending')
                >
                    Pending
                </option>

                <option
                    value="approved"
                    @selected(request('status') === 'approved')
                >
                    Approved
                </option>

                <option
                    value="paid"
                    @selected(request('status') === 'paid')
                >
                    Paid
                </option>

                <option
                    value="rejected"
                    @selected(request('status') === 'rejected')
                >
                    Rejected
                </option>

            </select>


            <button
                type="submit"
                class="ui-btn primary"
            >
                <i class="bi bi-search"></i>
                Search
            </button>


            @if(request()->hasAny([
                'search',
                'category',
                'fund_id',
                'status'
            ]))

                <a
                    href="{{ route('finance.expenses.index') }}"
                    class="ui-btn"
                >
                    Clear
                </a>

            @endif

        </form>

    </div>


    <div class="table-wrap">

        <table class="expense-table">

            <thead>

                <tr>
                    <th>Expense</th>
                    <th>Category</th>
                    <th>Payee</th>
                    <th>Description</th>
                    <th>Fund</th>
                    <th>Amount</th>
                    <th>Date</th>
                    <th>Status</th>
                    <th>Ledger</th>
                    <th></th>
                </tr>

            </thead>


            <tbody id="expenseBody">

                @forelse($expenses as $expense)

                    @php

                        $categoryLabel = match ($expense->category) {
                            'welfare' => 'Welfare',
                            'project' => 'Community Project',
                            'admin' => 'Administration',
                            default => ucfirst(
                                str_replace('_', ' ', $expense->category)
                            ),
                        };

                        $categoryClass = match ($expense->category) {
                            'welfare' => 'welfare',
                            'project' => 'project',
                            'admin' => 'admin',
                            default => '',
                        };

                        $statusLabel = match ($expense->status) {
                            'paid' => 'Paid',
                            'pending' => 'Pending',
                            'approved' => 'Approved',
                            'rejected' => 'Rejected',
                            default => ucfirst($expense->status),
                        };

                        $statusClass = match ($expense->status) {
                            'paid' => 'paid',
                            'pending' => 'pending',
                            'approved' => 'paid',
                            'rejected' => 'review',
                            default => 'pending',
                        };

                    @endphp

                    <tr>

                        <td>

                            <div class="expense-id">
                                {{ $expense->expense_number }}
                            </div>

                            @if($expense->transaction_reference)

                                <div class="reference">
                                    {{ $expense->transaction_reference }}
                                </div>

                            @endif

                        </td>


                        <td>

                            <span class="category {{ $categoryClass }}">
                                {{ $categoryLabel }}
                            </span>

                        </td>


                        <td class="payee">
                            {{ $expense->payee_name }}
                        </td>


                        <td>
                            {{ $expense->description }}
                        </td>


                        <td class="fund">

                            {{ $expense->fund?->name ?? 'Unassigned' }}

                            @if($expense->fund)

                                <div class="reference">
                                    {{ $expense->fund->fund_reference }}
                                </div>

                            @endif

                        </td>


                        <td class="amount">
                            PKR {{ \App\Domain\Finance\Support\Money::format($expense->amount) }}
                        </td>


                        <td>
                            {{ $expense->expense_date->format('d M Y') }}
                        </td>


                        <td>

                            <span class="status {{ $statusClass }}">
                                {{ $statusLabel }}
                            </span>

                        </td>
                        <td>

                                @if($expense->transaction)

                                    @php
                                        $ledgerStatus = $expense->transaction->status;

                                        $ledgerStatusLabel = match ($ledgerStatus) {
                                            'posted' => 'Posted',
                                            'void' => 'Voided',
                                            default => ucfirst($ledgerStatus),
                                        };

                                        $ledgerStatusClass = match ($ledgerStatus) {
                                            'posted' => 'posted',
                                            'void' => 'void',
                                            default => 'none',
                                        };
                                    @endphp

                                    <span class="ledger-status {{ $ledgerStatusClass }}">
                                        {{ $ledgerStatusLabel }}
                                    </span>

                                    <div class="ledger-reference">
                                        <a
                                            href="{{ route('finance.transactions.show', $expense->transaction) }}"
                                            style="color:inherit;text-decoration:none;"
                                        >
                                            {{ $expense->transaction->transaction_number }}
                                        </a>
                                    </div>

                                @else

                                    <span class="ledger-status none">
                                        No Entry
                                    </span>

                                @endif

                            </td>


                        <td>

                            <a
                                href="{{ route('finance.expenses.show', $expense) }}"
                                class="action"
                                title="View expense"
                            >
                                <i class="bi bi-eye"></i>
                            </a>

                        </td>

                    </tr>

                @empty

                    <tr>

                        <td
                            colspan="10"
                            style="
                                text-align:center;
                                padding:50px 20px;
                                color:#89948f;
                            "
                        >

                            <i
                                class="bi bi-receipt"
                                style="
                                    display:block;
                                    font-size:32px;
                                    margin-bottom:12px;
                                "
                            ></i>

                            <strong>
                                No expenses found.
                            </strong>

                            <div style="
                                margin-top:6px;
                                font-size:13px;
                            ">
                                Create an expense or change your search/filter criteria.
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
            {{ $expenses->firstItem() ?? 0 }}
            –
            {{ $expenses->lastItem() ?? 0 }}
            of
            {{ $expenses->total() }}
            expenses

        </div>


        <div>

            {{ $expenses->links() }}

        </div>

    </div>

</div>


<div class="notice">

    <i class="bi bi-shield-check"></i>

    <div>

        <strong>
            Expense control
        </strong>

        <p>
            Every expense should remain linked to a fund, approved purpose,
            payee, supporting evidence and authorization record. Restricted
            fund resources should only be used for their permitted purpose.
        </p>

    </div>

</div>

@endsection


