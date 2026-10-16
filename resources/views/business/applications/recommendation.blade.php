@extends('layouts.app')

@section('title', 'Business Application Recommendation')

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

    /* STATUS */

    .status {
        display:inline-flex;
        align-items:center;
        gap:5px;
        padding:5px 8px;
        border-radius:20px;
        background:#f1eef7;
        color:#77668c;
        font-size: 13px;
        font-weight:650;
    }

    .status::before {
        content:"";
        width:5px;
        height:5px;
        border-radius:50%;
        background:#8c77a3;
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

    .assessment-score {
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

    /* DECISION */

    .decision-options {
        display:grid;
        grid-template-columns:repeat(2,minmax(0,1fr));
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
        min-height:68px;
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
        color:var(--primary);
        border-color: #6b7570;
    }

    /* FORM */

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

    .form-grid {
        display:grid;
        grid-template-columns:repeat(2,minmax(0,1fr));
        gap:17px;
    }

    .full {
        grid-column:1/-1;
    }

    /* CONDITIONS */

    .condition-list {
        display:flex;
        flex-direction:column;
        gap:9px;
    }

    .condition {
        display:flex;
        align-items:flex-start;
        gap:9px;
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
        background:#d9c5e4;
        color:#6d4f7e;
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

    /* SIDEBAR */

    .side-number {
        text-align:center;
        font-size: 28px;
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
            <i class="bi bi-file-earmark-check"></i>
        </div>

        <div>

            <div class="title">
                Recommendation
            </div>

            <div class="meta">
                SBA-00041 · Rahim Khan · Kasso General Store
            </div>

        </div>

    </div>

    <div class="actions">

        <a
            href="{{ route('business.applications.viability', ['id' => 1]) }}"
            class="ui-btn"
        >
            <i class="bi bi-arrow-left"></i>
            Back
        </a>

        <!-- <button
            class="ui-btn success"
            onclick="previewSubmitRecommendation()"
        >
            <i class="bi bi-send-check"></i>
            Submit Recommendation
        </button> -->
        <a
            href="{{ route('business.applications.approval', ['id' => 1]) }}"
            class="ui-btn success"
        >
            <i class="bi bi-send-check"></i>
            Send for Approval
        </a>

    </div>

</div>


<div class="layout">

    <div>

        <!-- SUMMARY -->

        <div class="card">

            <div class="card-head">

                <div>
                    <strong>Assessment Summary</strong>

                    <span>
                        Summary of the completed viability assessment.
                    </span>
                </div>

                <span class="status">
                    Ready for Recommendation
                </span>

            </div>

            <div class="card-body">

                <div class="summary-grid">

                    <div class="summary-box">
                        <label>Overall Score</label>
                        <strong>76%</strong>
                    </div>

                    <div class="summary-box">
                        <label>Risk Level</label>
                        <strong>Medium</strong>
                    </div>

                    <div class="summary-box">
                        <label>Requested</label>
                        <strong>PKR 60,000</strong>
                    </div>

                    <div class="summary-box">
                        <label>Applicant Contribution</label>
                        <strong>PKR 20,000</strong>
                    </div>

                    <div class="summary-box">
                        <label>Expected Net Income</label>
                        <strong>PKR 23,000</strong>
                    </div>

                    <div class="summary-box">
                        <label>Break-even</label>
                        <strong>8 months</strong>
                    </div>

                </div>

            </div>

        </div>


        <!-- ASSESSMENT BREAKDOWN -->

        <div class="card">

            <div class="card-head">

                <div>
                    <strong>Assessment Breakdown</strong>

                    <span>
                        Key findings from viability assessment.
                    </span>
                </div>

            </div>

            <div class="card-body">

                <div class="assessment-row">

                    <div class="assessment-top">

                        <strong>Market Demand</strong>

                        <span class="assessment-score">
                            8 / 10
                        </span>

                    </div>

                    <div class="progress">
                        <span style="width:80%"></span>
                    </div>

                    <div class="assessment-note">
                        Essential goods have recurring local demand with
                        manageable competition.
                    </div>

                </div>


                <div class="assessment-row">

                    <div class="assessment-top">

                        <strong>Applicant Capability</strong>

                        <span class="assessment-score">
                            8 / 10
                        </span>

                    </div>

                    <div class="progress">
                        <span style="width:80%"></span>
                    </div>

                    <div class="assessment-note">
                        Applicant has relevant retail experience and intends
                        to operate the business personally.
                    </div>

                </div>


                <div class="assessment-row">

                    <div class="assessment-top">

                        <strong>Financial Feasibility</strong>

                        <span class="assessment-score">
                            7 / 10
                        </span>

                    </div>

                    <div class="progress">
                        <span style="width:70%"></span>
                    </div>

                    <div class="assessment-note">
                        Applicant contribution lowers the organization's
                        financial exposure.
                    </div>

                </div>


                <div class="assessment-row">

                    <div class="assessment-top">

                        <strong>Sustainability</strong>

                        <span class="assessment-score">
                            7 / 10
                        </span>

                    </div>

                    <div class="progress">
                        <span style="width:70%"></span>
                    </div>

                    <div class="assessment-note">
                        Projected income indicates potential for long-term
                        independence with appropriate monitoring.
                    </div>

                </div>

            </div>

        </div>


        <!-- RECOMMENDATION -->

        <div class="card">

            <div class="card-head">

                <div>
                    <strong>Committee Recommendation</strong>

                    <span>
                        Select the recommendation that should be submitted for formal approval.
                    </span>
                </div>

            </div>

            <div class="card-body">

                <div class="decision-options">

                    <div class="decision-option">

                        <input
                            type="radio"
                            name="recommendation"
                            id="recommendApprove"
                            checked
                        >

                        <label for="recommendApprove">

                            <strong>
                                Recommend Approval
                            </strong>

                            <span>
                                Proposal appears viable and can proceed
                                to formal approval.
                            </span>

                        </label>

                    </div>


                    <div class="decision-option">

                        <input
                            type="radio"
                            name="recommendation"
                            id="recommendConditional"
                        >

                        <label for="recommendConditional">

                            <strong>
                                Conditional Approval
                            </strong>

                            <span>
                                Approval recommended subject to specified
                                conditions.
                            </span>

                        </label>

                    </div>


                    <div class="decision-option">

                        <input
                            type="radio"
                            name="recommendation"
                            id="recommendMoreInfo"
                        >

                        <label for="recommendMoreInfo">

                            <strong>
                                Request More Information
                            </strong>

                            <span>
                                Additional evidence or verification is needed
                                before recommendation.
                            </span>

                        </label>

                    </div>


                    <div class="decision-option">

                        <input
                            type="radio"
                            name="recommendation"
                            id="recommendReject"
                        >

                        <label for="recommendReject">

                            <strong>
                                Do Not Recommend
                            </strong>

                            <span>
                                Proposal does not currently meet
                                organizational requirements.
                            </span>

                        </label>

                    </div>

                </div>


                <div class="form-grid" style="margin-top:18px;">

                    <div>

                        <label class="label">
                            Recommended Amount
                        </label>

                        <input
                            id="recommendedAmount"
                            class="control"
                            value="PKR 60,000"
                        >

                    </div>


                    <div>

                        <label class="label">
                            Recommended Funding Source
                        </label>

                        <select class="select">

                            <option selected>
                                Small Business Fund
                            </option>

                            <option>
                                Qard-e-Hasana Fund
                            </option>

                            <option>
                                General Welfare Fund
                            </option>

                        </select>

                    </div>


                    <div class="full">

                        <label class="label">
                            Recommendation Statement
                        </label>

                        <textarea
                            id="recommendationText"
                            class="control"
                        >Recommend approval of PKR 60,000 subject to final review of supplier quotations, confirmation of the applicant's contribution, execution of the required agreement and approval through the organization's defined decision-making process.</textarea>

                    </div>

                </div>

            </div>

        </div>


        <!-- CONDITIONS -->

        <div class="card">

            <div class="card-head">

                <div>
                    <strong>Recommended Conditions</strong>

                    <span>
                        Conditions that must be satisfied before disbursement.
                    </span>
                </div>

            </div>

            <div class="card-body">

                <div class="condition-list">

                    <label class="condition">

                        <input
                            type="checkbox"
                            checked
                        >

                        <span>
                            Applicant contribution of PKR 20,000 must be confirmed.
                        </span>

                    </label>


                    <label class="condition">

                        <input
                            type="checkbox"
                            checked
                        >

                        <span>
                            Supplier quotations / prices must be verified.
                        </span>

                    </label>


                    <label class="condition">

                        <input
                            type="checkbox"
                            checked
                        >

                        <span>
                            Required agreement must be completed before disbursement.
                        </span>

                    </label>


                    <label class="condition">

                        <input
                            type="checkbox"
                            checked
                        >

                        <span>
                            Organization-funded assets must be documented.
                        </span>

                    </label>


                    <label class="condition">

                        <input
                            type="checkbox"
                        >

                        <span>
                            Additional business training required before launch.
                        </span>

                    </label>

                </div>

            </div>

        </div>


        <!-- REVIEWERS -->

        <div class="card">

            <div class="card-head">

                <div>
                    <strong>Recommendation Review</strong>

                    <span>
                        Record who prepared and reviewed the recommendation.
                    </span>
                </div>

            </div>

            <div class="card-body">

                <div class="form-grid">

                    <div>

                        <label class="label">
                            Recommended By
                        </label>

                        <select class="select">

                            <option selected>
                                Implementation Committee
                            </option>

                            <option>
                                General Secretary
                            </option>

                        </select>

                    </div>


                    <div>

                        <label class="label">
                            Recommendation Date
                        </label>

                        <input
                            type="date"
                            class="control"
                            value="2026-08-15"
                        >

                    </div>


                    <div>

                        <label class="label">
                            Review Reference
                        </label>

                        <input
                            class="control"
                            value="REC-00021"
                        >

                    </div>


                    <div>

                        <label class="label">
                            Next Authority
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

                </div>

            </div>

        </div>


        <!-- WORKFLOW -->

        <div class="card">

            <div class="card-head">

                <div>
                    <strong>Approval Workflow</strong>

                    <span>
                        Where the application goes after recommendation.
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

                        <div class="workflow-circle current">
                            4
                        </div>

                        <strong>Recommendation</strong>

                        <span>Current</span>

                    </div>


                    <div class="workflow-stage">

                        <div class="workflow-circle pending">
                            5
                        </div>

                        <strong>Approval</strong>

                        <span>Pending</span>

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


        <!-- DOCUMENTS -->

        <div class="card">

            <div class="card-head">

                <div>
                    <strong>Recommendation Documents</strong>

                    <span>
                        Documents supporting the recommendation.
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
                            Viability Assessment
                        </strong>

                        <span>
                            Assessment · 15 Aug 2026
                        </span>

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

                        <strong>
                            Supplier Quotation
                        </strong>

                        <span>
                            Quotation · 13 Aug 2026
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
                            Field Verification Evidence
                        </strong>

                        <span>
                            4 files · 14 Aug 2026
                        </span>

                    </div>

                    <button class="doc-btn">
                        <i class="bi bi-eye"></i>
                    </button>

                </div>

            </div>

        </div>

    </div>


    <!-- SIDEBAR -->

    <aside>

        <div class="card">

            <div class="card-body">

                <div class="side-number">
                    PKR 60K
                </div>

                <div class="side-label">
                    Recommended Amount
                </div>

                <div style="
                    height:1px;
                    background:#eef1ef;
                    margin:16px 0;
                "></div>

                <div class="side-row">
                    <span>Application</span>
                    <span>SBA-00041</span>
                </div>

                <div class="side-row">
                    <span>Applicant</span>
                    <span>Rahim Khan</span>
                </div>

                <div class="side-row">
                    <span>Business</span>
                    <span>General Store</span>
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
                    <span>Next Authority</span>
                    <span>Decision-Making Committee</span>
                </div>

            </div>

        </div>


        <div class="card">

            <div class="card-head">
                <strong>Submission Notice</strong>
            </div>

            <div class="card-body">

                <div class="notice">

                    <i class="bi bi-shield-check"></i>

                    <p>
                        Submitting this recommendation should formally move
                        the application to the next approval stage. The final
                        decision remains with the authorized approving body.
                    </p>

                </div>

            </div>

        </div>

    </aside>

</div>

@endsection


@push('scripts')
<script>

    function previewSubmitRecommendation() {

        const confirmed =
            confirm(
                'Submit this recommendation to the Decision-Making Committee?'
            );

        if (!confirmed) {
            return;
        }

        alert(
            'UI preview only.\n\n' +
            'The recommendation would be submitted to the next approval stage once backend workflow functionality is connected.'
        );

    }

</script>
@endpush