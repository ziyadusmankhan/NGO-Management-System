@extends('layouts.app')

@section('title', 'Business Assistance Disbursement')

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
        background:#eef3f8;
        color:#55758d;
        font-size: 13px;
        font-weight:650;
        white-space:nowrap;
    }

    .status::before {
        content:"";
        width:5px;
        height:5px;
        border-radius:50%;
        background:#6d8ea5;
    }

    /* SUMMARY */

    .summary-grid {
        display:grid;
        grid-template-columns:repeat(3,minmax(0,1fr));
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

    /* FORM */

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
        min-height:95px;
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

    /* RELEASE METHOD */

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
        min-height:72px;
        padding:10px;
        border:1px solid var(--border);
        border-radius:10px;
        display:flex;
        flex-direction:column;
        justify-content:center;
        align-items:center;
        text-align:center;
        gap:6px;
        cursor:pointer;
    }

    .method-option label i {
        font-size: 18px;
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

    /* PROCUREMENT */

    .item-table-wrap {
        overflow-x:auto;
    }

    .item-table {
        width:100%;
        min-width:720px;
        border-collapse:collapse;
    }

    .item-table th {
        padding:10px;
        background:#fafcfb;
        border-bottom:1px solid #eef1ef;
        color: #6c7470;
        text-align:left;
        font-size: 13px;
        text-transform:uppercase;
        letter-spacing:.5px;
    }

    .item-table td {
        padding:9px 10px;
        border-bottom:1px solid #f0f3f1;
        font-size: 14px;
    }

    .item-table tr:last-child td {
        border-bottom:none;
    }

    .inline-control {
        width:100%;
        height:34px;
        border:1px solid var(--border);
        border-radius:7px;
        padding:0 8px;
        background:#fff;
        font-size: 14px;
        outline:none;
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

    /* CONFIRMATION */

    .confirmation {
        padding:14px;
        background:#f7f9f8;
        border:1px solid #e5ebe8;
        border-radius:10px;
    }

    .confirmation-row {
        display:flex;
        justify-content:space-between;
        gap:10px;
        padding:8px 0;
        border-bottom:1px solid #e4eae7;
        font-size: 14px;
    }

    .confirmation-row:last-child {
        border-bottom:none;
    }

    .confirmation-row span:first-child {
        color: #6d7571;
    }

    .confirmation-row span:last-child {
        color:#4a5750;
        font-weight:650;
        text-align:right;
    }

    /* DOCUMENTS */

    .upload {
        border:1px dashed #cbd6d0;
        border-radius:11px;
        padding:22px;
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

    /* WORKFLOW */

    .workflow {
        display:flex;
        align-items:flex-start;
        overflow-x:auto;
        padding-bottom:4px;
    }

    .workflow-stage {
        min-width:125px;
        text-align:center;
        position:relative;
    }

    .workflow-stage:not(:last-child)::after {
        content:"";
        position:absolute;
        width:35px;
        height:1px;
        top:15px;
        right:-17px;
        background:#d9e2de;
    }

    .workflow-circle {
        width:30px;
        height:30px;
        margin:0 auto 8px;
        border-radius:9px;
        display:flex;
        align-items:center;
        justify-content:center;
        font-size: 14px;
        font-weight:700;
        position:relative;
        z-index:1;
    }

    .workflow-circle.done {
        background:var(--primary);
        color:#fff;
    }

    .workflow-circle.current {
        background:#cbdce7;
        color:#536f7e;
    }

    .workflow-circle.pending {
        background:#eef1ef;
        color: #6d7671;
    }

    .workflow-stage strong {
        display:block;
        font-size: 13px;
    }

    .workflow-stage span {
        display:block;
        margin-top:3px;
        color: #6d7571;
        font-size: 12px;
    }

    /* SIDEBAR */

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

        .summary-grid,
        .form-grid,
        .method-grid {
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
                Disbursement
            </div>

            <div class="meta">
                SBA-00041 · Rahim Khan · Kasso General Store
            </div>

        </div>

    </div>

    <div class="actions">

        <a
            href="{{ route('business.applications.agreement', ['id' => 1]) }}"
            class="ui-btn"
        >
            <i class="bi bi-arrow-left"></i>
            Back
        </a>

        <a
            href="{{ route('business.applications.monitoring', ['id' => 1]) }}"
            class="ui-btn success"
        >
            <i class="bi bi-graph-up-arrow"></i>
            Start Monitoring
        </a>

    </div>

</div>


<div class="layout">

    <div>

        <!-- STATUS -->

        <div class="card">

            <div class="card-head">

                <div>
                    <strong>Disbursement Status</strong>

                    <span>
                        Approved assistance ready for controlled release.
                    </span>
                </div>

                <span class="status">
                    Ready for Release
                </span>

            </div>

            <div class="card-body">

                <div class="summary-grid">

                    <div class="summary-box">
                        <label>Application</label>
                        <strong>SBA-00041</strong>
                    </div>

                    <div class="summary-box">
                        <label>Approved Amount</label>
                        <strong>PKR 60,000</strong>
                    </div>

                    <div class="summary-box highlight">
                        <label>Fund</label>
                        <strong>Small Business Fund</strong>
                    </div>

                    <div class="summary-box">
                        <label>Applicant Contribution</label>
                        <strong>PKR 20,000</strong>
                    </div>

                    <div class="summary-box">
                        <label>Agreement</label>
                        <strong>AGR-SBA-00041</strong>
                    </div>

                    <div class="summary-box">
                        <label>Approval</label>
                        <strong>DEC-SBA-00041</strong>
                    </div>

                </div>

            </div>

        </div>


        <!-- APPLICANT -->

        <div class="card">

            <div class="card-head">

                <div>
                    <strong>Beneficiary</strong>

                    <span>
                        Person receiving the approved livelihood support.
                    </span>
                </div>

            </div>

            <div class="card-body">

                <div class="summary-grid">

                    <div class="summary-box">
                        <label>Name</label>
                        <strong>Rahim Khan</strong>
                    </div>

                    <div class="summary-box">
                        <label>Beneficiary ID</label>
                        <strong>BEN-00063</strong>
                    </div>

                    <div class="summary-box">
                        <label>Business</label>
                        <strong>Kasso General Store</strong>
                    </div>

                    <div class="summary-box">
                        <label>Location</label>
                        <strong>Kasso Main Market</strong>
                    </div>

                    <div class="summary-box">
                        <label>Applicant Contribution</label>
                        <strong>PKR 20,000</strong>
                    </div>

                    <div class="summary-box">
                        <label>Expected Start</label>
                        <strong>September 2026</strong>
                    </div>

                </div>

            </div>

        </div>


        <!-- RELEASE METHOD -->

        <div class="card">

            <div class="card-head">

                <div>
                    <strong>Disbursement Method</strong>

                    <span>
                        Select how the approved contribution will be released.
                    </span>
                </div>

            </div>

            <div class="card-body">

                <div class="method-grid">

                    <div class="method-option">

                        <input
                            type="radio"
                            name="method"
                            id="methodAssets"
                            checked
                        >

                        <label for="methodAssets">

                            <i class="bi bi-box-seam"></i>

                            <strong>
                                Direct Asset Purchase
                            </strong>

                            <span>
                                Organization purchases approved items.
                            </span>

                        </label>

                    </div>


                    <div class="method-option">

                        <input
                            type="radio"
                            name="method"
                            id="methodVendor"
                        >

                        <label for="methodVendor">

                            <i class="bi bi-shop-window"></i>

                            <strong>
                                Vendor Payment
                            </strong>

                            <span>
                                Organization pays approved supplier.
                            </span>

                        </label>

                    </div>


                    <div class="method-option">

                        <input
                            type="radio"
                            name="method"
                            id="methodCash"
                        >

                        <label for="methodCash">

                            <i class="bi bi-wallet2"></i>

                            <strong>
                                Controlled Cash Release
                            </strong>

                            <span>
                                Authorized cash release where permitted.
                            </span>

                        </label>

                    </div>

                </div>

            </div>

        </div>


        <!-- PROCUREMENT -->

        <div class="card">

            <div class="card-head">

                <div>
                    <strong>Approved Items / Procurement</strong>

                    <span>
                        Record the items or purchases covered by the approved assistance.
                    </span>
                </div>

            </div>

            <div class="card-body">

                <div class="item-table-wrap">

                    <table class="item-table">

                        <thead>

                            <tr>
                                <th>Item / Purpose</th>
                                <th>Supplier</th>
                                <th>Quantity</th>
                                <th>Unit Cost</th>
                                <th>Total</th>
                                <th>Verified</th>
                            </tr>

                        </thead>

                        <tbody>

                            <tr>

                                <td>
                                    Grocery inventory
                                </td>

                                <td>
                                    Kasso Wholesale
                                </td>

                                <td>
                                    <input
                                        class="inline-control"
                                        value="1"
                                    >
                                </td>

                                <td>
                                    <input
                                        class="inline-control"
                                        value="PKR 35,000"
                                    >
                                </td>

                                <td>
                                    PKR 35,000
                                </td>

                                <td>
                                    <input
                                        type="checkbox"
                                        checked
                                    >
                                </td>

                            </tr>


                            <tr>

                                <td>
                                    Display shelves
                                </td>

                                <td>
                                    Local Supplier
                                </td>

                                <td>
                                    <input
                                        class="inline-control"
                                        value="2"
                                    >
                                </td>

                                <td>
                                    <input
                                        class="inline-control"
                                        value="PKR 8,000"
                                    >
                                </td>

                                <td>
                                    PKR 16,000
                                </td>

                                <td>
                                    <input
                                        type="checkbox"
                                        checked
                                    >
                                </td>

                            </tr>


                            <tr>

                                <td>
                                    Business equipment
                                </td>

                                <td>
                                    Local Supplier
                                </td>

                                <td>
                                    <input
                                        class="inline-control"
                                        value="1"
                                    >
                                </td>

                                <td>
                                    <input
                                        class="inline-control"
                                        value="PKR 9,000"
                                    >
                                </td>

                                <td>
                                    PKR 9,000
                                </td>

                                <td>
                                    <input
                                        type="checkbox"
                                        checked
                                    >
                                </td>

                            </tr>

                        </tbody>

                    </table>

                </div>

            </div>

        </div>


        <!-- FINANCIAL RELEASE -->

        <div class="card">

            <div class="card-head">

                <div>
                    <strong>Financial Release</strong>

                    <span>
                        Record the financial transaction associated with the disbursement.
                    </span>
                </div>

            </div>

            <div class="card-body">

                <div class="form-grid">

                    <div>

                        <label class="label">
                            Disbursement Amount
                        </label>

                        <input
                            id="releaseAmount"
                            class="control"
                            value="PKR 60,000"
                        >

                    </div>


                    <div>

                        <label class="label">
                            Release Date
                        </label>

                        <input
                            type="date"
                            class="control"
                            value="2026-08-16"
                        >

                    </div>


                    <div>

                        <label class="label">
                            Payment Reference
                        </label>

                        <input
                            class="control"
                            value="DIS-SBA-00041"
                        >

                    </div>


                    <div>

                        <label class="label">
                            Authorized By
                        </label>

                        <select class="select">

                            <option selected>
                                Treasurer
                            </option>

                            <option>
                                General Secretary
                            </option>

                            <option>
                                Authorized Finance Officer
                            </option>

                        </select>

                    </div>


                    <div class="full">

                        <label class="label">
                            Financial Notes
                        </label>

                        <textarea class="control">Release is subject to completion of all approved conditions. The transaction should be recorded against the Small Business Fund and linked to the approved application and agreement.</textarea>

                    </div>

                </div>

            </div>

        </div>


        <!-- PRE-RELEASE CHECKLIST -->

        <div class="card">

            <div class="card-head">

                <div>
                    <strong>Pre-Disbursement Checklist</strong>

                    <span>
                        All required controls should be satisfied before release.
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
                            Final approval is recorded.
                        </span>

                    </label>


                    <label class="check-item">

                        <input
                            type="checkbox"
                            checked
                        >

                        <span>
                            Agreement has been prepared and reviewed.
                        </span>

                    </label>


                    <label class="check-item">

                        <input
                            type="checkbox"
                            checked
                        >

                        <span>
                            Required applicant contribution has been confirmed.
                        </span>

                    </label>


                    <label class="check-item">

                        <input
                            type="checkbox"
                            checked
                        >

                        <span>
                            Approved quotations and procurement items have been verified.
                        </span>

                    </label>


                    <label class="check-item">

                        <input
                            type="checkbox"
                        >

                        <span>
                            Required signatures have been completed.
                        </span>

                    </label>


                    <label class="check-item">

                        <input
                            type="checkbox"
                        >

                        <span>
                            Financial transaction reference is ready for posting.
                        </span>

                    </label>

                </div>

            </div>

        </div>


        <!-- CONFIRMATION -->

        <div class="card">

            <div class="card-head">

                <div>
                    <strong>Release Confirmation</strong>

                    <span>
                        Final review before marking the assistance as disbursed.
                    </span>
                </div>

            </div>

            <div class="card-body">

                <div class="confirmation">

                    <div class="confirmation-row">
                        <span>Beneficiary</span>
                        <span>Rahim Khan</span>
                    </div>

                    <div class="confirmation-row">
                        <span>Application</span>
                        <span>SBA-00041</span>
                    </div>

                    <div class="confirmation-row">
                        <span>Agreement</span>
                        <span>AGR-SBA-00041</span>
                    </div>

                    <div class="confirmation-row">
                        <span>Fund</span>
                        <span>Small Business Fund</span>
                    </div>

                    <div class="confirmation-row">
                        <span>Amount</span>
                        <span>PKR 60,000</span>
                    </div>

                    <div class="confirmation-row">
                        <span>Release Method</span>
                        <span>Direct Asset Purchase</span>
                    </div>

                </div>

            </div>

        </div>


        <!-- DOCUMENTS -->

        <div class="card">

            <div class="card-head">

                <div>
                    <strong>Disbursement Records</strong>

                    <span>
                        Attach purchase records, receipts and payment evidence.
                    </span>
                </div>

            </div>

            <div class="card-body">

                <label class="upload">

                    <i class="bi bi-cloud-arrow-up"></i>

                    <strong>
                        Upload disbursement documents
                    </strong>

                    <span>
                        Receipts, payment confirmations, invoices and procurement records
                    </span>

                    <input
                        type="file"
                        multiple
                        hidden
                    >

                </label>

            </div>

        </div>


        <!-- WORKFLOW -->

        <div class="card">

            <div class="card-head">

                <div>
                    <strong>Workflow</strong>

                    <span>
                        Disbursement is the release stage before business monitoring begins.
                    </span>
                </div>

            </div>

            <div class="card-body">

                <div class="workflow">

                    <div class="workflow-stage">

                        <div class="workflow-circle done">
                            <i class="bi bi-check-lg"></i>
                        </div>

                        <strong>Application</strong>
                        <span>Complete</span>

                    </div>


                    <div class="workflow-stage">

                        <div class="workflow-circle done">
                            <i class="bi bi-check-lg"></i>
                        </div>

                        <strong>Verification</strong>
                        <span>Complete</span>

                    </div>


                    <div class="workflow-stage">

                        <div class="workflow-circle done">
                            <i class="bi bi-check-lg"></i>
                        </div>

                        <strong>Viability</strong>
                        <span>Complete</span>

                    </div>


                    <div class="workflow-stage">

                        <div class="workflow-circle done">
                            <i class="bi bi-check-lg"></i>
                        </div>

                        <strong>Recommendation</strong>
                        <span>Complete</span>

                    </div>


                    <div class="workflow-stage">

                        <div class="workflow-circle done">
                            <i class="bi bi-check-lg"></i>
                        </div>

                        <strong>Approval</strong>
                        <span>Complete</span>

                    </div>


                    <div class="workflow-stage">

                        <div class="workflow-circle done">
                            <i class="bi bi-check-lg"></i>
                        </div>

                        <strong>Agreement</strong>
                        <span>Complete</span>

                    </div>


                    <div class="workflow-stage">

                        <div class="workflow-circle current">
                            7
                        </div>

                        <strong>Disbursement</strong>
                        <span>Current</span>

                    </div>

                </div>

            </div>

        </div>

    </div>


    <aside>

        <div class="card">

            <div class="card-body">

                <div class="side-number">
                    PKR 60K
                </div>

                <div class="side-label">
                    Approved Disbursement
                </div>

                <div style="
                    height:1px;
                    background:#eef1ef;
                    margin:16px 0;
                "></div>

                <div class="side-row">
                    <span>Status</span>
                    <span>Ready for Release</span>
                </div>

                <div class="side-row">
                    <span>Beneficiary</span>
                    <span>Rahim Khan</span>
                </div>

                <div class="side-row">
                    <span>Fund</span>
                    <span>Small Business Fund</span>
                </div>

                <div class="side-row">
                    <span>Agreement</span>
                    <span>AGR-SBA-00041</span>
                </div>

                <div class="side-row">
                    <span>Method</span>
                    <span>Asset Purchase</span>
                </div>

            </div>

        </div>


        <div class="card">

            <div class="card-head">
                <strong>Financial Control</strong>
            </div>

            <div class="card-body">

                <div class="notice">

                    <i class="bi bi-shield-check"></i>

                    <p>
                        Disbursement must remain linked to the approved
                        application, fund, agreement and supporting
                        financial records.
                    </p>

                </div>

            </div>

        </div>

    </aside>

</div>

@endsection


@push('scripts')
<script>

    function previewRelease() {

        const confirmed =
            confirm(
                'Confirm release of PKR 60,000 for SBA-00041?'
            );

        if (!confirmed) {
            return;
        }

        alert(
            'UI preview only.\n\n' +
            'The disbursement would be posted to the financial system and the business would move to monitoring once backend functionality is connected.'
        );

    }

</script>
@endpush