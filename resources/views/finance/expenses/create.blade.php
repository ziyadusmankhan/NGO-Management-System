@extends('layouts.app')

@section('title', 'New Expense')

@push('styles')
<style>
    .page-header {
        display:flex;
        justify-content:space-between;
        align-items:flex-start;
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
        padding:17px 20px;
        border-bottom:1px solid #eef1ef;
    }

    .card-head strong {
        font-size: 16px;
    }

    .card-head span {
        display:block;
        margin-top:4px;
        color: #6c7470;
        font-size: 14px;
        line-height:1.5;
    }

    .card-body {
        padding:20px;
    }

    .grid {
        display:grid;
        grid-template-columns:repeat(2,minmax(0,1fr));
        gap:17px;
    }

    .full {
        grid-column:1/-1;
    }

    .label {
        display:block;
        margin-bottom:7px;
        color:#36433d;
        font-size: 15px;
        font-weight:650;
    }

    .required {
        color: #b35b4b;
    }

    .control,
    .select {
        width:100%;
        height:42px;
        border:1px solid var(--border);
        border-radius:9px;
        padding:0 12px;
        background:#fff;
        color:var(--text);
        font-size: 15px;
        outline:none;
    }

    textarea.control {
        height:auto;
        min-height:105px;
        padding:11px 12px;
        resize:vertical;
        line-height:1.6;
    }

    .control:focus,
    .select:focus {
        border-color: #6b7670;
        box-shadow:0 0 0 3px rgba(23,63,53,.055);
    }

    .money {
        position:relative;
    }

    .money > span {
        position:absolute;
        left:12px;
        top:50%;
        transform:translateY(-50%);
        color: #6e7772;
        font-size: 15px;
        pointer-events:none;
    }

    .money .control {
        padding-left:42px;
    }

    /* CATEGORY */

    .category-grid {
        display:grid;
        grid-template-columns:repeat(3,minmax(0,1fr));
        gap:9px;
    }

    .category-option {
        position:relative;
    }

    .category-option input {
        position:absolute;
        opacity:0;
        pointer-events:none;
    }

    .category-option label {
        min-height:70px;
        padding:10px;
        border:1px solid var(--border);
        border-radius:10px;
        display:flex;
        flex-direction:column;
        align-items:center;
        justify-content:center;
        text-align:center;
        gap:6px;
        cursor:pointer;
    }

    .category-option label i {
        font-size: 18px;
    }

    .category-option label strong {
        font-size: 14px;
    }

    .category-option label span {
        color: #6c7470;
        font-size: 12px;
    }

    .category-option input:checked + label {
        background:var(--primary-soft);
        border-color: #6b7570;
        color:var(--primary);
    }

    /* PAYMENT */

    .payment-grid {
        display:grid;
        grid-template-columns:repeat(4,minmax(0,1fr));
        gap:9px;
    }

    .payment-option {
        position:relative;
    }

    .payment-option input {
        position:absolute;
        opacity:0;
        pointer-events:none;
    }

    .payment-option label {
        min-height:65px;
        padding:9px;
        border:1px solid var(--border);
        border-radius:10px;
        display:flex;
        flex-direction:column;
        align-items:center;
        justify-content:center;
        text-align:center;
        gap:5px;
        cursor:pointer;
    }

    .payment-option label i {
        font-size: 17px;
    }

    .payment-option label strong {
        font-size: 13px;
    }

    .payment-option input:checked + label {
        background:var(--primary-soft);
        border-color: #6b7570;
        color:var(--primary);
    }

    /* APPROVAL */

    .approval-list {
        display:flex;
        flex-direction:column;
        gap:9px;
    }

    .approval-item {
        display:flex;
        align-items:flex-start;
        gap:9px;
        padding:11px;
        background:#fafcfb;
        border:1px solid #edf1ee;
        border-radius:9px;
    }

    .approval-item input {
        margin-top:1px;
        accent-color:var(--primary);
    }

    .approval-item strong {
        display:block;
        font-size: 14px;
    }

    .approval-item span {
        display:block;
        margin-top:3px;
        color: #6d7872;
        font-size: 13px;
        line-height:1.5;
    }

    /* SUMMARY */

    .amount-summary {
        display:grid;
        grid-template-columns:repeat(3,minmax(0,1fr));
        gap:10px;
        margin-top:18px;
    }

    .amount-box {
        padding:13px;
        background:#fafcfb;
        border:1px solid #edf1ee;
        border-radius:10px;
    }

    .amount-box.highlight {
        background:var(--primary-soft);
        border-color: #6f7673;
    }

    .amount-box label {
        display:block;
        color: #6d7571;
        font-size: 13px;
        text-transform:uppercase;
    }

    .amount-box strong {
        display:block;
        margin-top:6px;
        font-size: 16px;
    }

    .amount-box.highlight strong {
        color:var(--primary);
    }

    /* UPLOAD */

    .upload {
        border:1px dashed #cbd6d0;
        border-radius:11px;
        padding:23px;
        text-align:center;
        background:#fbfcfb;
        cursor:pointer;
    }

    .upload i {
        font-size: 24px;
        color:var(--primary);
    }

    .upload strong {
        display:block;
        margin-top:8px;
        font-size: 15px;
    }

    .upload span {
        display:block;
        margin-top:4px;
        color: #6c7470;
        font-size: 13px;
    }

    /* PREVIEW */

    .preview {
        background:linear-gradient(145deg,#f8faf9,#f2f6f4);
        border:1px solid var(--border);
        border-radius:14px;
        padding:20px;
        margin-bottom:18px;
    }

    .preview-label {
        color: #6d7672;
        font-size: 14px;
        text-transform:uppercase;
        letter-spacing:.8px;
    }

    .preview-icon {
        width:60px;
        height:60px;
        margin:15px 0 11px;
        border-radius:17px;
        background:var(--primary);
        color:#fff;
        display:flex;
        align-items:center;
        justify-content:center;
        font-size: 20px;
    }

    .preview-name {
        font-size: 16px;
        font-weight:700;
    }

    .preview-sub {
        margin-top:4px;
        color: #6c7570;
        font-size: 13px;
    }

    .preview-divider {
        height:1px;
        background:#dde5e1;
        margin:16px 0;
    }

    .preview-row {
        display:flex;
        justify-content:space-between;
        gap:12px;
        padding:7px 0;
        font-size: 14px;
    }

    .preview-row span:first-child {
        color: #6e7672;
    }

    .preview-row span:last-child {
        color:#4a5750;
        font-weight:650;
        text-align:right;
    }

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

        .grid,
        .category-grid,
        .payment-grid,
        .amount-summary {
            grid-template-columns:1fr;
        }

        .full {
            grid-column:auto;
        }
    }
