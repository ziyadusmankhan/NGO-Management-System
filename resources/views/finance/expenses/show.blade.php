@extends('layouts.app')

@section('title', 'Expense Details')

@push('styles')
<link rel="stylesheet" href="{{ asset('css/views/finance-expenses-show-1.css') }}">
@endpush


@section('content')
@php
    $categoryLabel = match ($expense->category) {
        'welfare' => 'Welfare',
        'project' => 'Community Project',
        'admin' => 'Administration',
        'education' => 'Education',
        'medical' => 'Medical',
        'other' => 'Other',
        default => ucfirst(
            str_replace('_', ' ', $expense->category)
        ),
    };

    $paymentLabel = match ($expense->payment_method) {
        'cash' => 'Cash',
        'bank' => 'Bank Transfer',
        'mobile' => 'Mobile Payment',
        'vendor' => 'Direct Vendor',
        default => ucfirst($expense->payment_method),
    };

    $statusLabel = ucfirst($expense->status);

    $statusClass = match ($expense->status) {
        'paid', 'approved' => 'paid',
        'pending' => 'pending',
        'rejected' => 'review',
        default => 'pending',
    };
@endphp

<div class="page-header">

    <div class="identity">

        <div class="icon-box">
            <i class="bi bi-receipt"></i>
        </div>

        <div>

            <div class="title">
                {{ $expense->expense_number }}
            </div>

            <div class="meta">
                {{ $expense->payee_name }}
                ·
                {{ $categoryLabel }}
                ·
                {{ $expense->expense_date->format('d M Y') }}
            </div>

        </div>

    </div>

    <div class="actions">
        @if($expense->status === 'pending' && auth()->user()->hasPermission('finance.expenses.approve'))

            <form
                method="POST"
                action="{{ route('finance.expenses.approve', $expense) }}"
                style="display:inline;"
            >
                @csrf

                <button
                    type="submit"
                    class="ui-btn primary"
                    onclick="return confirm('Approve this expense?')"
                >
                    <i class="bi bi-check-circle"></i>
                    Approve
                </button>
            </form>

            <form
                method="POST"
                action="{{ route('finance.expenses.reject', $expense) }}"
                style="display:inline;"
            >
                @csrf

                <button
                    type="submit"
                    class="ui-btn"
                    style="color:#a85f58;"
                    onclick="return confirm('Reject this expense?')"
                >
                    <i class="bi bi-x-circle"></i>
                    Reject
                </button>
            </form>

        @endif
        @if($expense->status === 'approved' && auth()->user()->hasPermission('finance.expenses.approve'))

                <form
                    method="POST"
                    action="{{ route('finance.expenses.paid', $expense) }}"
                    style="display:inline;"
                >
                    @csrf

                    <button
                        type="submit"
                        class="ui-btn primary"
                        onclick="return confirm('Mark this expense as paid?')"
                    >
                        <i class="bi bi-cash-coin"></i>
                        Mark Paid
                    </button>
                </form>

                <form
                    method="POST"
                    action="{{ route('finance.expenses.reject', $expense) }}"
                    style="display:inline;"
                >
                    @csrf

                    <button
                        type="submit"
                        class="ui-btn"
                        style="color:#a85f58;"
                        onclick="return confirm('Reject this expense?')"
                    >
                        <i class="bi bi-x-circle"></i>
                        Reject
                    </button>
                </form>

            @endif

        @if(auth()->user()?->hasPermission('finance.view'))<a
            href="{{ route('finance.expenses.index') }}"
            class="ui-btn"
        >
            <i class="bi bi-arrow-left"></i>
            Back
        </a>@endif

        <button
            class="ui-btn"
            onclick="window.print()"
        >
            <i class="bi bi-printer"></i>
            Print
        </button>

        @if($expense->status !== 'paid' && auth()->user()->hasPermission('finance.expenses.manage'))

            @if(auth()->user()?->hasPermission('finance.view') && auth()->user()?->hasPermission('finance.expenses.manage'))<a
                href="{{ route('finance.expenses.edit', $expense) }}"
                class="ui-btn primary"
            >
                <i class="bi bi-pencil"></i>
                Edit
            </a>@endif

        @endif

    </div>

</div>


