@extends('layouts.app')

@section('title', 'Repayment Schedule')

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
    }

    .summary-box strong {
        display:block;
        margin-top:5px;
        font-size: 16px;
    }

    .summary-box.highlight strong {
        color:var(--primary);
    }

    .table-wrap {
        overflow-x:auto;
    }

    .repayment-table {
        width:100%;
        min-width:850px;
        border-collapse:collapse;
    }

    .repayment-table th {
        padding:11px 12px;
        background:#fafcfb;
        border-bottom:1px solid #eef1ef;
        color: #6c7470;
        font-size: 13px;
        text-transform:uppercase;
        letter-spacing:.5px;
        text-align:left;
        white-space:nowrap;
    }

    .repayment-table td {
        padding:12px;
        border-bottom:1px solid #f0f3f1;
        font-size: 14px;
        vertical-align:middle;
    }

    .repayment-table tr:last-child td {
        border-bottom:none;
    }

    .installment-number {
        width:27px;
        height:27px;
        border-radius:8px;
        background:var(--primary-soft);
        color:var(--primary);
        display:inline-flex;
        align-items:center;
        justify-content:center;
        font-size: 13px;
        font-weight:700;
    }

    .status {
        display:inline-flex;
        align-items:center;
        gap:5px;
        padding:5px 8px;
        border-radius:20px;
        font-size: 13px;
        font-weight:650;
        white-space:nowrap;
    }

    .status::before {
        content:"";
        width:5px;
        height:5px;
        border-radius:50%;
    }

    .status.paid {
        background:#edf7f0;
        color:#397955;
    }

    .status.paid::before {
        background:#4c9a6c;
    }

    .status.pending {
        background:#fff5e9;
        color: #976d38;
    }

    .status.pending::before {
        background:#d19a4d;
    }

    .status.overdue {
        background:#fff0ef;
        color:#a85f58;
    }

    .status.overdue::before {
        background:#b96b63;
    }

    .status.upcoming {
        background:#eef3f8;
        color:#55758d;
    }

    .status.upcoming::before {
        background:#6d8ea5;
    }

    .amount {
        font-weight:650;
    }

    .paid-amount {
        color:#397955;
    }

    .remaining {
        color: #976d38;
        font-weight:650;
    }

    .progress-wrap {
        width:115px;
    }

    .progress {
        height:6px;
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

    .progress-label {
        margin-top:4px;
        color: #6e7772;
        font-size: 12px;
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
            grid-template-columns:repeat(2,1fr);
        }
    }

    @media(max-width:450px) {
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
            <i class="bi bi-calendar3"></i>
        </div>

        <div>

            <div class="title">
                Repayment Schedule
            </div>

            <div class="meta">
                QRD-00031 · Ahmed Khan · Monthly Principal Repayment
            </div>

        </div>

    </div>

    <div class="actions">

        <a
            href="{{ route('qard.show', ['id' => 1]) }}"
            class="ui-btn"
        >
            <i class="bi bi-arrow-left"></i>
            Back
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

        <!-- SUMMARY -->

        <div class="card">

            <div class="card-head">

                <div>
                    <strong>Repayment Summary</strong>

                    <span>
                        Principal-only repayment schedule.
                    </span>
                </div>

            </div>

            <div class="card-body">

                <div class="summary-grid">

                    <div class="summary-box highlight">
                        <label>Total Principal</label>
                        <strong>PKR 100,000</strong>
                    </div>

                    <div class="summary-box">
                        <label>Recovered</label>
                        <strong>PKR 40,000</strong>
                    </div>

                    <div class="summary-box">
                        <label>Outstanding</label>
                        <strong>PKR 60,000</strong>
                    </div>

                    <div class="summary-box">
                        <label>Interest</label>
                        <strong>PKR 0</strong>
                    </div>

                    <div class="summary-box">
                        <label>Installment</label>
                        <strong>PKR 10,000</strong>
                    </div>

                    <div class="summary-box">
                        <label>Frequency</label>
                        <strong>Monthly</strong>
                    </div>

                    <div class="summary-box">
                        <label>Paid Installments</label>
                        <strong>3 / 10</strong>
                    </div>

                    <div class="summary-box">
                        <label>Next Due</label>
                        <strong>01 Sep 2026</strong>
                    </div>

                </div>

            </div>

        </div>


        <!-- PRINCIPAL NOTICE -->

        <div class="card">

            <div class="card-body">

                <div class="principle">

                    <i class="bi bi-shield-check"></i>

                    <p>
                        This schedule represents principal repayment only.
                        Total scheduled repayment is exactly PKR 100,000.
                        No interest, service charge or interest-like amount
                        is included.
                    </p>

                </div>

            </div>

        </div>


        <!-- SCHEDULE -->

        <div class="card">

            <div class="card-head">

                <div>
                    <strong>Installment Schedule</strong>

                    <span>
                        Monthly repayment schedule for QRD-00031.
                    </span>
                </div>

                <button
                    class="ui-btn"
                    onclick="window.print()"
                >
                    <i class="bi bi-printer"></i>
                    Print
                </button>

            </div>

            <div class="card-body" style="padding:0;">

                <div class="table-wrap">

                    <table class="repayment-table">

                        <thead>

                            <tr>
                                <th>#</th>
                                <th>Due Date</th>
                                <th>Principal Due</th>
                                <th>Paid</th>
                                <th>Remaining</th>
                                <th>Status</th>
                                <th>Payment Reference</th>
                            </tr>

                        </thead>

                        <tbody>

                            <tr>

                                <td>
                                    <span class="installment-number">1</span>
                                </td>

                                <td>
                                    01 Jun 2026
                                </td>

                                <td class="amount">
                                    PKR 10,000
                                </td>

                                <td class="paid-amount">
                                    PKR 10,000
                                </td>

                                <td>
                                    PKR 90,000
                                </td>

                                <td>
                                    <span class="status paid">
                                        Paid
                                    </span>
                                </td>

                                <td>
                                    PAY-QRD-001
                                </td>

                            </tr>


                            <tr>

                                <td>
                                    <span class="installment-number">2</span>
                                </td>

                                <td>
                                    01 Jul 2026
                                </td>

                                <td class="amount">
                                    PKR 10,000
                                </td>

                                <td class="paid-amount">
                                    PKR 10,000
                                </td>

                                <td>
                                    PKR 80,000
                                </td>

                                <td>
                                    <span class="status paid">
                                        Paid
                                    </span>
                                </td>

                                <td>
                                    PAY-QRD-019
                                </td>

                            </tr>


                            <tr>

                                <td>
                                    <span class="installment-number">3</span>
                                </td>

                                <td>
                                    01 Aug 2026
                                </td>

                                <td class="amount">
                                    PKR 10,000
                                </td>

                                <td class="paid-amount">
                                    PKR 10,000
                                </td>

                                <td>
                                    PKR 70,000
                                </td>

                                <td>
                                    <span class="status paid">
                                        Paid
                                    </span>
                                </td>

                                <td>
                                    PAY-QRD-032
                                </td>

                            </tr>


                            <tr>

                                <td>
                                    <span class="installment-number">4</span>
                                </td>

                                <td>
                                    01 Sep 2026
                                </td>

                                <td class="amount">
                                    PKR 10,000
                                </td>

                                <td>
                                    PKR 0
                                </td>

                                <td class="remaining">
                                    PKR 60,000
                                </td>

                                <td>
                                    <span class="status pending">
                                        Pending
                                    </span>
                                </td>

                                <td>
                                    —
                                </td>

                            </tr>


                            <tr>

                                <td>
                                    <span class="installment-number">5</span>
                                </td>

                                <td>
                                    01 Oct 2026
                                </td>

                                <td class="amount">
                                    PKR 10,000
                                </td>

                                <td>
                                    PKR 0
                                </td>

                                <td class="remaining">
                                    PKR 50,000
                                </td>

                                <td>
                                    <span class="status upcoming">
                                        Upcoming
                                    </span>
                                </td>

                                <td>
                                    —
                                </td>

                            </tr>


                            <tr>

                                <td>
                                    <span class="installment-number">6</span>
                                </td>

                                <td>
                                    01 Nov 2026
                                </td>

                                <td class="amount">
                                    PKR 10,000
                                </td>

                                <td>
                                    PKR 0
                                </td>

                                <td class="remaining">
                                    PKR 40,000
                                </td>

                                <td>
                                    <span class="status upcoming">
                                        Upcoming
                                    </span>
                                </td>

                                <td>
                                    —
                                </td>

                            </tr>


                            <tr>

                                <td>
                                    <span class="installment-number">7</span>
                                </td>

                                <td>
                                    01 Dec 2026
                                </td>

                                <td class="amount">
                                    PKR 10,000
                                </td>

                                <td>
                                    PKR 0
                                </td>

                                <td class="remaining">
                                    PKR 30,000
                                </td>

                                <td>
                                    <span class="status upcoming">
                                        Upcoming
                                    </span>
                                </td>

                                <td>
                                    —
                                </td>

                            </tr>


                            <tr>

                                <td>
                                    <span class="installment-number">8</span>
                                </td>

                                <td>
                                    01 Jan 2027
                                </td>

                                <td class="amount">
                                    PKR 10,000
                                </td>

                                <td>
                                    PKR 0
                                </td>

                                <td class="remaining">
                                    PKR 20,000
                                </td>

                                <td>
                                    <span class="status upcoming">
                                        Upcoming
                                    </span>
                                </td>

                                <td>
                                    —
                                </td>

                            </tr>


                            <tr>

                                <td>
                                    <span class="installment-number">9</span>
                                </td>

                                <td>
                                    01 Feb 2027
                                </td>

                                <td class="amount">
                                    PKR 10,000
                                </td>

                                <td>
                                    PKR 0
                                </td>

                                <td class="remaining">
                                    PKR 10,000
                                </td>

                                <td>
                                    <span class="status upcoming">
                                        Upcoming
                                    </span>
                                </td>

                                <td>
                                    —
                                </td>

                            </tr>


                            <tr>

                                <td>
                                    <span class="installment-number">10</span>
                                </td>

                                <td>
                                    01 Mar 2027
                                </td>

                                <td class="amount">
                                    PKR 10,000
                                </td>

                                <td>
                                    PKR 0
                                </td>

                                <td class="remaining">
                                    PKR 0
                                </td>

                                <td>
                                    <span class="status upcoming">
                                        Upcoming
                                    </span>
                                </td>

                                <td>
                                    —
                                </td>

                            </tr>

                        </tbody>

                    </table>

                </div>

            </div>

        </div>


        <!-- PROGRESS -->

        <div class="card">

            <div class="card-head">

                <div>
                    <strong>Repayment Progress</strong>

                    <span>
                        Recovery of the original principal.
                    </span>
                </div>

            </div>

            <div class="card-body">

                <div class="progress-top">

                    <span>
                        PKR 40,000 recovered
                    </span>

                    <span>
                        40%
                    </span>

                </div>

                <div class="progress">

                    <span style="width:40%"></span>

                </div>

                <div style="
                    margin-top:9px;
                    color: #6e7772;
                    font-size: 13px;
                ">
                    3 of 10 scheduled installments have been completed.
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
                    <span>Original</span>
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
                    <span>Installment</span>
                    <span>PKR 10K</span>
                </div>

                <div class="side-row">
                    <span>Interest</span>
                    <span>None</span>
                </div>

            </div>

        </div>


        <div class="card">

            <div class="card-head">
                <strong>Fund Recovery</strong>
            </div>

            <div class="card-body">

                <div class="principle">

                    <i class="bi bi-arrow-repeat"></i>

                    <p>
                        Each principal repayment increases the amount
                        available for future Qard assistance from the
                        relevant fund.
                    </p>

                </div>

            </div>

        </div>

    </aside>

</div>

@endsection