</style>
@endpush


@section('content')
<form
    method="POST"
    action="{{ route('finance.expenses.store') }}"
    enctype="multipart/form-data"
    id="expenseForm"
>
    @csrf

<div class="page-header">

    <div>

        <h1>New Expense</h1>

        <p>
            Record an organizational expense and link it to the correct fund, purpose and authorization.
        </p>

    </div>
    

    <div class="actions">

        <a
            href="{{ route('finance.expenses.index') }}"
            class="ui-btn"
        >
            <i class="bi bi-arrow-left"></i>
            Back
        </a>

        <button
            type="submit"
            class="ui-btn primary"
        >
            <i class="bi bi-check-lg"></i>
            Record Expense
        </button>

    </div>

</div>


<div class="layout">

    <div>

        <!-- BASIC INFORMATION -->

        <div class="card">

            <div class="card-head">

                <strong>Expense Information</strong>

                <span>
                    Basic information about the expense.
                </span>

            </div>

            <div class="card-body">

                <div class="grid">

                    <div>

                        <label class="label">
                            Expense ID
                        </label>

                        <input
                            class="control"
                            value="EXP-00084"
                            readonly
                        >

                    </div>


                    <div>

                        <label class="label">
                            Expense Date
                        </label>

                        <input
                            type="date"
                            name="expense_date"
                            class="control"
                            value="{{ old('expense_date', now()->toDateString()) }}"
                            required
                        >

                    </div>


                    <div>

                        <label class="label">
                            Payee / Supplier <span class="required">*</span>
                        </label>

                        <input
                            id="payee"
                            name="payee_name"
                            class="control"
                            value="{{ old('payee_name') }}"
                            placeholder="Person, supplier or organization"
                            required
                        >

                    </div>


                    <div>

                        <label class="label">
                            Reference
                        </label>

                        <input
                            name="expense_reference"
                            class="control"
                            value="{{ old('expense_reference') }}"
                            placeholder="Payment / expense reference"
                        >

                    </div>

                </div>

            </div>

        </div>


        <!-- CATEGORY -->

        <div class="card">

            <div class="card-head">

                <strong>Expense Category</strong>

                <span>
                    Classify the expenditure according to its purpose.
                </span>

            </div>

            <div class="card-body">

                <div class="category-grid">

                    <div class="category-option">

                        <input
                            type="radio"
                            name="category"
                            id="welfare"
                            value="welfare"
                            @checked(old('category', 'welfare') === 'welfare')
                        >

                        <label for="welfare">

                            <i class="bi bi-heart-handshake"></i>

                            <strong>
                                Welfare
                            </strong>

                            <span>
                                Direct beneficiary support
                            </span>

                        </label>

                    </div>


                    <div class="category-option">

                        <input
                            type="radio"
                            name="category"
                            id="project"
                            value="project"
                            @checked(old('category') === 'project')
                        >

                        <label for="project">

                            <i class="bi bi-buildings"></i>

                            <strong>
                                Community Project
                            </strong>

                            <span>
                                Village development
                            </span>

                        </label>

                    </div>


                    <div class="category-option">

                        <input
                            type="radio"
                            name="category"
                            id="admin"
                            value="admin"
                            @checked(old('category') === 'admin')
                        >

                        <label for="admin">

                            <i class="bi bi-briefcase"></i>

                            <strong>
                                Administration
                            </strong>

                            <span>
                                Organizational operations
                            </span>

                        </label>

                    </div>


                    <div class="category-option">

                        <input
                            type="radio"
                            name="category"
                            id="education"
                            value="education"
                            @checked(old('category') === 'education')
                        >

                        <label for="education">

                            <i class="bi bi-mortarboard"></i>

                            <strong>
                                Education
                            </strong>

                            <span>
                                Student assistance
                            </span>

                        </label>

                    </div>


                    <div class="category-option">

                        <input
                            type="radio"
                            name="category"
                            id="medical"
                            value="medical"
                            @checked(old('category') === 'medical')
                        >

                        <label for="medical">

                            <i class="bi bi-heart-pulse"></i>

                            <strong>
                                Medical
                            </strong>

                            <span>
                                Health-related support
                            </span>

                        </label>

                    </div>


                    <div class="category-option">

                        <input
                            type="radio"
                            name="category"
                            id="other"
                            value="other"
                            @checked(old('category') === 'other')
                        >

                        <label for="other">

                            <i class="bi bi-three-dots"></i>

                            <strong>
                                Other
                            </strong>

                            <span>
                                Other approved use
                            </span>

                        </label>

                    </div>

                </div>

            </div>

        </div>


        <!-- AMOUNT & FUND -->

        <div class="card">

            <div class="card-head">

                <strong>Amount & Fund</strong>

                <span>
                    Link the expenditure to its financial source and available balance.
                </span>

            </div>

            <div class="card-body">

                <div class="grid">

                    <div>

                        <label class="label">
                            Expense Amount <span class="required">*</span>
                        </label>

                        <div class="money">

                            <span>PKR</span>

                            <input
                                id="expenseAmount"
                                name="amount"
                                type="number"
                                class="control"
                                value="{{ old('amount') }}"
                                placeholder="0"
                                min="0.01"
                                step="0.01"
                                required
                            >

                        </div>

                    </div>


                    <div>

                        <label class="label">
                            Fund <span class="required">*</span>
                        </label>

                        <select
                            id="fund"
                            name="fund_id"
                            class="select"
                            required
                        >
                            <option value="">
                                Select Fund
                            </option>

                            @foreach($funds as $fund)

                                <option
                                    value="{{ $fund->id }}"
                                    data-type="{{ $fund->type }}"
                                    data-purpose="{{ $fund->purpose }}"
                                    @selected((string) old('fund_id') === (string) $fund->id)
                                >
                                    {{ $fund->name }}
                                    — PKR {{ \App\Domain\Finance\Support\Money::format($fundBalances[$fund->id] ?? 0) }}
                                </option>

                            @endforeach
                        </select>

                        <div
                            id="fundRestrictionMessage"
                            style="
                                display:none;
                                margin-top:8px;
                                padding:9px 11px;
                                border-radius:8px;
                                background:#fff5e9;
                                border:1px solid #ead9bc;
                                color:#8a6637;
                                font-size:13px;
                                line-height:1.5;"></div>

                    </div>


                    <div>

                        <label class="label">
                            Available Fund Balance
                        </label>

                        <input
                            id="availableBalance"
                            class="control"
                            value="PKR 0"
                            readonly
                        >

                    </div>


                    <div>

                        <label class="label">
                            Expense Purpose
                        </label>

                        <select class="select">

                            <option>
                                General Welfare
                            </option>

                            <option>
                                Food Assistance
                            </option>

                            <option>
                                Medical Assistance
                            </option>

                            <option>
                                Education Support
                            </option>

                            <option>
                                Community Development
                            </option>

                            <option>
                                Administrative Operations
                            </option>

                        </select>

                    </div>

                </div>


                <div class="amount-summary">

                    <div class="amount-box">

                        <label>
                            Fund Before
                        </label>

                        <strong id="beforeBalance">
                            PKR 0
                        </strong>

                    </div>


                    <div class="amount-box highlight">

                        <label>
                            Expense
                        </label>

                        <strong id="summaryExpense">
                            PKR 0
                        </strong>

                    </div>


                    <div class="amount-box">

                        <label>
                            Fund After
                        </label>

                        <strong id="afterBalance">
                            PKR 0
                        </strong>

                    </div>

                </div>

            </div>

        </div>


        <!-- DESCRIPTION -->

        <div class="card">

            <div class="card-head">

                <strong>Expense Description</strong>

                <span>
                    Explain exactly what the money is being used for.
                </span>

            </div>

            <div class="card-body">

                <textarea
                id="description"
                name="description"
                class="control"
                required></textarea>

            </div>

        </div>


        <!-- PAYMENT -->

        <div class="card">

            <div class="card-head">

                <strong>Payment Method</strong>

                <span>
                    Record how the expense was or will be paid.
                </span>

            </div>

            <div class="card-body">

                <div class="payment-grid">

                    <div class="payment-option">

                        <input
                            type="radio"
                            name="payment_method"
                            id="cash"
                            value="cash"
                            @checked(old('payment_method', 'cash') === 'cash')
                        >

                        <label for="cash">

                            <i class="bi bi-wallet2"></i>

                            <strong>
                                Cash
                            </strong>

                        </label>

                    </div>


                    <div class="payment-option">

                        <input
                            type="radio"
                            name="payment_method"
                            id="bank"
                            value="bank"
                            @checked(old('payment_method') === 'bank')
                        >

                        <label for="bank">

                            <i class="bi bi-bank"></i>

                            <strong>
                                Bank Transfer
                            </strong>

                        </label>

                    </div>


                    <div class="payment-option">

                        <input
                            type="radio"
                            name="payment_method"
                            id="mobile"
                            value="mobile"
                            @checked(old('payment_method') === 'mobile')
                        >

                        <label for="mobile">

                            <i class="bi bi-phone"></i>

                            <strong>
                                Mobile Payment
                            </strong>

                        </label>

                    </div>


                    <div class="payment-option">

                        <input
                            type="radio"
                            name="payment_method"
                            id="vendor"
                            value="vendor"
                            @checked(old('payment_method') === 'vendor')
                        >

                        <label for="vendor">

                            <i class="bi bi-shop"></i>

                            <strong>
                                Direct Vendor
                            </strong>

                        </label>

                    </div>

                </div>


                <div class="grid" style="margin-top:18px;">

                    <div>

                        <label class="label">
                            Transaction Reference
                        </label>

                        <input
                            name="transaction_reference"
                            class="control"
                            value="{{ old('transaction_reference') }}"
                            placeholder="Payment / transaction reference"
                        >

                    </div>


                    <div>

                        <label class="label">
                            Receipt Number
                        </label>

                        <input
                            name="receipt_number"
                            class="control"
                            value="{{ old('receipt_number') }}"
                            placeholder="Receipt number"
                        >

                    </div>

                </div>

            </div>

        </div>


        <!-- APPROVAL -->

        <div class="card">

            <div class="card-head">

                <strong>Approval & Authorization</strong>

                <span>
                    Confirm that the expense has passed the required organizational controls.
                </span>

            </div>

            <div class="card-body">

                <div class="approval-list">

                    <label class="approval-item">

                        <input
                            type="checkbox"
                            checked
                        >

                        <div>

                            <strong>
                                Expense purpose has been reviewed
                            </strong>

                            <span>
                                The spending is connected to an approved organizational activity.
                            </span>

                        </div>

                    </label>


                    <label class="approval-item">

                        <input
                            type="checkbox"
                            checked
                        >

                        <div>

                            <strong>
                                Correct fund selected
                            </strong>

                            <span>
                                The selected fund permits this category of expenditure.
                            </span>

                        </div>

                    </label>


                    <label class="approval-item">

                        <input
                            type="checkbox"
                        >

                        <div>

                            <strong>
                                Supporting receipt verified
                            </strong>

                            <span>
                                Evidence has been checked and attached to the record.
                            </span>

                        </div>

                    </label>


                    <label class="approval-item">

                        <input
                            type="checkbox"
                        >

                        <div>

                            <strong>
                                Authorized person approved
                            </strong>

                            <span>
                                The expense has received approval according to organizational procedure.
                            </span>

                        </div>

                    </label>

                </div>


                <div class="grid" style="margin-top:18px;">

                    <div>

                        <label class="label">
                            Approved By
                        </label>

                        <select class="select">

                            <option>
                                Treasurer
                            </option>

                            <option>
                                General Secretary
                            </option>

                            <option>
                                Finance Committee
                            </option>

                        </select>

                    </div>


                    <div>

                        <label class="label">
                            Approval Date
                        </label>

                        <input
                            type="date"
                            class="control"
                            value="2026-08-16"
                        >

                    </div>

                </div>

            </div>

        </div>


        <!-- DOCUMENTS -->
        <input
            type="hidden"
            name="status"
            value="pending"
        >

        <input
            type="hidden"
            name="has_supporting_document"
            value="0"
        >

        <label style="
            display:flex;
            align-items:center;
            gap:8px;
            margin-top:12px;
            font-size:13px;
            color:#58645e;
        ">
            <input
                type="checkbox"
                name="has_supporting_document"
                value="1"
            >
            Supporting document has been verified
        </label>

        <div class="card">

            <div class="card-head">

                <strong>Supporting Documents</strong>

                <span>
                    Attach invoices, receipts, quotations, approvals or other evidence.
                </span>

            </div>

            <div class="card-body">

                <label class="upload">

                    <i class="bi bi-cloud-arrow-up"></i>

                    <strong>
                        Upload expense documents
                    </strong>

                    <span>
                        Invoice, receipt, quotation, approval or payment evidence
                    </span>

                    <input
                        type="file"
                        multiple
                        hidden
                    >

                </label>

            </div>

        </div>

        <div
            id="documentRequirementMessage"
            style="
                display:none;
                margin-top:10px;
                padding:9px 11px;
                border-radius:8px;
                background:#fff5e9;
                border:1px solid #ead9bc;
                color:#8a6637;
                font-size:13px;
                line-height:1.5;
            "
        ></div>


        <!-- INTERNAL NOTES -->

        <div class="card">

            <div class="card-head">

                <strong>Internal Notes</strong>

                <span>
                    Administrative notes about this expenditure.
                </span>

            </div>

            <div class="card-body">

                <textarea
                    name="internal_notes"
                    class="control"
                    placeholder="Add internal notes..."
                >{{ old('internal_notes') }}</textarea>

            </div>

        </div>

    </div>


    <aside>

        <!-- PREVIEW -->

        <div class="preview">

            <div class="preview-label">
                Expense Preview
            </div>

            <div class="preview-icon">
                <i class="bi bi-receipt"></i>
            </div>

            <div
                class="preview-name"
                id="previewPayee"
            >
                New Expense
            </div>

            <div class="preview-sub">
                EXP-00084
            </div>

            <div class="preview-divider"></div>

            <div class="preview-row">

                <span>
                    Amount
                </span>

                <span id="previewAmount">
                    PKR 0
                </span>

            </div>


            <div class="preview-row">

                <span>
                    Category
                </span>

                <span id="previewCategory">
                    Welfare
                </span>

            </div>


            <div class="preview-row">

                <span>
                    Fund
                </span>

                <span id="previewFund">
                    General Fund
                </span>

            </div>


            <div class="preview-row">

                <span>
                    Status
                </span>

                <span>
                    Draft
                </span>

            </div>

        </div>


        <div class="card">

            <div class="card-head">
                <strong>Expense Control</strong>
            </div>

            <div class="card-body">

                <div class="notice">

                    <i class="bi bi-shield-check"></i>

                    <p>
                        Every expense should be linked to a valid fund,
                        approved purpose and supporting evidence. Spending
                        should never silently exceed the available fund balance.
                    </p>

                </div>

            </div>

        </div>


        <div class="card">

            <div class="card-head">
                <strong>Expense Flow</strong>
            </div>

            <div class="card-body">

                <div style="
                    display:flex;
                    flex-direction:column;
                    gap:11px;
                ">

                    <div style="
                        display:flex;
                        gap:9px;
                        align-items:center;
                    ">

                        <div style="
                            width:25px;
                            height:25px;
                            border-radius:8px;
                            background:var(--primary-soft);
                            color:var(--primary);
                            display:flex;
                            align-items:center;
                            justify-content:center;
                            font-size: 14px;
                        ">
                            1
                        </div>

                        <span style="font-size: 14px;">
                            Expense Created
                        </span>

                    </div>


                    <div style="
                        display:flex;
                        gap:9px;
                        align-items:center;
                    ">

                        <div style="
                            width:25px;
                            height:25px;
                            border-radius:8px;
                            background:var(--primary-soft);
                            color:var(--primary);
                            display:flex;
                            align-items:center;
                            justify-content:center;
                            font-size: 14px;
                        ">
                            2
                        </div>

                        <span style="font-size: 14px;">
                            Purpose & Fund Checked
                        </span>

                    </div>


                    <div style="
                        display:flex;
                        gap:9px;
                        align-items:center;
                    ">

                        <div style="
                            width:25px;
                            height:25px;
                            border-radius:8px;
                            background:var(--primary-soft);
                            color:var(--primary);
                            display:flex;
                            align-items:center;
                            justify-content:center;
                            font-size: 14px;
                        ">
                            3
                        </div>

                        <span style="font-size: 14px;">
                            Approval
                        </span>

                    </div>


                    <div style="
                        display:flex;
                        gap:9px;
                        align-items:center;
                    ">

                        <div style="
                            width:25px;
                            height:25px;
                            border-radius:8px;
                            background:var(--primary-soft);
                            color:var(--primary);
                            display:flex;
                            align-items:center;
                            justify-content:center;
                            font-size: 14px;
                        ">
                            4
                        </div>

                        <span style="font-size: 14px;">
                            Payment
                        </span>

                    </div>


                    <div style="
                        display:flex;
                        gap:9px;
                        align-items:center;
                    ">

                        <div style="
                            width:25px;
                            height:25px;
                            border-radius:8px;
                            background:var(--primary-soft);
                            color:var(--primary);
                            display:flex;
                            align-items:center;
                            justify-content:center;
                            font-size: 14px;
                        ">
                            5
                        </div>

                        <span style="font-size: 14px;">
                            Fund Balance Updated
                        </span>

                    </div>

                </div>

            </div>

        </div>

    </aside>