<div class="layout">

    <div>

        <!-- OVERVIEW -->

        <div class="card">

            <div class="card-head">

                <div>
                    <strong>Expense Overview</strong>

                    <span>
                        Complete record of this expenditure.
                    </span>
                </div>

                <span class="status {{ $statusClass }}">
                    {{ $statusLabel }}
                </span>

            </div>

            <div class="card-body">

                <div class="summary-grid">

                    <div class="summary-box highlight">
                        <label>Expense Amount</label>
                        <strong>
                            PKR {{ \App\Domain\Finance\Support\Money::format($expense->amount) }}
                        </strong>
                    </div>

                    <div class="summary-box">
                        <label>Expense ID</label>
                        <strong>
                            {{ $expense->expense_number }}
                        </strong>
                    </div>

                    <div class="summary-box">
                        <label>Reference</label>
                        <strong>
                            {{ $expense->transaction_reference ?: '—' }}
                        </strong>
                    </div>

                    <div class="summary-box">
                        <label>Date</label>
                        <strong>
                            {{ $expense->expense_date->format('d M Y') }}
                        </strong>
                    </div>

                    <div class="summary-box">
                        <label>Category</label>
                        <strong>
                            {{ $categoryLabel }}
                        </strong>
                    </div>

                    <div class="summary-box">
                        <label>Fund</label>
                        <strong>
                            {{ $fund?->name ?? 'Unassigned' }}
                        </strong>
                    </div>

                    <div class="summary-box">
                        <label>Payment</label>
                        <strong>
                            {{ $paymentLabel }}
                        </strong>
                    </div>

                    <div class="summary-box">
                        <label>Status</label>
                        <strong>
                            <span class="status {{ $statusClass }}">
                                {{ $statusLabel }}
                            </span>
                        </strong>
                    </div>

                </div>

            </div>

        </div>


        <!-- EXPENSE DETAILS -->

        <div class="card">

            <div class="card-head">

                <div>
                    <strong>Expense Details</strong>

                    <span>
                        Purpose, payee and transaction information.
                    </span>
                </div>

            </div>

            <div class="card-body">

                <div class="info-grid">

                    <div class="info">
                        <label>Payee / Supplier</label>

                        <strong>
                            {{ $expense->payee_name }}
                        </strong>
                    </div>

                    <div class="info">
                        <label>Expense Category</label>

                        <strong>
                            {{ $categoryLabel }}
                        </strong>
                    </div>

                    <div class="info">
                        <label>Expense Purpose</label>

                        <strong>
                            {{ $expense->purpose_notes ?: '—' }}
                        </strong>
                    </div>

                    <div class="info">
                        <label>Payment Method</label>

                        <strong>
                            {{ $paymentLabel }}
                        </strong>
                    </div>

                    <div class="info">
                        <label>Transaction Reference</label>

                        <strong>
                            {{ $expense->transaction_reference ?: '—' }}
                        </strong>
                    </div>
                    <div class="info">
                        <label>Ledger Transaction</label>

                        <strong>
                            @if($expense->transaction)
                                @if(auth()->user()?->hasPermission('finance.view'))<a
                                    href="{{ route('finance.transactions.show', $expense->transaction) }}"
                                    style="color:var(--primary); text-decoration:none;"
                                >
                                    {{ $expense->transaction->reference }}
                                </a>@endif

                                · {{ ucfirst($expense->transaction->status) }}
                            @else
                                —
                            @endif
                        </strong>
                    </div>

                    <div class="info">
                        <label>Receipt Number</label>

                        <strong>
                            {{ $expense->receipt_number ?: '—' }}
                        </strong>
                    </div>

                    <div class="info">
                        <label>Expense Date</label>

                        <strong>
                            {{ $expense->expense_date->format('d M Y') }}
                        </strong>
                    </div>

                    <div class="info">
                        <label>Recorded By</label>

                        <strong>
                            {{ $expense->recordedBy?->name ?? 'System' }}
                        </strong>
                    </div>

                    <div class="info full">
                        <label>Description</label>

                        <p>
                            {{ $expense->description }}
                        </p>
                    </div>

                </div>

            </div>

        </div>


        <!-- FUND IMPACT -->

        <div class="card">

            <div class="card-head">

                <div>
                    <strong>Fund Impact</strong>

                    <span>
                        How this expense affected the associated fund.
                    </span>
                </div>

            </div>

            <div class="card-body">

                <div class="fund-box">

                    <div class="fund-top">

                        <div class="fund-name">
                            {{ $fund?->name ?? 'No Fund Assigned' }}
                        </div>

                        @if($fund)
                            <div class="fund-type">
                                {{ ucfirst($fund->type) }}
                            </div>
                        @endif

                    </div>

                    <div class="fund-meta">

                        <div class="fund-stat">
                            <label>Balance Before</label>

                            <strong>
                                PKR {{ \App\Domain\Finance\Support\Money::format($balanceBefore) }}
                            </strong>
                        </div>

                        <div class="fund-stat">
                            <label>Expense</label>

                            <strong>
                                PKR {{ \App\Domain\Finance\Support\Money::format($expense->amount) }}
                            </strong>
                        </div>

                        <div class="fund-stat">
                            <label>Balance After</label>

                            <strong>
                                PKR {{ \App\Domain\Finance\Support\Money::format($balanceAfter) }}
                            </strong>
                        </div>

                    </div>

                </div>

            </div>

        </div>


        <!-- APPROVAL -->

        <div class="card">

            <div class="card-head">

                <div>
                    <strong>Approval & Authorization</strong>

                    <span>
                        Record of the controls applied to this expense.
                    </span>
                </div>

            </div>

            <div class="card-body">

                <div class="approval-grid">

                    <div class="approval-box">
                        <label>Expense Status</label>

                        <strong>
                            {{ $statusLabel }}
                        </strong>
                    </div>

                    <div class="approval-box">
                        <label>Supporting Document</label>

                        <strong>
                            {{ $expense->has_supporting_document ? 'Verified' : 'Not Recorded' }}
                        </strong>
                    </div>

                    <div class="approval-box">
                        <label>Authorized By</label>

                        <strong>
                            {{ $expense->approvedBy?->name ?? 'Not approved' }}
                        </strong>
                    </div>

                    <div class="approval-box">
                        <label>Approval Date</label>

                        <strong>
                            {{ $expense->approved_at
                                ? $expense->approved_at->format('d M Y')
                                : '—'
                            }}
                        </strong>
                    </div>

                    <div class="approval-box">
                        <label>Recorded By</label>

                        <strong>
                            {{ $expense->recordedBy?->name ?? 'System' }}
                        </strong>
                    </div>

                    <div class="approval-box">
                        <label>Payment Status</label>

                        <strong>
                            {{ $statusLabel }}
                        </strong>
                    </div>

                </div>

            </div>

        </div>


        <!-- DOCUMENTS -->

        <div class="card">

            <div class="card-head">

                <div>
                    <strong>Supporting Documents</strong>

                    <span>
                        Evidence associated with the expense.
                    </span>
                </div>

            </div>

            <div class="card-body">

                @if($expense->has_supporting_document)

                    <div class="document-row">

                        <div class="document-icon">
                            <i class="bi bi-file-earmark-check"></i>
                        </div>

                        <div class="document-info">

                            <strong>
                                Supporting Document
                            </strong>

                            <span>
                                Document verified for this expense
                            </span>

                        </div>

                    </div>

                @else

                    <div style="
                        padding:20px 0;
                        color:#89948f;
                        text-align:center;
                        font-size:14px;
                    ">
                        No supporting document has been recorded.
                    </div>

                @endif

            </div>

        </div>


        <!-- FINANCIAL TRACE -->

        <div class="card">

            <div class="card-head">

                <div>
                    <strong>Financial Trace</strong>

                    <span>
                        Trace the expense from fund to approved activity.
                    </span>
                </div>

            </div>

            <div class="card-body">

                <div class="trace">

                <div class="trace-step">
                    <div class="trace-icon">
                        <i class="bi bi-wallet2"></i>
                    </div>

                    <strong>
                        {{ $fund?->name ?? 'No Fund Assigned' }}
                    </strong>

                    <span>
                        PKR {{ \App\Domain\Finance\Support\Money::format($balanceBefore) }} before
                    </span>
                </div>

                <div class="trace-arrow">
                    <i class="bi bi-arrow-right"></i>
                </div>

                <div class="trace-step">
                    <div class="trace-icon">
                        <i class="bi bi-receipt"></i>
                    </div>

                    <strong>
                        Expense
                    </strong>

                    <span>
                        PKR {{ \App\Domain\Finance\Support\Money::format($expense->amount) }}
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
                        {{ $categoryLabel }}
                    </strong>

                    <span>
                        {{ $expense->purpose_notes ?: 'Recorded expenditure' }}
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
                        Fund Balance
                    </strong>

                    <span>
                        PKR {{ \App\Domain\Finance\Support\Money::format($balanceAfter) }} after
                    </span>
                </div>

            </div>

            </div>

        </div>


        <!-- ACTIVITY -->

        <div class="card">

            <div class="card-head">

                <div>
                    <strong>Expense Activity</strong>

                    <span>
                        Important events associated with the expense.
                    </span>
                </div>

            </div>

            <div class="card-body">

                @if($expense->status === 'paid')

                    <div class="timeline-item">
                        <div class="timeline-dot"></div>

                        <div>
                            <div class="timeline-title">
                                Expense paid
                            </div>

                            <div class="timeline-meta">
                                {{ $expense->updated_at?->format('d M Y H:i') ?? '—' }}
                                ·
                                {{ $expense->approvedBy?->name ?? $expense->recordedBy?->name ?? 'System' }}
                            </div>

                            <div class="timeline-note">
                                PKR {{ \App\Domain\Finance\Support\Money::format($expense->amount) }}
                                recorded as paid
                                {{ $fund ? 'from ' . $fund->name : '' }}.
                            </div>
                        </div>
                    </div>

                @endif

                @if(in_array($expense->status, ['approved', 'paid'], true))

                    <div class="timeline-item">
                        <div class="timeline-dot"></div>

                        <div>
                            <div class="timeline-title">
                                Expense approved
                            </div>

                            <div class="timeline-meta">
                                {{ $expense->approved_at
                                    ? $expense->approved_at->format('d M Y H:i')
                                    : '—' }}
                                ·
                                {{ $expense->approvedBy?->name ?? 'System' }}
                            </div>

                            <div class="timeline-note">
                                The expense was authorized for financial processing.
                            </div>
                        </div>
                    </div>

                @endif

                @if($expense->status === 'rejected')

                    <div class="timeline-item">
                        <div class="timeline-dot"></div>

                        <div>
                            <div class="timeline-title">
                                Expense rejected
                            </div>

                            <div class="timeline-meta">
                                {{ $expense->updated_at?->format('d M Y H:i') ?? '—' }}
                            </div>

                            <div class="timeline-note">
                                This expense was rejected and has no active posted
                                financial transaction.
                            </div>
                        </div>
                    </div>

                @endif

                <div class="timeline-item">
                    <div class="timeline-dot"></div>

                    <div>
                        <div class="timeline-title">
                            Expense created
                        </div>

                        <div class="timeline-meta">
                            {{ $expense->created_at?->format('d M Y H:i') ?? '—' }}
                            ·
                            {{ $expense->recordedBy?->name ?? 'System' }}
                        </div>

                        <div class="timeline-note">
                            Expense {{ $expense->expense_number }} was recorded
                            and entered into the approval workflow.
                        </div>
                    </div>
                </div>

            </div>

        </div>


        <!-- CONTROL -->

        <div class="card">

            <div class="card-head">

                <div>
                    <strong>Financial Control</strong>

                    <span>
                        Administrative notes and audit protection.
                    </span>
                </div>

            </div>

            <div class="card-body">

                <div class="notice">

                    <i class="bi bi-shield-check"></i>

                    <p>
                        This expense is linked to the General Fund, its
                        approved welfare purpose, payment reference and
                        supporting documents. The financial trail remains
                        identifiable for later reconciliation and reporting.
                    </p>

                </div>

            </div>

        </div>

    </div>


    <aside>

        <div class="card">

            <div class="card-body">

                <div class="side-number">
                    PKR {{ \App\Domain\Finance\Support\Money::format($expense->amount) }}
                </div>

                <div class="side-label">
                    Expense Amount
                </div>

                <div style="
                    height:1px;
                    background:#eef1ef;
                    margin:16px 0;
                "></div>

                <div class="side-row">
                    <span>Status</span>
                    <span>{{ $statusLabel }}</span>
                </div>

                <div class="side-row">
                    <span>Category</span>
                    <span>{{ $categoryLabel }}</span>
                </div>

                <div class="side-row">
                    <span>Fund</span>
                    <span>{{ $fund?->name ?? 'Unassigned' }}</span>
                </div>

                <div class="side-row">
                    <span>Payee</span>
                    <span>{{ $expense->payee_name }}</span>
                </div>

                <div class="side-row">
                    <span>Date</span>
                    <span>{{ $expense->expense_date->format('d M Y') }}</span>
                </div>

            </div>

        </div>


        <div class="card">

            <div class="card-head">
                <strong>Fund Control</strong>
            </div>

            <div class="card-body">

                <div class="notice">

                    <i class="bi bi-shield-check"></i>

                    <p>
                        The expense has been recorded against an identified
                        fund and approved purpose. Supporting evidence and
                        authorization should remain attached to the record.
                    </p>

                </div>

            </div>

        </div>

    </aside>

</div>

@endsection