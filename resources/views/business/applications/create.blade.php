@extends('layouts.app')

@section('title', 'New Small Business Application')

@push('styles')
<style>
    .page-header {
        display: flex;
        justify-content: space-between;
        align-items: flex-start;
        gap: 20px;
        margin-bottom: 24px;
    }

    .page-header h1 {
        margin: 0;
        font-size: 25px;
        font-weight: 700;
        letter-spacing: -.5px;
    }

    .page-header p {
        margin: 7px 0 0;
        color: var(--muted);
        font-size: 16px;
    }

    .actions {
        display: flex;
        gap: 8px;
    }

    .ui-btn {
        height: 40px;
        padding: 0 14px;
        border-radius: 10px;
        display: inline-flex;
        align-items: center;
        justify-content: center;
        gap: 7px;
        font-size: 15px;
        font-weight: 650;
        border: 1px solid var(--border);
        background: #fff;
        color: #637069;
        text-decoration: none;
        cursor: pointer;
    }

    .ui-btn.primary {
        background: var(--primary);
        border-color: var(--primary);
        color: #fff;
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
        padding: 17px 20px;
        border-bottom: 1px solid #eef1ef;
    }

    .card-head strong {
        font-size: 16px;
    }

    .card-head span {
        display: block;
        margin-top: 4px;
        color: #6c7470;
        font-size: 14px;
        line-height: 1.5;
    }

    .card-body {
        padding: 20px;
    }

    .grid {
        display: grid;
        grid-template-columns: repeat(2, minmax(0, 1fr));
        gap: 17px;
    }

    .full {
        grid-column: 1 / -1;
    }

    .label {
        display: block;
        margin-bottom: 7px;
        color: #36433d;
        font-size: 15px;
        font-weight: 650;
    }

    .required {
        color: #b35b4b;
    }

    .control,
    .select {
        width: 100%;
        height: 42px;
        border: 1px solid var(--border);
        border-radius: 9px;
        padding: 0 12px;
        background: #fff;
        color: var(--text);
        font-size: 15px;
        outline: none;
    }

    textarea.control {
        height: auto;
        min-height: 105px;
        padding: 11px 12px;
        resize: vertical;
        line-height: 1.6;
    }

    .control:focus,
    .select:focus {
        border-color: #6b7670;
        box-shadow: 0 0 0 3px rgba(23,63,53,.055);
    }

    .help {
        margin-top: 5px;
        color: #6d7571;
        font-size: 13px;
        line-height: 1.5;
    }

    /* BUSINESS TYPE */

    .business-types {
        display: grid;
        grid-template-columns: repeat(3, minmax(0, 1fr));
        gap: 9px;
    }

    .type-option {
        position: relative;
    }

    .type-option input {
        position: absolute;
        opacity: 0;
        pointer-events: none;
    }

    .type-option label {
        min-height: 70px;
        padding: 10px;
        border: 1px solid var(--border);
        border-radius: 10px;
        display: flex;
        flex-direction: column;
        align-items: center;
        justify-content: center;
        text-align: center;
        gap: 6px;
        cursor: pointer;
        transition: .15s;
    }

    .type-option label i {
        font-size: 18px;
    }

    .type-option label strong {
        font-size: 14px;
    }

    .type-option label span {
        color: #6c7470;
        font-size: 12px;
    }

    .type-option input:checked + label {
        background: var(--primary-soft);
        border-color: #6b7570;
        color: var(--primary);
    }

    /* MONEY */

    .money-prefix {
        position: relative;
    }

    .money-prefix span {
        position: absolute;
        left: 12px;
        top: 50%;
        transform: translateY(-50%);
        color: #6d7772;
        font-size: 15px;
        pointer-events: none;
    }

    .money-prefix .control {
        padding-left: 42px;
    }

    /* CALCULATION */

    .finance-summary {
        display: grid;
        grid-template-columns: repeat(3, minmax(0, 1fr));
        gap: 10px;
        margin-top: 18px;
    }

    .finance-box {
        padding: 13px;
        border: 1px solid #edf1ee;
        border-radius: 10px;
        background: #fafcfb;
    }

    .finance-box label {
        display: block;
        color: #6d7571;
        font-size: 13px;
        text-transform: uppercase;
        letter-spacing: .5px;
    }

    .finance-box strong {
        display: block;
        margin-top: 6px;
        font-size: 16px;
    }

    .finance-box.highlight {
        background: var(--primary-soft);
        border-color: #6f7673;
    }

    .finance-box.highlight strong {
        color: var(--primary);
    }

    /* RISK */

    .risk-options {
        display: flex;
        flex-wrap: wrap;
        gap: 8px;
    }

    .risk-option {
        position: relative;
    }

    .risk-option input {
        position: absolute;
        opacity: 0;
        pointer-events: none;
    }

    .risk-option label {
        height: 34px;
        padding: 0 11px;
        border: 1px solid var(--border);
        border-radius: 30px;
        display: inline-flex;
        align-items: center;
        gap: 6px;
        color: #6b766f;
        font-size: 14px;
        cursor: pointer;
    }

    .risk-option label::before {
        content: "";
        width: 6px;
        height: 6px;
        border-radius: 50%;
        background: #b1bbb6;
    }

    .risk-option input:checked + label {
        background: var(--primary-soft);
        border-color: #6b7570;
        color: var(--primary);
    }

    .risk-option input:checked + label::before {
        background: var(--primary);
    }

    /* DOCUMENTS */

    .upload {
        border: 1px dashed #cbd6d0;
        border-radius: 11px;
        padding: 23px;
        text-align: center;
        background: #fbfcfb;
        cursor: pointer;
    }

    .upload i {
        font-size: 24px;
        color: var(--primary);
    }

    .upload strong {
        display: block;
        margin-top: 8px;
        font-size: 15px;
    }

    .upload span {
        display: block;
        margin-top: 4px;
        color: #6c7470;
        font-size: 13px;
    }

    /* PREVIEW */

    .preview {
        background: linear-gradient(145deg, #f8faf9, #f2f6f4);
        border: 1px solid var(--border);
        border-radius: 14px;
        padding: 20px;
        margin-bottom: 18px;
    }

    .preview-label {
        color: #6d7672;
        font-size: 14px;
        text-transform: uppercase;
        letter-spacing: .8px;
    }

    .preview-icon {
        width: 60px;
        height: 60px;
        margin: 15px 0 11px;
        border-radius: 17px;
        background: var(--primary);
        color: #fff;
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 20px;
    }

    .preview-name {
        font-size: 16px;
        font-weight: 700;
    }

    .preview-sub {
        margin-top: 4px;
        color: #6c7570;
        font-size: 13px;
    }

    .preview-divider {
        height: 1px;
        background: #dde5e1;
        margin: 16px 0;
    }

    .preview-row {
        display: flex;
        justify-content: space-between;
        gap: 12px;
        padding: 7px 0;
        font-size: 14px;
    }

    .preview-row span:first-child {
        color: #6e7672;
    }

    .preview-row span:last-child {
        color: #4a5750;
        font-weight: 650;
        text-align: right;
    }

    /* WORKFLOW */

    .workflow {
        display: flex;
        flex-direction: column;
        gap: 12px;
    }

    .workflow-step {
        display: flex;
        gap: 9px;
        align-items: flex-start;
    }

    .workflow-icon {
        width: 24px;
        height: 24px;
        border-radius: 8px;
        background: var(--primary-soft);
        color: var(--primary);
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 14px;
        flex-shrink: 0;
    }

    .workflow-text strong {
        display: block;
        font-size: 14px;
    }

    .workflow-text span {
        display: block;
        margin-top: 3px;
        color: #6c7470;
        font-size: 12px;
        line-height: 1.45;
    }

    /* NOTICE */

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
        }

        .grid,
        .business-types {
            grid-template-columns: 1fr;
        }

        .full {
            grid-column: auto;
        }

        .finance-summary {
            grid-template-columns: 1fr;
        }
    }
