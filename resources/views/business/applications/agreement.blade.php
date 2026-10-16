@extends('layouts.app')

@section('title', 'Business Assistance Agreement')

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
        background:#fff5e9;
        color: #976d38;
        font-size: 13px;
        font-weight:650;
        white-space:nowrap;
    }

    .status::before {
        content:"";
        width:5px;
        height:5px;
        border-radius:50%;
        background:#d19a4d;
    }

    /* Summary */

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

    /* Form */

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

    .help {
        margin-top:5px;
        color: #6d7571;
        font-size: 13px;
        line-height:1.5;
    }

    /* Parties */

    .party-grid {
        display:grid;
        grid-template-columns:repeat(2,minmax(0,1fr));
        gap:10px;
    }

    .party {
        padding:13px;
        border:1px solid #edf1ee;
        background:#fafcfb;
        border-radius:10px;
    }

    .party-role {
        color: #6d7571;
        font-size: 13px;
        text-transform:uppercase;
        letter-spacing:.5px;
    }

    .party-name {
        margin-top:5px;
        font-size: 15px;
        font-weight:650;
    }

    .party-meta {
        margin-top:3px;
        color: #6d7671;
        font-size: 13px;
    }

    /* Conditions */

    .conditions {
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
        border-radius:9px;
        background:#fafcfb;
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

    /* Responsibilities */

    .responsibility {
        display:flex;
        align-items:flex-start;
        gap:10px;
        padding:11px 0;
        border-bottom:1px solid #f0f3f1;
    }

    .responsibility:last-child {
        border-bottom:none;
    }

    .responsibility i {
        width:27px;
        height:27px;
        border-radius:8px;
        background:var(--primary-soft);
        color:var(--primary);
        display:flex;
        align-items:center;
        justify-content:center;
        font-size: 15px;
        flex-shrink:0;
    }

    .responsibility strong {
        display:block;
        font-size: 14px;
    }

    .responsibility span {
        display:block;
        margin-top:3px;
        color: #6d7570;
        font-size: 13px;
        line-height:1.5;
    }

    /* Witnesses */

    .witness-grid {
        display:grid;
        grid-template-columns:repeat(2,minmax(0,1fr));
        gap:17px;
    }

    /* Signatures */

    .signature-grid {
        display:grid;
        grid-template-columns:repeat(3,minmax(0,1fr));
        gap:10px;
    }

    .signature-box {
        padding:14px;
        border:1px solid #edf1ee;
        border-radius:10px;
        background:#fafcfb;
        text-align:center;
    }

    .signature-icon {
        width:35px;
        height:35px;
        margin:0 auto 8px;
        border-radius:10px;
        background:#eef1ef;
        color: #6c7570;
        display:flex;
        align-items:center;
        justify-content:center;
    }

    .signature-box strong {
        display:block;
        font-size: 14px;
    }

    .signature-box span {
        display:block;
        margin-top:3px;
        color: #6c7470;
        font-size: 13px;
    }

    .signed {
        color:#397955 !important;
    }

    /* Document */

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

    /* Workflow */

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
        background:#f1d7a2;
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

    /* Sidebar */

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
        .form-grid,
        .party-grid,
        .witness-grid,
        .signature-grid {
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
            <i class="bi bi-file-earmark-text"></i>
        </div>

        <div>

            <div class="title">
                Business Assistance Agreement
            </div>

            <div class="meta">
                SBA-00041 · Rahim Khan · Kasso General Store
            </div>

        </div>

    </div>

    <div class="actions">

        <a
            href="{{ route('business.applications.approval', ['id' => 1]) }}"
            class="ui-btn"
        >
            <i class="bi bi-arrow-left"></i>
            Back
        </a>

        <a
            href="{{ route('business.applications.disbursement', ['id' => 1]) }}"
            class="ui-btn success"
        >
            <i class="bi bi-cash-stack"></i>
            Proceed to Disbursement
        </a>

    </div>

</div>


<div class="layout">

    <div>

        <!-- AGREEMENT STATUS -->

        <div class="card">

            <div class="card-head">

                <div>
                    <strong>Agreement Status</strong>

                    <span>
                        Approved application awaiting agreement completion.
                    </span>
                </div>

                <span class="status">
                    Draft
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
                        <label>Organization Fund</label>
                        <strong>Small Business Fund</strong>
                    </div>

                    <div class="summary-box">
                        <label>Approval Reference</label>
                        <strong>DEC-SBA-00041</strong>
                    </div>

                    <div class="summary-box">
                        <label>Approval Date</label>
                        <strong>16 Aug 2026</strong>
                    </div>

                    <div class="summary-box">
                        <label>Agreement Reference</label>
                        <strong>AGR-SBA-00041</strong>
                    </div>

                </div>

            </div>

        </div>


        <!-- PARTIES -->

        <div class="card">

            <div class="card-head">

                <div>
                    <strong>Agreement Parties</strong>

                    <span>
                        People and organizational representatives involved in the agreement.
                    </span>
                </div>

            </div>

            <div class="card-body">

                <div class="party-grid">

                    <div class="party">

                        <div class="party-role">
                            Beneficiary / Applicant
                        </div>

                        <div class="party-name">
                            Rahim Khan
                        </div>

                        <div class="party-meta">
                            BEN-00063 · Kasso
                        </div>

                    </div>


                    <div class="party">

                        <div class="party-role">
                            Organization Representative
                        </div>

                        <div class="party-name">
                            General Secretary
                        </div>

                        <div class="party-meta">
                            Authorized representative
                        </div>

                    </div>


                    <div class="party">

                        <div class="party-role">
                            Implementation Representative
                        </div>

                        <div class="party-name">
                            Abid Anwar
                        </div>

                        <div class="party-meta">
                            Implementation Committee
                        </div>

                    </div>


                    <div class="party">

                        <div class="party-role">
                            Fund / Finance Representative
                        </div>

                        <div class="party-name">
                            Treasurer
                        </div>

                        <div class="party-meta">
                            Financial authorization
                        </div>

                    </div>

                </div>

            </div>

        </div>


        <!-- TERMS -->

        <div class="card">

            <div class="card-head">

                <div>
                    <strong>Agreement Terms</strong>

                    <span>
                        Approved terms governing the assistance.
                    </span>
                </div>

            </div>

            <div class="card-body">

                <div class="form-grid">

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
                            Agreement Date
                        </label>

                        <input
                            type="date"
                            class="control"
                            value="2026-08-16"
                        >

                    </div>


                    <div>

                        <label class="label">
                            Business Start Deadline
                        </label>

                        <input
                            type="date"
                            class="control"
                            value="2026-09-15"
                        >

                    </div>


                    <div>

                        <label class="label">
                            Monitoring Period
                        </label>

                        <select class="select">

                            <option>
                                3 months
                            </option>

                            <option selected>
                                6 months
                            </option>

                            <option>
                                12 months
                            </option>

                        </select>

                    </div>


                    <div class="full">

                        <label class="label">
                            Agreement Terms & Conditions
                        </label>

                        <textarea class="control">The organization will provide approved business assistance subject to the agreed conditions. The applicant will use the assistance for the approved business purpose, cooperate with monitoring activities, maintain the business responsibly, and provide information required for organizational review.</textarea>

                    </div>

                </div>

            </div>

        </div>


        <!-- RESPONSIBILITIES -->

        <div class="card">

            <div class="card-head">

                <div>
                    <strong>Responsibilities</strong>

                    <span>
                        Responsibilities of the beneficiary and organization.
                    </span>
                </div>

            </div>

            <div class="card-body">

                <div class="responsibility">

                    <i class="bi bi-person-check"></i>

                    <div>

                        <strong>Applicant — Use assistance for approved purpose</strong>

                        <span>
                            The approved contribution must be used only for
                            the agreed business purpose.
                        </span>

                    </div>

                </div>


                <div class="responsibility">

                    <i class="bi bi-clipboard-check"></i>

                    <div>

                        <strong>Applicant — Cooperate with monitoring</strong>

                        <span>
                            The applicant agrees to reasonable field visits,
                            progress checks and documentation.
                        </span>

                    </div>

                </div>


                <div class="responsibility">

                    <i class="bi bi-receipt"></i>

                    <div>

                        <strong>Organization — Maintain records</strong>

                        <span>
                            Purchases, approvals, documents and financial
                            transactions must remain properly documented.
                        </span>

                    </div>

                </div>


                <div class="responsibility">

                    <i class="bi bi-graph-up"></i>

                    <div>

                        <strong>Applicant — Report material changes</strong>

                        <span>
                            Significant changes affecting the business should
                            be reported to the organization.
                        </span>

                    </div>

                </div>

            </div>

        </div>


        <!-- CONDITIONS -->

        <div class="card">

            <div class="card-head">

                <div>
                    <strong>Pre-Disbursement Conditions</strong>

                    <span>
                        Conditions that must be confirmed before funds/assets are provided.
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
                            Applicant contribution of PKR 20,000 confirmed.
                        </span>

                    </label>


                    <label class="condition">

                        <input
                            type="checkbox"
                            checked
                        >

                        <span>
                            Supplier quotations / prices have been verified.
                        </span>

                    </label>


                    <label class="condition">

                        <input
                            type="checkbox"
                            checked
                        >

                        <span>
                            Required business documents are complete.
                        </span>

                    </label>


                    <label class="condition">

                        <input
                            type="checkbox"
                        >

                        <span>
                            Procurement items have been finalized.
                        </span>

                    </label>


                    <label class="condition">

                        <input
                            type="checkbox"
                        >

                        <span>
                            Applicant and organizational signatures completed.
                        </span>

                    </label>

                </div>

            </div>

        </div>


        <!-- WITNESSES -->

        <div class="card">

            <div class="card-head">

                <div>
                    <strong>Witnesses</strong>

                    <span>
                        Record required witnesses or supporting signatories.
                    </span>
                </div>

            </div>

            <div class="card-body">

                <div class="witness-grid">

                    <div>

                        <label class="label">
                            Witness 1 Name
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
                            Witness 2 Name
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


        <!-- SIGNATURES -->

        <div class="card">

            <div class="card-head">

                <div>
                    <strong>Signatures</strong>

                    <span>
                        Track agreement execution by required parties.
                    </span>
                </div>

            </div>

            <div class="card-body">

                <div class="signature-grid">

                    <div class="signature-box">

                        <div class="signature-icon">
                            <i class="bi bi-person"></i>
                        </div>

                        <strong>Applicant</strong>

                        <span>
                            Pending signature
                        </span>

                    </div>


                    <div class="signature-box">

                        <div class="signature-icon">
                            <i class="bi bi-building"></i>
                        </div>

                        <strong>Organization Representative</strong>

                        <span class="signed">
                            Ready for signing
                        </span>

                    </div>


                    <div class="signature-box">

                        <div class="signature-icon">
                            <i class="bi bi-people"></i>
                        </div>

                        <strong>Witnesses</strong>

                        <span>
                            Pending signatures
                        </span>

                    </div>

                </div>

            </div>

        </div>


        <!-- DOCUMENT -->

        <div class="card">

            <div class="card-head">

                <div>
                    <strong>Agreement Document</strong>

                    <span>
                        Attach the finalized agreement after signing.
                    </span>
                </div>

            </div>

            <div class="card-body">

                <label class="upload">

                    <i class="bi bi-cloud-arrow-up"></i>

                    <strong>
                        Upload signed agreement
                    </strong>

                    <span>
                        PDF, JPG or PNG · Final signed agreement
                    </span>

                    <input
                        type="file"
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
                        Agreement is the stage immediately before disbursement.
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

                        <div class="workflow-circle current">
                            6
                        </div>

                        <strong>Agreement</strong>
                        <span>Current</span>

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

    </div>


    <!-- SIDEBAR -->

    <aside>

        <div class="card">

            <div class="card-body">

                <div class="side-number">
                    PKR 60K
                </div>

                <div class="side-label">
                    Approved Assistance
                </div>

                <div style="
                    height:1px;
                    background:#eef1ef;
                    margin:16px 0;
                "></div>

                <div class="side-row">
                    <span>Status</span>
                    <span>Draft</span>
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
                    <span>Agreement</span>
                    <span>AGR-SBA-00041</span>
                </div>

            </div>

        </div>


        <div class="card">

            <div class="card-head">
                <strong>Pre-Disbursement Control</strong>
            </div>

            <div class="card-body">

                <div class="notice">

                    <i class="bi bi-shield-check"></i>

                    <p>
                        Disbursement should remain blocked until required
                        agreement conditions, signatures and documentation
                        have been completed.
                    </p>

                </div>

            </div>

        </div>

    </aside>

</div>

@endsection


@push('scripts')
<script>

    function previewSaveAgreement() {

        const confirmed =
            confirm(
                'Save this business assistance agreement?'
            );

        if (!confirmed) {
            return;
        }

        alert(
            'UI preview only.\n\n' +
            'The agreement would be saved and become eligible for the disbursement stage once backend functionality is connected.'
        );

    }

</script>
@endpush