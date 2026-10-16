@extends('layouts.app')

@section('title', 'Edit Fund')

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

    /* FUND TYPE */

    .type-grid {
        display:grid;
        grid-template-columns:repeat(3,minmax(0,1fr));
        gap:9px;
    }

    .type-option {
        position:relative;
    }

    .type-option input {
        position:absolute;
        opacity:0;
        pointer-events:none;
    }

    .type-option label {
        min-height:72px;
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

    .type-option label i {
        font-size: 18px;
    }

    .type-option label strong {
        font-size: 14px;
    }

    .type-option label span {
        color: #6c7470;
        font-size: 12px;
    }

    .type-option input:checked + label {
        background:var(--primary-soft);
        border-color: #6b7570;
        color:var(--primary);
    }

    /* RULES */

    .rules {
        display:flex;
        flex-direction:column;
        gap:9px;
    }

    .rule {
        display:flex;
        align-items:flex-start;
        gap:9px;
        padding:10px;
        background:#fafcfb;
        border:1px solid #edf1ee;
        border-radius:9px;
    }

    .rule input {
        margin-top:1px;
        accent-color:var(--primary);
    }

    .rule strong {
        display:block;
        font-size: 14px;
    }

    .rule span {
        display:block;
        margin-top:3px;
        color: #6d7872;
        font-size: 13px;
        line-height:1.5;
    }

    /* BALANCE */

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

    .balance-grid {
        display:grid;
        grid-template-columns:repeat(3,minmax(0,1fr));
        gap:10px;
        margin-top:18px;
    }

    .balance-box {
        padding:13px;
        border:1px solid #edf1ee;
        border-radius:10px;
        background:#fafcfb;
    }

    .balance-box.highlight {
        background:var(--primary-soft);
        border-color: #6f7673;
    }

    .balance-box label {
        display:block;
        color: #6d7571;
        font-size: 13px;
        text-transform:uppercase;
    }

    .balance-box strong {
        display:block;
        margin-top:6px;
        font-size: 16px;
    }

    .balance-box.highlight strong {
        color:var(--primary);
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
        .type-grid,
        .balance-grid {
            grid-template-columns:1fr;
        }

        .full {
            grid-column:auto;
        }
    }
</style>
@endpush


@section('content')
@if ($errors->any())

    <div class="card">

        <div class="card-body">

            <strong style="color:#a85f58;">
                Please correct the following:
            </strong>

            <ul style="
                margin:8px 0 0;
                padding-left:20px;
                color:#a85f58;
            ">

                @foreach ($errors->all() as $error)

                    <li>
                        {{ $error }}
                    </li>

                @endforeach

            </ul>

        </div>

    </div>

@endif

<div class="page-header">

    <div>

        <h1>Edit Fund</h1>

        <p>
            Update the identity, purpose, restrictions and financial controls
            for this fund.
        </p>

    </div>

    <div class="actions">

        <a
            href="{{ route('finance.funds.index') }}"
            class="ui-btn"
        >
            <i class="bi bi-arrow-left"></i>
            Back
        </a>

        <button
            type="submit"
            form="fundForm"
            class="ui-btn primary"
        >
            <i class="bi bi-check-lg"></i>
            Save Changes
        </button>

    </div>

</div>

<form
    id="fundForm"
    method="POST"
    action="{{ route('finance.funds.update', $fund) }}"
>
    @csrf
    @method('PUT')
<div class="layout">

    <div>

        <!-- IDENTITY -->

        <div class="card">

            <div class="card-head">

                <strong>Fund Information</strong>

                <span>
                    Define the identity and basic purpose of the fund.
                </span>

            </div>

            <div class="card-body">

                <div class="grid">

                    <div>

                        <label class="label">
                            Fund Name <span class="required">*</span>
                        </label>

                        <input
                            id="fundName"
                            name="name"
                            class="control"
                            value="{{ old('name', $fund->name) }}"
                            required
                        >

                    </div>


                    <div>

                        <label class="label">
                            Fund Reference
                        </label>

                        <input
                            class="control"
                            value="{{ $fund->fund_reference }}"
                            readonly
                        >

                    </div>


                    <div>

                        <label class="label">
                            Fund Code
                        </label>

                        <input
                            name="code"
                            class="control"
                            value="{{ old('code', $fund->code) }}"
                            placeholder="e.g. SBF-01"
                        >

                    </div>


                    <div>

                        <label class="label">
                            Responsible Authority
                        </label>

                        <select
                            name="responsible_authority"
                            class="select"
                        >
                            <option value="">Select authority</option>

                            <option
                                value="General Secretary"
                                @selected(
                                    old('responsible_authority', $fund->responsible_authority)
                                    === 'General Secretary'
                                )
                            >
                                General Secretary
                            </option>

                            <option
                                value="Treasurer"
                                @selected(
                                    old('responsible_authority', $fund->responsible_authority)
                                    === 'Treasurer'
                                )
                            >
                                Treasurer
                            </option>

                            <option
                                value="Finance Committee"
                                @selected(
                                    old('responsible_authority', $fund->responsible_authority)
                                    === 'Finance Committee'
                                )
                            >
                                Finance Committee
                            </option>

                            <option
                                value="Decision-Making Committee"
                                @selected(
                                    old('responsible_authority', $fund->responsible_authority)
                                    === 'Decision-Making Committee'
                                )
                            >
                                Decision-Making Committee
                            </option>
                        </select>

                    </div>


                    <div class="full">

                        <label class="label">
                            Fund Description
                        </label>

                        <textarea
                             name="description"
                             class="control"
                             id="fundDescription"
                             placeholder="Describe what this fund is intended to support..."
                             >{{ old('description', $fund->description) }}</textarea>

                    </div>

                </div>

            </div>

        </div>


        <!-- TYPE -->

        <div class="card">

            <div class="card-head">

                <strong>Fund Type</strong>

                <span>
                    Select whether the fund is general, restricted or designated.
                </span>

            </div>

            <div class="card-body">

                <div class="type-grid">

                    <div class="type-option">

                        <input type="radio"
                                name="type"
                                id="general"
                                value="general"
                                @checked(old('type', $fund->type) === 'general')>

                        <label for="general">

                            <i class="bi bi-wallet2"></i>

                            <strong>
                                General
                            </strong>

                            <span>
                                Unrestricted organizational resources
                            </span>

                        </label>

                    </div>


                    <div class="type-option">

                        <input
                            type="radio"
                            name="type"
                            id="restricted"
                            value="restricted"
                            @checked(old('type', $fund->type) === 'restricted')
                        >

                        <label for="restricted">

                            <i class="bi bi-lock"></i>

                            <strong>
                                Restricted
                            </strong>

                            <span>
                                Limited to an approved purpose
                            </span>

                        </label>

                    </div>


                    <div class="type-option">

                        <input
                            type="radio"
                            name="type"
                            id="designated"
                            value="designated"
                            @checked(old('type', $fund->type) === 'designated')
                        >

                        <label for="designated">

                            <i class="bi bi-bookmark-check"></i>

                            <strong>
                                Designated
                            </strong>

                            <span>
                                Donor / committee designated use
                            </span>

                        </label>

                    </div>

                </div>

            </div>

        </div>


        <!-- PURPOSE -->

        <div class="card">

            <div class="card-head">

                <strong>Purpose & Restrictions</strong>

                <span>
                    Define exactly how the fund may be used.
                </span>

            </div>

            <div class="card-body">

                <div class="grid">

                    <div>

                        <label class="label">
                            Primary Purpose <span class="required">*</span>
                        </label>

                        <select
                            id="fundPurpose"
                            name="purpose"
                            class="select"
                            required
                        >

                            <option
                                value="general_welfare"
                                @selected(old('purpose', $fund->purpose) === 'general_welfare')
                            >
                                General Welfare
                            </option>

                            <option
                                value="small_business"
                                @selected(old('purpose', $fund->purpose) === 'small_business')
                            >
                                Small Business Assistance
                            </option>

                            <option
                                value="qard"
                                @selected(old('purpose', $fund->purpose) === 'qard')
                            >
                                Qard-e-Hasana
                            </option>

                            <option
                                value="education"
                                @selected(old('purpose', $fund->purpose) === 'education')
                            >
                                Education Support
                            </option>

                            <option
                                value="emergency"
                                @selected(old('purpose', $fund->purpose) === 'emergency')
                            >
                                Emergency Assistance
                            </option>

                            <option
                                value="community_development"
                                @selected(old('purpose', $fund->purpose) === 'community_development')
                            >
                                Community Development
                            </option>

                        </select>

                    </div>


                    <div>

                        <label class="label">
                            Default Status
                        </label>

                        <select
                            name="status"
                            class="select"
                        >

                            <option
                                value="active"
                                @selected(old('status', $fund->status) === 'active')
                            >
                                Active
                            </option>

                            <option
                                value="inactive"
                                @selected(old('status', $fund->status) === 'inactive')
                            >
                                Inactive
                            </option>

                        </select>

                    </div>


                    <div class="full">

                        <label class="label">
                            Usage Rules
                        </label>

                        <textarea
                            id="usageRules"
                            name="usage_rules"
                            class="control"
                        >{{ old('usage_rules', $fund->usage_rules) }}</textarea>

                    </div>

                </div>


                <div class="rules">

                    <label class="rule">

                        <input
                            type="checkbox"
                            name="restrict_spending"
                            value="1"
                            @checked(old('restrict_spending', $fund->restrict_spending))
                        >

                        <div>

                            <strong>
                                Restrict spending to approved purpose
                            </strong>

                            <span>
                                Prevent expenses from being assigned to unrelated activities.
                            </span>

                        </div>

                    </label>


                    <label class="rule">

                        <input
                            type="checkbox"
                            name="require_approval"
                            value="1"
                            @checked(old('require_approval', $fund->require_approval))
                        >

                        <div>

                            <strong>
                                Require approval before allocation
                            </strong>

                            <span>
                                New allocations require authorized review before commitment.
                            </span>

                        </div>

                    </label>


                    <label class="rule">

                        <input
                            type="checkbox"
                            name="require_documents"
                            value="1"
                            @checked(old('require_documents', $fund->require_documents))
                        >

                        <div>

                            <strong>
                                Require supporting documents
                            </strong>

                            <span>
                                Fund transactions should include appropriate evidence.
                            </span>

                        </div>

                    </label>


                    <label class="rule">

                        <input
                            type="checkbox"
                            name="monitor_balance"
                            value="1"
                            @checked(old('monitor_balance', $fund->monitor_balance))
                        >

                        <div>

                            <strong>
                                Monitor restricted-fund balance
                            </strong>

                            <span>
                                Fund availability should be checked before approved spending.
                            </span>

                        </div>

                    </label>

                </div>

            </div>

        </div>


        <!-- OPENING BALANCE -->

        <div class="card">

            <div class="card-head">

                <strong>Opening Balance</strong>

                <span>
                    Define the initial amount when the fund is created.
                </span>

            </div>

            <div class="card-body">

                <div class="grid">

                    <div>

                        <label class="label">
                            Opening Balance
                        </label>

                        <div class="money">

                            <span>PKR</span>

                            <input
                                id="openingBalance"
                                name="opening_balance"
                                type="number"
                                min="0"
                                step="0.01"
                                value="{{ old('opening_balance', $fund->opening_balance) }}"
                            >

                        </div>

                    </div>


                    <div>

                        <label class="label">
                            Opening Balance Date
                        </label>

                        <input
                            type="date"
                            name="opening_balance_date"
                            class="control"
                            value="{{ old('opening_balance_date', optional($fund->opening_balance_date)->format('Y-m-d') ?? $fund->opening_balance_date) }}"
                        >

                    </div>

                </div>


                <div class="balance-grid">

                    <div class="balance-box">

                        <label>
                            Opening
                        </label>

                        <strong id="balanceOpening">
                            PKR {{ number_format($fund->opening_balance, 0) }}
                        </strong>

                    </div>


                    <div class="balance-box">

                        <label>
                            Allocated
                        </label>

                        <strong>
                            PKR 0
                        </strong>

                    </div>


                    <div class="balance-box highlight">

                        <label>
                            Available
                        </label>

                        <strong id="balanceAvailable">
                            PKR {{ number_format($fund->opening_balance, 0) }}
                        </strong>

                    </div>

                </div>

            </div>

        </div>


        <!-- CONTROLS -->

        <div class="card">

            <div class="card-head">

                <strong>Fund Controls</strong>

                <span>
                    Establish controls for financial accountability.
                </span>

            </div>

            <div class="card-body">

                <div class="rules">

                    <label class="rule">

                        <input
                            type="checkbox"
                            name="record_transactions"
                            value="1"
                            @checked(old('record_transactions', $fund->record_transactions))
                        >

                        <div>

                            <strong>
                                Record every fund transaction
                            </strong>

                            <span>
                                Donations, allocations, expenses, recoveries and adjustments remain traceable.
                            </span>

                        </div>

                    </label>


                    <label class="rule">

                        <input
                            type="checkbox"
                            name="prevent_negative_balance"
                            value="1"
                            @checked(old('prevent_negative_balance', $fund->prevent_negative_balance))
                        >

                        <div>

                            <strong>
                                Prevent negative available balance
                            </strong>

                            <span>
                                Spending should not exceed available funds.
                            </span>

                        </div>

                    </label>


                    <label class="rule">

                        <input
                            type="checkbox"
                            name="maintain_audit_history"
                            value="1"
                            @checked(old('maintain_audit_history', $fund->maintain_audit_history))
                        >

                        <div>

                            <strong>
                                Maintain audit history
                            </strong>

                            <span>
                                Fund creation and subsequent changes should remain auditable.
                            </span>

                        </div>

                    </label>


                    <label class="rule">

                        <input
                            type="checkbox"
                            name="protect_restricted_designation"
                            value="1"
                            @checked(old('protect_restricted_designation', $fund->protect_restricted_designation))
                        >

                        <div>

                            <strong>
                                Protect restricted designation
                            </strong>

                            <span>
                                Restricted funds should not be silently converted into general resources.
                            </span>

                        </div>

                    </label>

                </div>

            </div>

        </div>


        <!-- NOTES -->

        <div class="card">

            <div class="card-head">

                <strong>Internal Notes</strong>

                <span>
                    Administrative notes about this fund.
                </span>

            </div>

            <div class="card-body">

                <textarea
                    name="internal_notes"
                    class="control"
                >{{ old('internal_notes', $fund->internal_notes) }}</textarea>

            </div>

        </div>

    </div>


    <aside>

        <!-- PREVIEW -->

        <div class="preview">

            <div class="preview-label">
                Fund Preview
            </div>

            <div class="preview-icon">
                <i class="bi bi-wallet2"></i>
            </div>

            <div
                class="preview-name"
                id="previewFundName"
            >
                {{ $fund->name }}
            </div>

            <div class="preview-sub">
                {{ $fund->fund_reference }}
            </div>

            <div class="preview-divider"></div>

            <div class="preview-row">

                <span>
                    Type
                </span>

                <span id="previewFundType">
                    {{ ucfirst($fund->type) }}
                </span>

            </div>


            <div class="preview-row">

                <span>
                    Purpose
                </span>

                <span id="previewFundPurpose">
                    {{ \Illuminate\Support\Str::headline($fund->purpose) }}
                </span>

            </div>


            <div class="preview-row">

                <span>
                    Opening
                </span>

                <span id="previewOpening">
                    PKR {{ number_format($fund->opening_balance, 0) }}
                </span>

            </div>


            <div class="preview-row">

                <span>
                    Status
                </span>

                <span>
                    {{ ucfirst($fund->status) }}
                </span>

            </div>

        </div>


        <div class="card">

            <div class="card-head">

                <strong>
                    Fund Principle
                </strong>

            </div>

            <div class="card-body">

                <div class="notice">

                    <i class="bi bi-shield-check"></i>

                    <p>
                        Fund balances must remain traceable. Restricted or
                        designated resources should only be used for the
                        approved purpose and should remain separately identifiable.
                    </p>

                </div>

            </div>

        </div>


        <div class="card">

            <div class="card-head">

                <strong>
                    Fund Lifecycle
                </strong>

                <span>
                    How the fund will be used.
                </span>

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
                            Fund Created
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
                            Donations / Opening Capital
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
                            Allocation
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
                            Approved Use
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
                            Reconciliation / Reporting
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

    const fundName =
        document.getElementById('fundName');

    const fundPurpose =
        document.getElementById('fundPurpose');

    const openingBalance =
        document.getElementById('openingBalance');


    function money(value) {

        return 'PKR ' +
            Number(value || 0)
                .toLocaleString('en-PK');

    }


    fundName.addEventListener('input', function () {

        document.getElementById('previewFundName')
            .textContent =
                this.value.trim() || 'New Fund';

    });


    fundPurpose.addEventListener('change', function () {

        const text =
            this.options[this.selectedIndex]?.text || '';

        document.getElementById('previewFundPurpose')
            .textContent = text;

    });


    openingBalance.addEventListener('input', function () {

        const value =
            Number(this.value || 0);

        const formatted =
            money(value);

        document.getElementById('balanceOpening')
            .textContent = formatted;

        document.getElementById('balanceAvailable')
            .textContent = formatted;

        document.getElementById('previewOpening')
            .textContent = formatted;

    });


    document
        .querySelectorAll('input[name="fundType"]')
        .forEach(input => {

            input.addEventListener('change', function () {

                document.getElementById('previewFundType')
                    .textContent =
                        this.value;

            });

        });


    

</script>
@endpush