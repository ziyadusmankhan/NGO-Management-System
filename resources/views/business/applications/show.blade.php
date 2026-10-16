@extends('layouts.app')

@section('title', 'Small Business Application Details')

@push('styles')
<style>
    .page-header {
        display: flex;
        justify-content: space-between;
        align-items: flex-start;
        gap: 20px;
        margin-bottom: 22px;
    }

    .identity {
        display: flex;
        align-items: center;
        gap: 14px;
    }

    .icon-box {
        width: 62px;
        height: 62px;
        border-radius: 17px;
        background: var(--primary-soft);
        color: var(--primary);
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 23px;
    }

    .title {
        font-size: 23px;
        font-weight: 700;
        letter-spacing: -.4px;
    }

    .meta {
        margin-top: 4px;
        color: #6e7773;
        font-size: 15px;
    }

    .actions {
        display: flex;
        gap: 8px;
        flex-wrap: wrap;
    }

    .ui-btn {
        height: 39px;
        padding: 0 12px;
        border-radius: 9px;
        display: inline-flex;
        align-items: center;
        gap: 7px;
        border: 1px solid var(--border);
        background: #fff;
        color: #65726c;
        font-size: 15px;
        font-weight: 650;
        text-decoration: none;
        cursor: pointer;
    }

    .ui-btn.primary {
        background: var(--primary);
        border-color: var(--primary);
        color: #fff;
    }

    .ui-btn.approve {
        background: #edf7f0;
        border-color: #6a746d;
        color: #397955;
    }

    .ui-btn.reject {
        background: #fff0ef;
        border-color: #7c706f;
        color: #a85f58;
    }

    .layout {
        display: grid;
        grid-template-columns: minmax(0, 1fr) 300px;
        gap: 18px;
    }

    .card {
        background: #fff;
        border: 1px solid var(--border);
        border-radius: 14px;
        overflow: hidden;
        margin-bottom: 18px;
    }

    .card-head {
        padding: 16px 19px;
        border-bottom: 1px solid #eef1ef;
        display: flex;
        align-items: center;
        justify-content: space-between;
        gap: 15px;
    }

    .card-head strong {
        font-size: 16px;
    }

    .card-head span {
        display: block;
        margin-top: 4px;
        color: #6c7470;
        font-size: 14px;
    }

    .card-body {
        padding: 18px 19px;
    }

    /* Status */

    .stage {
        display: inline-flex;
        align-items: center;
        gap: 5px;
        padding: 5px 8px;
        border-radius: 20px;
        background: #f1eef7;
        color: #77668c;
        font-size: 13px;
        font-weight: 650;
    }

    .stage::before {
        content: "";
        width: 5px;
        height: 5px;
        border-radius: 50%;
        background: #8c77a3;
    }

    .risk {
        display: inline-flex;
        padding: 5px 8px;
        border-radius: 20px;
        background: #fff5e9;
        color: #976d38;
        font-size: 13px;
        font-weight: 650;
    }

    /* Overview */

    .overview-grid {
        display: grid;
        grid-template-columns: repeat(3, minmax(0, 1fr));
        gap: 10px;
    }

    .overview {
        padding: 13px;
        background: #fafcfb;
        border: 1px solid #edf1ee;
        border-radius: 10px;
    }

    .overview label {
        display: block;
        color: #6d7571;
        font-size: 13px;
        text-transform: uppercase;
        letter-spacing: .5px;
    }

    .overview strong {
        display: block;
        margin-top: 5px;
        font-size: 16px;
    }

    /* Applicant */

    .applicant-box {
        display: flex;
        align-items: center;
        gap: 11px;
    }

    .avatar {
        width: 40px;
        height: 40px;
        border-radius: 11px;
        background: var(--primary-soft);
        color: var(--primary);
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 15px;
        font-weight: 700;
    }

    .applicant-name {
        font-size: 15px;
        font-weight: 650;
    }

    .applicant-meta {
        margin-top: 3px;
        color: #6c7470;
        font-size: 13px;
    }

    /* Business */

    .business-description {
        color: #67746d;
        font-size: 14px;
        line-height: 1.7;
    }

    .business-grid {
        display: grid;
        grid-template-columns: repeat(2, minmax(0, 1fr));
        gap: 15px;
        margin-top: 17px;
    }

    .detail label {
        display: block;
        margin-bottom: 5px;
        color: #6d7571;
        font-size: 13px;
        text-transform: uppercase;
        letter-spacing: .5px;
    }

    .detail strong {
        display: block;
        font-size: 15px;
        color: #46534c;
    }

    /* Finance */

    .finance-grid {
        display: grid;
        grid-template-columns: repeat(3, minmax(0, 1fr));
        gap: 10px;
    }

    .finance-box {
        padding: 13px;
        background: #fafcfb;
        border: 1px solid #edf1ee;
        border-radius: 10px;
    }

    .finance-box.highlight {
        background: var(--primary-soft);
        border-color: #6f7673;
    }

    .finance-box label {
        display: block;
        color: #6d7571;
        font-size: 13px;
        text-transform: uppercase;
    }

    .finance-box strong {
        display: block;
        margin-top: 6px;
        color: #46534c;
        font-size: 16px;
    }

    .finance-box.highlight strong {
        color: var(--primary);
    }

    /* Projections */

    .projection-grid {
        display: grid;
        grid-template-columns: repeat(4, minmax(0, 1fr));
        gap: 10px;
    }

    .projection {
        padding: 12px;
        border: 1px solid #edf1ee;
        border-radius: 10px;
        background: #fafcfb;
    }

    .projection label {
        display: block;
        color: #6d7571;
        font-size: 12px;
        text-transform: uppercase;
    }

    .projection strong {
        display: block;
        margin-top: 5px;
        font-size: 16px;
    }

    .projection.net {
        background: var(--primary-soft);
        border-color: #6f7673;
    }

    .projection.net strong {
        color: var(--primary);
    }

    /* Risk */

    .risk-grid {
        display: grid;
        grid-template-columns: repeat(2, minmax(0, 1fr));
        gap: 10px;
    }

    .risk-box {
        padding: 12px;
        border: 1px solid #edf1ee;
        border-radius: 10px;
        background: #fafcfb;
    }

    .risk-box strong {
        display: block;
        font-size: 14px;
        margin-bottom: 5px;
    }

    .risk-box p {
        margin: 0;
        color: #69766f;
        font-size: 13px;
        line-height: 1.6;
    }

    /* Workflow */

    .workflow {
        display: flex;
        align-items: flex-start;
        overflow-x: auto;
        padding-bottom: 4px;
    }

    .workflow-stage {
        min-width: 135px;
        text-align: center;
        position: relative;
    }

    .workflow-stage:not(:last-child)::after {
        content: "";
        position: absolute;
        width: 40px;
        height: 1px;
        top: 15px;
        right: -20px;
        background: #d9e2de;
    }

    .workflow-circle {
        width: 30px;
        height: 30px;
        margin: 0 auto 8px;
        border-radius: 9px;
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 14px;
        font-weight: 700;
        position: relative;
        z-index: 1;
    }

    .workflow-circle.done {
        background: var(--primary);
        color: #fff;
    }

    .workflow-circle.current {
        background: #d9c5e4;
        color: #6d4f7e;
    }

    .workflow-circle.pending {
        background: #eef1ef;
        color: #6d7671;
    }

    .workflow-stage strong {
        display: block;
        font-size: 13px;
    }

    .workflow-stage span {
        display: block;
        margin-top: 3px;
        color: #6d7571;
        font-size: 12px;
    }

    /* Timeline */

    .timeline-item {
        display: flex;
        gap: 11px;
        padding-bottom: 18px;
        position: relative;
    }

    .timeline-item:last-child {
        padding-bottom: 0;
    }

    .timeline-dot {
        width: 9px;
        height: 9px;
        margin-top: 4px;
        border-radius: 50%;
        background: var(--primary);
        flex-shrink: 0;
        position: relative;
    }

    .timeline-item:not(:last-child) .timeline-dot::after {
        content: "";
        position: absolute;
        width: 1px;
        height: 29px;
        left: 4px;
        top: 9px;
        background: #dae3df;
    }

    .timeline-title {
        font-size: 15px;
        font-weight: 650;
    }

    .timeline-meta {
        margin-top: 3px;
        color: #6c7470;
        font-size: 13px;
    }

    .timeline-note {
        margin-top: 6px;
        padding: 8px 10px;
        background: #fafcfb;
        border: 1px solid #edf1ee;
        border-radius: 8px;
        color: #6d7972;
        font-size: 13px;
        line-height: 1.5;
    }

    /* Documents */

    .document-row {
        display: flex;
        align-items: center;
        gap: 10px;
        padding: 11px 0;
        border-bottom: 1px solid #f0f3f1;
    }

    .document-row:last-child {
        border-bottom: none;
    }

    .document-icon {
        width: 32px;
        height: 32px;
        border-radius: 9px;
        background: #f4f7f5;
        color: var(--primary);
        display: flex;
        align-items: center;
        justify-content: center;
    }

    .document-info {
        flex: 1;
    }

    .document-info strong {
        display: block;
        font-size: 14px;
    }

    .document-info span {
        display: block;
        margin-top: 3px;
        color: #6c7470;
        font-size: 13px;
    }

    .doc-action {
        width: 28px;
        height: 28px;
        border: 1px solid var(--border);
        background: #fff;
        border-radius: 7px;
        color: #6b766f;
    }

    /* Sidebar */

    .side-number {
        font-size: 27px;
        font-weight: 700;
        color: var(--primary);
        text-align: center;
    }

    .side-label {
        text-align: center;
        margin-top: 3px;
        color: #6e7772;
        font-size: 14px;
    }

    .side-row {
        display: flex;
        justify-content: space-between;
        gap: 10px;
        padding: 9px 0;
        border-bottom: 1px solid #f0f3f1;
        font-size: 14px;
    }

    .side-row:last-child {
        border-bottom: none;
    }

    .side-row span:first-child {
        color: #6d7571;
    }

    .side-row span:last-child {
        color: #4a5750;
        font-weight: 650;
        text-align: right;
    }

    .notice {
        display: flex;
        gap: 9px;
        padding: 12px;
        background: #f7f9f8;
        border: 1px solid #e5ebe8;
        border-radius: 10px;
    }

    .notice i {
        color: var(--primary);
        font-size: 16px;
    }

    .notice p {
        margin: 0;
        color: #6b766f;
        font-size: 13px;
        line-height: 1.6;
    }

    @media (max-width: 950px) {
        .layout {
            grid-template-columns: 1fr;
        }
    }

    @media (max-width: 700px) {
        .page-header {
            flex-direction: column;
        }

        .actions {
            width: 100%;
        }

        .actions .ui-btn {
            flex: 1;
            justify-content: center;
        }

        .overview-grid,
        .business-grid,
        .finance-grid,
        .projection-grid,
        .risk-grid {
            grid-template-columns: 1fr;
        }
    }
