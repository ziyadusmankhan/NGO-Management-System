@extends('layouts.app')

@section('title', 'Viability Assessment')

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
        gap:7px;
        border:1px solid var(--border);
        background:#fff;
        color:#65726c;
        font-size: 15px;
        font-weight:650;
        cursor:pointer;
        text-decoration:none;
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

    /* FORM */

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

    .help {
        margin-top:5px;
        color: #6d7571;
        font-size: 13px;
        line-height:1.5;
    }

    /* SCORE */

    .score-grid {
        display:grid;
        grid-template-columns:repeat(4,minmax(0,1fr));
        gap:10px;
    }

    .score-card {
        padding:13px;
        background:#fafcfb;
        border:1px solid #edf1ee;
        border-radius:10px;
        text-align:center;
    }

    .score-number {
        font-size: 20px;
        font-weight:700;
        color:var(--primary);
    }

    .score-label {
        margin-top:4px;
        color: #6d7772;
        font-size: 13px;
    }

    /* RATING */

    .rating-row {
        padding:13px 0;
        border-bottom:1px solid #f0f3f1;
    }

    .rating-row:last-child {
        border-bottom:none;
    }

    .rating-top {
        display:flex;
        align-items:center;
        justify-content:space-between;
        gap:10px;
    }

    .rating-top strong {
        font-size: 15px;
    }

    .rating-value {
        color:var(--primary);
        font-size: 14px;
        font-weight:650;
    }

    .rating-bar {
        height:7px;
        margin-top:8px;
        background:#edf1ee;
        border-radius:20px;
        overflow:hidden;
    }

    .rating-fill {
        height:100%;
        background:var(--primary);
        border-radius:20px;
    }

    /* RISK */

    .risk-grid {
        display:grid;
        grid-template-columns:repeat(2,minmax(0,1fr));
        gap:10px;
    }

    .risk-box {
        padding:12px;
        border:1px solid #edf1ee;
        border-radius:10px;
        background:#fafcfb;
    }

    .risk-box strong {
        display:block;
        font-size: 14px;
        margin-bottom:5px;
    }

    .risk-box p {
        margin:0;
        color:#69766f;
        font-size: 13px;
        line-height:1.6;
    }

    .risk-low {
        border-left:3px solid #6a9b78;
    }

    .risk-medium {
        border-left:3px solid #d1a04f;
    }

    .risk-high {
        border-left:3px solid #bd6b62;
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
        border:1px solid #edf1ee;
        border-radius:9px;
        background:#fafcfb;
    }

    .check-item input {
        margin-top:1px;
        accent-color:var(--primary);
    }

    .check-item span {
        color:#627068;
        font-size: 14px;
        line-height:1.5;
    }

    /* RECOMMENDATION */

    .recommendation-options {
        display:flex;
        flex-wrap:wrap;
        gap:8px;
    }

    .recommendation-option {
        position:relative;
    }

    .recommendation-option input {
        position:absolute;
        opacity:0;
        pointer-events:none;
    }

    .recommendation-option label {
        height:35px;
        padding:0 11px;
        border:1px solid var(--border);
        border-radius:30px;
        display:inline-flex;
        align-items:center;
        gap:6px;
        font-size: 14px;
        color: #6b766f;
        cursor:pointer;
    }

    .recommendation-option input:checked + label {
        background:var(--primary-soft);
        color:var(--primary);
        border-color: #6b7570;
        font-weight:650;
    }

    /* ATTACHMENTS */

    .upload {
        border:1px dashed #cbd6d0;
        border-radius:11px;
        padding:21px;
        text-align:center;
        background:#fbfcfb;
        cursor:pointer;
    }

    .upload i {
        font-size: 23px;
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

    /* SIDEBAR */

    .score-summary {
        text-align:center;
        padding:7px 0 16px;
    }

    .score-total {
        font-size: 35px;
        font-weight:700;
        color:var(--primary);
    }

    .score-total-label {
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

    .mini-progress {
        height:7px;
        background:#edf1ee;
        border-radius:20px;
        overflow:hidden;
        margin-top:9px;
    }

    .mini-progress span {
        display:block;
        width:78%;
        height:100%;
        background:var(--primary);
        border-radius:20px;
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
            justify-content:center;
        }

        .grid,
        .risk-grid {
            grid-template-columns:1fr;
        }

        .full {
            grid-column:auto;
        }

        .score-grid {
            grid-template-columns:repeat(2,1fr);
        }
    }
</style>
@endpush


@section('content')

<div class="page-header">

    <div class="identity">

        <div class="icon-box">
            <i class="bi bi-clipboard2-check"></i>
        </div>

        <div>

            <a
                href="{{ route('business.applications.viability', ['id' => 1]) }}"
                class="ui-btn primary"
            >
                <i class="bi bi-clipboard2-check"></i>
                Viability Assessment
            </a>

            <div class="meta">
                SBA-00041 · Rahim Khan · Kasso General Store
            </div>

        </div>

    </div>

    <div class="actions">

        <a
            href="{{ route('business.applications.show', ['id' => 1]) }}"
            class="ui-btn"
        >
            <i class="bi bi-arrow-left"></i>
            Back
        </a>

        <a
            href="{{ route('business.applications.recommendation', ['id' => 1]) }}"
            class="ui-btn success"
        >
            <i class="bi bi-send-check"></i>
            Continue to Recommendation
        </a>

    </div>

</div>


<div class="layout">

    <div>

        <!-- =====================================
             ASSESSMENT STATUS
        ====================================== -->

        <div class="card">

            <div class="card-head">

                <div>
                    <strong>Assessment Status</strong>

                    <span>
                        Structured assessment before formal recommendation.
                    </span>
                </div>

                <span class="status">
                    In Progress
                </span>

            </div>

            <div class="card-body">

                <div class="grid">

                    <div>

                        <label class="label">
                            Assessor
                        </label>

                        <select class="select">

                            <option>
                                Implementation Committee
                            </option>

                            <option>
                                Abid Anwar
                            </option>

                            <option>
                                General Secretary
                            </option>

                        </select>

                    </div>


                    <div>

                        <label class="label">
                            Assessment Date
                        </label>

                        <input
                            type="date"
                            class="control"
                            value="2026-08-15"
                        >

                    </div>

                </div>

            </div>

        </div>


        <!-- =====================================
             SCORE
        ====================================== -->

        <div class="card">

            <div class="card-head">

                <div>
                    <strong>Viability Score</strong>

                    <span>
                        Overall assessment of the proposed livelihood opportunity.
                    </span>
                </div>

            </div>

            <div class="card-body">

                <div class="score-grid">

                    <div class="score-card">
                        <div class="score-number">8/10</div>
                        <div class="score-label">Market Demand</div>
                    </div>

                    <div class="score-card">
                        <div class="score-number">8/10</div>
                        <div class="score-label">Applicant Capability</div>
                    </div>

                    <div class="score-card">
                        <div class="score-number">7/10</div>
                        <div class="score-label">Financial Feasibility</div>
                    </div>

                    <div class="score-card">
                        <div class="score-number">7/10</div>
                        <div class="score-label">Sustainability</div>
                    </div>

                </div>

            </div>

        </div>


        <!-- =====================================
             MARKET
        ====================================== -->

        <div class="card">

            <div class="card-head">

                <div>
                    <strong>Market Demand</strong>

                    <span>
                        Evaluate demand, competition and business location.
                    </span>
                </div>

            </div>

            <div class="card-body">

                <div class="rating-row">

                    <div class="rating-top">

                        <strong>Local Customer Demand</strong>

                        <span class="rating-value">
                            8 / 10
                        </span>

                    </div>

                    <div class="rating-bar">
                        <div
                            class="rating-fill"
                            style="width:80%"
                        ></div>
                    </div>

                </div>


                <div class="rating-row">

                    <div class="rating-top">

                        <strong>Competition</strong>

                        <span class="rating-value">
                            6 / 10
                        </span>

                    </div>

                    <div class="rating-bar">
                        <div
                            class="rating-fill"
                            style="width:60%"
                        ></div>
                    </div>

                </div>


                <div class="rating-row">

                    <div class="rating-top">

                        <strong>Business Location</strong>

                        <span class="rating-value">
                            8 / 10
                        </span>

                    </div>

                    <div class="rating-bar">
                        <div
                            class="rating-fill"
                            style="width:80%"
                        ></div>
                    </div>

                </div>


                <div style="margin-top:16px;">

                    <label class="label">
                        Market Assessment
                    </label>

                    <textarea class="control">Essential household goods have recurring demand in the local area. The proposed location provides access to nearby households. Competition exists but the applicant expects to differentiate through product availability and convenient location.</textarea>

                </div>

            </div>

        </div>


        <!-- =====================================
             APPLICANT CAPABILITY
        ====================================== -->

        <div class="card">

            <div class="card-head">

                <div>
                    <strong>Applicant Capability</strong>

                    <span>
                        Review skills, experience and ability to manage the business.
                    </span>
                </div>

            </div>

            <div class="card-body">

                <div class="checklist">

                    <label class="check-item">
                        <input type="checkbox" checked>
                        <span>
                            Applicant has relevant experience in the proposed business.
                        </span>
                    </label>

                    <label class="check-item">
                        <input type="checkbox" checked>
                        <span>
                            Applicant understands basic purchasing and inventory management.
                        </span>
                    </label>

                    <label class="check-item">
                        <input type="checkbox" checked>
                        <span>
                            Applicant intends to operate the business personally.
                        </span>
                    </label>

                    <label class="check-item">
                        <input type="checkbox">
                        <span>
                            Additional training may be required before launch.
                        </span>
                    </label>

                </div>


                <div style="margin-top:17px;">

                    <label class="label">
                        Capability Assessment
                    </label>

                    <textarea class="control">The applicant has approximately four years of relevant retail purchasing experience and appears capable of managing daily operations. Additional guidance on inventory tracking and financial record keeping is recommended.</textarea>

                </div>

            </div>

        </div>


        <!-- =====================================
             FINANCIAL FEASIBILITY
        ====================================== -->

        <div class="card">

            <div class="card-head">

                <div>
                    <strong>Financial Feasibility</strong>

                    <span>
                        Review costs, projected income and applicant contribution.
                    </span>
                </div>

            </div>

            <div class="card-body">

                <div class="grid">

                    <div>

                        <label class="label">
                            Total Investment
                        </label>

                        <input
                            class="control"
                            value="PKR 80,000"
                            readonly
                        >

                    </div>

                    <div>

                        <label class="label">
                            Applicant Contribution
                        </label>

                        <input
                            class="control"
                            value="PKR 20,000"
                            readonly
                        >

                    </div>

                    <div>

                        <label class="label">
                            Organization Contribution
                        </label>

                        <input
                            class="control"
                            value="PKR 60,000"
                            readonly
                        >

                    </div>

                    <div>

                        <label class="label">
                            Expected Monthly Net Income
                        </label>

                        <input
                            class="control"
                            value="PKR 23,000"
                            readonly
                        >

                    </div>

                </div>


                <div style="margin-top:18px;">

                    <label class="label">
                        Financial Assessment
                    </label>

                    <textarea class="control">The proposed investment appears proportionate to the intended business. The applicant's own contribution reduces the organization-funded portion. Projected net income appears sufficient to improve household income, subject to actual sales and expense performance.</textarea>

                </div>

            </div>

        </div>


        <!-- =====================================
             SUSTAINABILITY
        ====================================== -->

        <div class="card">

            <div class="card-head">

                <div>
                    <strong>Sustainability</strong>

                    <span>
                        Assess whether the business can continue without recurring assistance.
                    </span>
                </div>

            </div>

            <div class="card-body">

                <div class="rating-row">

                    <div class="rating-top">

                        <strong>Income Sustainability</strong>

                        <span class="rating-value">
                            7 / 10
                        </span>

                    </div>

                    <div class="rating-bar">
                        <div
                            class="rating-fill"
                            style="width:70%"
                        ></div>
                    </div>

                </div>


                <div class="rating-row">

                    <div class="rating-top">

                        <strong>Long-term Independence</strong>

                        <span class="rating-value">
                            8 / 10
                        </span>

                    </div>

                    <div class="rating-bar">
                        <div
                            class="rating-fill"
                            style="width:80%"
                        ></div>
                    </div>

                </div>


                <div style="margin-top:16px;">

                    <label class="label">
                        Sustainability Plan
                    </label>

                    <textarea class="control">The applicant intends to reinvest part of the monthly profit into inventory and gradually increase stock variety. The business is expected to become self-sustaining once a stable customer base is established.</textarea>

                </div>

            </div>

        </div>


        <!-- =====================================
             RISKS
        ====================================== -->

        <div class="card">

            <div class="card-head">

                <div>
                    <strong>Risk Assessment</strong>

                    <span>
                        Identify risks and proposed mitigation.
                    </span>
                </div>

            </div>

            <div class="card-body">

                <div class="risk-grid">

                    <div class="risk-box risk-low">

                        <strong>
                            Applicant Risk
                        </strong>

                        <p>
                            Relevant experience and personal involvement
                            reduce operational risk.
                        </p>

                    </div>


                    <div class="risk-box risk-medium">

                        <strong>
                            Market Risk
                        </strong>

                        <p>
                            Existing competitors may affect margins and
                            customer acquisition.
                        </p>

                    </div>


                    <div class="risk-box risk-medium">

                        <strong>
                            Financial Risk
                        </strong>

                        <p>
                            Early cash-flow pressure may occur before
                            stable sales are established.
                        </p>

                    </div>


                    <div class="risk-box risk-low">

                        <strong>
                            Asset Risk
                        </strong>

                        <p>
                            Planned organization-funded assets can be
                            verified and documented.
                        </p>

                    </div>

                </div>


                <div style="margin-top:17px;">

                    <label class="label">
                        Overall Risk Assessment
                    </label>

                    <select class="select">

                        <option>
                            Low
                        </option>

                        <option selected>
                            Medium
                        </option>

                        <option>
                            High
                        </option>

                    </select>

                </div>

            </div>

        </div>


        <!-- =====================================
             CHECKLIST
        ====================================== -->

        <div class="card">

            <div class="card-head">

                <div>
                    <strong>Viability Checklist</strong>

                    <span>
                        Final assessment controls.
                    </span>
                </div>

            </div>

            <div class="card-body">

                <div class="checklist">

                    <label class="check-item">
                        <input type="checkbox" checked>
                        <span>
                            Applicant identity and beneficiary record verified.
                        </span>
                    </label>

                    <label class="check-item">
                        <input type="checkbox" checked>
                        <span>
                            Field verification completed.
                        </span>
                    </label>

                    <label class="check-item">
                        <input type="checkbox" checked>
                        <span>
                            Business costs and requested contribution reviewed.
                        </span>
                    </label>

                    <label class="check-item">
                        <input type="checkbox" checked>
                        <span>
                            Business plan and supporting documents reviewed.
                        </span>
                    </label>

                    <label class="check-item">
                        <input type="checkbox" checked>
                        <span>
                            Major risks identified and assessed.
                        </span>
                    </label>

                    <label class="check-item">
                        <input type="checkbox">
                        <span>
                            Additional quotation / price verification required.
                        </span>
                    </label>

                </div>

            </div>

        </div>


        <!-- =====================================
             RECOMMENDATION
        ====================================== -->

        <div class="card">

            <div class="card-head">

                <div>
                    <strong>Assessment Recommendation</strong>

                    <span>
                        Preliminary recommendation before formal committee decision.
                    </span>
                </div>

            </div>

            <div class="card-body">

                <div class="recommendation-options">

                    <div class="recommendation-option">

                        <input
                            type="radio"
                            name="recommendation"
                            id="recRecommend"
                            checked
                        >

                        <label for="recRecommend">
                            Recommend
                        </label>

                    </div>


                    <div class="recommendation-option">

                        <input
                            type="radio"
                            name="recommendation"
                            id="recConditional"
                        >

                        <label for="recConditional">
                            Conditional
                        </label>

                    </div>


                    <div class="recommendation-option">

                        <input
                            type="radio"
                            name="recommendation"
                            id="recHold"
                        >

                        <label for="recHold">
                            Hold for More Information
                        </label>

                    </div>


                    <div class="recommendation-option">

                        <input
                            type="radio"
                            name="recommendation"
                            id="recReject"
                        >

                        <label for="recReject">
                            Not Recommended
                        </label>

                    </div>

                </div>


                <div style="margin-top:17px;">

                    <label class="label">
                        Recommendation Notes
                    </label>

                    <textarea class="control">Recommend subject to final review of supplier quotations and approval through the organization's defined decision-making process.</textarea>

                </div>

            </div>

        </div>


        <!-- =====================================
             EVIDENCE
        ====================================== -->

        <div class="card">

            <div class="card-head">

                <div>
                    <strong>Assessment Documents</strong>

                    <span>
                        Supporting evidence for the viability decision.
                    </span>
                </div>

            </div>

            <div class="card-body">

                <label class="upload">

                    <i class="bi bi-cloud-arrow-up"></i>

                    <strong>
                        Upload assessment documents
                    </strong>

                    <span>
                        Quotations, assessment forms, market evidence and reports
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


    <!-- =====================================
         SIDEBAR
    ====================================== -->

    <aside>

        <div class="card">

            <div class="card-body">

                <div class="score-summary">

                    <div class="score-total">
                        76%
                    </div>

                    <div class="score-total-label">
                        Overall Viability Score
                    </div>

                    <div class="mini-progress">
                        <span></span>
                    </div>

                </div>


                <div class="side-row">
                    <span>Status</span>
                    <span>In Progress</span>
                </div>

                <div class="side-row">
                    <span>Risk</span>
                    <span>Medium</span>
                </div>

                <div class="side-row">
                    <span>Applicant</span>
                    <span>Rahim Khan</span>
                </div>

                <div class="side-row">
                    <span>Requested</span>
                    <span>PKR 60,000</span>
                </div>

                <div class="side-row">
                    <span>Assessor</span>
                    <span>Implementation Committee</span>
                </div>

            </div>

        </div>


        <div class="card">

            <div class="card-head">
                <strong>Next Step</strong>
            </div>

            <div class="card-body">

                <div class="notice">

                    <i class="bi bi-arrow-right-circle"></i>

                    <p>
                        Once the assessment is completed, the application
                        can move to the recommendation stage for review by
                        the appropriate committee.
                    </p>

                </div>

            </div>

        </div>

    </aside>

</div>

@endsection


@push('scripts')
<script>

    function previewSubmitAssessment() {

        const confirmed =
            confirm(
                'Submit this viability assessment for recommendation?'
            );

        if (!confirmed) {
            return;
        }

        alert(
            'UI preview only.\n\n' +
            'The viability assessment would now be submitted to the next workflow stage once backend functionality is connected.'
        );

    }

</script>
@endpush