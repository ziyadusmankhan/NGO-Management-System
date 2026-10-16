@extends('layouts.app')

@section('title', 'Qard Details')

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
        background:#4c9a6c;
        border-radius:50%;
    }

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

    .person-box {
        display:flex;
        align-items:center;
        gap:11px;
    }

    .avatar {
        width:41px;
        height:41px;
        border-radius:11px;
        background:var(--primary-soft);
        color:var(--primary);
        display:flex;
        align-items:center;
        justify-content:center;
        font-size: 15px;
        font-weight:700;
    }

    .person-name {
        font-size: 15px;
        font-weight:650;
    }

    .person-meta {
        margin-top:3px;
        color: #6c7470;
        font-size: 13px;
    }

    .progress-wrap {
        margin-top:16px;
    }

    .progress-top {
        display:flex;
        justify-content:space-between;
        gap:10px;
        font-size: 14px;
    }

    .progress-top span:first-child {
        color: #6e7772;
    }

    .progress-top span:last-child {
        color:var(--primary);
        font-weight:650;
    }

    .progress {
        height:8px;
        margin-top:7px;
        background:#edf1ee;
        border-radius:20px;
        overflow:hidden;
    }

    .progress span {
        display:block;
        height:100%;
        width:40%;
        background:var(--primary);
        border-radius:20px;
    }

    .principle {
        display:flex;
        gap:10px;
        padding:13px;
        background:#f7f9f8;
        border:1px solid #e5ebe8;
        border-radius:10px;
    }

    .principle i {
        color:var(--primary);
        font-size: 17px;
    }

    .principle p {
        margin:0;
        color: #6b766f;
        font-size: 13px;
        line-height:1.65;
    }

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

    .payment-table-wrap {
        overflow-x:auto;
    }

    .payment-table {
        width:100%;
        min-width:650px;
        border-collapse:collapse;
    }

    .payment-table th {
        padding:10px;
        background:#fafcfb;
        border-bottom:1px solid #eef1ef;
        color: #6c7470;
        font-size: 13px;
        text-transform:uppercase;
        text-align:left;
    }

    .payment-table td {
        padding:11px 10px;
        border-bottom:1px solid #f0f3f1;
        font-size: 14px;
    }

    .payment-table tr:last-child td {
        border-bottom:none;
    }

    .paid {
        color:#397955;
        font-weight:650;
    }

    .pending {
        color: #976d38;
        font-weight:650;
    }

    .overdue {
        color:#a85f58;
        font-weight:650;
    }

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

        .summary-grid {
            grid-template-columns:1fr;
        }
    }
</style>
@endpush


@section('content')

<div class="page-header">

    <div class="identity">

        <div class="icon-box">
            <i class="bi bi-arrow-repeat"></i>
        </div>

        <div>

            <div class="title">
                QRD-00031
            </div>

            <div class="meta">
                Ahmed Khan · Business Setup · Issued 01 May 2026
            </div>

        </div>

    </div>

    <div class="actions">

        <a
            href="{{ route('qard.index') }}"
            class="ui-btn"
        >
            <i class="bi bi-arrow-left"></i>
            Back
        </a>

        <a
            href="{{ route('qard.repayment', ['id' => 1]) }}"
            class="ui-btn primary"
        >
            <i class="bi bi-calendar3"></i>
            Repayment Schedule
        </a>

        <a
            href="{{ route('qard.recovery', ['id' => 1]) }}"
            class="ui-btn success"
        >
            <i class="bi bi-cash-stack"></i>
            Record Payment
        </a>

    </div>

</div>


