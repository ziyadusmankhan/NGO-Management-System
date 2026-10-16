@extends('layouts.app')

@section('title', 'Qard Payment & Recovery')

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

    .ui-btn.success {
        background:#edf7f0;
        border-color: #6a746d;
        color:#397955;
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
        background:#fff5e9;
        color: #976d38;
        font-size: 13px;
        font-weight:650;
    }

    .status::before {
        content:"";
        width:5px;
        height:5px;
        border-radius:50%;
        background:#d19a4d;
    }

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
    }

    .summary-box strong {
        display:block;
        margin-top:5px;
        font-size: 16px;
    }

    .summary-box.highlight strong {
        color:var(--primary);
    }

    .form-grid {
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
        min-height:100px;
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

    .calculation {
        display:grid;
        grid-template-columns:repeat(3,minmax(0,1fr));
        gap:10px;
        margin-top:18px;
    }

    .calculation-box {
        padding:13px;
        background:#fafcfb;
        border:1px solid #edf1ee;
        border-radius:10px;
    }

    .calculation-box.highlight {
        background:var(--primary-soft);
        border-color: #6f7673;
    }

    .calculation-box label {
        display:block;
        color: #6d7571;
        font-size: 13px;
        text-transform:uppercase;
    }

    .calculation-box strong {
        display:block;
        margin-top:6px;
        font-size: 16px;
    }

    .calculation-box.highlight strong {
        color:var(--primary);
    }

    .method-grid {
        display:grid;
        grid-template-columns:repeat(3,minmax(0,1fr));
        gap:9px;
    }

    .method-option {
        position:relative;
    }

    .method-option input {
        position:absolute;
        opacity:0;
        pointer-events:none;
    }

    .method-option label {
        min-height:68px;
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

    .method-option label i {
        font-size: 17px;
    }

    .method-option label strong {
        font-size: 14px;
    }

    .method-option label span {
        color: #6c7470;
        font-size: 12px;
    }

    .method-option input:checked + label {
        background:var(--primary-soft);
        border-color: #6b7570;
        color:var(--primary);
    }

    .checklist {
        display:flex;
        flex-direction:column;
        gap:9px;
    }

    .check-item {
        display:flex;
        align-items:flex-start;
        gap:9px;
        padding:10px;
        border:1px solid #edf1ee;
        border-radius:9px;
        background:#fafcfb;
    }

    .check-item input {
        margin-top:1px;
        accent-color:var(--primary);
    }

    .check-item span {
        color:#65726c;
        font-size: 14px;
        line-height:1.5;
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

    .payment-history {
        width:100%;
        min-width:650px;
        border-collapse:collapse;
    }

    .payment-history th {
        padding:10px;
        background:#fafcfb;
        border-bottom:1px solid #eef1ef;
        color: #6c7470;
        font-size: 13px;
        text-transform:uppercase;
        text-align:left;
    }

    .payment-history td {
        padding:11px 10px;
        border-bottom:1px solid #f0f3f1;
        font-size: 14px;
    }

    .payment-history tr:last-child td {
        border-bottom:none;
    }

    .paid {
        color:#397955;
        font-weight:650;
    }

    .document-upload {
        border:1px dashed #cbd6d0;
        border-radius:11px;
        padding:22px;
        text-align:center;
        background:#fbfcfb;
        cursor:pointer;
    }

    .document-upload i {
        color:var(--primary);
        font-size: 23px;
    }

    .document-upload strong {
        display:block;
        margin-top:8px;
        font-size: 15px;
    }

    .document-upload span {
        display:block;
        margin-top:4px;
        color: #6c7470;
        font-size: 13px;
    }

    .side-number {
        text-align:center;
        font-size: 30px;
        font-weight:700;
        color:var(--primary);
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
        .form-grid,
        .method-grid,
        .calculation {
            grid-template-columns:1fr;
        }

        .full {
            grid-column:auto;
        }
    }
</style>
@endpush


@section('content')

<div class="page-header">

    <div class="identity">

        <div class="icon-box">
            <i class="bi bi-cash-stack"></i>
        </div>

        <div>

            <div class="title">
                Record Qard Payment
            </div>

            <div class="meta">
                QRD-00031 · Ahmed Khan · Principal Recovery
            </div>

        </div>

    </div>

    <div class="actions">

        <a
            href="{{ route('qard.show', ['id' => 1]) }}"
            class="ui-btn"
        >
            <i class="bi bi-arrow-left"></i>
            Back
        </a>

        <button
            class="ui-btn success"
            onclick="previewRecordPayment()"
        >
            <i class="bi bi-check-lg"></i>
            Record Payment
        </button>

    </div>

</div>


<div class="layout">

    <div>

        <!-- CURRENT POSITION -->

        <div class="card">

            <div class="card-head">

                <div>
                    <strong>Current Qard Position</strong>

                    <span>
                        Payment will reduce outstanding principal.
                    </span>
                </div>

                <span class="status">
                    Active
                </span>

            </div>

            <div class="card-body">

                <div class="summary-grid">

                    <div class="summary-box">
                        <label>Original Principal</label>
                        <strong>PKR 100,000</strong>
                    </div>

                    <div class="summary-box">
                        <label>Already Recovered</label>
                        <strong>PKR 40,000</strong>
                    </div>

                    <div class="summary-box highlight">
                        <label>Outstanding Principal</label>
                        <strong>PKR 60,000</strong>
                    </div>

                    <div class="summary-box">
                        <label>Interest</label>
                        <strong>PKR 0</strong>
                    </div>

                </div>

            </div>

        </div>


        <!-- PAYMENT -->

        <div class="card">

            <div class="card-head">

                <div>
                    <strong>Payment Details</strong>

                    <span>
                        Record the principal amount being returned.
                    </span>
                </div>

            </div>

            <div class="card-body">

                <div class="form-grid">

                    <div>

                        <label class="label">
                            Payment Amount <span class="required">*</span>
                        </label>

                        <div class="money">

                            <span>PKR</span>

                            <input
                                id="paymentAmount"
                                type="number"
                                class="control"
                                value="10,000"
                            >

                        </div>

                    </div>


                    <div>

                        <label class="label">
                            Payment Date
                        </label>

                        <input
                            type="date"
                            class="control"
                            value="2026-09-01"
                        >

                    </div>


                    <div>

                        <label class="label">
                            Installment
                        </label>

                        <select class="select">

                            <option selected>
                                Installment #4 — 01 Sep 2026
                            </option>

                            <option>
                                Early / Partial Principal Payment
                            </option>

                        </select>

                    </div>


                    <div>

                        <label class="label">
                            Payment Reference
                        </label>

                        <input
                            class="control"
                            value="PAY-QRD-041"
                        >

                    </div>


                    <div class="full">

                        <label class="label">
                            Payment Method
                        </label>

                        <div class="method-grid">

                            <div class="method-option">

                                <input
                                    type="radio"
                                    name="paymentMethod"
                                    id="cash"
                                    checked
                                >

                                <label for="cash">

                                    <i class="bi bi-wallet2"></i>

                                    <strong>
                                        Cash
                                    </strong>

                                    <span>
                                        Cash collection
                                    </span>

                                </label>

                            </div>


                            <div class="method-option">

                                <input
                                    type="radio"
                                    name="paymentMethod"
                                    id="bank"
                                >

                                <label for="bank">

                                    <i class="bi bi-bank"></i>

                                    <strong>
                                        Bank
                                    </strong>

                                    <span>
                                        Bank transfer
                                    </span>

                                </label>

                            </div>


                            <div class="method-option">

                                <input
                                    type="radio"
                                    name="paymentMethod"
                                    id="mobile"
                                >

                                <label for="mobile">

                                    <i class="bi bi-phone"></i>

                                    <strong>
                                        Mobile Payment
                                    </strong>

                                    <span>
                                        Digital payment
                                    </span>

                                </label>

                            </div>

                        </div>

                    </div>


                    <div class="full">

                        <label class="label">
                            Notes
                        </label>

                        <textarea
                            class="control"
                            placeholder="Add payment or recovery notes..."
                        ></textarea>

                    </div>

                </div>


                <div class="calculation">

                    <div class="calculation-box">

                        <label>
                            Outstanding Before
                        </label>

                        <strong id="beforeAmount">
                            PKR 60,000
                        </strong>

                    </div>


                    <div class="calculation-box highlight">

                        <label>
                            Payment
                        </label>

                        <strong id="currentPayment">
                            PKR 10,000
                        </strong>

                    </div>


                    <div class="calculation-box">

                        <label>
                            Outstanding After
                        </label>

                        <strong id="afterAmount">
                            PKR 50,000
                        </strong>

                    </div>

                </div>

            </div>

        </div>


        <!-- PAYMENT CONTROLS -->

        <div class="card">

            <div class="card-head">

                <div>
                    <strong>Recovery Controls</strong>

                    <span>
                        Verify the transaction before recording the recovery.
                    </span>
                </div>

            </div>

            <div class="card-body">

                <div class="checklist">

                    <label class="check-item">

                        <input
                            type="checkbox"
                            checked
                        >

                        <span>
                            Payment amount does not exceed outstanding principal.
                        </span>

                    </label>


                    <label class="check-item">

                        <input
                            type="checkbox"
                            checked
                        >

                        <span>
                            Payment has been received or verified.
                        </span>

                    </label>


                    <label class="check-item">

                        <input
                            type="checkbox"
                            checked
                        >

                        <span>
                            Payment reference has been recorded.
                        </span>

                    </label>


                    <label class="check-item">

                        <input
                            type="checkbox"
                            checked
                        >

                        <span>
                            Recovery will be posted against the correct Qard and fund.
                        </span>

                    </label>

                </div>

            </div>

        </div>


        <!-- HISTORY -->

        <div class="card">

            <div class="card-head">

                <div>
                    <strong>Recent Payments</strong>

                    <span>
                        Previous principal recoveries.
                    </span>
                </div>

            </div>

            <div class="card-body" style="padding:0;">

                <div style="overflow-x:auto;">

                    <table class="payment-history">

                        <thead>

                            <tr>
                                <th>Date</th>
                                <th>Reference</th>
                                <th>Amount</th>
                                <th>Method</th>
                                <th>Status</th>
                            </tr>

                        </thead>

                        <tbody>

                            <tr>
                                <td>01 Aug 2026</td>
                                <td>PAY-QRD-032</td>
                                <td>PKR 10,000</td>
                                <td>Cash</td>
                                <td class="paid">Recovered</td>
                            </tr>

                            <tr>
                                <td>02 Jul 2026</td>
                                <td>PAY-QRD-019</td>
                                <td>PKR 10,000</td>
                                <td>Bank</td>
                                <td class="paid">Recovered</td>
                            </tr>

                            <tr>
                                <td>01 Jun 2026</td>
                                <td>PAY-QRD-001</td>
                                <td>PKR 10,000</td>
                                <td>Cash</td>
                                <td class="paid">Recovered</td>
                            </tr>

                        </tbody>

                    </table>

                </div>

            </div>

        </div>


        <!-- EVIDENCE -->

        <div class="card">

            <div class="card-head">

                <div>
                    <strong>Payment Evidence</strong>

                    <span>
                        Attach proof of payment or receipt where required.
                    </span>
                </div>

            </div>

            <div class="card-body">

                <label class="document-upload">

                    <i class="bi bi-cloud-arrow-up"></i>

                    <strong>
                        Upload payment evidence
                    </strong>

                    <span>
                        Receipt, transfer proof or payment record
                    </span>

                    <input
                        type="file"
                        hidden
                    >

                </label>

            </div>

        </div>

    </div>


    <aside>

        <div class="card">

            <div class="card-body">

                <div class="side-number" id="sideOutstanding">
                    PKR 50K
                </div>

                <div class="side-label">
                    After This Payment
                </div>

                <div style="
                    height:1px;
                    background:#eef1ef;
                    margin:16px 0;
                "></div>

                <div class="side-row">
                    <span>Before</span>
                    <span>PKR 60K</span>
                </div>

                <div class="side-row">
                    <span>Payment</span>
                    <span id="sidePayment">PKR 10K</span>
                </div>

                <div class="side-row">
                    <span>After</span>
                    <span id="sideAfter">PKR 50K</span>
                </div>

                <div class="side-row">
                    <span>Interest</span>
                    <span>None</span>
                </div>

            </div>

        </div>


        <div class="card">

            <div class="card-head">
                <strong>Principal Recovery</strong>
            </div>

            <div class="card-body">

                <div class="notice">

                    <i class="bi bi-arrow-repeat"></i>

                    <p>
                        This payment represents recovery of principal only.
                        Once recorded, the recovered amount can become
                        available for recycling through the relevant fund.
                    </p>

                </div>

            </div>

        </div>

    </aside>

</div>

@endsection


@push('scripts')
<script>

    const paymentAmount =
        document.getElementById('paymentAmount');

    function updateRecoveryPreview() {

        let payment =
            Number(
                String(paymentAmount.value)
                    .replace(/,/g, '')
            );

        if (!Number.isFinite(payment) || payment < 0) {
            payment = 0;
        }

        const before = 60000;

        const after =
            Math.max(
                before - payment,
                0
            );

        const formattedBefore =
            'PKR ' +
            before.toLocaleString('en-PK');

        const formattedPayment =
            'PKR ' +
            payment.toLocaleString('en-PK');

        const formattedAfter =
            'PKR ' +
            after.toLocaleString('en-PK');


        document.getElementById('beforeAmount')
            .textContent = formattedBefore;

        document.getElementById('currentPayment')
            .textContent = formattedPayment;

        document.getElementById('afterAmount')
            .textContent = formattedAfter;

        document.getElementById('sidePayment')
            .textContent = formattedPayment;

        document.getElementById('sideAfter')
            .textContent = formattedAfter;

        document.getElementById('sideOutstanding')
            .textContent =
                'PKR ' +
                after.toLocaleString('en-PK');

    }


    paymentAmount.addEventListener(
        'input',
        updateRecoveryPreview
    );


    function previewRecordPayment() {

        const payment =
            Number(
                String(paymentAmount.value)
                    .replace(/,/g, '')
            );

        if (
            !Number.isFinite(payment) ||
            payment <= 0
        ) {

            alert(
                'Please enter a valid payment amount.'
            );

            paymentAmount.focus();

            return;

        }


        if (payment > 60000) {

            alert(
                'Payment cannot exceed the outstanding principal of PKR 60,000.'
            );

            paymentAmount.focus();

            return;

        }


        alert(
            'UI preview only.\n\n' +
            'This principal recovery would be recorded against QRD-00031 once backend functionality is connected.'
        );

    }


    updateRecoveryPreview();

</script>
@endpush