</div>
</form>
@endsection


@push('scripts')
<script>
    const payee = document.getElementById('payee');
    const amount = document.getElementById('expenseAmount');
    const fund = document.getElementById('fund');
    const expenseForm = document.getElementById('expenseForm');
    const restrictionMessage =
        document.getElementById('fundRestrictionMessage');

    const fundBalances = @json($fundBalances);

    @php
        $fundControlsData = $funds->mapWithKeys(function ($fund) {
            return [
                $fund->id => [
                    'require_approval' => (bool) $fund->require_approval,
                    'require_documents' => (bool) $fund->require_documents,
                    'restrict_spending' => (bool) $fund->restrict_spending,
                ],
            ];
        });
    @endphp
    const fundControls = @json($fundControlsData);

    const allowedCategories = {
        general_welfare: [
            'welfare',
            'medical',
            'education',
        ],

        small_business: [
            'project',
            'other',
        ],

        qard: [
            'welfare',
            'medical',
            'education',
        ],

        education: [
            'education',
        ],

        emergency: [
            'medical',
            'welfare',
        ],

        community_development: [
            'project',
        ],
    };

    function money(value) {
        return 'PKR ' +
            FoundationMoney.formatMinor(value);
    }

    function getSelectedFund() {
        const option =
            fund.options[fund.selectedIndex];

        if (!option || !option.value) {
            return null;
        }

        return {
            id: option.value,
            type: option.dataset.type,
            purpose: option.dataset.purpose,
            name: option.textContent.trim()
        };
    }

    function getSelectedCategory() {
        const selected =
            document.querySelector(
                'input[name="category"]:checked'
            );

        return selected
            ? selected.value
            : null;
    }

    function categoryLabel(category) {
        return {
            welfare: 'Welfare',
            project: 'Community Project',
            admin: 'Administration',
            education: 'Education',
            medical: 'Medical',
            other: 'Other'
        }[category] || category;
    }

    function isCategoryAllowed() {
        const selectedFund = getSelectedFund();
        const category = getSelectedCategory();

        if (!selectedFund || !category) {
            return true;
        }

        if (selectedFund.type === 'general') {
            return true;
        }

        const allowed =
            allowedCategories[selectedFund.purpose] || [];

        return allowed.includes(category);
    }

    function updateRestrictionWarning() {
        const selectedFund = getSelectedFund();
        const category = getSelectedCategory();

        if (
            !selectedFund ||
            !category ||
            selectedFund.type === 'general'
        ) {
            restrictionMessage.style.display = 'none';
            restrictionMessage.textContent = '';
            return;
        }

        if (isCategoryAllowed()) {
            restrictionMessage.style.display = 'none';
            restrictionMessage.textContent = '';
            return;
        }

        const purpose =
            selectedFund.purpose
                .replaceAll('_', ' ');

        restrictionMessage.textContent =
            `This category is not permitted for ${selectedFund.name}. ` +
            `This fund is restricted to its configured purpose (${purpose}).`;

        restrictionMessage.style.display = 'block';
    }

    function getAvailableBalance() {
        const fundId = fund.value;

        return fundId && fundBalances[fundId]
            ? FoundationMoney.minor(fundBalances[fundId])
            : 0n;
    }

    function updateAmount() {
        const expense =
            FoundationMoney.minor(amount.value);

        const availableBalance =
            getAvailableBalance();

        const after =
            (availableBalance > expense ? availableBalance - expense : 0n);

        document.getElementById('previewAmount')
            .textContent = money(expense);

        document.getElementById('summaryExpense')
            .textContent = money(expense);

        document.getElementById('beforeBalance')
            .textContent = money(availableBalance);

        document.getElementById('afterBalance')
            .textContent = money(after);

        document.getElementById('availableBalance')
            .value = money(availableBalance);
    }

    function updateFundControls() {
        const fundId = fund.value;

        const controls =
            fundControls[fundId] || {};

        const message =
            document.getElementById(
                'documentRequirementMessage'
            );

        if (controls.require_documents) {

            message.textContent =
                'This fund requires supporting documentation before the expense can be approved.';

            message.style.display = 'block';

        } else {

            message.textContent = '';
            message.style.display = 'none';
        }
    }

    payee.addEventListener('input', function () {
        document.getElementById('previewPayee')
            .textContent =
                this.value.trim() || 'New Expense';
    });

    amount.addEventListener('input', updateAmount);

    fund.addEventListener('change', function () {

        document.getElementById('previewFund')
            .textContent =
                this.options[this.selectedIndex]
                    ?.textContent
                    ?.trim() || 'Select Fund';

        updateAmount();
        updateRestrictionWarning();
        updateFundControls();
    });

    document
        .querySelectorAll('input[name="category"]')
        .forEach(input => {

            input.addEventListener('change', function () {

                const label =
                    this.nextElementSibling
                        ?.querySelector('strong')
                        ?.textContent
                    || categoryLabel(this.value);

                document.getElementById('previewCategory')
                    .textContent = label;

                updateRestrictionWarning();
            });

        });

    expenseForm.addEventListener('submit', function (event) {

        if (!isCategoryAllowed()) {

            event.preventDefault();

            alert(
                'The selected expense category is not permitted for this fund.'
            );

            updateRestrictionWarning();

            return;
        }
    });

    updateAmount();
    updateRestrictionWarning();
    updateFundControls();
</script>
@endpush