<div class="layout">

    <div>

        <!-- OVERVIEW -->

        <div class="card">

            <div class="card-head">

                <div>
                    <strong>Qard Overview</strong>

                    <span>
                        Current principal and repayment position.
                    </span>
                </div>

                <span class="status">
                    Active
                </span>

            </div>

            <div class="card-body">

                <div class="summary-grid">

                    <div class="summary-box">
                        <label>Qard ID</label>
                        <strong>QRD-00031</strong>
                    </div>

                    <div class="summary-box">
                        <label>Purpose</label>
                        <strong>Business Setup</strong>
                    </div>

                    <div class="summary-box highlight">
                        <label>Original Principal</label>
                        <strong>PKR 100,000</strong>
                    </div>

                    <div class="summary-box">
                        <label>Recovered</label>
                        <strong>PKR 40,000</strong>
                    </div>

                    <div class="summary-box">
                        <label>Outstanding Principal</label>
                        <strong>PKR 60,000</strong>
                    </div>

                    <div class="summary-box">
                        <label>Interest</label>
                        <strong>PKR 0</strong>
                    </div>

                </div>


                <div class="progress-wrap">

                    <div class="progress-top">

                        <span>
                            Principal Recovery
                        </span>

                        <span>
                            40%
                        </span>

                    </div>

                    <div class="progress">
                        <span></span>
                    </div>

                </div>

            </div>

        </div>


        <!-- BORROWER -->

        <div class="card">

            <div class="card-head">

                <div>
                    <strong>Borrower</strong>

                    <span>
                        Linked beneficiary information.
                    </span>
                </div>

            </div>

            <div class="card-body">

                <div class="person-box">

                    <div class="avatar">
                        AK
                    </div>

                    <div style="flex:1">

                        <div class="person-name">
                            Ahmed Khan
                        </div>

                        <div class="person-meta">
                            BEN-00091 · Kasso · Poor but Employable
                        </div>

                    </div>

                    <a
                        href="{{ route('beneficiaries.show', ['id' => 1]) }}"
                        class="ui-btn"
                    >
                        View Beneficiary
                    </a>

                </div>

            </div>

        </div>


        <!-- TERMS -->

        <div class="card">

            <div class="card-head">

                <div>
                    <strong>Qard Terms</strong>

                    <span>
                        Agreed repayment structure.
                    </span>
                </div>

            </div>

            <div class="card-body">

                <div class="summary-grid">

                    <div class="summary-box">
                        <label>Frequency</label>
                        <strong>Monthly</strong>
                    </div>

                    <div class="summary-box">
                        <label>Installments</label>
                        <strong>10</strong>
                    </div>

                    <div class="summary-box">
                        <label>Installment Amount</label>
                        <strong>PKR 10,000</strong>
                    </div>

                    <div class="summary-box">
                        <label>Start Date</label>
                        <strong>01 Jun 2026</strong>
                    </div>

                    <div class="summary-box">
                        <label>Expected End</label>
                        <strong>01 Mar 2027</strong>
                    </div>

                    <div class="summary-box highlight">
                        <label>Total Repayable</label>
                        <strong>PKR 100,000</strong>
                    </div>

                </div>


                <div style="margin-top:15px;">

                    <div class="principle">

                        <i class="bi bi-shield-check"></i>

                        <p>
                            This Qard is principal-only. The borrower is
                            required to return PKR 100,000 in total.
                            No interest or interest-like charge applies.
                        </p>

                    </div>

                </div>

            </div>

        </div>


        <!-- PAYMENT HISTORY -->

        <div class="card">

            <div class="card-head">

                <div>
                    <strong>Payment History</strong>

                    <span>
                        Principal repayments recorded to date.
                    </span>
                </div>

            </div>

            <div class="card-body">

                <div class="payment-table-wrap">

                    <table class="payment-table">

                        <thead>

                            <tr>
                                <th>Installment</th>
                                <th>Due Date</th>
                                <th>Paid Date</th>
                                <th>Amount</th>
                                <th>Reference</th>
                                <th>Status</th>
                            </tr>

                        </thead>

                        <tbody>

                            <tr>
                                <td>1</td>
                                <td>01 Jun 2026</td>
                                <td>01 Jun 2026</td>
                                <td>PKR 10,000</td>
                                <td>PAY-QRD-001</td>
                                <td class="paid">Paid</td>
                            </tr>

                            <tr>
                                <td>2</td>
                                <td>01 Jul 2026</td>
                                <td>02 Jul 2026</td>
                                <td>PKR 10,000</td>
                                <td>PAY-QRD-019</td>
                                <td class="paid">Paid</td>
                            </tr>

                            <tr>
                                <td>3</td>
                                <td>01 Aug 2026</td>
                                <td>01 Aug 2026</td>
                                <td>PKR 10,000</td>
                                <td>PAY-QRD-032</td>
                                <td class="paid">Paid</td>
                            </tr>

                            <tr>
                                <td>4</td>
                                <td>01 Sep 2026</td>
                                <td>—</td>
                                <td>PKR 10,000</td>
                                <td>—</td>
                                <td class="pending">Pending</td>
                            </tr>

                            <tr>
                                <td>5</td>
                                <td>01 Oct 2026</td>
                                <td>—</td>
                                <td>PKR 10,000</td>
                                <td>—</td>
                                <td class="pending">Upcoming</td>
                            </tr>

                        </tbody>

                    </table>

                </div>

            </div>

        </div>


        <!-- AGREEMENT -->

        <div class="card">

            <div class="card-head">

                <div>
                    <strong>Agreement</strong>

                    <span>
                        Qard agreement and supporting parties.
                    </span>
                </div>

            </div>

            <div class="card-body">

                <div class="summary-grid">

                    <div class="summary-box">
                        <label>Agreement</label>
                        <strong>AGR-QRD-00031</strong>
                    </div>

                    <div class="summary-box">
                        <label>Agreement Date</label>
                        <strong>01 May 2026</strong>
                    </div>

                    <div class="summary-box">
                        <label>Borrower</label>
                        <strong>Ahmed Khan</strong>
                    </div>

                    <div class="summary-box">
                        <label>Witness 1</label>
                        <strong>Naveed Alam</strong>
                    </div>

                    <div class="summary-box">
                        <label>Witness 2</label>
                        <strong>Shahzad Khan</strong>
                    </div>

                    <div class="summary-box">
                        <label>Signature Status</label>
                        <strong>Complete</strong>
                    </div>

                </div>

            </div>

        </div>


        <!-- ACTIVITY -->

        <div class="card">

            <div class="card-head">

                <div>
                    <strong>Qard Activity</strong>

                    <span>
                        Important lifecycle events.
                    </span>
                </div>

            </div>

            <div class="card-body">

                <div class="timeline-item">

                    <div class="timeline-dot"></div>

                    <div>

                        <div class="timeline-title">
                            Principal disbursed
                        </div>

                        <div class="timeline-meta">
                            01 May 2026 · PKR 100,000
                        </div>

                    </div>

                </div>


                <div class="timeline-item">

                    <div class="timeline-dot"></div>

                    <div>

                        <div class="timeline-title">
                            Qard agreement completed
                        </div>

                        <div class="timeline-meta">
                            01 May 2026 · AGR-QRD-00031
                        </div>

                    </div>

                </div>


                <div class="timeline-item">

                    <div class="timeline-dot"></div>

                    <div>

                        <div class="timeline-title">
                            Qard approved
                        </div>

                        <div class="timeline-meta">
                            28 Apr 2026 · Decision-Making Committee
                        </div>

                    </div>

                </div>

            </div>

        </div>


        <!-- DOCUMENTS -->

        <div class="card">

            <div class="card-head">

                <div>
                    <strong>Documents</strong>

                    <span>
                        Qard-related supporting records.
                    </span>
                </div>

            </div>

            <div class="card-body">

                <div class="document-row">

                    <div class="document-icon">
                        <i class="bi bi-file-earmark-text"></i>
                    </div>

                    <div class="document-info">
                        <strong>Qard Agreement</strong>
                        <span>PDF · AGR-QRD-00031</span>
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
                        <strong>Approval Record</strong>
                        <span>PDF · DEC-QRD-00031</span>
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
                    Outstanding Principal
                </div>

                <div style="
                    height:1px;
                    background:#eef1ef;
                    margin:16px 0;
                "></div>

                <div class="side-row">
                    <span>Status</span>
                    <span>Active</span>
                </div>

                <div class="side-row">
                    <span>Original Principal</span>
                    <span>PKR 100K</span>
                </div>

                <div class="side-row">
                    <span>Recovered</span>
                    <span>PKR 40K</span>
                </div>

                <div class="side-row">
                    <span>Next Due</span>
                    <span>01 Sep 2026</span>
                </div>

                <div class="side-row">
                    <span>Fund</span>
                    <span>Qard-e-Hasana Fund</span>
                </div>

            </div>

        </div>


        <div class="card">

            <div class="card-head">
                <strong>Fund Recycling</strong>
            </div>

            <div class="card-body">

                <div class="principle">

                    <i class="bi bi-arrow-repeat"></i>

                    <p>
                        Recovered principal can return to the relevant
                        Qard fund and become available for another
                        approved Qard.
                    </p>

                </div>

            </div>

        </div>

    </aside>

</div>

@endsection