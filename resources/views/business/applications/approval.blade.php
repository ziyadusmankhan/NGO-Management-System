@extends('layouts.app')

@section('title', 'Business Application Approval')

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

    .ui-btn.success {
        background:#edf7f0;
        border-color: #6a746d;
        color:#397955;
    }

    .ui-btn.reject {
        background:#fff0ef;
        border-color: #7c706f;
        color:#a85f58;
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

    /* STATUS */

    .status {
        display:inline-flex;
        align-items:center;
        gap:5px;
        padding:5px 8px;
        border-radius:20px;
        background:#f4efe4;
        color:#876a30;
        font-size: 13px;
        font-weight:650;
    }

    .status::before {
        content:"";
        width:5px;
        height:5px;
        border-radius:50%;
        background:#c89b3c;
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

    .summary-box.highlight {
        background:var(--primary-soft);
        border-color: #6f7673;
    }

    .summary-box.highlight strong {
        color:var(--primary);
    }

    /* DECISION */

    .decision-box {
        padding:15px;
        background:#fafcfb;
        border:1px solid #edf1ee;
        border-radius:10px;
    }

    .decision-options {
        display:grid;
        grid-template-columns:repeat(3,minmax(0,1fr));
        gap:9px;
    }

    .decision-option {
        position:relative;
    }

    .decision-option input {
        position:absolute;
        opacity:0;
        pointer-events:none;
    }

    .decision-option label {
        min-height:74px;
        padding:11px;
        border:1px solid var(--border);
        border-radius:10px;
        display:flex;
        flex-direction:column;
        justify-content:center;
        cursor:pointer;
    }

    .decision-option label strong {
        font-size: 15px;
    }

    .decision-option label span {
        margin-top:4px;
        color: #6c7470;
        font-size: 13px;
        line-height:1.45;
    }

    .decision-option input:checked + label {
        background:var(--primary-soft);
        border-color: #6b7570;
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
        min-height:105px;
        height:auto;
        padding:11px 12px;
        resize:vertical;
        line-height:1.6;
    }

    .control:focus,
    .select:focus {
        border-color: #6b7670;
        box-shadow:0 0 0 3px rgba(23,63,53,.055);
    }

    /* ASSESSMENT */

    .assessment-row {
        padding:13px 0;
        border-bottom:1px solid #f0f3f1;
    }

    .assessment-row:last-child {
        border-bottom:none;
    }

    .assessment-top {
        display:flex;
        justify-content:space-between;
        gap:10px;
    }

    .assessment-top strong {
        font-size: 15px;
    }

    .score {
        color:var(--primary);
        font-size: 14px;
        font-weight:650;
    }

    .progress {
        height:7px;
        margin-top:8px;
        background:#edf1ee;
        border-radius:20px;
        overflow:hidden;
    }

    .progress span {
        display:block;
        height:100%;
        background:var(--primary);
        border-radius:20px;
    }

    .assessment-note {
        margin-top:6px;
        color: #6b7670;
        font-size: 13px;
        line-height:1.55;
    }

    /* CONDITIONS */

    .conditions {
        display:flex;
        flex-direction:column;
        gap:9px;
    }

    .condition {
        display:flex;
        gap:9px;
        align-items:flex-start;
        padding:10px;
        border:1px solid #edf1ee;
        background:#fafcfb;
        border-radius:9px;
    }

    .condition input {
        margin-top:1px;
        accent-color:var(--primary);
    }

    .condition span {
        color:#65726c;
        font-size: 14px;
        line-height:1.5;
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
        background:#e5d5ae;
        color:#765d26;
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
        font-size: 15px;
        font-weight:650;
    }

    .timeline-meta {
        margin-top:3px;
        color: #6c7470;
        font-size: 13px;
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

    /* DOCUMENT */

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
        width:32px;
        height:32px;
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
        font-size: 14px;
    }

    .document-info span {
        display:block;
        margin-top:3px;
        color: #6c7470;
        font-size: 13px;
    }

    .doc-btn {
        width:28px;
        height:28px;
        border:1px solid var(--border);
        background:#fff;
        border-radius:7px;
        color: #6b766f;
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
        .decision-options,
        .form-grid {
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
            <i class="bi bi-gavel"></i>
        </div>

        <div>

            <div class="title">
                Approval Decision
            </div>

            <div class="meta">
                SBA-00041 · Rahim Khan · Kasso General Store
            </div>

        </div>

    </div>

    <div class="actions">

        <a
            href="{{ route('business.applications.recommendation', ['id' => 1]) }}"
            class="ui-btn"
        >
            <i class="bi bi-arrow-left"></i>
            Back
        </a>

        <button
            class="ui-btn reject"
            onclick="previewApproval('rejected')"
        >
            <i class="bi bi-x-circle"></i>
            Reject
        </button>

        <a
            href="{{ route('business.applications.agreement', ['id' => 1]) }}"
            class="ui-btn primary"
        >
            <i class="bi bi-file-earmark-text"></i>
            Proceed to Agreement
        </a>

    </div>

</div>


<div class="layout">

    <div>

        <!-- =====================================
             STATUS
        ====================================== -->

        <div class="card">

            <div class="card-head">

                <div>
                    <strong>Approval Status</strong>

                    <span>
                        Final decision by the authorized approving body.
                    </span>
                </div>

                <span class="status">
                    Awaiting Decision
                </span>

            </div>

            <div class="card-body">

                <div class="summary-grid">

                    <div class="summary-box">
                        <label>Application</label>
                        <strong>SBA-00041</strong>
                    </div>

                    <div class="summary-box">
                        <label>Recommended Amount</label>
                        <strong>PKR 60,000</strong>
                    </div>

                    <div class="summary-box">
                        <label>Viability Score</label>
                        <strong>76%</strong>
                    </div>

                    <div class="summary-box">
                        <label>Risk</label>
                        <strong>Medium</strong>
                    </div>

                    <div class="summary-box">
                        <label>Applicant Contribution</label>
                        <strong>PKR 20,000</strong>
                    </div>

                    <div class="summary-box highlight">
                        <label>Organization Exposure</label>
                        <strong>PKR 60,000</strong>
                    </div>

                </div>

            </div>

        </div>


        <!-- =====================================
             APPLICANT & BUSINESS
        ====================================== -->

        <div class="card">

            <div class="card-head">

                <div>
                    <strong>Applicant & Business</strong>

                    <span>
                        Summary for the approving committee.
                    </span>
                </div>

            </div>

            <div class="card-body">

                <div class="summary-grid">

                    <div class="summary-box">
                        <label>Applicant</label>
                        <strong>Rahim Khan</strong>
                    </div>

                    <div class="summary-box">
                        <label>Beneficiary</label>
                        <strong>BEN-00063</strong>
                    </div>

                    <div class="summary-box">
                        <label>Business</label>
                        <strong>Kasso General Store</strong>
                    </div>

                    <div class="summary-box">
                        <label>Business Type</label>
                        <strong>Retail</strong>
                    </div>

                    <div class="summary-box">
                        <label>Location</label>
                        <strong>Kasso Main Market</strong>
                    </div>

                    <div class="summary-box">
                        <label>Expected Net Income</label>
                        <strong>PKR 23,000 / month</strong>
                    </div>

                </div>

            </div>

        </div>


        <!-- =====================================
             RECOMMENDATION
        ====================================== -->

        <div class="card">

            <div class="card-head">

                <div>
                    <strong>Committee Recommendation</strong>

                    <span>
                        Recommendation received from the Implementation Committee.
                    </span>
                </div>

            </div>

            <div class="card-body">

                <div class="decision-box">

                    <div class="summary-box highlight">

                        <label>Recommendation</label>

                        <strong>
                            Recommend Approval
                        </strong>

                    </div>

                    <div
                        style="
                            margin-top:12px;
                            color:#66736c;
                            font-size: 14px;
                            line-height:1.7;
                        "
                    >
                        Recommend approval of PKR 60,000 subject to confirmation
                        of the applicant contribution, final quotation review,
                        completion of the required agreement and adherence to
                        the organization's approval controls.
                    </div>

                </div>

            </div>

        </div>


        <!-- =====================================
             VIABILITY
        ====================================== -->

        <div class="card">

            <div class="card-head">

                <div>
                    <strong>Viability Assessment</strong>

                    <span>
                        Key findings considered by the approving body.
                    </span>
                </div>

            </div>

            <div class="card-body">

                <div class="assessment-row">

                    <div class="assessment-top">
                        <strong>Market Demand</strong>
                        <span class="score">8 / 10</span>
                    </div>

                    <div class="progress">
                        <span style="width:80%"></span>
                    </div>

                </div>


                <div class="assessment-row">

                    <div class="assessment-top">
                        <strong>Applicant Capability</strong>
                        <span class="score">8 / 10</span>
                    </div>

                    <div class="progress">
                        <span style="width:80%"></span>
                    </div>

                </div>


                <div class="assessment-row">

                    <div class="assessment-top">
                        <strong>Financial Feasibility</strong>
                        <span class="score">7 / 10</span>
                    </div>

                    <div class="progress">
                        <span style="width:70%"></span>
                    </div>

                </div>


                <div class="assessment-row">

                    <div class="assessment-top">
                        <strong>Sustainability</strong>
                        <span class="score">7 / 10</span>
                    </div>

                    <div class="progress">
                        <span style="width:70%"></span>
                    </div>

                </div>

            </div>

        </div>


        <!-- =====================================
             APPROVAL DECISION
        ====================================== -->

        <div class="card">

            <div class="card-head">

                <div>
                    <strong>Formal Decision</strong>

                    <span>
                        Record the decision of the authorized approving body.
                    </span>
                </div>

            </div>

            <div class="card-body">

                <div class="decision-options">

                    <div class="decision-option">

                        <input
                            type="radio"
                            name="decision"
                            id="decisionApprove"
                            checked
                        >

                        <label for="decisionApprove">

                            <strong>
                                Approve
                            </strong>

                            <span>
                                Approve the recommendation and proceed
                                to agreement.
                            </span>

                        </label>

                    </div>


                    <div class="decision-option">

                        <input
                            type="radio"
                            name="decision"
                            id="decisionConditional"
                        >

                        <label for="decisionConditional">

                            <strong>
                                Approve With Conditions
                            </strong>

                            <span>
                                Approve subject to specified conditions.
                            </span>

                        </label>

                    </div>


                    <div class="decision-option">

                        <input
                            type="radio"
                            name="decision"
                            id="decisionReject"
                        >

                        <label for="decisionReject">

                            <strong>
                                Reject
                            </strong>

                            <span>
                                Do not approve the proposed assistance.
                            </span>

                        </label>

                    </div>

                </div>


                <div class="form-grid" style="margin-top:18px;">

                    <div>

                        <label class="label">
                            Approved Amount
                        </label>

                        <input
                            class="control"
                            value="PKR 60,000"
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


                    <div>

                        <label class="label">
                            Decision Reference
                        </label>

                        <input
                            class="control"
                            value="DEC-SBA-00041"
                        >

                    </div>


                    <div>

                        <label class="label">
                            Approving Committee
                        </label>

                        <select class="select">

                            <option selected>
                                Decision-Making Committee
                            </option>

                            <option>
                                General Secretary
                            </option>

                        </select>

                    </div>


                    <div class="full">

                        <label class="label">
                            Decision Notes
                        </label>

                        <textarea class="control">Approved subject to confirmation of the applicant's contribution, quotation verification, completion of agreement and documented asset procurement.</textarea>

                    </div>

                </div>

            </div>

        </div>


        <!-- =====================================
             APPROVAL CONDITIONS
        ====================================== -->

        <div class="card">

            <div class="card-head">

                <div>
                    <strong>Approval Conditions</strong>

                    <span>
                        Conditions that must be satisfied before disbursement.
                    </span>
                </div>

            </div>

            <div class="card-body">

                <div class="conditions">

                    <label class="condition">

                        <input
                            type="checkbox"
                            checked
                        >

                        <span>
                            Applicant contribution verified before disbursement.
                        </span>

                    </label>


                    <label class="condition">

                        <input
                            type="checkbox"
                            checked
                        >

                        <span>
                            Required quotations / price checks completed.
                        </span>

                    </label>


                    <label class="condition">

                        <input
                            type="checkbox"
                            checked
                        >

                        <span>
                            Agreement signed by required parties.
                        </span>

                    </label>


                    <label class="condition">

                        <input
                            type="checkbox"
                            checked
                        >

                        <span>
                            Procurement and asset records documented.
                        </span>

                    </label>


                    <label class="condition">

                        <input
                            type="checkbox"
                        >

                        <span>
                            Additional monitoring requirement before business launch.
                        </span>

                    </label>

                </div>

            </div>

        </div>


        <!-- =====================================
             WORKFLOW
        ====================================== -->

        <div class="card">

            <div class="card-head">

                <div>
                    <strong>Approval Workflow</strong>

                    <span>
                        Current position after committee recommendation.
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

                        <div class="workflow-circle current">
                            5
                        </div>

                        <strong>Approval</strong>
                        <span>Current</span>

                    </div>


                    <div class="workflow-stage">

                        <div class="workflow-circle pending">
                            6
                        </div>

                        <strong>Agreement</strong>
                        <span>Pending</span>

                    </div>


                    <div class="workflow-stage">

                        <div class="workflow-circle pending">
                            7
                        </div>

                        <strong>Disbursement</strong>
                        <span>Pending</span>

                    </div>

                </div>

            </div>

        </div>


        <!-- =====================================
             HISTORY
        ====================================== -->

        <div class="card">

            <div class="card-head">

                <div>
                    <strong>Decision History</strong>

                    <span>
                        Approval-related activity.
                    </span>
                </div>

            </div>

            <div class="card-body">

                <div class="timeline-item">

                    <div class="timeline-dot"></div>

                    <div>
                        <div class="timeline-title">
                            Application submitted for approval
                        </div>

                        <div class="timeline-meta">
                            15 Aug 2026 · Implementation Committee
                        </div>
                    </div>

                </div>


                <div class="timeline-item">

                    <div class="timeline-dot"></div>

                    <div>
                        <div class="timeline-title">
                            Recommendation completed
                        </div>

                        <div class="timeline-meta">
                            15 Aug 2026 · Implementation Committee
                        </div>
                    </div>

                </div>


                <div class="timeline-item">

                    <div class="timeline-dot"></div>

                    <div>
                        <div class="timeline-title">
                            Viability assessment completed
                        </div>

                        <div class="timeline-meta">
                            15 Aug 2026 · Implementation Committee
                        </div>
                    </div>

                </div>

            </div>

        </div>


        <!-- =====================================
             DOCUMENTS
        ====================================== -->

        <div class="card">

            <div class="card-head">

                <div>
                    <strong>Decision Documents</strong>

                    <span>
                        Records available to the approving committee.
                    </span>
                </div>

            </div>

            <div class="card-body">

                <div class="document-row">

                    <div class="document-icon">
                        <i class="bi bi-file-earmark-text"></i>
                    </div>

                    <div class="document-info">
                        <strong>Viability Assessment</strong>
                        <span>15 Aug 2026 · PDF</span>
                    </div>

                    <button class="doc-btn">
                        <i class="bi bi-eye"></i>
                    </button>

                </div>


                <div class="document-row">

                    <div class="document-icon">
                        <i class="bi bi-file-earmark-text"></i>
                    </div>

                    <div class="document-info">
                        <strong>Implementation Recommendation</strong>
                        <span>15 Aug 2026 · PDF</span>
                    </div>

                    <button class="doc-btn">
                        <i class="bi bi-eye"></i>
                    </button>

                </div>


                <div class="document-row">

                    <div class="document-icon">
                        <i class="bi bi-file-earmark-text"></i>
                    </div>

                    <div class="document-info">
                        <strong>Field Verification Report</strong>
                        <span>14 Aug 2026 · PDF</span>
                    </div>

                    <button class="doc-btn">
                        <i class="bi bi-eye"></i>
                    </button>

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
                    Proposed Approval
                </div>

                <div style="
                    height:1px;
                    background:#eef1ef;
                    margin:16px 0;
                "></div>

                <div class="side-row">
                    <span>Status</span>
                    <span>Awaiting Decision</span>
                </div>

                <div class="side-row">
                    <span>Recommendation</span>
                    <span>Approve</span>
                </div>

                <div class="side-row">
                    <span>Viability</span>
                    <span>76%</span>
                </div>

                <div class="side-row">
                    <span>Risk</span>
                    <span>Medium</span>
                </div>

                <div class="side-row">
                    <span>Fund</span>
                    <span>Small Business Fund</span>
                </div>

            </div>

        </div>


        <div class="card">

            <div class="card-head">
                <strong>Approval Control</strong>
            </div>

            <div class="card-body">

                <div class="notice">

                    <i class="bi bi-shield-check"></i>

                    <p>
                        The approving authority should not approve its own
                        financial request. All approval actions should be
                        recorded in the audit trail.
                    </p>

                </div>

            </div>

        </div>

    </aside>

</div>

@endsection


@push('scripts')
<script>

    function previewApproval(decision) {

        let action;

        if (decision === 'approved') {
            action = 'approved';
        } else {
            action = 'rejected';
        }

        const confirmed =
            confirm(
                'Are you sure you want to mark this application as ' +
                action +
                '?'
            );

        if (!confirmed) {
            return;
        }

        alert(
            'UI preview only.\n\n' +
            'The application would be marked as ' +
            action +
            ' once backend approval functionality is connected.'
        );

    }

</script>
@endpush