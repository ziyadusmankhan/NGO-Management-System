@extends('layouts.app')

@section('title', 'New Donation')

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

    /* donor */

    .donor-types {
        display:grid;
        grid-template-columns:repeat(3,minmax(0,1fr));
        gap:9px;
    }

    .donor-option {
        position:relative;
    }

    .donor-option input {
        position:absolute;
        opacity:0;
        pointer-events:none;
    }

    .donor-option label {
        min-height:64px;
        padding:10px;
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

    .donor-option label i {
        font-size: 17px;
    }

    .donor-option label strong {
        font-size: 14px;
    }

    .donor-option label span {
        color: #6c7470;
        font-size: 12px;
    }

    .donor-option input:checked + label {
        background:var(--primary-soft);
        border-color: #6b7570;
        color:var(--primary);
    }

    /* amount */

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

    /* designation */

    .designation-grid {
        display:grid;
        grid-template-columns:repeat(2,minmax(0,1fr));
        gap:9px;
    }

    .designation-option {
        position:relative;
    }

    .designation-option input {
        position:absolute;
        opacity:0;
        pointer-events:none;
    }

    .designation-option label {
        min-height:72px;
        padding:11px;
        border:1px solid var(--border);
        border-radius:10px;
        display:flex;
        flex-direction:column;
        justify-content:center;
        cursor:pointer;
    }

    .designation-option label strong {
        font-size: 15px;
    }

    .designation-option label span {
        margin-top:4px;
        color: #6c7470;
        font-size: 13px;
        line-height:1.45;
    }

    .designation-option input:checked + label {
        background:var(--primary-soft);
        border-color: #6b7570;
        color:var(--primary);
    }

    /* payment */

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
        min-height:66px;
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

    /* allocation preview */

    .allocation {
        display:grid;
        grid-template-columns:repeat(3,minmax(0,1fr));
        gap:10px;
        margin-top:18px;
    }

    .allocation-box {
        padding:13px;
        border:1px solid #edf1ee;
        border-radius:10px;
        background:#fafcfb;
    }

    .allocation-box.highlight {
        background:var(--primary-soft);
        border-color: #6f7673;
    }

    .allocation-box label {
        display:block;
        color: #6d7571;
        font-size: 13px;
        text-transform:uppercase;
    }

    .allocation-box strong {
        display:block;
        margin-top:6px;
        font-size: 16px;
    }

    .allocation-box.highlight strong {
        color:var(--primary);
    }

    /* upload */

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

    /* preview */

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
        .donor-types,
        .designation-grid,
        .payment-grid,
        .allocation {
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

                    <li>{{ $error }}</li>

                @endforeach

            </ul>

        </div>

    </div>

@endif

<div class="page-header">

    <div>

        <h1>New Donation</h1>

        <p>
            Record a donor contribution and assign it to the appropriate purpose and fund.
        </p>

    </div>

    <div class="actions">

        <a
            href="{{ route('finance.donations.index') }}"
            class="ui-btn"
        >
            <i class="bi bi-arrow-left"></i>
            Back
        </a>

        <button
            type="submit"
            form="donationForm"
            class="ui-btn primary"
        >
            <i class="bi bi-check-lg"></i>
            Record Donation
        </button>

    </div>

</div>

<form
    id="donationForm"
    method="POST"
    action="{{ route('finance.donations.store') }}"
>
    @csrf
<div class="layout">

    <div>

        <!-- DONOR -->

        <div class="card">

            <div class="card-head">

                <strong>Donor Information</strong>

                <span>
                    Record the source of the donation.
                </span>

            </div>

            <div class="card-body">

                <div class="donor-types">

                    <div class="donor-option">

                        <input
                            type="radio"
                            name="donor_type"
                            value="individual"
                            id="individual"
                            @checked(old('donor_type', 'individual') === 'individual')
                        >

                        <label for="individual">

                            <i class="bi bi-person"></i>

                            <strong>
                                Individual
                            </strong>

                            <span>
                                Personal donor
                            </span>

                        </label>

                    </div>


                    <div class="donor-option">

                        <input
                            type="radio"
                            name="donor_type"
                            value="family"
                            id="family"
                            @checked(old('donor_type') === 'family')
                        >

                        <label for="family">

                            <i class="bi bi-people"></i>

                            <strong>
                                Family
                            </strong>

                            <span>
                                Family contribution
                            </span>

                        </label>

                    </div>


                    <div class="donor-option">

                        <input
                            type="radio"
                            name="donor_type"
                            value="organization"
                            id="organization"
                            @checked(old('donor_type') === 'organization')
                        >

                        <label for="organization">

                            <i class="bi bi-building"></i>

                            <strong>
                                Organization
                            </strong>

                            <span>
                                Company / institution
                            </span>

                        </label>

                    </div>

                </div>


                <div class="grid" style="margin-top:18px;">

                    <div>

                        <label class="label">
                            Donor Name <span class="required">*</span>
                        </label>

                        <input
                            id="donorName"
                            name="donor_name"
                            class="control"
                            value="{{ old('donor_name') }}"
                            placeholder="Full donor name"
                            required
                        >

                    </div>


                    <div>

                        <label class="label">
                            Donation ID
                        </label>

                        <input
                            class="control"
                            value="DON-00128"
                            readonly
                        >

                    </div>


                    <div>

                        <label class="label">
                            Phone
                        </label>

                        <input
                            name="phone"
                            class="control"
                            value="{{ old('phone') }}"
                            placeholder="Phone number"
                        >

                    </div>


                    <div>

                        <label class="label">
                            Email
                        </label>

                        <input
                            type="email"
                            name="email"
                            class="control"
                            value="{{ old('email') }}"
                            placeholder="Email address"

                        >

                    </div>

                </div>

            </div>

        </div>


        <!-- DONATION DETAILS -->

        <div class="card">

            <div class="card-head">

                <strong>Donation Details</strong>

                <span>
                    Amount, date and payment information.
                </span>

            </div>

            <div class="card-body">

                <div class="grid">

                    <div>

                        <label class="label">
                            Donation Amount <span class="required">*</span>
                        </label>

                        <div class="money">

                            <span>PKR</span>

                            <input
                                id="donationAmount"
                                name="amount"
                                type="number"
                                min="0.01"
                                step="0.01"
                                value="{{ old('amount') }}"
                                class="control"
                                placeholder="0"
                                required
                            >

                        </div>

                    </div>


                    <div>

                        <label class="label">
                            Donation Date
                        </label>

                        <input
                            type="date"
                            name="donation_date"
                            class="control"
                            value="{{ old('donation_date', date('Y-m-d')) }}"
                            required
                        >

                    </div>

                </div>


                <div style="margin-top:18px;">

                    <label class="label">
                        Payment Method
                    </label>

                    <div class="payment-grid">

                        <div class="payment-option">

                            <input
                                type="radio"
                                name="payment_method"
                                id="cash"
                                value="cash"
                                @checked(old('payment_method') === 'cash')
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
                                @checked(old('payment_method', 'bank') === 'bank')
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
                                id="online"
                                value="online"
                                @checked(old('payment_method') === 'online')
                            >

                            <label for="online">

                                <i class="bi bi-globe2"></i>

                                <strong>
                                    Online
                                </strong>

                            </label>

                        </div>

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
                            placeholder="Bank / payment reference"
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
                            placeholder="Optional receipt number"
                        >

                    </div>

                </div>

            </div>

        </div>


        <!-- PURPOSE / FUND -->

        <div class="card">

            <div class="card-head">

                <strong>Purpose & Fund</strong>

                <span>
                    Classify whether the donation is unrestricted or designated for a specific purpose.
                </span>

            </div>

            <div class="card-body">

                <div class="designation-grid">

                    <div class="designation-option">

                        <input
                            type="radio"
                            name="designation"
                            id="unrestricted"
                            value="unrestricted"
                            @checked(old('designation', 'unrestricted') === 'unrestricted')
                        >

                        <label for="unrestricted">

                            <strong>
                                Unrestricted Donation
                            </strong>

                            <span>
                                Organization may use the contribution for approved general needs.
                            </span>

                        </label>

                    </div>


                    <div class="designation-option">

                        <input
                            type="radio"
                            name="designation"
                            id="restricted"
                            value="restricted"
                            @checked(old('designation') === 'restricted')
                        >

                        <label for="restricted">

                            <strong>
                                Restricted / Designated
                            </strong>

                            <span>
                                Must remain associated with the specified purpose or fund.
                            </span>

                        </label>

                    </div>

                </div>


                <div class="grid" style="margin-top:18px;">

                    <div>

                        <label class="label">
                            Purpose <span class="required">*</span>
                        </label>

                        <select
                            id="purpose"
                            name="purpose"
                            class="select"
                            required
                        >

                            <option
                                value="general"
                                @selected(old('purpose', 'general') === 'general')
                            >
                                General Welfare
                            </option>

                            <option
                                value="business"
                                @selected(old('purpose') === 'business')
                            >
                                Small Business Assistance
                            </option>

                            <option
                                value="qard"
                                @selected(old('purpose') === 'qard')
                            >
                                Qard-e-Hasana
                            </option>

                            <option
                                value="education"
                                @selected(old('purpose') === 'education')
                            >
                                Education Support
                            </option>

                            <option
                                value="emergency"
                                @selected(old('purpose') === 'emergency')
                            >
                                Emergency Assistance
                            </option>

                            <option
                                value="project"
                                @selected(old('purpose') === 'project')
                            >
                                Community Project
                            </option>

                        </select>

                    </div>


                    <div>

                        <label class="label">
                            Destination Fund <span class="required">*</span>
                        </label>

                        <select
                            id="fund"
                            name="fund_id"
                            class="select"
                        >
                            <option value="">
                                Not assigned
                            </option>

                            @foreach ($funds as $fund)

                                <option
                                    value="{{ $fund->id }}"
                                    @selected(
                                        old('fund_id') == $fund->id
                                    )
                                >
                                    {{ $fund->name }}
                                    ({{ $fund->fund_reference }})
                                </option>

                            @endforeach
                        </select>

                    </div>


                    <div class="full">

                        <label class="label">
                            Purpose Notes
                        </label>

                        <textarea
                            name="purpose_notes"
                            class="control"
                            placeholder="Add any donor instruction, designation details or notes..."
                        >{{ old('purpose_notes') }}</textarea>

                    </div>

                </div>


                <div class="allocation">

                    <div class="allocation-box">

                        <label>
                            Donation
                        </label>

                        <strong id="allocationDonation">
                            PKR 0
                        </strong>

                    </div>


                    <div class="allocation-box">

                        <label>
                            Designation
                        </label>

                        <strong id="allocationType">
                            Unrestricted
                        </strong>

                    </div>


                    <div class="allocation-box highlight">

                        <label>
                            Fund
                        </label>

                        <strong id="allocationFund">
                            General Fund
                        </strong>

                    </div>

                </div>

            </div>

        </div>


        <!-- DOCUMENTS -->

        <div class="card">

            <div class="card-head">

                <strong>Supporting Records</strong>

                <span>
                    Attach receipts, transfer proof or donor correspondence where applicable.
                </span>

            </div>

            <div class="card-body">

                <label class="upload">

                    <i class="bi bi-cloud-arrow-up"></i>

                    <strong>
                        Upload donation records
                    </strong>

                    <span>
                        Receipt, payment proof, donor instruction or supporting document
                    </span>

                    <input
                        type="file"
                        multiple
                        hidden
                    >

                </label>

            </div>

        </div>


        <!-- NOTES -->

        <div class="card">

            <div class="card-head">

                <strong>Internal Notes</strong>

                <span>
                    Internal administrative notes.
                </span>

            </div>

            <div class="card-body">

                <textarea
                    name="internal_notes"
                    class="control"
                    placeholder="Add internal notes about the donation..."
                >{{ old('internal_notes') }}</textarea>

            </div>

        </div>

    </div>


    <!-- SIDEBAR -->

    <aside>

        <div class="preview">

            <div class="preview-label">
                Donation Preview
            </div>

            <div class="preview-icon">
                <i class="bi bi-cash-coin"></i>
            </div>

            <div
                class="preview-name"
                id="previewDonor"
            >
                New Donor
            </div>

            <div class="preview-sub">
                DON-00128
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
                    Type
                </span>

                <span id="previewDesignation">
                    Unrestricted
                </span>

            </div>


            <div class="preview-row">

                <span>
                    Purpose
                </span>

                <span id="previewPurpose">
                    General Welfare
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
                    Pending
                </span>

            </div>

        </div>


        <div class="card">

            <div class="card-head">

                <strong>
                    Fund Control
                </strong>

            </div>

            <div class="card-body">

                <div class="notice">

                    <i class="bi bi-shield-check"></i>

                    <p>
                        A designated donation should remain linked to its
                        approved purpose and restricted fund. The financial
                        transaction should remain traceable from donation
                        through future use.
                    </p>

                </div>

            </div>

        </div>


        <div class="card">

            <div class="card-head">

                <strong>
                    Donation Flow
                </strong>

            </div>

            <div class="card-body">

                <div
                    style="
                        display:flex;
                        flex-direction:column;
                        gap:11px;
                    "
                >

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
                            Donation Recorded
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
                            Fund Assigned
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
                            Receipt / Record
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
                            Available for Approved Use
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

    const donorName =
        document.getElementById('donorName');

    const donationAmount =
        document.getElementById('donationAmount');

    const purpose =
        document.getElementById('purpose');

    const fund =
        document.getElementById('fund');


    function money(value) {

        return 'PKR ' +
            Number(value || 0)
                .toLocaleString('en-PK');

    }


    donorName.addEventListener('input', function () {

        document.getElementById('previewDonor')
            .textContent =
                this.value.trim() || 'New Donor';

    });


    donationAmount.addEventListener('input', function () {

        const value =
            Number(this.value || 0);

        const formatted =
            money(value);

        document.getElementById('previewAmount')
            .textContent = formatted;

        document.getElementById('allocationDonation')
            .textContent = formatted;

    });


    purpose.addEventListener('change', function () {

        const text =
            this.options[this.selectedIndex]?.text || '';

        document.getElementById('previewPurpose')
            .textContent = text;

    });


    fund.addEventListener('change', function () {

        const text =
            this.options[this.selectedIndex]?.text || '';

        document.getElementById('previewFund')
            .textContent = text;

        document.getElementById('allocationFund')
            .textContent = text;

    });


    document
        .querySelectorAll('input[name="designation"]')
        .forEach(input => {

            input.addEventListener('change', function () {

                const checked =
                    document.querySelector(
                        'input[name="designation"]:checked'
                    );

                const label =
                    document.querySelector(
                        `label[for="${checked.id}"] strong`
                    );

                const value =
                    label
                        ? label.textContent
                        : '';

                document.getElementById('previewDesignation')
                    .textContent = value;

                document.getElementById('allocationType')
                    .textContent = value;

            });

        });


    // function previewSaveDonation() {

    //     if (!donorName.value.trim()) {

    //         alert(
    //             'Please enter the donor name.'
    //         );

    //         donorName.focus();

    //         return;

    //     }


    //     if (
    //         !donationAmount.value ||
    //         Number(donationAmount.value) <= 0
    //     ) {

    //         alert(
    //             'Please enter a valid donation amount.'
    //         );

    //         donationAmount.focus();

    //         return;

    //     }


    //     alert(
    //         'UI preview only.\n\n' +
    //         'This donation is ready to be recorded once backend functionality is connected.'
    //     );

    // }

</script>
@endpush