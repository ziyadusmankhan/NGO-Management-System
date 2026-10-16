@extends('layouts.app')

@section('title', 'New Procurement')

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
        font-size:25px;
        font-weight:700;
        letter-spacing:-.5px;
    }

    .page-header p {
        margin:7px 0 0;
        color:var(--muted);
        font-size:15px;
    }

    .actions {
        display:flex;
        gap:8px;
        flex-wrap:wrap;
    }

    .ui-btn {
        height:40px;
        padding:0 14px;
        border-radius:10px;
        display:inline-flex;
        align-items:center;
        justify-content:center;
        gap:7px;
        font-size:14px;
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
        font-size:15px;
    }

    .card-head span {
        display:block;
        margin-top:4px;
        color:#929d97;
        font-size:13px;
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
        font-size:14px;
        font-weight:650;
    }

    .required {
        color:#b75d4d;
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
        font-size:14px;
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
        border-color:#b8cbc1;
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
        color:#8d9892;
        font-size:14px;
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

    .category-option label i {
        font-size:17px;
    }

    .category-option label strong {
        font-size:13px;
    }

    .category-option label span {
        color:#929d97;
        font-size:12px;
    }

    .category-option input:checked + label {
        background:var(--primary-soft);
        border-color:#c7d8d0;
        color:var(--primary);
    }

    /* ITEMS */

    .items-table-wrap {
        overflow-x:auto;
    }

    .items-table {
        width:100%;
        min-width:760px;
        border-collapse:collapse;
    }

    .items-table th {
        padding:10px;
        background:#fafcfb;
        border-bottom:1px solid #eef1ef;
        color:#96a19b;
        font-size:13px;
        text-transform:uppercase;
        letter-spacing:.5px;
        text-align:left;
    }

    .items-table td {
        padding:9px;
        border-bottom:1px solid #f0f3f1;
    }

    .item-input {
        width:100%;
        height:36px;
        border:1px solid var(--border);
        border-radius:8px;
        padding:0 9px;
        font-size:13px;
        outline:none;
    }

    .item-input:focus {
        border-color:#b8cbc1;
        box-shadow:0 0 0 3px rgba(23,63,53,.045);
    }

    .remove-item {
        width:28px;
        height:28px;
        border:1px solid #ead8d5;
        background:#fff;
        color:#a85f58;
        border-radius:7px;
        cursor:pointer;
    }

    .items-footer {
        display:flex;
        justify-content:space-between;
        align-items:center;
        gap:10px;
        margin-top:11px;
    }

    .total-box {
        min-width:220px;
        padding:13px;
        border-radius:10px;
        background:var(--primary-soft);
        border:1px solid #d5e3dd;
    }

    .total-box label {
        display:block;
        color:#98a29d;
        font-size:13px;
        text-transform:uppercase;
    }

    .total-box strong {
        display:block;
        margin-top:5px;
        color:var(--primary);
        font-size:17px;
    }

    /* SUPPLIER */

    .supplier-grid {
        display:grid;
        grid-template-columns:repeat(3,minmax(0,1fr));
        gap:9px;
    }

    .supplier-option {
        position:relative;
    }

    .supplier-option input {
        position:absolute;
        opacity:0;
        pointer-events:none;
    }

    .supplier-option label {
        min-height:72px;
        padding:10px;
        border:1px solid var(--border);
        border-radius:10px;
        display:flex;
        flex-direction:column;
        justify-content:center;
        cursor:pointer;
    }

    .supplier-option label strong {
        font-size:13px;
    }

    .supplier-option label span {
        margin-top:4px;
        color:#929d97;
        font-size:12px;
        line-height:1.4;
    }

    .supplier-option input:checked + label {
        background:var(--primary-soft);
        border-color:#c7d8d0;
        color:var(--primary);
    }

    /* QUOTATIONS */

    .quotation {
        display:flex;
        align-items:center;
        gap:10px;
        padding:11px 0;
        border-bottom:1px solid #f0f3f1;
    }

    .quotation:last-child {
        border-bottom:none;
    }

    .quotation-icon {
        width:34px;
        height:34px;
        border-radius:9px;
        background:#f4f7f5;
        color:var(--primary);
        display:flex;
        align-items:center;
        justify-content:center;
    }

    .quotation-info {
        flex:1;
    }

    .quotation-info strong {
        display:block;
        font-size:13px;
    }

    .quotation-info span {
        display:block;
        margin-top:3px;
        color:#96a19b;
        font-size:13px;
    }

    .quotation-price {
        color:#4d5a53;
        font-size:13px;
        font-weight:650;
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
        font-size:13px;
    }

    .approval-item span {
        display:block;
        margin-top:3px;
        color:#7f8b84;
        font-size:13px;
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
        font-size:24px;
        color:var(--primary);
    }

    .upload strong {
        display:block;
        margin-top:8px;
        font-size:14px;
    }

    .upload span {
        display:block;
        margin-top:4px;
        color:#96a19b;
        font-size:13px;
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
        color:#93a09a;
        font-size:13px;
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
        font-size:20px;
    }

    .preview-name {
        font-size:14px;
        font-weight:700;
    }

    .preview-sub {
        margin-top:4px;
        color:#8b9690;
        font-size:13px;
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
        font-size:13px;
    }

    .preview-row span:first-child {
        color:#95a09a;
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
        font-size:14px;
    }

    .notice p {
        margin:0;
        color:#748079;
        font-size:13px;
        line-height:1.6;
    }

    /* FLOW */

    .flow {
        display:flex;
        flex-direction:column;
        gap:11px;
    }

    .flow-step {
        display:flex;
        align-items:center;
        gap:9px;
    }

    .flow-number {
        width:25px;
        height:25px;
        border-radius:8px;
        background:var(--primary-soft);
        color:var(--primary);
        display:flex;
        align-items:center;
        justify-content:center;
        font-size:13px;
        font-weight:700;
    }

    .flow-step span {
        font-size:13px;
        color:#58655e;
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
        .supplier-grid {
            grid-template-columns:1fr;
        }

        .full {
            grid-column:auto;
        }

        .items-footer {
            flex-direction:column;
            align-items:stretch;
        }

        .total-box {
            width:100%;
        }
    }
</style>
@endpush


@section('content')

<div class="page-header">

    <div>

        <h1>New Procurement Request</h1>

        <p>
            Create a purchasing request and route it through the appropriate approval process.
        </p>

    </div>

    <div class="actions">

        <a
            href="{{ route('operations.procurement.index') }}"
            class="ui-btn"
        >
            <i class="bi bi-arrow-left"></i>
            Back
        </a>

        <button
            class="ui-btn primary"
            onclick="previewCreateProcurement()"
        >
            <i class="bi bi-check-lg"></i>
            Create Request
        </button>

    </div>

</div>


<div class="layout">

    <div>

        <!-- BASIC INFORMATION -->

        <div class="card">

            <div class="card-head">

                <strong>Request Information</strong>

                <span>
                    Define the procurement request and its purpose.
                </span>

            </div>

            <div class="card-body">

                <div class="grid">

                    <div class="full">

                        <label class="label">
                            Request Title <span class="required">*</span>
                        </label>

                        <input
                            id="requestTitle"
                            class="control"
                            placeholder="e.g. Walking Path Materials"
                        >

                    </div>


                    <div>

                        <label class="label">
                            Procurement ID
                        </label>

                        <input
                            class="control"
                            value="PRC-00043"
                            readonly
                        >

                    </div>


                    <div>

                        <label class="label">
                            Request Date
                        </label>

                        <input
                            id="requestDate"
                            type="date"
                            class="control"
                            value="2026-08-18"
                        >

                    </div>


                    <div class="full">

                        <label class="label">
                            Procurement Description <span class="required">*</span>
                        </label>

                        <textarea
                            id="description"
                            class="control"
                            placeholder="Describe what needs to be purchased and why it is required..."
                        ></textarea>

                    </div>

                </div>

            </div>

        </div>


        <!-- CATEGORY -->

        <div class="card">

            <div class="card-head">

                <strong>Procurement Category</strong>

                <span>
                    Select the organizational activity the procurement supports.
                </span>

            </div>

            <div class="card-body">

                <div class="category-grid">

                    <div class="category-option">

                        <input
                            type="radio"
                            name="category"
                            id="project"
                            value="Community Project"
                            checked
                        >

                        <label for="project">

                            <i class="bi bi-buildings"></i>

                            <strong>
                                Community Project
                            </strong>

                            <span>
                                Infrastructure / development
                            </span>

                        </label>

                    </div>


                    <div class="category-option">

                        <input
                            type="radio"
                            name="category"
                            id="welfare"
                            value="Welfare"
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
                            id="business"
                            value="Small Business"
                        >

                        <label for="business">

                            <i class="bi bi-shop"></i>

                            <strong>
                                Small Business
                            </strong>

                            <span>
                                Livelihood assistance
                            </span>

                        </label>

                    </div>

                </div>

            </div>

        </div>


        <!-- ITEMS -->

        <div class="card">

            <div class="card-head">

                <strong>Procurement Items</strong>

                <span>
                    Add each item, quantity and estimated unit cost.
                </span>

            </div>

            <div class="card-body">

                <div class="items-table-wrap">

                    <table class="items-table">

                        <thead>

                            <tr>
                                <th style="width:31%">Item</th>
                                <th style="width:15%">Quantity</th>
                                <th style="width:22%">Unit Cost</th>
                                <th style="width:22%">Total</th>
                                <th style="width:10%"></th>
                            </tr>

                        </thead>

                        <tbody id="itemsBody">

                            <tr>

                                <td>
                                    <input
                                        class="item-input item-name"
                                        placeholder="Item name"
                                        value="Cement Bags"
                                    >
                                </td>

                                <td>
                                    <input
                                        class="item-input item-qty"
                                        type="number"
                                        min="1"
                                        value="50"
                                    >
                                </td>

                                <td>

                                    <div class="money">

                                        <span>PKR</span>

                                        <input
                                            class="item-input item-cost"
                                            type="number"
                                            min="0"
                                            value="1500"
                                            style="padding-left:39px;"
                                        >

                                    </div>

                                </td>

                                <td class="item-total">
                                    PKR 75,000
                                </td>

                                <td>

                                    <button
                                        type="button"
                                        class="remove-item"
                                        onclick="removeItem(this)"
                                    >
                                        <i class="bi bi-trash"></i>
                                    </button>

                                </td>

                            </tr>


                            <tr>

                                <td>
                                    <input
                                        class="item-input item-name"
                                        placeholder="Item name"
                                        value="Sand"
                                    >
                                </td>

                                <td>
                                    <input
                                        class="item-input item-qty"
                                        type="number"
                                        min="1"
                                        value="20"
                                    >
                                </td>

                                <td>

                                    <div class="money">

                                        <span>PKR</span>

                                        <input
                                            class="item-input item-cost"
                                            type="number"
                                            min="0"
                                            value="2500"
                                            style="padding-left:39px;"
                                        >

                                    </div>

                                </td>

                                <td class="item-total">
                                    PKR 50,000
                                </td>

                                <td>

                                    <button
                                        type="button"
                                        class="remove-item"
                                        onclick="removeItem(this)"
                                    >
                                        <i class="bi bi-trash"></i>
                                    </button>

                                </td>

                            </tr>

                        </tbody>

                    </table>

                </div>


                <div class="items-footer">

                    <button
                        type="button"
                        class="ui-btn"
                        onclick="addItem()"
                    >
                        <i class="bi bi-plus-lg"></i>
                        Add Item
                    </button>

                    <div class="total-box">

                        <label>
                            Estimated Total
                        </label>

                        <strong id="itemsTotal">
                            PKR 125,000
                        </strong>

                    </div>

                </div>

            </div>

        </div>


        <!-- FUND & ACTIVITY -->

        <div class="card">

            <div class="card-head">

                <strong>Fund & Linked Activity</strong>

                <span>
                    Connect the procurement to the appropriate source and organizational record.
                </span>

            </div>

            <div class="card-body">

                <div class="grid">

                    <div>

                        <label class="label">
                            Fund <span class="required">*</span>
                        </label>

                        <select
                            id="fund"
                            class="select"
                        >

                            <option value="Community Development Fund">
                                Community Development Fund
                            </option>

                            <option value="General Fund">
                                General Fund
                            </option>

                            <option value="Small Business Fund">
                                Small Business Fund
                            </option>

                            <option value="Education Fund">
                                Education Fund
                            </option>

                            <option value="Emergency Fund">
                                Emergency Fund
                            </option>

                        </select>

                    </div>


                    <div>

                        <label class="label">
                            Linked Record Type
                        </label>

                        <select class="select">

                            <option>
                                Community Project
                            </option>

                            <option>
                                Welfare Activity
                            </option>

                            <option>
                                Small Business Assistance
                            </option>

                        </select>

                    </div>


                    <div class="full">

                        <label class="label">
                            Linked Record
                        </label>

                        <select class="select">

                            <option>
                                PRJ-00014 · Village Walking Path Improvement
                            </option>

                            <option>
                                PRJ-00011 · Community Water Improvement
                            </option>

                            <option>
                                SBA-00041 · Kasso General Store
                            </option>

                        </select>

                    </div>

                </div>

            </div>

        </div>


        <!-- SUPPLIER -->

        <div class="card">

            <div class="card-head">

                <strong>Supplier Information</strong>

                <span>
                    Record the supplier selection or quotation status.
                </span>

            </div>

            <div class="card-body">

                <div class="supplier-grid">

                    <div class="supplier-option">

                        <input
                            type="radio"
                            name="supplier"
                            id="supplierKnown"
                            checked
                        >

                        <label for="supplierKnown">

                            <strong>
                                Existing Supplier
                            </strong>

                            <span>
                                Select from registered suppliers
                            </span>

                        </label>

                    </div>


                    <div class="supplier-option">

                        <input
                            type="radio"
                            name="supplier"
                            id="supplierNew"
                        >

                        <label for="supplierNew">

                            <strong>
                                New Supplier
                            </strong>

                            <span>
                                Create supplier during procurement
                            </span>

                        </label>

                    </div>


                    <div class="supplier-option">

                        <input
                            type="radio"
                            name="supplier"
                            id="supplierQuotes"
                        >

                        <label for="supplierQuotes">

                            <strong>
                                Quotation Comparison
                            </strong>

                            <span>
                                Compare multiple supplier quotations
                            </span>

                        </label>

                    </div>

                </div>


                <div class="grid" style="margin-top:18px;">

                    <div>

                        <label class="label">
                            Supplier
                        </label>

                        <select class="select">

                            <option>
                                Kasso Building Materials
                            </option>

                            <option>
                                Kasso Food Supplier
                            </option>

                            <option>
                                Local Market Supplies
                            </option>

                        </select>

                    </div>


                    <div>

                        <label class="label">
                            Supplier Contact
                        </label>

                        <input
                            class="control"
                            placeholder="Phone / email"
                        >

                    </div>

                </div>

            </div>

        </div>


        <!-- QUOTATIONS -->

        <div class="card">

            <div class="card-head">

                <strong>Quotations</strong>

                <span>
                    Compare and attach supplier quotations where required.
                </span>

            </div>

            <div class="card-body">

                <div class="quotation">

                    <div class="quotation-icon">
                        <i class="bi bi-file-earmark-pdf"></i>
                    </div>

                    <div class="quotation-info">

                        <strong>
                            Kasso Building Materials
                        </strong>

                        <span>
                            Quotation · QTN-1024
                        </span>

                    </div>

                    <div class="quotation-price">
                        PKR 125,000
                    </div>

                </div>


                <div class="quotation">

                    <div class="quotation-icon">
                        <i class="bi bi-file-earmark-pdf"></i>
                    </div>

                    <div class="quotation-info">

                        <strong>
                            Local Construction Supplier
                        </strong>

                        <span>
                            Quotation · QTN-1021
                        </span>

                    </div>

                    <div class="quotation-price">
                        PKR 132,000
                    </div>

                </div>


                <div style="margin-top:13px;">

                    <label class="upload">

                        <i class="bi bi-cloud-arrow-up"></i>

                        <strong>
                            Upload quotation
                        </strong>

                        <span>
                            PDF, image or supplier document
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


        <!-- APPROVAL -->

        <div class="card">

            <div class="card-head">

                <strong>Approval Requirements</strong>

                <span>
                    Confirm the procurement is ready for committee or authorized review.
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
                                Procurement purpose verified
                            </strong>

                            <span>
                                Request is connected to an approved organizational activity.
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
                                Correct fund identified
                            </strong>

                            <span>
                                Selected fund is appropriate for the requested purchase.
                            </span>

                        </div>

                    </label>


                    <label class="approval-item">

                        <input
                            type="checkbox"
                        >

                        <div>

                            <strong>
                                Quotations reviewed
                            </strong>

                            <span>
                                Required supplier comparisons have been completed.
                            </span>

                        </div>

                    </label>


                    <label class="approval-item">

                        <input
                            type="checkbox"
                        >

                        <div>

                            <strong>
                                Authorized approval obtained
                            </strong>

                            <span>
                                Appropriate authority has approved the procurement request.
                            </span>

                        </div>

                    </label>

                </div>

            </div>

        </div>


        <!-- DOCUMENTS -->

        <div class="card">

            <div class="card-head">

                <strong>Supporting Documents</strong>

                <span>
                    Attach specifications, quotations, approvals and other records.
                </span>

            </div>

            <div class="card-body">

                <label class="upload">

                    <i class="bi bi-cloud-arrow-up"></i>

                    <strong>
                        Upload procurement documents
                    </strong>

                    <span>
                        Specifications, quotations, approvals, invoices or other evidence
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
                Procurement Preview
            </div>

            <div class="preview-icon">
                <i class="bi bi-cart-check"></i>
            </div>

            <div
                class="preview-name"
                id="previewTitle"
            >
                New Procurement
            </div>

            <div class="preview-sub">
                PRC-00043
            </div>

            <div class="preview-divider"></div>

            <div class="preview-row">

                <span>
                    Category
                </span>

                <span id="previewCategory">
                    Community Project
                </span>

            </div>


            <div class="preview-row">

                <span>
                    Fund
                </span>

                <span id="previewFund">
                    Community Development Fund
                </span>

            </div>


            <div class="preview-row">

                <span>
                    Estimated Total
                </span>

                <span id="previewTotal">
                    PKR 125,000
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


        <!-- CONTROL -->

        <div class="card">

            <div class="card-head">
                <strong>Procurement Control</strong>
            </div>

            <div class="card-body">

                <div class="notice">

                    <i class="bi bi-shield-check"></i>

                    <p>
                        Procurement should remain connected to an approved
                        purpose, authorized fund, supplier evidence and
                        appropriate approval before an order is placed.
                    </p>

                </div>

            </div>

        </div>


        <!-- FLOW -->

        <div class="card">

            <div class="card-head">
                <strong>Procurement Workflow</strong>
            </div>

            <div class="card-body">

                <div class="flow">

                    <div class="flow-step">
                        <div class="flow-number">1</div>
                        <span>Request Created</span>
                    </div>

                    <div class="flow-step">
                        <div class="flow-number">2</div>
                        <span>Quotation / Supplier Review</span>
                    </div>

                    <div class="flow-step">
                        <div class="flow-number">3</div>
                        <span>Approval</span>
                    </div>

                    <div class="flow-step">
                        <div class="flow-number">4</div>
                        <span>Purchase Order</span>
                    </div>

                    <div class="flow-step">
                        <div class="flow-number">5</div>
                        <span>Receive Items</span>
                    </div>

                    <div class="flow-step">
                        <div class="flow-number">6</div>
                        <span>Invoice / Expense</span>
                    </div>

                </div>

            </div>

        </div>

    </aside>

</div>

@endsection


@push('scripts')
<script>

    const requestTitle =
        document.getElementById('requestTitle');

    const fund =
        document.getElementById('fund');


    function money(value) {

        return 'PKR ' +
            Number(value || 0)
                .toLocaleString('en-PK');

    }


    requestTitle.addEventListener('input', function () {

        document.getElementById('previewTitle')
            .textContent =
                this.value.trim() ||
                'New Procurement';

    });


    fund.addEventListener('change', function () {

        document.getElementById('previewFund')
            .textContent =
                this.value;

    });


    document
        .querySelectorAll(
            'input[name="category"]'
        )
        .forEach(input => {

            input.addEventListener(
                'change',
                function () {

                    document.getElementById(
                        'previewCategory'
                    ).textContent =
                        this.value;

                }
            );

        });


    function updateItemsTotal() {

        let total = 0;


        document
            .querySelectorAll('#itemsBody tr')
            .forEach(row => {

                const qty =
                    Number(
                        row.querySelector(
                            '.item-qty'
                        )?.value || 0
                    );

                const cost =
                    Number(
                        row.querySelector(
                            '.item-cost'
                        )?.value || 0
                    );

                const itemTotal =
                    qty * cost;


                const totalCell =
                    row.querySelector(
                        '.item-total'
                    );

                if (totalCell) {

                    totalCell.textContent =
                        money(itemTotal);

                }


                total += itemTotal;

            });


        document.getElementById(
            'itemsTotal'
        ).textContent =
            money(total);


        document.getElementById(
            'previewTotal'
        ).textContent =
            money(total);

    }


    function attachItemListeners(row) {

        row.querySelectorAll(
            '.item-qty, .item-cost'
        ).forEach(input => {

            input.addEventListener(
                'input',
                updateItemsTotal
            );

        });

    }


    function addItem() {

        const row =
            document.createElement('tr');

        row.innerHTML = `

            <td>

                <input
                    class="item-input item-name"
                    placeholder="Item name"
                >

            </td>


            <td>

                <input
                    class="item-input item-qty"
                    type="number"
                    min="1"
                    value="1"
                >

            </td>


            <td>

                <div class="money">

                    <span>PKR</span>

                    <input
                        class="item-input item-cost"
                        type="number"
                        min="0"
                        value="0"
                        style="padding-left:39px;"
                    >

                </div>

            </td>


            <td class="item-total">
                PKR 0
            </td>


            <td>

                <button
                    type="button"
                    class="remove-item"
                    onclick="removeItem(this)"
                >
                    <i class="bi bi-trash"></i>
                </button>

            </td>

        `;


        document
            .getElementById('itemsBody')
            .appendChild(row);


        attachItemListeners(row);

        updateItemsTotal();

    }


    function removeItem(button) {

        const rows =
            document.querySelectorAll(
                '#itemsBody tr'
            );


        if (rows.length <= 1) {

            alert(
                'At least one procurement item is required.'
            );

            return;

        }


        button
            .closest('tr')
            .remove();


        updateItemsTotal();

    }


    document
        .querySelectorAll(
            '#itemsBody tr'
        )
        .forEach(
            attachItemListeners
        );


    function previewCreateProcurement() {

        if (!requestTitle.value.trim()) {

            alert(
                'Please enter the procurement request title.'
            );

            requestTitle.focus();

            return;

        }


        const rows =
            document.querySelectorAll(
                '#itemsBody tr'
            );


        let validItem =
            false;


        rows.forEach(row => {

            const name =
                row.querySelector(
                    '.item-name'
                )?.value.trim();

            const qty =
                Number(
                    row.querySelector(
                        '.item-qty'
                    )?.value || 0
                );


            if (
                name &&
                qty > 0
            ) {

                validItem = true;

            }

        });


        if (!validItem) {

            alert(
                'Please add at least one valid procurement item.'
            );

            return;

        }


        alert(
            'UI preview only.\n\n' +
            'This procurement request is ready to be created once backend functionality is connected.'
        );

    }


    updateItemsTotal();

</script>
@endpush