</style>
@endpush


@section('content')

<div class="page-header">

    <div>
        <h1>New Small Business Application</h1>

        <p>
            Create a livelihood assistance application for a deserving and capable applicant.
        </p>
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
            type="button"
            class="ui-btn primary"
            onclick="previewSubmitBusiness()"
        >
            <i class="bi bi-check-lg"></i>
            Submit Application
        </button>

    </div>

</div>


<div class="layout">

    <div>

        <!-- =====================================
             APPLICANT
        ====================================== -->

        <div class="card">

            <div class="card-head">

                <strong>Applicant Information</strong>

                <span>
                    Link the business application to an existing beneficiary/member.
                </span>

            </div>

            <div class="card-body">

                <div class="grid">

                    <div>

                        <label class="label">
                            Applicant <span class="required">*</span>
                        </label>

                        <select
                            id="applicant"
                            class="select"
                        >

                            <option value="">
                                Select applicant
                            </option>

                            <option value="rahim">
                                Rahim Khan — BEN-00063
                            </option>

                            <option value="noor">
                                Noor Alam — BEN-00071
                            </option>

                            <option value="imad">
                                Imad Hussain — BEN-00078
                            </option>

                            <option value="samad">
                                Abdul Samad — BEN-00069
                            </option>

                        </select>

                    </div>


                    <div>

                        <label class="label">
                            Application ID
                        </label>

                        <input
                            class="control"
                            value="SBA-00042"
                            readonly
                        >

                    </div>

                </div>

            </div>

        </div>


        <!-- =====================================
             BUSINESS
        ====================================== -->

        <div class="card">

            <div class="card-head">

                <strong>Proposed Business</strong>

                <span>
                    Information about the proposed sustainable livelihood activity.
                </span>

            </div>

            <div class="card-body">

                <div class="grid">

                    <div>

                        <label class="label">
                            Business Name <span class="required">*</span>
                        </label>

                        <input
                            id="businessName"
                            class="control"
                            placeholder="e.g. Kasso General Store"
                        >

                    </div>


                    <div>

                        <label class="label">
                            Business Location
                        </label>

                        <input
                            class="control"
                            placeholder="Village / market / area"
                        >

                    </div>


                    <div class="full">

                        <label class="label">
                            Business Type
                        </label>

                        <div class="business-types">

                            <div class="type-option">

                                <input
                                    type="radio"
                                    name="businessType"
                                    id="typeRetail"
                                    checked
                                >

                                <label for="typeRetail">

                                    <i class="bi bi-shop"></i>

                                    <strong>
                                        Retail
                                    </strong>

                                    <span>
                                        Shop / general store
                                    </span>

                                </label>

                            </div>


                            <div class="type-option">

                                <input
                                    type="radio"
                                    name="businessType"
                                    id="typeServices"
                                >

                                <label for="typeServices">

                                    <i class="bi bi-tools"></i>

                                    <strong>
                                        Services
                                    </strong>

                                    <span>
                                        Repair / technical / service
                                    </span>

                                </label>

                            </div>


                            <div class="type-option">

                                <input
                                    type="radio"
                                    name="businessType"
                                    id="typeAgriculture"
                                >

                                <label for="typeAgriculture">

                                    <i class="bi bi-tree"></i>

                                    <strong>
                                        Agriculture
                                    </strong>

                                    <span>
                                        Farming / crops
                                    </span>

                                </label>

                            </div>


                            <div class="type-option">

                                <input
                                    type="radio"
                                    name="businessType"
                                    id="typeLivestock"
                                >

                                <label for="typeLivestock">

                                    <i class="bi bi-bug"></i>

                                    <strong>
                                        Livestock
                                    </strong>

                                    <span>
                                        Livestock / animal unit
                                    </span>

                                </label>

                            </div>


                            <div class="type-option">

                                <input
                                    type="radio"
                                    name="businessType"
                                    id="typeCraft"
                                >

                                <label for="typeCraft">

                                    <i class="bi bi-scissors"></i>

                                    <strong>
                                        Craft / Home Business
                                    </strong>

                                    <span>
                                        Tailoring / home production
                                    </span>

                                </label>

                            </div>

                        </div>

                    </div>


                    <div class="full">

                        <label class="label">
                            Business Description <span class="required">*</span>
                        </label>

                        <textarea
                            class="control"
                            placeholder="Describe the proposed business, products/services, target customers and operating model..."
                        ></textarea>

                    </div>


                    <div class="full">

                        <label class="label">
                            Applicant Experience
                        </label>

                        <textarea
                            class="control"
                            placeholder="Describe relevant skills, experience and previous business activity..."
                        ></textarea>

                    </div>

                </div>

            </div>

        </div>


        <!-- =====================================
             INVESTMENT
        ====================================== -->

        <div class="card">

            <div class="card-head">

                <strong>Investment & Funding</strong>

                <span>
                    Define total investment, applicant contribution and requested support.
                </span>

            </div>

            <div class="card-body">

                <div class="grid">

                    <div>

                        <label class="label">
                            Total Investment
                        </label>

                        <div class="money-prefix">

                            <span>PKR</span>

                            <input
                                id="totalInvestment"
                                type="number"
                                class="control"
                                placeholder="0"
                            >

                        </div>

                    </div>


                    <div>

                        <label class="label">
                            Applicant Contribution
                        </label>

                        <div class="money-prefix">

                            <span>PKR</span>

                            <input
                                id="applicantContribution"
                                type="number"
                                class="control"
                                placeholder="0"
                            >

                        </div>

                    </div>


                    <div>

                        <label class="label">
                            Requested Organization Contribution
                        </label>

                        <div class="money-prefix">

                            <span>PKR</span>

                            <input
                                id="organizationContribution"
                                type="number"
                                class="control"
                                placeholder="0"
                            >

                        </div>

                    </div>


                    <div>

                        <label class="label">
                            Funding Source
                        </label>

                        <select class="select">

                            <option>
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

                </div>


                <div class="finance-summary">

                    <div class="finance-box">

                        <label>
                            Total Investment
                        </label>

                        <strong id="summaryInvestment">
                            PKR 0
                        </strong>

                    </div>


                    <div class="finance-box">

                        <label>
                            Applicant Contribution
                        </label>

                        <strong id="summaryApplicant">
                            PKR 0
                        </strong>

                    </div>


                    <div class="finance-box highlight">

                        <label>
                            Organization Contribution
                        </label>

                        <strong id="summaryOrganization">
                            PKR 0
                        </strong>

                    </div>

                </div>

            </div>

        </div>


        <!-- =====================================
             BUSINESS PROJECTION
        ====================================== -->

        <div class="card">

            <div class="card-head">

                <strong>Business Projection</strong>

                <span>
                    Expected business performance used during viability assessment.
                </span>

            </div>

            <div class="card-body">

                <div class="grid">

                    <div>

                        <label class="label">
                            Expected Monthly Sales
                        </label>

                        <div class="money-prefix">

                            <span>PKR</span>

                            <input
                                type="number"
                                class="control"
                                placeholder="0"
                            >

                        </div>

                    </div>


                    <div>

                        <label class="label">
                            Expected Monthly Expenses
                        </label>

                        <div class="money-prefix">

                            <span>PKR</span>

                            <input
                                type="number"
                                class="control"
                                placeholder="0"
                            >

                        </div>

                    </div>


                    <div>

                        <label class="label">
                            Expected Monthly Net Income
                        </label>

                        <div class="money-prefix">

                            <span>PKR</span>

                            <input
                                type="number"
                                class="control"
                                placeholder="0"
                            >

                        </div>

                    </div>


                    <div>

                        <label class="label">
                            Expected Break-even Period
                        </label>

                        <select class="select">

                            <option>
                                Less than 6 months
                            </option>

                            <option>
                                6–12 months
                            </option>

                            <option>
                                12–24 months
                            </option>

                            <option>
                                More than 24 months
                            </option>

                        </select>

                    </div>


                    <div class="full">

                        <label class="label">
                            Sustainability Plan
                        </label>

                        <textarea
                            class="control"
                            placeholder="Explain how the business is expected to become self-sustaining..."
                        ></textarea>

                    </div>

                </div>

            </div>

        </div>


        <!-- =====================================
             RISKS
        ====================================== -->

        <div class="card">

            <div class="card-head">

                <strong>Risks & Assessment Notes</strong>

                <span>
                    Preliminary risk information before formal viability assessment.
                </span>

            </div>

            <div class="card-body">

                <div>

                    <label class="label">
                        Preliminary Risk Level
                    </label>

                    <div class="risk-options">

                        <div class="risk-option">

                            <input
                                type="radio"
                                name="risk"
                                id="riskLow"
                            >

                            <label for="riskLow">
                                Low
                            </label>

                        </div>

                        <div class="risk-option">

                            <input
                                type="radio"
                                name="risk"
                                id="riskMedium"
                                checked
                            >

                            <label for="riskMedium">
                                Medium
                            </label>

                        </div>

                        <div class="risk-option">

                            <input
                                type="radio"
                                name="risk"
                                id="riskHigh"
                            >

                            <label for="riskHigh">
                                High
                            </label>

                        </div>

                    </div>

                </div>


                <div style="margin-top:18px;">

                    <label class="label">
                        Risks / Challenges
                    </label>

                    <textarea
                        class="control"
                        placeholder="Describe market, location, experience, supply, repayment or other identified risks..."
                    ></textarea>

                </div>


                <div style="margin-top:17px;">

                    <label class="label">
                        Proposed Use of Funds
                    </label>

                    <textarea
                        class="control"
                        placeholder="List the items/assets the organization would finance..."
                    ></textarea>

                </div>

            </div>

        </div>


        <!-- =====================================
             DOCUMENTS
        ====================================== -->

        <div class="card">

            <div class="card-head">

                <strong>Supporting Documents</strong>

                <span>
                    Business plan, quotations, estimates, photos and other supporting records.
                </span>

            </div>

            <div class="card-body">

                <label class="upload">

                    <i class="bi bi-cloud-arrow-up"></i>

                    <strong>
                        Upload supporting documents
                    </strong>

                    <span>
                        PDF, JPG, PNG · Business plans, quotations, estimates and photos
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

        <div class="preview">

            <div class="preview-label">
                Application Preview
            </div>

            <div class="preview-icon">
                <i class="bi bi-shop"></i>
            </div>

            <div
                class="preview-name"
                id="previewBusiness"
            >
                New Business
            </div>

            <div class="preview-sub">
                SBA-00042
            </div>

            <div class="preview-divider"></div>

            <div class="preview-row">

                <span>Applicant</span>

                <span id="previewApplicant">
                    Not selected
                </span>

            </div>

            <div class="preview-row">

                <span>Type</span>

                <span id="previewType">
                    Retail
                </span>

            </div>

            <div class="preview-row">

                <span>Requested</span>

                <span id="previewRequested">
                    PKR 0
                </span>

            </div>

            <div class="preview-row">

                <span>Risk</span>

                <span>
                    Medium
                </span>

            </div>

            <div class="preview-row">

                <span>Stage</span>

                <span>
                    Draft
                </span>

            </div>

        </div>


        <!-- WORKFLOW -->

        <div class="card">

            <div class="card-head">

                <strong>
                    Application Workflow
                </strong>

                <span>
                    Future approval path
                </span>

            </div>

            <div class="card-body">

                <div class="workflow">

                    <div class="workflow-step">

                        <div class="workflow-icon">
                            1
                        </div>

                        <div class="workflow-text">

                            <strong>
                                Application
                            </strong>

                            <span>
                                Applicant submits business proposal.
                            </span>

                        </div>

                    </div>


                    <div class="workflow-step">

                        <div class="workflow-icon">
                            2
                        </div>

                        <div class="workflow-text">

                            <strong>
                                Field Verification
                            </strong>

                            <span>
                                Applicant and business circumstances are verified.
                            </span>

                        </div>

                    </div>


                    <div class="workflow-step">

                        <div class="workflow-icon">
                            3
                        </div>

                        <div class="workflow-text">

                            <strong>
                                Viability Review
                            </strong>

                            <span>
                                Business plan, costs, risks and sustainability assessed.
                            </span>

                        </div>

                    </div>


                    <div class="workflow-step">

                        <div class="workflow-icon">
                            4
                        </div>

                        <div class="workflow-text">

                            <strong>
                                Approval
                            </strong>

                            <span>
                                Appropriate committee makes the decision.
                            </span>

                        </div>

                    </div>


                    <div class="workflow-step">

                        <div class="workflow-icon">
                            5
                        </div>

                        <div class="workflow-text">

                            <strong>
                                Agreement & Disbursement
                            </strong>

                            <span>
                                Approved assets or funds are provided.
                            </span>

                        </div>

                    </div>


                    <div class="workflow-step">

                        <div class="workflow-icon">
                            6
                        </div>

                        <div class="workflow-text">

                            <strong>
                                Monitoring
                            </strong>

                            <span>
                                Business progress and sustainability are reviewed.
                            </span>

                        </div>

                    </div>

                </div>

            </div>

        </div>


        <div class="card">

            <div class="card-head">
                <strong>
                    Sustainability Principle
                </strong>
            </div>

            <div class="card-body">

                <div class="notice">

                    <i class="bi bi-arrow-repeat"></i>

                    <p>
                        Where applicable, recovered funds from sustainable
                        projects should be reused to help additional deserving
                        people rather than treated as ordinary income.
                    </p>

                </div>

            </div>

        </div>

    </aside>

