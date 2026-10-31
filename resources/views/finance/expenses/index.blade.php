@extends('layouts.app')

@section('title', 'Expenses')

@push('styles')
<link rel="stylesheet" href="{{ asset('css/views/finance-expenses-index-1.css') }}">
@endpush


@section('content')
@if($errors->any()) @include('shared.action-feedback') @endif

<div class="page-header">

    <div>
        <h1>Expenses</h1>

        <p>
            Record, review and monitor organizational spending across funds and approved activities.
        </p>
    </div>

    <div class="actions">

        @include('shared.finance-welfare-export', ['exportKind'=>'expenses'])

        @if(auth()->user()->hasPermission('finance.expenses.manage'))

            @if(auth()->user()?->hasPermission('finance.view') && auth()->user()?->hasPermission('finance.expenses.manage'))<a
                href="{{ route('finance.expenses.create') }}"
                class="ui-btn primary"
            >
                <i class="bi bi-plus-lg"></i>
                New Expense
            </a>@endif

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

                @if(auth()->user()?->hasPermission('finance.view'))<a
                    href="{{ route('finance.expenses.index') }}"
                    class="ui-btn"
                >
                    Clear
                </a>@endif

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
                                        @if(auth()->user()?->hasPermission('finance.view'))<a
                                            href="{{ route('finance.transactions.show', $expense->transaction) }}"
                                            style="color:inherit;text-decoration:none;"
                                        >
                                            {{ $expense->transaction->transaction_number }}
                                        </a>@endif
                                    </div>

                                @else

                                    <span class="ledger-status none">
                                        No Entry
                                    </span>

                                @endif

                            </td>


                        <td>

                            @if(auth()->user()?->hasPermission('finance.view'))<a
                                href="{{ route('finance.expenses.show', $expense) }}"
                                class="action"
                                title="View expense"
                            >
                                <i class="bi bi-eye"></i>
                            </a>@endif

                        </td>

                    </tr>

                @empty

                    <tr>

                        <td
                            colspan="10"
                            style="
                                text-align:center;
                                padding:50px 20px;
                                color:var(--legacy-text-89948f, #89948f);
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

@include('projects.finance-payments')
@include('operations.procurement.finance-payments')
@endsection