</style>
@endpush


@section('content')

<div class="page-header">

    <div class="identity">

        <div class="icon-box">
            <i class="bi bi-shop"></i>
        </div>

        <div>

            <div class="title">
                SBA-00041
            </div>

            <div class="meta">
                Rahim Khan · Kasso General Store · Submitted 12 Aug 2026
            </div>

        </div>

    </div>


    <div class="actions">

        <a
            href="{{ route('business.applications.index') }}"
            class="ui-btn"
        >
            <i class="bi bi-arrow-left"></i>
            Back
        </a>

        <button
            class="ui-btn reject"
            onclick="previewDecision('rejected')"
        >
            <i class="bi bi-x-circle"></i>
            Reject
        </button>

        <button
            class="ui-btn approve"
            onclick="previewDecision('approved')"
        >
            <i class="bi bi-check-circle"></i>
            Approve
        </button>

    </div>

</div>


<div class="layout">

    <div>

        <!-- =====================================
             OVERVIEW
        ====================================== -->

        <div class="card">

            <div class="card-head">

                <div>
                    <strong>Application Overview</strong>

                    <span>
                        Current business application status.
                    </span>
                </div>

                <span class="stage">
                    Viability Review
                </span>

            </div>

            <div class="card-body">

                <div class="overview-grid">

                    <div class="overview">
                        <label>Application ID</label>
                        <strong>SBA-00041</strong>
                    </div>

                    <div class="overview">
                        <label>Business Type</label>
                        <strong>Retail</strong>
                    </div>

                    <div class="overview">
                        <label>Location</label>
                        <strong>Kasso</strong>
                    </div>

                    <div class="overview">
                        <label>Requested Amount</label>
                        <strong>PKR 60,000</strong>
                    </div>

                    <div class="overview">
                        <label>Applicant Contribution</label>
                        <strong>PKR 20,000</strong>
                    </div>

                    <div class="overview">
                        <label>Risk</label>
                        <strong>Medium</strong>
                    </div>

                </div>

            </div>

        </div>


        <!-- =====================================
             APPLICANT
        ====================================== -->

        <div class="card">

            <div class="card-head">

                <div>
                    <strong>Applicant</strong>

                    <span>
                        Linked beneficiary information.
                    </span>
                </div>

            </div>

            <div class="card-body">

                <div class="applicant-box">

                    <div class="avatar">
                        RK
                    </div>

                    <div style="flex:1">

                        <div class="applicant-name">
                            Rahim Khan
                        </div>

                        <div class="applicant-meta">
                            BEN-00063 · Kasso · Poor but Employable
                        </div>

                    </div>

                    <a
                        href="{{ route('beneficiaries.show', ['id' => 3]) }}"
                        class="ui-btn"
                    >
                        View Beneficiary
                    </a>

                </div>

            </div>

        </div>


        <!-- =====================================
             BUSINESS
        ====================================== -->

        <div class="card">

            <div class="card-head">

                <div>
                    <strong>Proposed Business</strong>

                    <span>
                        Business model and operating information.
                    </span>
                </div>

            </div>

            <div class="card-body">

                <div class="business-description">

                    The applicant proposes to establish a small general store
                    in Kasso serving household customers with essential food,
                    grocery and basic household items. The applicant has
                    experience in retail purchasing and intends to operate
                    the business personally.

                </div>


                <div class="business-grid">

                    <div class="detail">
                        <label>Business Name</label>
                        <strong>Kasso General Store</strong>
                    </div>

                    <div class="detail">
                        <label>Business Type</label>
                        <strong>Retail</strong>
                    </div>

                    <div class="detail">
                        <label>Location</label>
                        <strong>Kasso Main Market</strong>
                    </div>

                    <div class="detail">
                        <label>Operating Model</label>
                        <strong>Owner-operated</strong>
                    </div>

                    <div class="detail">
                        <label>Relevant Experience</label>
                        <strong>4 years retail purchasing</strong>
                    </div>

                    <div class="detail">
                        <label>Expected Start</label>
                        <strong>September 2026</strong>
                    </div>

                </div>

            </div>

        </div>


        <!-- =====================================
             FINANCIAL STRUCTURE
        ====================================== -->

        <div class="card">

            <div class="card-head">

                <div>
                    <strong>Investment & Funding</strong>

                    <span>
                        Proposed investment structure.
                    </span>
                </div>

            </div>

            <div class="card-body">

                <div class="finance-grid">

                    <div class="finance-box">

                        <label>Total Investment</label>

                        <strong>
                            PKR 80,000
                        </strong>

                    </div>


                    <div class="finance-box">

                        <label>Applicant Contribution</label>

                        <strong>
                            PKR 20,000
                        </strong>

                    </div>


                    <div class="finance-box highlight">

                        <label>Organization Contribution</label>

                        <strong>
                            PKR 60,000
                        </strong>

                    </div>

                </div>

            </div>

        </div>


        <!-- =====================================
             BUSINESS PROJECTIONS
        ====================================== -->

        <div class="card">

            <div class="card-head">

                <div>
                    <strong>Business Projection</strong>

                    <span>
                        Expected monthly performance.
                    </span>
                </div>

            </div>

            <div class="card-body">

                <div class="projection-grid">

                    <div class="projection">

                        <label>
                            Expected Sales
                        </label>

                        <strong>
                            PKR 95,000
                        </strong>

                    </div>


                    <div class="projection">

                        <label>
                            Expected Expenses
                        </label>

                        <strong>
                            PKR 72,000
                        </strong>

                    </div>


                    <div class="projection net">

                        <label>
                            Expected Net Income
                        </label>

                        <strong>
                            PKR 23,000
                        </strong>

                    </div>


                    <div class="projection">

                        <label>
                            Break-even
                        </label>

                        <strong>
                            8 months
                        </strong>

                    </div>

                </div>


                <p style="
                    margin:16px 0 0;
                    color:#69766f;
                    font-size: 14px;
                    line-height:1.7;
                ">
                    The proposed business is expected to generate sufficient
                    monthly income to support the household and gradually
                    recover the organization's contribution where applicable.
                </p>

            </div>

        </div>


        <!-- =====================================
             VIABILITY / RISKS
        ====================================== -->

        <div class="card">

            <div class="card-head">

                <div>
                    <strong>Viability Assessment</strong>

                    <span>
                        Preliminary assessment before formal committee recommendation.
                    </span>
                </div>

                <span class="risk">
                    Medium Risk
                </span>

            </div>

            <div class="card-body">

                <div class="risk-grid">

                    <div class="risk-box">

                        <strong>
                            Market Demand
                        </strong>

                        <p>
                            Essential household goods have regular local demand.
                            Competition exists but appears manageable.
                        </p>

                    </div>


                    <div class="risk-box">

                        <strong>
                            Applicant Capability
                        </strong>

                        <p>
                            Applicant has relevant purchasing and retail experience.
                        </p>

                    </div>


                    <div class="risk-box">

                        <strong>
                            Financial Risk
                        </strong>

                        <p>
                            Proposed applicant contribution reduces the required
                            organizational contribution.
                        </p>

                    </div>


                    <div class="risk-box">

                        <strong>
                            Key Challenge
                        </strong>

                        <p>
                            Initial customer base and inventory turnover require
                            monitoring during the early months.
                        </p>

                    </div>

                </div>

            </div>

        </div>


        <!-- =====================================
             WORKFLOW
        ====================================== -->

        <div class="card">

            <div class="card-head">

                <div>
                    <strong>Application Workflow</strong>

                    <span>
                        Current position in the approval process.
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

                        <span>
                            Submitted
                        </span>

                    </div>


                    <div class="workflow-stage">

                        <div class="workflow-circle done">
                            <i class="bi bi-check-lg"></i>
                        </div>

                        <strong>Verification</strong>

                        <span>
                            Completed
                        </span>

                    </div>


                    <div class="workflow-stage">

                        <div class="workflow-circle current">
                            3
                        </div>

                        <strong>Viability</strong>

                        <span>
                            Current
                        </span>

                    </div>


                    <div class="workflow-stage">

                        <div class="workflow-circle pending">
                            4
                        </div>

                        <strong>Recommendation</strong>

                        <span>
                            Pending
                        </span>

                    </div>


                    <div class="workflow-stage">

                        <div class="workflow-circle pending">
                            5
                        </div>

                        <strong>Approval</strong>

                        <span>
                            Pending
                        </span>

                    </div>


                    <div class="workflow-stage">

                        <div class="workflow-circle pending">
                            6
                        </div>

                        <strong>Agreement</strong>

                        <span>
                            Pending
                        </span>

                    </div>


                    <div class="workflow-stage">

                        <div class="workflow-circle pending">
                            7
                        </div>

                        <strong>Disbursement</strong>

                        <span>
                            Pending
                        </span>

                    </div>

                </div>

            </div>

        </div>


        <!-- =====================================
             ACTIVITY
        ====================================== -->

        <div class="card">

            <div class="card-head">

                <div>
                    <strong>Activity History</strong>

                    <span>
                        Application events and reviews.
                    </span>
                </div>

            </div>

            <div class="card-body">

                <div class="timeline-item">

                    <div class="timeline-dot"></div>

                    <div>

                        <div class="timeline-title">
                            Viability review started
                        </div>

                        <div class="timeline-meta">
                            14 Aug 2026 · Implementation Committee
                        </div>

                        <div class="timeline-note">
                            Business proposal and financial projection are
                            being reviewed before recommendation.
                        </div>

                    </div>

                </div>


                <div class="timeline-item">

                    <div class="timeline-dot"></div>

                    <div>

                        <div class="timeline-title">
                            Field verification completed
                        </div>

                        <div class="timeline-meta">
                            14 Aug 2026 · Abid Anwar
                        </div>

                    </div>

                </div>


                <div class="timeline-item">

                    <div class="timeline-dot"></div>

                    <div>

                        <div class="timeline-title">
                            Application submitted
                        </div>

                        <div class="timeline-meta">
                            12 Aug 2026 · Rahim Khan
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
                    <strong>Documents</strong>

                    <span>
                        Supporting business records.
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
                            Business Proposal
                        </strong>

                        <span>
                            PDF · Added 12 Aug 2026
                        </span>

                    </div>

                    <button class="doc-action">
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
                            PDF · Added 13 Aug 2026
                        </span>

                    </div>

                    <button class="doc-action">
                        <i class="bi bi-eye"></i>
                    </button>

                </div>


                <div class="document-row">

                    <div class="document-icon">
                        <i class="bi bi-images"></i>
                    </div>

                    <div class="document-info">

                        <strong>
                            Field Verification Photos
                        </strong>

                        <span>
                            4 images · Added 14 Aug 2026
                        </span>

                    </div>

                    <button class="doc-action">
                        <i class="bi bi-eye"></i>
                    </button>

                </div>

            </div>

        </div>

    </div>


    <!-- =====================================
         SIDE PANEL
    ====================================== -->

    <aside>

        <div class="card">

            <div class="card-body">

                <div class="side-number">
                    PKR 60K
                </div>

                <div class="side-label">
                    Organization Contribution
                </div>

                <div style="
                    height:1px;
                    background:#eef1ef;
                    margin:16px 0;
                "></div>

                <div class="side-row">
                    <span>Status</span>
                    <span>Viability Review</span>
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
                    <span>Business</span>
                    <span>General Store</span>
                </div>

                <div class="side-row">
                    <span>Fund</span>
                    <span>Small Business Fund</span>
                </div>

                <div class="side-row">
                    <span>Submitted</span>
                    <span>12 Aug 2026</span>
                </div>

            </div>

        </div>


        <!-- DECISION -->

        <div class="card">

            <div class="card-head">

                <div>
                    <strong>Available Actions</strong>

                    <span>
                        Actions for authorized reviewers.
                    </span>
                </div>

            </div>

            <div class="card-body">

                <div style="
                    display:grid;
                    grid-template-columns:1fr;
                    gap:8px;
                ">

                    <button
                        class="ui-btn primary"
                        onclick="previewDecision('recommendation')"
                    >
                        <i class="bi bi-arrow-up-circle"></i>
                        Submit Recommendation
                    </button>

                    <button
                        class="ui-btn approve"
                        onclick="previewDecision('approved')"
                    >
                        <i class="bi bi-check-circle"></i>
                        Approve
                    </button>

                    <button
                        class="ui-btn reject"
                        onclick="previewDecision('rejected')"
                    >
                        <i class="bi bi-x-circle"></i>
                        Reject
                    </button>

                </div>

            </div>

        </div>


        <!-- NOTICE -->

        <div class="card">

            <div class="card-head">
                <strong>Sustainability</strong>
            </div>

            <div class="card-body">

                <div class="notice">

                    <i class="bi bi-arrow-repeat"></i>

                    <p>
                        Sustainable livelihood assistance should aim to
                        empower capable beneficiaries and, where applicable,
                        allow recovered funds to support future projects.
                    </p>

                </div>

            </div>

        </div>

    </aside>

</div>

@endsection


@push('scripts')
<script>

    function previewDecision(action) {

        let message = '';

        if (action === 'recommendation') {

            message =
                'The application would be submitted for committee recommendation once backend workflow functionality is connected.';

        } else if (action === 'approved') {

            message =
                'The application would be approved once backend authorization and approval rules are connected.';

        } else {

            message =
                'The application would be rejected once backend workflow functionality is connected.';

        }

        alert(
            'UI preview only.\n\n' +
            message
        );

    }

</script>
@endpush