</div>

@endsection


@push('scripts')
<script>

    const applicant =
        document.getElementById('applicant');

    const businessName =
        document.getElementById('businessName');

    const totalInvestment =
        document.getElementById('totalInvestment');

    const applicantContribution =
        document.getElementById('applicantContribution');

    const organizationContribution =
        document.getElementById('organizationContribution');


    function money(value) {

        return 'PKR ' +
            Number(value || 0)
                .toLocaleString('en-PK');

    }


    function updateFinancePreview() {

        const total =
            Number(totalInvestment.value || 0);

        const applicantValue =
            Number(applicantContribution.value || 0);

        const organizationValue =
            Number(organizationContribution.value || 0);


        document.getElementById('summaryInvestment')
            .textContent = money(total);

        document.getElementById('summaryApplicant')
            .textContent = money(applicantValue);

        document.getElementById('summaryOrganization')
            .textContent = money(organizationValue);

        document.getElementById('previewRequested')
            .textContent = money(organizationValue);

    }


    applicant.addEventListener('change', function () {

        const text =
            this.options[this.selectedIndex]?.text || '';

        document.getElementById('previewApplicant')
            .textContent =
                text || 'Not selected';

    });


    businessName.addEventListener('input', function () {

        document.getElementById('previewBusiness')
            .textContent =
                this.value.trim() || 'New Business';

    });


    [
        totalInvestment,
        applicantContribution,
        organizationContribution
    ].forEach(input => {

        input.addEventListener(
            'input',
            updateFinancePreview
        );

    });


    document
        .querySelectorAll('input[name="businessType"]')
        .forEach(input => {

            input.addEventListener('change', function () {

                const label =
                    document.querySelector(
                        `label[for="${this.id}"] strong`
                    );

                document.getElementById('previewType')
                    .textContent =
                        label
                            ? label.textContent
                            : '';

            });

        });


    function previewSubmitBusiness() {

        if (!applicant.value) {

            alert(
                'Please select an applicant.'
            );

            applicant.focus();

            return;

        }


        if (!businessName.value.trim()) {

            alert(
                'Please enter the business name.'
            );

            businessName.focus();

            return;

        }


        alert(
            'UI preview only.\n\n' +
            'The Small Business Application is ready to be submitted ' +
            'once backend functionality is connected.'
        );

    }

</script>
@endpush