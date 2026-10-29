@extends('layouts.app')

@section('title', 'New Donation')

@push('styles')
<link rel="stylesheet" href="{{ asset('css/views/finance-donations-create-1.css') }}">
@endpush


@section('content')
<div class="alert alert-info">For a donation restricted to a named project, first create its dedicated fund on the Project page. Select that fund below, with <strong>Restricted</strong> designation and <strong>Project</strong> purpose. Its remaining balance stays restricted after project closure.</div>
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

        @if(auth()->user()?->hasPermission('finance.view'))<a
            href="{{ route('finance.donations.index') }}"
            class="ui-btn"
        >
            <i class="bi bi-arrow-left"></i>
            Back
        </a>@endif

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
                                    {{ $fund->name }}{{ $fund->project_id ? ' · Dedicated project fund' : '' }}
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
            FoundationMoney.format(value);

    }


    donorName.addEventListener('input', function () {

        document.getElementById('previewDonor')
            .textContent =
                this.value.trim() || 'New Donor';

    });


    donationAmount.addEventListener('input', function () {

        const value =
            this.value || '0';

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