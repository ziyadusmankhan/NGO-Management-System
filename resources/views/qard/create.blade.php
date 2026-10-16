@extends('layouts.app')

@section('title', 'New Qard-e-Hasana')

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

    .help {
        margin-top:5px;
        color: #6d7571;
        font-size: 13px;
        line-height:1.5;
    }

    /* PURPOSE */

    .purpose-grid {
        display:grid;
        grid-template-columns:repeat(3,minmax(0,1fr));
        gap:9px;
    }

    .purpose-option {
        position:relative;
    }

    .purpose-option input {
        position:absolute;
        opacity:0;
        pointer-events:none;
    }

    .purpose-option label {
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

    .purpose-option label i {
        font-size: 18px;
    }

    .purpose-option label strong {
        font-size: 14px;
    }

    .purpose-option label span {
        color: #6c7470;
        font-size: 12px;
    }

    .purpose-option input:checked + label {
        background:var(--primary-soft);
        border-color: #6b7570;
        color:var(--primary);
    }

    /* MONEY */

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

    /* TERMS */

    .terms-grid {
        display:grid;
        grid-template-columns:repeat(4,minmax(0,1fr));
        gap:10px;
    }

    .term-box {
        padding:13px;
        background:#fafcfb;
        border:1px solid #edf1ee;
        border-radius:10px;
    }

    .term-box label {
        display:block;
        color: #6d7571;
        font-size: 13px;
        text-transform:uppercase;
        letter-spacing:.5px;
    }

    .term-box strong {
        display:block;
        margin-top:6px;
        font-size: 16px;
    }

    /* INSTALLMENT */

    .calculation {
        display:grid;
        grid-template-columns:repeat(3,minmax(0,1fr));
        gap:10px;
        margin-top:18px;
    }

    .calculation-box {
        padding:13px;
        border:1px solid #edf1ee;
        border-radius:10px;
        background:#fafcfb;
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

    /* AGREEMENT */

    .agreement-box {
        padding:14px;
        background:#fafcfb;
        border:1px solid #edf1ee;
        border-radius:10px;
    }

    .agreement-row {
        display:flex;
        justify-content:space-between;
        gap:12px;
        padding:8px 0;
        border-bottom:1px solid #e8eeeb;
        font-size: 14px;
    }

    .agreement-row:last-child {
        border-bottom:none;
    }

    .agreement-row span:first-child {
        color: #6d7571;
    }

    .agreement-row span:last-child {
        color:#4a5750;
        font-weight:650;
        text-align:right;
    }

    /* WITNESS */

    .witness-grid {
        display:grid;
        grid-template-columns:repeat(2,minmax(0,1fr));
        gap:17px;
    }

    /* CHECKLIST */

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
        background:#fafcfb;
        border:1px solid #edf1ee;
        border-radius:9px;
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

    /* PRINCIPLE */

    .principle {
        display:flex;
        gap:9px;
        padding:12px;
        background:#f7f9f8;
        border:1px solid #e5ebe8;
        border-radius:10px;
    }

    .principle i {
        color:var(--primary);
        font-size: 16px;
    }

    .principle p {
        margin:0;
        color: #6b766f;
        font-size: 13px;
        line-height:1.6;
    }

    .workflow {
        display:flex;
        flex-direction:column;
        gap:11px;
    }

    .workflow-step {
        display:flex;
        gap:9px;
        align-items:flex-start;
    }

    .workflow-icon {
        width:24px;
        height:24px;
        border-radius:8px;
        background:var(--primary-soft);
        color:var(--primary);
        display:flex;
        align-items:center;
        justify-content:center;
        font-size: 14px;
        flex-shrink:0;
    }

    .workflow-text strong {
        display:block;
        font-size: 14px;
    }

    .workflow-text span {
        display:block;
        margin-top:3px;
        color: #6c7470;
        font-size: 12px;
        line-height:1.45;
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
        .witness-grid {
            grid-template-columns:1fr;
        }

        .purpose-grid {
            grid-template-columns:repeat(2,1fr);
        }

        .terms-grid,
        .calculation {
            grid-template-columns:1fr;
        }

        .full {
            grid-column:auto;
        }
    }

    @media(max-width:450px) {
        .purpose-grid {
            grid-template-columns:1fr;
        }
    }
</style>
@endpush


@section('content')

<div class="page-header">

    <div>
        <h1>New Qard-e-Hasana</h1>

        <p>
            Create an interest-free, principal-only Qard record for an approved deserving case.
        </p>
    </div>

    <div class="actions">

        <a
            href="{{ route('qard.index') }}"
            class="ui-btn"
        >
            <i class="bi bi-arrow-left"></i>
            Back
        </a>

        <button
            class="ui-btn primary"
            onclick="previewSaveQard()"
        >
            <i class="bi bi-check-lg"></i>
            Create Qard
        </button>

    </div>

</div>


<div class="layout">

    <div>

        <!-- BORROWER -->

        <div class="card">

            <div class="card-head">

                <strong>Borrower Information</strong>

                <span>
                    Link the Qard to a verified beneficiary record.
                </span>

            </div>

            <div class="card-body">

                <div class="grid">

                    <div>

                        <label class="label">
                            Borrower <span class="required">*</span>
                        </label>

                        <select
                            id="borrower"
                            class="select"
                        >

                            <option value="">
                                Select borrower
                            </option>

                            <option value="ahmed">
                                Ahmed Khan — BEN-00091
                            </option>

                            <option value="farzana">
                                Farzana Malik — BEN-00087
                            </option>

                            <option value="muhammad">
                                Muhammad Noor — BEN-00079
                            </option>

                            <option value="yasir">
                                Yasir Ahmad — BEN-00094
                            </option>

                        </select>

                    </div>


                    <div>

                        <label class="label">
                            Qard ID
                        </label>

                        <input
                            class="control"
                            value="QRD-00032"
                            readonly
                        >

                    </div>

                </div>

            </div>

        </div>


        <!-- PURPOSE -->

        <div class="card">

            <div class="card-head">

                <strong>Purpose of Qard</strong>

                <span>
                    Select the approved purpose and explain the need.
                </span>

            </div>

            <div class="card-body">

                <div class="purpose-grid">

                    <div class="purpose-option">

                        <input
                            type="radio"
                            name="purpose"
                            id="purposeBusiness"
                            checked
                        >

                        <label for="purposeBusiness">

                            <i class="bi bi-shop"></i>

                            <strong>
                                Business
                            </strong>

                            <span>
                                Small business / livelihood
                            </span>

                        </label>

                    </div>


                    <div class="purpose-option">

                        <input
                            type="radio"
                            name="purpose"
                            id="purposeEducation"
                        >

                        <label for="purposeEducation">

                            <i class="bi bi-mortarboard"></i>

                            <strong>
                                Education
                            </strong>

                            <span>
                                Fees / educational expenses
                            </span>

                        </label>

                    </div>


                    <div class="purpose-option">

                        <input
                            type="radio"
                            name="purpose"
                            id="purposeMedical"
                        >

                        <label for="purposeMedical">

                            <i class="bi bi-heart-pulse"></i>

                            <strong>
                                Medical
                            </strong>

                            <span>
                                Medical need
                            </span>

                        </label>

                    </div>


                    <div class="purpose-option">

                        <input
                            type="radio"
                            name="purpose"
                            id="purposeEmergency"
                        >

                        <label for="purposeEmergency">

                            <i class="bi bi-exclamation-circle"></i>

                            <strong>
                                Emergency
                            </strong>

                            <span>
                                Urgent temporary need
                            </span>

                        </label>

                    </div>


                    <div class="purpose-option">

                        <input
                            type="radio"
                            name="purpose"
                            id="purposeLivelihood"
                        >

                        <label for="purposeLivelihood">

                            <i class="bi bi-graph-up-arrow"></i>

                            <strong>
                                Livelihood
                            </strong>

                            <span>
                                Income-generating activity
                            </span>

                        </label>

                    </div>

                </div>


                <div style="margin-top:18px;">

                    <label class="label">
                        Purpose Details <span class="required">*</span>
                    </label>

                    <textarea
                        class="control"
                        placeholder="Explain why the Qard is needed and how the principal will be used..."
                    ></textarea>

                </div>

            </div>

        </div>


        <!-- PRINCIPAL -->

        <div class="card">

            <div class="card-head">

                <strong>Principal & Fund</strong>

                <span>
                    Qard-e-Hasana records principal only. No interest or interest-like charge is included.
                </span>

            </div>

            <div class="card-body">

                <div class="grid">

                    <div>

                        <label class="label">
                            Principal Amount <span class="required">*</span>
                        </label>

                        <div class="money">

                            <span>PKR</span>

                            <input
                                id="principal"
                                type="number"
                                class="control"
                                placeholder="0"
                            >

                        </div>

                    </div>


                    <div>

                        <label class="label">
                            Fund Source <span class="required">*</span>
                        </label>

                        <select
                            class="select"
                        >

                            <option>
                                Qard-e-Hasana Fund
                            </option>

                            <option>
                                Restricted Livelihood Fund
                            </option>

                            <option>
                                General Welfare Fund
                            </option>

                        </select>

                    </div>


                    <div>

                        <label class="label">
                            Approval Reference
                        </label>

                        <input
                            class="control"
                            value="DEC-QRD-00032"
                        >

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


                <div class="calculation">

                    <div class="calculation-box">

                        <label>
                            Principal
                        </label>

                        <strong id="previewPrincipal">
                            PKR 0
                        </strong>

                    </div>


                    <div class="calculation-box">

                        <label>
                            Interest
                        </label>

                        <strong>
                            PKR 0
                        </strong>

                    </div>


                    <div class="calculation-box highlight">

                        <label>
                            Total Repayable
                        </label>

                        <strong id="previewRepayable">
                            PKR 0
                        </strong>

                    </div>

                </div>

            </div>

        </div>


        <!-- REPAYMENT -->

        <div class="card">

            <div class="card-head">

                <strong>Repayment Terms</strong>

                <span>
                    Define how the principal will be returned.
                </span>

            </div>

            <div class="card-body">

                <div class="grid">

                    <div>

                        <label class="label">
                            Repayment Frequency
                        </label>

                        <select
                            id="frequency"
                            class="select"
                        >

                            <option value="weekly">
                                Weekly
                            </option>

                            <option
                                value="monthly"
                                selected
                            >
                                Monthly
                            </option>

                            <option value="quarterly">
                                Quarterly
                            </option>

                        </select>

                    </div>


                    <div>

                        <label class="label">
                            Number of Installments
                        </label>

                        <input
                            id="installments"
                            type="number"
                            class="control"
                            value="10"
                            min="1"
                        >

                    </div>


                    <div>

                        <label class="label">
                            Start Date
                        </label>

                        <input
                            id="startDate"
                            type="date"
                            class="control"
                            value="2026-09-01"
                        >

                    </div>


                    <div>

                        <label class="label">
                            Grace Period
                        </label>

                        <select class="select">

                            <option>
                                None
                            </option>

                            <option selected>
                                1 Month
                            </option>

                            <option>
                                2 Months
                            </option>

                            <option>
                                3 Months
                            </option>

                        </select>

                    </div>

                </div>


                <div class="calculation">

                    <div class="calculation-box">

                        <label>
                            Installments
                        </label>

                        <strong id="previewInstallments">
                            10
                        </strong>

                    </div>


                    <div class="calculation-box highlight">

                        <label>
                            Approx. Installment
                        </label>

                        <strong id="previewInstallmentAmount">
                            PKR 0
                        </strong>

                    </div>


                    <div class="calculation-box">

                        <label>
                            Interest
                        </label>

                        <strong>
                            0%
                        </strong>

                    </div>

                </div>

            </div>

        </div>


        <!-- AGREEMENT -->

        <div class="card">

            <div class="card-head">

                <strong>Qard Agreement</strong>

                <span>
                    Record the agreement reference and required parties.
                </span>

            </div>

            <div class="card-body">

                <div class="agreement-box">

                    <div class="agreement-row">
                        <span>Agreement Reference</span>
                        <span>AGR-QRD-00032</span>
                    </div>

                    <div class="agreement-row">
                        <span>Borrower</span>
                        <span>Ahmed Khan</span>
                    </div>

                    <div class="agreement-row">
                        <span>Principal</span>
                        <span id="agreementPrincipal">PKR 0</span>
                    </div>

                    <div class="agreement-row">
                        <span>Total Repayable</span>
                        <span id="agreementRepayable">PKR 0</span>
                    </div>

                    <div class="agreement-row">
                        <span>Interest</span>
                        <span>None</span>
                    </div>

                </div>


                <div style="margin-top:17px;">

                    <label class="label">
                        Agreement Terms
                    </label>

                    <textarea class="control">The Qard is provided without interest. The borrower is responsible for returning the principal according to the agreed repayment schedule. No interest or interest-like charge is included.</textarea>

                </div>

            </div>

        </div>


        <!-- WITNESSES -->

        <div class="card">

            <div class="card-head">

                <strong>Witnesses</strong>

                <span>
                    Record the witnesses associated with the Qard agreement.
                </span>

            </div>

            <div class="card-body">

                <div class="witness-grid">

                    <div>

                        <label class="label">
                            Witness 1
                        </label>

                        <input
                            class="control"
                            value="Naveed Alam"
                        >

                    </div>


                    <div>

                        <label class="label">
                            Witness 1 Contact
                        </label>

                        <input
                            class="control"
                            placeholder="Phone / identification"
                        >

                    </div>


                    <div>

                        <label class="label">
                            Witness 2
                        </label>

                        <input
                            class="control"
                            value="Shahzad Khan"
                        >

                    </div>


                    <div>

                        <label class="label">
                            Witness 2 Contact
                        </label>

                        <input
                            class="control"
                            placeholder="Phone / identification"
                        >

                    </div>

                </div>

            </div>

        </div>


        <!-- CHECKLIST -->

        <div class="card">

            <div class="card-head">

                <strong>Qard Controls</strong>

                <span>
                    Verify the conditions before creating the Qard record.
                </span>

            </div>

            <div class="card-body">

                <div class="checklist">

                    <label class="check-item">

                        <input
                            type="checkbox"
                            checked
                        >

                        <span>
                            Borrower is an approved and verified beneficiary.
                        </span>

                    </label>


                    <label class="check-item">

                        <input
                            type="checkbox"
                            checked
                        >

                        <span>
                            Purpose of the Qard has been reviewed and approved.
                        </span>

                    </label>


                    <label class="check-item">

                        <input
                            type="checkbox"
                            checked
                        >

                        <span>
                            Available fund balance is sufficient for the principal.
                        </span>

                    </label>


                    <label class="check-item">

                        <input
                            type="checkbox"
                            checked
                        >

                        <span>
                            Repayment schedule has been agreed with the borrower.
                        </span>

                    </label>


                    <label class="check-item">

                        <input
                            type="checkbox"
                        >

                        <span>
                            Required agreement signatures have been completed.
                        </span>

                    </label>

                </div>

            </div>

        </div>


        <!-- DOCUMENTS -->

        <div class="card">

            <div class="card-head">

                <strong>Supporting Documents</strong>

                <span>
                    Attach approval, agreement and supporting records.
                </span>

            </div>

            <div class="card-body">

                <label class="upload">

                    <i class="bi bi-cloud-arrow-up"></i>

                    <strong>
                        Upload Qard documents
                    </strong>

                    <span>
                        Agreements, approvals, identification and supporting evidence
                    </span>

                    <input
                        type="file"
                        multiple
                        hidden
                    >

                </label>

            </div>

        </div>

    </div>


    <aside>

        <!-- PREVIEW -->

        <div class="preview">

            <div class="preview-label">
                Qard Preview
            </div>

            <div class="preview-icon">
                <i class="bi bi-arrow-repeat"></i>
            </div>

            <div
                class="preview-name"
                id="previewBorrower"
            >
                New Qard
            </div>

            <div class="preview-sub">
                QRD-00032
            </div>

            <div class="preview-divider"></div>

            <div class="preview-row">
                <span>Purpose</span>
                <span id="previewPurpose">Business</span>
            </div>

            <div class="preview-row">
                <span>Principal</span>
                <span id="sidePrincipal">PKR 0</span>
            </div>

            <div class="preview-row">
                <span>Interest</span>
                <span>None</span>
            </div>

            <div class="preview-row">
                <span>Frequency</span>
                <span id="sideFrequency">Monthly</span>
            </div>

            <div class="preview-row">
                <span>Installments</span>
                <span id="sideInstallments">10</span>
            </div>

        </div>


        <!-- PRINCIPLE -->

        <div class="card">

            <div class="card-head">
                <strong>Qard Principle</strong>
            </div>

            <div class="card-body">

                <div class="principle">

                    <i class="bi bi-shield-check"></i>

                    <p>
                        This is a Qard-e-Hasana. The borrower returns
                        the principal only. No interest or interest-like
                        charge is included.
                    </p>

                </div>

            </div>

        </div>


        <!-- WORKFLOW -->

        <div class="card">

            <div class="card-head">

                <strong>Qard Workflow</strong>

                <span>
                    Lifecycle of the Qard.
                </span>

            </div>

            <div class="card-body">

                <div class="workflow">

                    <div class="workflow-step">

                        <div class="workflow-icon">1</div>

                        <div class="workflow-text">

                            <strong>
                                Request
                            </strong>

                            <span>
                                Borrower request is recorded and assessed.
                            </span>

                        </div>

                    </div>


                    <div class="workflow-step">

                        <div class="workflow-icon">2</div>

                        <div class="workflow-text">

                            <strong>
                                Approval
                            </strong>

                            <span>
                                Authorized committee approves the Qard.
                            </span>

                        </div>

                    </div>


                    <div class="workflow-step">

                        <div class="workflow-icon">3</div>

                        <div class="workflow-text">

                            <strong>
                                Agreement
                            </strong>

                            <span>
                                Terms, witnesses and repayment schedule recorded.
                            </span>

                        </div>

                    </div>


                    <div class="workflow-step">

                        <div class="workflow-icon">4</div>

                        <div class="workflow-text">

                            <strong>
                                Disbursement
                            </strong>

                            <span>
                                Principal is released to the approved borrower.
                            </span>

                        </div>

                    </div>


                    <div class="workflow-step">

                        <div class="workflow-icon">5</div>

                        <div class="workflow-text">

                            <strong>
                                Repayment
                            </strong>

                            <span>
                                Principal installments are recorded.
                            </span>

                        </div>

                    </div>


                    <div class="workflow-step">

                        <div class="workflow-icon">6</div>

                        <div class="workflow-text">

                            <strong>
                                Recovery
                            </strong>

                            <span>
                                Recovered principal is tracked and reconciled.
                            </span>

                        </div>

                    </div>


                    <div class="workflow-step">

                        <div class="workflow-icon">7</div>

                        <div class="workflow-text">

                            <strong>
                                Fund Recycling
                            </strong>

                            <span>
                                Recovered principal can support another case.
                            </span>

                        </div>

                    </div>

                </div>

            </div>

        </div>

    </aside>

</div>

@endsection


@push('scripts')
<script>

    const borrower =
        document.getElementById('borrower');

    const principal =
        document.getElementById('principal');

    const installments =
        document.getElementById('installments');

    const frequency =
        document.getElementById('frequency');


    function money(value) {

        return 'PKR ' +
            Number(value || 0)
                .toLocaleString('en-PK');

    }


    function updateQardPreview() {

        const amount =
            Number(principal.value || 0);

        const count =
            Number(installments.value || 0);


        const installment =
            count > 0
                ? amount / count
                : 0;


        document.getElementById('previewPrincipal')
            .textContent = money(amount);

        document.getElementById('previewRepayable')
            .textContent = money(amount);

        document.getElementById('previewInstallments')
            .textContent = count || 0;

        document.getElementById('previewInstallmentAmount')
            .textContent = money(
                Math.ceil(installment)
            );

        document.getElementById('sidePrincipal')
            .textContent = money(amount);

        document.getElementById('agreementPrincipal')
            .textContent = money(amount);

        document.getElementById('agreementRepayable')
            .textContent = money(amount);

        document.getElementById('sideInstallments')
            .textContent = count || 0;

    }


    borrower.addEventListener('change', function () {

        const text =
            this.options[this.selectedIndex]?.text || '';

        document.getElementById('previewBorrower')
            .textContent =
                text || 'New Qard';

    });


    principal.addEventListener(
        'input',
        updateQardPreview
    );

    installments.addEventListener(
        'input',
        updateQardPreview
    );


    frequency.addEventListener('change', function () {

        const text =
            this.options[this.selectedIndex]?.text || '';

        document.getElementById('sideFrequency')
            .textContent =
                text;

    });


    document
        .querySelectorAll('input[name="purpose"]')
        .forEach(input => {

            input.addEventListener('change', function () {

                const label =
                    document.querySelector(
                        `label[for="${this.id}"] strong`
                    );

                document.getElementById('previewPurpose')
                    .textContent =
                        label
                            ? label.textContent
                            : '';

            });

        });


    function previewSaveQard() {

        if (!borrower.value) {

            alert(
                'Please select a borrower.'
            );

            borrower.focus();

            return;

        }


        if (
            !principal.value ||
            Number(principal.value) <= 0
        ) {

            alert(
                'Please enter a valid principal amount.'
            );

            principal.focus();

            return;

        }


        if (
            !installments.value ||
            Number(installments.value) <= 0
        ) {

            alert(
                'Please enter a valid number of installments.'
            );

            installments.focus();

            return;

        }


        alert(
            'UI preview only.\n\n' +
            'The Qard record is ready to be created once backend functionality is connected.'
        );

    }


    updateQardPreview();

</script>
@endpush