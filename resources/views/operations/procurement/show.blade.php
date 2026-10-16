@extends('layouts.app')

@section('title', 'Procurement Details')

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
        font-size:22px;
    }

    .title {
        font-size:23px;
        font-weight:700;
        letter-spacing:-.4px;
    }

    .meta {
        margin-top:4px;
        color:#8d9993;
        font-size:14px;
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
        font-size:14px;
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
        border-color:#d5e8da;
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
        font-size:15px;
    }

    .card-head span {
        display:block;
        margin-top:4px;
        color:#929d97;
        font-size:13px;
    }

    .card-body {
        padding:18px 19px;
    }

    .status,
    .approval,
    .category {
        display:inline-flex;
        align-items:center;
        gap:5px;
        padding:5px 8px;
        border-radius:20px;
        font-size:13px;
        font-weight:650;
        white-space:nowrap;
    }

    .status::before,
    .approval::before {
        content:"";
        width:5px;
        height:5px;
        border-radius:50%;
    }

    .status.review {
        background:#f1eef7;
        color:#77668c;
    }

    .status.review::before {
        background:#8c77a3;
    }

    .approval.pending {
        background:#fff5e9;
        color:#a8793e;
    }

    .approval.pending::before {
        background:#d19a4d;
    }

    .category.project {
        background:#f1eef7;
        color:#77668c;
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
        border-color:#d5e3dd;
    }

    .summary-box.warning {
        background:#fff8ee;
        border-color:#f0dfc2;
    }

    .summary-box label {
        display:block;
        color:#98a29d;
        font-size:13px;
        text-transform:uppercase;
        letter-spacing:.5px;
    }

    .summary-box strong {
        display:block;
        margin-top:5px;
        font-size:15px;
    }

    .summary-box.highlight strong {
        color:var(--primary);
    }

    .summary-box.warning strong {
        color:#a8793e;
    }

    /* INFO */

    .info-grid {
        display:grid;
        grid-template-columns:repeat(2,minmax(0,1fr));
        gap:16px;
    }

    .info label {
        display:block;
        margin-bottom:5px;
        color:#98a29d;
        font-size:13px;
        text-transform:uppercase;
        letter-spacing:.5px;
    }

    .info strong {
        display:block;
        color:#46534c;
        font-size:14px;
    }

    .info p {
        margin:0;
        color:#68756e;
        font-size:13px;
        line-height:1.65;
    }

    .full {
        grid-column:1/-1;
    }

    /* ITEMS */

    .table-wrap {
        overflow-x:auto;
    }

    .items-table {
        width:100%;
        min-width:700px;
        border-collapse:collapse;
    }

    .items-table th {
        padding:10px 11px;
        background:#fafcfb;
        border-bottom:1px solid #eef1ef;
        color:#96a19b;
        font-size:13px;
        text-transform:uppercase;
        letter-spacing:.5px;
        text-align:left;
    }

    .items-table td {
        padding:11px;
        border-bottom:1px solid #f0f3f1;
        font-size:13px;
    }

    .items-table tr:last-child td {
        border-bottom:none;
    }

    .amount {
        font-weight:650;
    }

    .total-row td {
        background:#fafcfb;
        font-weight:700;
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
        font-size:15px;
        font-weight:700;
    }

    .fund-type {
        padding:5px 8px;
        border-radius:20px;
        background:#eef3f8;
        color:#55758d;
        font-size:13px;
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
        color:#98a29d;
        font-size:12px;
        text-transform:uppercase;
    }

    .fund-stat strong {
        display:block;
        margin-top:4px;
        font-size:14px;
    }

    /* SUPPLIER */

    .supplier-box {
        display:flex;
        align-items:center;
        gap:11px;
        padding:13px;
        border:1px solid #edf1ee;
        background:#fafcfb;
        border-radius:10px;
    }

    .supplier-icon {
        width:38px;
        height:38px;
        border-radius:10px;
        background:var(--primary-soft);
        color:var(--primary);
        display:flex;
        align-items:center;
        justify-content:center;
    }

    .supplier-info {
        flex:1;
    }

    .supplier-info strong {
        display:block;
        font-size:14px;
    }

    .supplier-info span {
        display:block;
        margin-top:3px;
        color:#96a19b;
        font-size:13px;
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
        font-size:13px;
    }

    .document-info span {
        display:block;
        margin-top:3px;
        color:#96a19b;
        font-size:13px;
    }

    .doc-btn {
        width:29px;
        height:29px;
        border:1px solid var(--border);
        background:#fff;
        border-radius:7px;
        color:#748079;
    }

    /* APPROVAL */

    .approval-grid {
        display:grid;
        grid-template-columns:repeat(2,minmax(0,1fr));
        gap:10px;
    }

    .approval-box {
        padding:13px;
        border:1px solid #edf1ee;
        background:#fafcfb;
        border-radius:10px;
    }

    .approval-box label {
        display:block;
        color:#98a29d;
        font-size:13px;
        text-transform:uppercase;
    }

    .approval-box strong {
        display:block;
        margin-top:5px;
        font-size:14px;
    }

    .approval-box.pending {
        background:#fff8ee;
        border-color:#f0dfc2;
    }

    .approval-box.pending strong {
        color:#a8793e;
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
        font-size:14px;
        font-weight:650;
    }

    .timeline-meta {
        margin-top:3px;
        color:#929d97;
        font-size:13px;
    }

    .timeline-note {
        margin-top:6px;
        color:#6d7972;
        font-size:13px;
        line-height:1.5;
    }

    /* NOTES */

    .note {
        padding:12px;
        background:#fafcfb;
        border:1px solid #edf1ee;
        border-radius:9px;
        color:#6e7a73;
        font-size:13px;
        line-height:1.6;
    }

    /* SIDEBAR */

    .side-number {
        text-align:center;
        font-size:30px;
        font-weight:700;
        color:var(--primary);
    }

    .side-label {
        text-align:center;
        margin-top:3px;
        color:#8d9892;
        font-size:13px;
    }

    .side-row {
        display:flex;
        justify-content:space-between;
        gap:10px;
        padding:9px 0;
        border-bottom:1px solid #f0f3f1;
        font-size:13px;
    }

    .side-row:last-child {
        border-bottom:none;
    }

    .side-row span:first-child {
        color:#98a29d;
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
        font-size:14px;
    }

    .notice p {
        margin:0;
        color:#748079;
        font-size:13px;
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

<div class="page-header">

    <div class="identity">

        <div class="icon-box">
            <i class="bi bi-cart-check"></i>
        </div>

        <div>

            <div class="title">
                PRC-00042
            </div>

            <div class="meta">
                Walking Path Materials · Community Development · 17 Aug 2026
            </div>

        </div>

    </div>

    <div class="actions">

        <a
            href="{{ route('operations.procurement.index') }}"
            class="ui-btn"
        >
            <i class="bi bi-arrow-left"></i>
            Back
        </a>

        <a
            href="{{ route('operations.procurement.create') }}"
            class="ui-btn primary"
        >
            <i class="bi bi-pencil"></i>
            Edit
        </a>

        <button
            class="ui-btn success"
            onclick="previewApprove()"
        >
            <i class="bi bi-check-lg"></i>
            Approve
        </button>

    </div>

</div>


<div class="layout">

    <div>

        <!-- OVERVIEW -->

        <div class="card">

            <div class="card-head">

                <div>
                    <strong>Procurement Overview</strong>

                    <span>
                        Current status and financial position of the request.
                    </span>
                </div>

                <span class="status review">
                    Under Review
                </span>

            </div>

            <div class="card-body">

                <div class="summary-grid">

                    <div class="summary-box highlight">
                        <label>Estimated Total</label>
                        <strong>PKR 125,000</strong>
                    </div>

                    <div class="summary-box">
                        <label>Procurement ID</label>
                        <strong>PRC-00042</strong>
                    </div>

                    <div class="summary-box">
                        <label>Category</label>
                        <strong>Community Project</strong>
                    </div>

                    <div class="summary-box">
                        <label>Request Date</label>
                        <strong>17 Aug 2026</strong>
                    </div>

                    <div class="summary-box">
                        <label>Approval</label>
                        <strong>Pending</strong>
                    </div>

                    <div class="summary-box">
                        <label>Supplier</label>
                        <strong>Kasso Building Materials</strong>
                    </div>

                    <div class="summary-box">
                        <label>Linked Project</label>
                        <strong>PRJ-00014</strong>
                    </div>

                    <div class="summary-box warning">
                        <label>Next Action</label>
                        <strong>Committee Review</strong>
                    </div>

                </div>

            </div>

        </div>


        <!-- DESCRIPTION -->

        <div class="card">

            <div class="card-head">

                <div>
                    <strong>Request Details</strong>

                    <span>
                        Purpose and description of the procurement.
                    </span>
                </div>

                <span class="category project">
                    Community Project
                </span>

            </div>

            <div class="card-body">

                <div class="info-grid">

                    <div class="info">

                        <label>Request Title</label>

                        <strong>
                            Walking Path Materials
                        </strong>

                    </div>


                    <div class="info">

                        <label>Requested By</label>

                        <strong>
                            Community Development Committee
                        </strong>

                    </div>


                    <div class="info">

                        <label>Linked Project</label>

                        <strong>
                            PRJ-00014 · Village Walking Path Improvement
                        </strong>

                    </div>


                    <div class="info">

                        <label>Fund</label>

                        <strong>
                            Community Development Fund
                        </strong>

                    </div>


                    <div class="info full">

                        <label>Description</label>

                        <p>
                            Procurement request for construction materials
                            required to repair and improve damaged walking
                            paths in priority areas of Kasso. The procurement
                            supports the approved community development project.
                        </p>

                    </div>

                </div>

            </div>

        </div>


        <!-- ITEMS -->

        <div class="card">

            <div class="card-head">

                <div>
                    <strong>Requested Items</strong>

                    <span>
                        Items and estimated procurement costs.
                    </span>
                </div>

            </div>

            <div class="card-body" style="padding:0;">

                <div class="table-wrap">

                    <table class="items-table">

                        <thead>

                            <tr>
                                <th>Item</th>
                                <th>Quantity</th>
                                <th>Unit Cost</th>
                                <th>Total</th>
                            </tr>

                        </thead>

                        <tbody>

                            <tr>
                                <td>Cement Bags</td>
                                <td>50</td>
                                <td>PKR 1,500</td>
                                <td class="amount">PKR 75,000</td>
                            </tr>

                            <tr>
                                <td>Sand</td>
                                <td>20</td>
                                <td>PKR 2,500</td>
                                <td class="amount">PKR 50,000</td>
                            </tr>

                            <tr class="total-row">
                                <td colspan="3">
                                    Estimated Total
                                </td>
                                <td>
                                    PKR 125,000
                                </td>
                            </tr>

                        </tbody>

                    </table>

                </div>

            </div>

        </div>


        <!-- FUND IMPACT -->

        <div class="card">

            <div class="card-head">

                <div>
                    <strong>Fund Impact</strong>

                    <span>
                        Financial source for the procurement.
                    </span>
                </div>

            </div>

            <div class="card-body">

                <div class="fund-box">

                    <div class="fund-top">

                        <div class="fund-name">
                            Community Development Fund
                        </div>

                        <div class="fund-type">
                            Restricted
                        </div>

                    </div>

                    <div class="fund-meta">

                        <div class="fund-stat">
                            <label>Current Balance</label>
                            <strong>PKR 410,000</strong>
                        </div>

                        <div class="fund-stat">
                            <label>This Procurement</label>
                            <strong>PKR 125,000</strong>
                        </div>

                        <div class="fund-stat">
                            <label>Balance After</label>
                            <strong>PKR 285,000</strong>
                        </div>

                    </div>

                </div>


                <div
                    style="
                        margin-top:12px;
                    "
                    class="notice"
                >

                    <i class="bi bi-shield-check"></i>

                    <p>
                        The procurement is linked to the Community Development
                        Fund and the Village Walking Path Improvement project.
                        The amount remains subject to approval before commitment.
                    </p>

                </div>

            </div>

        </div>


        <!-- SUPPLIER -->

        <div class="card">

            <div class="card-head">

                <div>
                    <strong>Supplier</strong>

                    <span>
                        Selected supplier and procurement quotations.
                    </span>
                </div>

            </div>

            <div class="card-body">

                <div class="supplier-box">

                    <div class="supplier-icon">
                        <i class="bi bi-shop"></i>
                    </div>

                    <div class="supplier-info">

                        <strong>
                            Kasso Building Materials
                        </strong>

                        <span>
                            Local Supplier · Quote QTN-1024 · PKR 125,000
                        </span>

                    </div>

                    <span class="approval approved">
                        Selected
                    </span>

                </div>


                <div style="margin-top:15px;">

                    <div class="document-row">

                        <div class="document-icon">
                            <i class="bi bi-file-earmark-pdf"></i>
                        </div>

                        <div class="document-info">
                            <strong>
                                Selected Supplier Quotation
                            </strong>

                            <span>
                                QTN-1024 · PKR 125,000
                            </span>
                        </div>

                        <button class="doc-btn">
                            <i class="bi bi-eye"></i>
                        </button>

                        <button class="doc-btn">
                            <i class="bi bi-download"></i>
                        </button>

                    </div>


                    <div class="document-row">

                        <div class="document-icon">
                            <i class="bi bi-file-earmark-pdf"></i>
                        </div>

                        <div class="document-info">
                            <strong>
                                Comparison Quotation
                            </strong>

                            <span>
                                QTN-1021 · PKR 132,000
                            </span>
                        </div>

                        <button class="doc-btn">
                            <i class="bi bi-eye"></i>
                        </button>

                    </div>

                </div>

            </div>

        </div>


        <!-- APPROVAL -->

        <div class="card">

            <div class="card-head">

                <div>
                    <strong>Approval Status</strong>

                    <span>
                        Current procurement authorization.
                    </span>
                </div>

                <span class="approval pending">
                    Pending
                </span>

            </div>

            <div class="card-body">

                <div class="approval-grid">

                    <div class="approval-box">
                        <label>Purpose Verified</label>
                        <strong>Confirmed</strong>
                    </div>

                    <div class="approval-box">
                        <label>Fund Verified</label>
                        <strong>Confirmed</strong>
                    </div>

                    <div class="approval-box">
                        <label>Quotations Reviewed</label>
                        <strong>Confirmed</strong>
                    </div>

                    <div class="approval-box pending">
                        <label>Authorized Approval</label>
                        <strong>Pending Committee Review</strong>
                    </div>

                    <div class="approval-box">
                        <label>Requested By</label>
                        <strong>Community Development Committee</strong>
                    </div>

                    <div class="approval-box">
                        <label>Next Authority</label>
                        <strong>Decision-Making Committee</strong>
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
                        Procurement records and evidence.
                    </span>
                </div>

            </div>

            <div class="card-body">

                <div class="document-row">

                    <div class="document-icon">
                        <i class="bi bi-file-earmark-text"></i>
                    </div>

                    <div class="document-info">

                        <strong>
                            Procurement Request
                        </strong>

                        <span>
                            PRC-00042 · Request record
                        </span>

                    </div>

                    <button class="doc-btn">
                        <i class="bi bi-eye"></i>
                    </button>

                </div>


                <div class="document-row">

                    <div class="document-icon">
                        <i class="bi bi-file-earmark-pdf"></i>
                    </div>

                    <div class="document-info">

                        <strong>
                            Supplier Quotation
                        </strong>

                        <span>
                            QTN-1024
                        </span>

                    </div>

                    <button class="doc-btn">
                        <i class="bi bi-eye"></i>
                    </button>

                </div>


                <div class="document-row">

                    <div class="document-icon">
                        <i class="bi bi-file-earmark-image"></i>
                    </div>

                    <div class="document-info">

                        <strong>
                            Material Specification
                        </strong>

                        <span>
                            Project requirement document
                        </span>

                    </div>

                    <button class="doc-btn">
                        <i class="bi bi-eye"></i>
                    </button>

                </div>

            </div>

        </div>


        <!-- ACTIVITY -->

        <div class="card">

            <div class="card-head">

                <div>
                    <strong>Procurement Activity</strong>

                    <span>
                        Complete lifecycle of the request.
                    </span>
                </div>

            </div>

            <div class="card-body">

                <div class="timeline-item">

                    <div class="timeline-dot"></div>

                    <div>

                        <div class="timeline-title">
                            Procurement submitted for approval
                        </div>

                        <div class="timeline-meta">
                            17 Aug 2026 · Community Development Committee
                        </div>

                        <div class="timeline-note">
                            Request submitted to the Decision-Making Committee
                            for authorization.
                        </div>

                    </div>

                </div>


                <div class="timeline-item">

                    <div class="timeline-dot"></div>

                    <div>

                        <div class="timeline-title">
                            Supplier quotation selected
                        </div>

                        <div class="timeline-meta">
                            17 Aug 2026 · Procurement Review
                        </div>

                    </div>

                </div>


                <div class="timeline-item">

                    <div class="timeline-dot"></div>

                    <div>

                        <div class="timeline-title">
                            Procurement request created
                        </div>

                        <div class="timeline-meta">
                            17 Aug 2026 · Project Committee
                        </div>

                    </div>

                </div>

            </div>

        </div>

    </div>


    <aside>

        <div class="card">

            <div class="card-body">

                <div class="side-number">
                    PKR 125K
                </div>

                <div class="side-label">
                    Estimated Procurement
                </div>

                <div style="
                    height:1px;
                    background:#eef1ef;
                    margin:16px 0;
                "></div>

                <div class="side-row">
                    <span>Status</span>
                    <span>Under Review</span>
                </div>

                <div class="side-row">
                    <span>Approval</span>
                    <span>Pending</span>
                </div>

                <div class="side-row">
                    <span>Fund</span>
                    <span>Community Development Fund</span>
                </div>

                <div class="side-row">
                    <span>Supplier</span>
                    <span>Kasso Building Materials</span>
                </div>

                <div class="side-row">
                    <span>Items</span>
                    <span>2</span>
                </div>

            </div>

        </div>


        <div class="card">

            <div class="card-head">
                <strong>Next Step</strong>
            </div>

            <div class="card-body">

                <div class="notice">

                    <i class="bi bi-exclamation-circle"></i>

                    <p>
                        The procurement is waiting for authorized committee
                        approval. Once approved, the next stage can be a
                        purchase order and supplier fulfillment.
                    </p>

                </div>

                <div style="margin-top:12px;">

                    <button
                        class="ui-btn success"
                        style="width:100%;"
                        onclick="previewApprove()"
                    >
                        <i class="bi bi-check-lg"></i>
                        Approve Procurement
                    </button>

                </div>

            </div>

        </div>


        <div class="card">

            <div class="card-head">
                <strong>Procurement Lifecycle</strong>
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
                            background:#edf7f0;
                            color:#397955;
                            display:flex;
                            align-items:center;
                            justify-content:center;
                            font-size:13px;
                        ">
                            ✓
                        </div>

                        <span style="font-size:13px;">
                            Request Created
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
                            background:#edf7f0;
                            color:#397955;
                            display:flex;
                            align-items:center;
                            justify-content:center;
                            font-size:13px;
                        ">
                            ✓
                        </div>

                        <span style="font-size:13px;">
                            Quotations Reviewed
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
                            background:#fff5e9;
                            color:#a8793e;
                            display:flex;
                            align-items:center;
                            justify-content:center;
                            font-size:13px;
                        ">
                            3
                        </div>

                        <span style="font-size:13px;">
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
                            background:#f4f7f5;
                            color:#9aa49f;
                            display:flex;
                            align-items:center;
                            justify-content:center;
                            font-size:13px;
                        ">
                            4
                        </div>

                        <span style="font-size:13px;">
                            Purchase Order
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
                            background:#f4f7f5;
                            color:#9aa49f;
                            display:flex;
                            align-items:center;
                            justify-content:center;
                            font-size:13px;
                        ">
                            5
                        </div>

                        <span style="font-size:13px;">
                            Receive & Reconcile
                        </span>
                    </div>

                </div>

            </div>

        </div>

    </aside>

</div>

@endsection


@push('scripts')
<script>

    function previewApprove() {

        const confirmed =
            confirm(
                'Approve procurement request PRC-00042 for PKR 125,000?'
            );

        if (!confirmed) {
            return;
        }

        alert(
            'UI preview only.\n\n' +
            'This procurement would move to Approved status once backend approval functionality is connected.'
        );

    }

</script>
@endpush