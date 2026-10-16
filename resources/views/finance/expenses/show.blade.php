@extends('layouts.app')

@section('title', 'Expense Details')

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
        font-size: 13px;
        font-weight:650;
        white-space:nowrap;
    }

    .status.paid {
        background:#edf7f0;
        color:#397955;
    }

    .status.paid::before,
    .status.pending::before,
    .status.review::before {
        content:"";
        width:5px;
        height:5px;
        border-radius:50%;
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

    /* INFO */

    .info-grid {
        display:grid;
        grid-template-columns:repeat(2,minmax(0,1fr));
        gap:17px;
    }

    .info label {
        display:block;
        margin-bottom:5px;
        color: #6d7571;
        font-size: 13px;
        text-transform:uppercase;
        letter-spacing:.5px;
    }

    .info strong {
        display:block;
        color:#46534c;
        font-size: 15px;
    }

    .info p {
        margin:0;
        color:#68756e;
        font-size: 14px;
        line-height:1.65;
    }

    .full {
        grid-column:1/-1;
    }

    /* FUND */

    .fund-box {
        padding:15px;
        border:1px solid #dce7e1;
        background:#f7faf8;
        border-radius:11px;
    }

    .fund-top {
        display:flex;
        justify-content:space-between;
        align-items:center;
        gap:10px;
    }

    .fund-name {
        color:var(--primary);
        font-size: 16px;
        font-weight:700;
    }

    .fund-type {
        padding:5px 8px;
        border-radius:20px;
        background:#eef3f8;
        color:#55758d;
        font-size: 13px;
        font-weight:650;
    }

    .fund-meta {
        display:grid;
        grid-template-columns:repeat(3,1fr);
        gap:10px;
        margin-top:12px;
    }

    .fund-stat {
        padding:10px;
        background:#fff;
        border:1px solid #e7edea;
        border-radius:8px;
    }

    .fund-stat label {
        display:block;
        color: #6d7571;
        font-size: 12px;
        text-transform:uppercase;
    }

    .fund-stat strong {
        display:block;
        margin-top:4px;
        font-size: 15px;
    }

    /* APPROVAL */

    .approval-grid {
        display:grid;
        grid-template-columns:repeat(2,minmax(0,1fr));
        gap:10px;
    }

    .approval-box {
        padding:13px;
        background:#fafcfb;
        border:1px solid #edf1ee;
        border-radius:10px;
    }

    .approval-box label {
        display:block;
        color: #6d7571;
        font-size: 13px;
        text-transform:uppercase;
    }

    .approval-box strong {
        display:block;
        margin-top:5px;
        font-size: 15px;
    }

    /* DOCUMENTS */

    .document-row {
        display:flex;
        align-items:center;
        gap:10px;
        padding:11px 0;
        border-bottom:1px solid #f0f3f1;
    }

    .document-row:last-child {
        border-bottom:none;
    }

    .document-icon {
        width:34px;
        height:34px;
        border-radius:9px;
        background:#f4f7f5;
        color:var(--primary);
        display:flex;
        align-items:center;
        justify-content:center;
    }

    .document-info {
        flex:1;
    }

    .document-info strong {
        display:block;
        font-size: 14px;
    }

    .document-info span {
        display:block;
        margin-top:3px;
        color: #6c7470;
        font-size: 13px;
    }

    .doc-btn {
        width:29px;
        height:29px;
        border:1px solid var(--border);
        background:#fff;
        border-radius:7px;
        color: #6b766f;
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
        padding:8px 10px;
        background:#fafcfb;
        border:1px solid #edf1ee;
        border-radius:8px;
        color:#6d7972;
        font-size: 13px;
        line-height:1.5;
    }

    /* FINANCIAL TRACE */

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

    /* NOTICE */

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

    /* SIDEBAR */

    .side-number {
        text-align:center;
        font-size: 30px;
        font-weight:700;
        color:#a85f58;
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

        .summary-grid,
        .info-grid,
        .approval-grid,
        .fund-meta {
            grid-template-columns:1fr;
        }

        .full {
            grid-column:auto;
        }
    }
</style>
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

        <a
            href="{{ route('finance.expenses.index') }}"
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

        @if($expense->status !== 'paid' && auth()->user()->hasPermission('finance.expenses.manage'))

            <a
                href="{{ route('finance.expenses.edit', $expense) }}"
                class="ui-btn primary"
            >
                <i class="bi bi-pencil"></i>
                Edit
            </a>

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
                            PKR {{ number_format($expense->amount, 0) }}
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
                                <a
                                    href="{{ route('finance.transactions.show', $expense->transaction) }}"
                                    style="color:var(--primary); text-decoration:none;"
                                >
                                    {{ $expense->transaction->reference }}
                                </a>

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
                                PKR {{ number_format($balanceBefore, 0) }}
                            </strong>
                        </div>

                        <div class="fund-stat">
                            <label>Expense</label>

                            <strong>
                                PKR {{ number_format($expense->amount, 0) }}
                            </strong>
                        </div>

                        <div class="fund-stat">
                            <label>Balance After</label>

                            <strong>
                                PKR {{ number_format($balanceAfter, 0) }}
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
                        PKR {{ number_format($balanceBefore, 0) }} before
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
                        PKR {{ number_format($expense->amount, 0) }}
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
                        PKR {{ number_format($balanceAfter, 0) }} after
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
                                PKR {{ number_format($expense->amount, 0) }}
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
                    PKR {{ number_format($expense->amount, 0) }}
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