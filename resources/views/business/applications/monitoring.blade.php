@extends('layouts.app')

@section('title', 'Business Monitoring')

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
        background:#edf7f0;
        color:#397955;
        font-size: 13px;
        font-weight:650;
        white-space:nowrap;
    }

    .status::before {
        content:"";
        width:5px;
        height:5px;
        border-radius:50%;
        background:#4c9a6c;
    }

    /* SUMMARY */

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

    /* METRICS */

    .metrics {
        display:grid;
        grid-template-columns:repeat(3,minmax(0,1fr));
        gap:10px;
    }

    .metric {
        padding:14px;
        border:1px solid #edf1ee;
        border-radius:10px;
        background:#fafcfb;
    }

    .metric label {
        display:block;
        color: #6d7571;
        font-size: 13px;
        text-transform:uppercase;
    }

    .metric strong {
        display:block;
        margin-top:6px;
        font-size: 18px;
    }

    .metric small {
        display:block;
        margin-top:4px;
        color: #6c7470;
        font-size: 12px;
    }

    /* PERFORMANCE */

    .performance-row {
        padding:13px 0;
        border-bottom:1px solid #f0f3f1;
    }

    .performance-row:last-child {
        border-bottom:none;
    }

    .performance-top {
        display:flex;
        justify-content:space-between;
        gap:10px;
    }

    .performance-top strong {
        font-size: 15px;
    }

    .performance-value {
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

    .performance-note {
        margin-top:6px;
        color: #6b7670;
        font-size: 13px;
        line-height:1.55;
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

    /* MONITORING VISIT */

    .visit-grid {
        display:grid;
        grid-template-columns:repeat(3,minmax(0,1fr));
        gap:10px;
    }

    .visit {
        padding:13px;
        border:1px solid #edf1ee;
        border-radius:10px;
        background:#fafcfb;
    }

    .visit-date {
        color:var(--primary);
        font-size: 14px;
        font-weight:650;
    }

    .visit-title {
        margin-top:4px;
        font-size: 15px;
        font-weight:650;
    }

    .visit-meta {
        margin-top:3px;
        color: #6c7470;
        font-size: 13px;
    }

    /* ISSUES */

    .issue-list {
        display:flex;
        flex-direction:column;
        gap:9px;
    }

    .issue {
        display:flex;
        gap:9px;
        align-items:flex-start;
        padding:10px;
        border:1px solid #edf1ee;
        border-radius:9px;
        background:#fafcfb;
    }

    .issue i {
        font-size: 16px;
        color: #976d38;
        margin-top:1px;
    }

    .issue strong {
        display:block;
        font-size: 14px;
    }

    .issue span {
        display:block;
        margin-top:3px;
        color: #6d7872;
        font-size: 13px;
        line-height:1.5;
    }

    /* DECISION */

    .decision-grid {
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
        min-height:68px;
        padding:10px;
        border:1px solid var(--border);
        border-radius:10px;
        display:flex;
        flex-direction:column;
        justify-content:center;
        cursor:pointer;
    }

    .decision-option label strong {
        font-size: 14px;
    }

    .decision-option label span {
        margin-top:4px;
        color: #6c7470;
        font-size: 12px;
        line-height:1.45;
    }

    .decision-option input:checked + label {
        background:var(--primary-soft);
        border-color: #6b7570;
        color:var(--primary);
    }

    /* DOCUMENT */

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
        background:#cfe1d5;
        color:#397955;
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
        .metrics,
        .form-grid,
        .visit-grid,
        .decision-grid {
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
            <i class="bi bi-graph-up-arrow"></i>
        </div>

        <div>

            <div class="title">
                Business Monitoring
            </div>

            <div class="meta">
                SBA-00041 · Rahim Khan · Kasso General Store
            </div>

        </div>

    </div>

    <div class="actions">

        <a
            href="{{ route('business.applications.disbursement', ['id' => 1]) }}"
            class="ui-btn"
        >
            <i class="bi bi-arrow-left"></i>
            Back
        </a>

        <button
            class="ui-btn primary"
            onclick="previewSaveMonitoring()"
        >
            <i class="bi bi-check-lg"></i>
            Save Monitoring
        </button>

    </div>

</div>


<div class="layout">

    <div>

        <!-- STATUS -->

        <div class="card">

            <div class="card-head">

                <div>
                    <strong>Business Status</strong>

                    <span>
                        Current operating status after disbursement.
                    </span>
                </div>

                <span class="status">
                    Active
                </span>

            </div>

            <div class="card-body">

                <div class="summary-grid">

                    <div class="summary-box">
                        <label>Application</label>
                        <strong>SBA-00041</strong>
                    </div>

                    <div class="summary-box">
                        <label>Business Started</label>
                        <strong>05 Sep 2026</strong>
                    </div>

                    <div class="summary-box highlight">
                        <label>Current Status</label>
                        <strong>Operating</strong>
                    </div>

                    <div class="summary-box">
                        <label>Approved Support</label>
                        <strong>PKR 60,000</strong>
                    </div>

                    <div class="summary-box">
                        <label>Monitoring Period</label>
                        <strong>6 Months</strong>
                    </div>

                    <div class="summary-box">
                        <label>Next Review</label>
                        <strong>30 Sep 2026</strong>
                    </div>

                </div>

            </div>

        </div>


        <!-- CURRENT PERFORMANCE -->

        <div class="card">

            <div class="card-head">

                <div>
                    <strong>Current Performance</strong>

                    <span>
                        Latest reported business performance.
                    </span>
                </div>

            </div>

            <div class="card-body">

                <div class="metrics">

                    <div class="metric">

                        <label>Monthly Sales</label>

                        <strong>
                            PKR 88,000
                        </strong>

                        <small>
                            Latest month
                        </small>

                    </div>


                    <div class="metric">

                        <label>Monthly Expenses</label>

                        <strong>
                            PKR 66,000
                        </strong>

                        <small>
                            Latest month
                        </small>

                    </div>


                    <div class="metric">

                        <label>Net Income</label>

                        <strong>
                            PKR 22,000
                        </strong>

                        <small>
                            Approx. monthly
                        </small>

                    </div>

                </div>

            </div>

        </div>


        <!-- PERFORMANCE INDICATORS -->

        <div class="card">

            <div class="card-head">

                <div>
                    <strong>Performance Indicators</strong>

                    <span>
                        Comparison with the approved business plan.
                    </span>
                </div>

            </div>

            <div class="card-body">

                <div class="performance-row">

                    <div class="performance-top">
                        <strong>Sales Target</strong>
                        <span class="performance-value">88%</span>
                    </div>

                    <div class="progress">
                        <span style="width:88%"></span>
                    </div>

                    <div class="performance-note">
                        Actual monthly sales are approximately PKR 88,000
                        compared with the projected PKR 95,000.
                    </div>

                </div>


                <div class="performance-row">

                    <div class="performance-top">
                        <strong>Net Income Target</strong>
                        <span class="performance-value">96%</span>
                    </div>

                    <div class="progress">
                        <span style="width:96%"></span>
                    </div>

                    <div class="performance-note">
                        Current net income is close to the projected
                        PKR 23,000 monthly level.
                    </div>

                </div>


                <div class="performance-row">

                    <div class="performance-top">
                        <strong>Business Sustainability</strong>
                        <span class="performance-value">82%</span>
                    </div>

                    <div class="progress">
                        <span style="width:82%"></span>
                    </div>

                    <div class="performance-note">
                        Business is operating independently with stable
                        customer demand and regular stock turnover.
                    </div>

                </div>

            </div>

        </div>


        <!-- MONITORING VISITS -->

        <div class="card">

            <div class="card-head">

                <div>
                    <strong>Monitoring Visits</strong>

                    <span>
                        Field monitoring and follow-up history.
                    </span>
                </div>

                <button
                    class="ui-btn primary"
                    onclick="previewVisit()"
                >
                    <i class="bi bi-plus-lg"></i>
                    Add Visit
                </button>

            </div>

            <div class="card-body">

                <div class="visit-grid">

                    <div class="visit">

                        <div class="visit-date">
                            05 Sep 2026
                        </div>

                        <div class="visit-title">
                            Business Started
                        </div>

                        <div class="visit-meta">
                            Abid Anwar · Initial visit
                        </div>

                    </div>


                    <div class="visit">

                        <div class="visit-date">
                            15 Sep 2026
                        </div>

                        <div class="visit-title">
                            Initial Monitoring
                        </div>

                        <div class="visit-meta">
                            Ali Rahman · Operating review
                        </div>

                    </div>


                    <div class="visit">

                        <div class="visit-date">
                            30 Sep 2026
                        </div>

                        <div class="visit-title">
                            Scheduled Review
                        </div>

                        <div class="visit-meta">
                            Pending
                        </div>

                    </div>

                </div>

            </div>

        </div>


        <!-- MONITORING REPORT -->

        <div class="card">

            <div class="card-head">

                <div>
                    <strong>Monitoring Report</strong>

                    <span>
                        Record the latest business assessment.
                    </span>
                </div>

            </div>

            <div class="card-body">

                <div class="form-grid">

                    <div>

                        <label class="label">
                            Monitoring Date
                        </label>

                        <input
                            type="date"
                            class="control"
                            value="2026-09-15"
                        >

                    </div>


                    <div>

                        <label class="label">
                            Monitored By
                        </label>

                        <select class="select">

                            <option selected>
                                Ali Rahman
                            </option>

                            <option>
                                Abid Anwar
                            </option>

                            <option>
                                Implementation Committee
                            </option>

                        </select>

                    </div>


                    <div>

                        <label class="label">
                            Business Status
                        </label>

                        <select class="select">

                            <option selected>
                                Operating Normally
                            </option>

                            <option>
                                Operating With Difficulties
                            </option>

                            <option>
                                Temporarily Closed
                            </option>

                            <option>
                                Permanently Closed
                            </option>

                        </select>

                    </div>


                    <div>

                        <label class="label">
                            Overall Assessment
                        </label>

                        <select class="select">

                            <option selected>
                                Satisfactory
                            </option>

                            <option>
                                Needs Improvement
                            </option>

                            <option>
                                At Risk
                            </option>

                        </select>

                    </div>


                    <div class="full">

                        <label class="label">
                            Business Progress
                        </label>

                        <textarea class="control">The business is operating with stable customer demand. Sales are slightly below the original projection, but the business remains profitable and the applicant is actively managing operations.</textarea>

                    </div>


                    <div class="full">

                        <label class="label">
                            Current Challenges
                        </label>

                        <textarea class="control">Wholesale price fluctuations and limited initial working capital are the primary current challenges.</textarea>

                    </div>


                    <div class="full">

                        <label class="label">
                            Follow-up Actions
                        </label>

                        <textarea class="control">Continue monthly monitoring for the next two months. Applicant should maintain basic sales and expense records and review stock turnover regularly.</textarea>

                    </div>

                </div>

            </div>

        </div>


        <!-- ISSUES -->

        <div class="card">

            <div class="card-head">

                <div>
                    <strong>Issues & Risk Monitoring</strong>

                    <span>
                        Track problems that may affect sustainability.
                    </span>
                </div>

            </div>

            <div class="card-body">

                <div class="issue-list">

                    <div class="issue">

                        <i class="bi bi-exclamation-circle"></i>

                        <div>

                            <strong>
                                Inventory Cost Pressure
                            </strong>

                            <span>
                                Wholesale prices have increased and may reduce margins
                                during some months.
                            </span>

                        </div>

                    </div>


                    <div class="issue">

                        <i class="bi bi-info-circle"></i>

                        <div>

                            <strong>
                                Record Keeping
                            </strong>

                            <span>
                                Applicant should maintain more consistent sales
                                and expense records.
                            </span>

                        </div>

                    </div>

                </div>

            </div>

        </div>


        <!-- CONTINUATION DECISION -->

        <div class="card">

            <div class="card-head">

                <div>
                    <strong>Monitoring Decision</strong>

                    <span>
                        Decide the next action based on the monitoring results.
                    </span>
                </div>

            </div>

            <div class="card-body">

                <div class="decision-grid">

                    <div class="decision-option">

                        <input
                            type="radio"
                            name="monitoringDecision"
                            id="continue"
                            checked
                        >

                        <label for="continue">

                            <strong>
                                Continue Monitoring
                            </strong>

                            <span>
                                Business is progressing and should remain under
                                normal monitoring.
                            </span>

                        </label>

                    </div>


                    <div class="decision-option">

                        <input
                            type="radio"
                            name="monitoringDecision"
                            id="support"
                        >

                        <label for="support">

                            <strong>
                                Additional Support Needed
                            </strong>

                            <span>
                                Follow-up or additional intervention is required.
                            </span>

                        </label>

                    </div>


                    <div class="decision-option">

                        <input
                            type="radio"
                            name="monitoringDecision"
                            id="close"
                        >

                        <label for="close">

                            <strong>
                                Close Monitoring
                            </strong>

                            <span>
                                Business is stable and no further regular monitoring
                                is currently required.
                            </span>

                        </label>

                    </div>

                </div>


                <div style="margin-top:17px;">

                    <label class="label">
                        Decision Notes
                    </label>

                    <textarea class="control">Continue normal monitoring. Business remains operational and broadly aligned with the approved livelihood objective.</textarea>

                </div>

            </div>

        </div>


        <!-- RECOVERY -->

        <div class="card">

            <div class="card-head">

                <div>
                    <strong>Recovery / Fund Recycling</strong>

                    <span>
                        Track recovery where the assistance structure requires it.
                    </span>

                </div>

            </div>

            <div class="card-body">

                <div class="summary-grid">

                    <div class="summary-box">
                        <label>Original Assistance</label>
                        <strong>PKR 60,000</strong>
                    </div>

                    <div class="summary-box">
                        <label>Recovered</label>
                        <strong>PKR 0</strong>
                    </div>

                    <div class="summary-box highlight">
                        <label>Recoverable Balance</label>
                        <strong>PKR 0</strong>
                    </div>

                </div>

                <div style="margin-top:14px;">

                    <div class="notice">

                        <i class="bi bi-arrow-repeat"></i>

                        <p>
                            Where recovery applies, recovered principal should
                            remain associated with the appropriate restricted
                            fund and become available for another approved
                            small-business project.
                        </p>

                    </div>

                </div>

            </div>

        </div>


        <!-- DOCUMENTS -->

        <div class="card">

            <div class="card-head">

                <div>
                    <strong>Monitoring Evidence</strong>

                    <span>
                        Attach field reports, photos and supporting records.
                    </span>
                </div>

            </div>

            <div class="card-body">

                <label class="upload">

                    <i class="bi bi-cloud-arrow-up"></i>

                    <strong>
                        Upload monitoring evidence
                    </strong>

                    <span>
                        Field photos, monitoring reports, sales records and supporting documents
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
                        Final stage of the Small Business Assistance workflow.
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

                        <div class="workflow-circle done">
                            <i class="bi bi-check-lg"></i>
                        </div>

                        <strong>Disbursement</strong>
                        <span>Complete</span>

                    </div>


                    <div class="workflow-stage">

                        <div class="workflow-circle current">
                            8
                        </div>

                        <strong>Monitoring</strong>
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
                    82%
                </div>

                <div class="side-label">
                    Sustainability Score
                </div>

                <div style="
                    height:1px;
                    background:#eef1ef;
                    margin:16px 0;
                "></div>

                <div class="side-row">
                    <span>Status</span>
                    <span>Operating</span>
                </div>

                <div class="side-row">
                    <span>Business Started</span>
                    <span>05 Sep 2026</span>
                </div>

                <div class="side-row">
                    <span>Monthly Sales</span>
                    <span>PKR 88,000</span>
                </div>

                <div class="side-row">
                    <span>Net Income</span>
                    <span>PKR 22,000</span>
                </div>

                <div class="side-row">
                    <span>Next Review</span>
                    <span>30 Sep 2026</span>
                </div>

            </div>

        </div>


        <div class="card">

            <div class="card-head">
                <strong>Monitoring Principle</strong>
            </div>

            <div class="card-body">

                <div class="notice">

                    <i class="bi bi-graph-up-arrow"></i>

                    <p>
                        Monitoring should determine whether the business
                        is becoming sustainable and identify practical
                        support or corrective actions when needed.
                    </p>

                </div>

            </div>

        </div>

    </aside>

</div>

@endsection


@push('scripts')
<script>

    function previewSaveMonitoring() {

        const confirmed =
            confirm(
                'Save this monitoring report?'
            );

        if (!confirmed) {
            return;
        }

        alert(
            'UI preview only.\n\n' +
            'The monitoring record would be saved and the business progress history updated once backend functionality is connected.'
        );

    }


    function previewVisit() {

        alert(
            'UI preview only.\n\n' +
            'A new monitoring visit form will be connected when backend functionality is implemented.'
        );

    }

</script>
@endpush