@extends('layouts.app')

@section('title', 'Edit Fund')

@push('styles')
<link rel="stylesheet" href="{{ asset('css/views/finance-funds-edit-1.css') }}">
@endpush


@section('content')
@if ($errors->any())

    <div class="card">

        <div class="card-body">

            <strong style="color:var(--legacy-text-a85f58, #a85f58);">
                Please correct the following:
            </strong>

            <ul style="
                margin:8px 0 0;
                padding-left:20px;
                color:var(--legacy-text-a85f58, #a85f58);
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
        @if($designationLocked)
            <p role="status">This fund has activity or linked commitments. Its opening balance, opening date, type, purpose, spending restriction and designation protection are locked. You can still update its name, description and other details.</p>
        @endif

        <p>
            Update the identity, purpose, restrictions and financial controls
            for this fund.
        </p>

    </div>

    <div class="actions">

        @if(auth()->user()?->hasPermission('finance.view'))<a
            href="{{ route('finance.funds.index') }}"
            class="ui-btn"
        >
            <i class="bi bi-arrow-left"></i>
            Back
        </a>@endif

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
                            @if(! in_array($fund->purpose, ['general_welfare', 'small_business', 'qard', 'education', 'emergency', 'community_development'], true))
                                <option value="{{ $fund->purpose }}" @selected(old('purpose', $fund->purpose) === $fund->purpose)>{{ \Illuminate\Support\Str::headline($fund->purpose) }} (existing purpose)</option>
                            @endif

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
                            Current Ledger Balance
                        </label>

                        <strong>
                            PKR {{ \App\Domain\Finance\Support\Money::format($summary['balance']) }}
                        </strong>

                    </div>


                    <div class="balance-box">

                        <label>
                            Allocated (unpaid)
                        </label>

                        <strong>
                            PKR {{ \App\Domain\Finance\Support\Money::format($summary['allocated']) }}
                        </strong>

                    </div>


                    <div class="balance-box highlight">

                        <label>
                            Available after allocations
                        </label>

                        <strong>
                            PKR {{ \App\Domain\Finance\Support\Money::format($summary['available']) }}
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
                    PKR {{ \App\Domain\Finance\Support\Money::format($fund->opening_balance) }}
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
            FoundationMoney.format(value);

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
            this.value || '0';

        const formatted =
            money(value);

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
