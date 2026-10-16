@extends('layouts.app')

@section('title', 'Qard Fund Recycling')

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
    }

    .status::before {
        content:"";
        width:5px;
        height:5px;
        border-radius:50%;
        background:#d19a4d;
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

    .flow {
        display:flex;
        align-items:center;
        gap:0;
        overflow-x:auto;
        padding:5px 0 8px;
    }

    .flow-step {
        min-width:135px;
        text-align:center;
    }

    .flow-icon {
        width:38px;
        height:38px;
        margin:0 auto 8px;
        border-radius:11px;
        background:var(--primary-soft);
        color:var(--primary);
        display:flex;
        align-items:center;
        justify-content:center;
        font-size: 17px;
    }

    .flow-step strong {
        display:block;
        font-size: 14px;
    }

    .flow-step span {
        display:block;
        margin-top:3px;
        color: #6c7470;
        font-size: 12px;
    }

    .flow-arrow {
        color: #6e7471;
        font-size: 16px;
        min-width:26px;
        text-align:center;
    }

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

    .recycle-calculation {
        display:grid;
        grid-template-columns:repeat(3,minmax(0,1fr));
        gap:10px;
        margin-top:18px;
    }

    .recycle-box {
        padding:14px;
        border:1px solid #edf1ee;
        border-radius:10px;
        background:#fafcfb;
    }

    .recycle-box.highlight {
        background:var(--primary-soft);
        border-color: #6f7673;
    }

    .recycle-box label {
        display:block;
        color: #6d7571;
        font-size: 13px;
        text-transform:uppercase;
    }

    .recycle-box strong {
        display:block;
        margin-top:6px;
        font-size: 17px;
    }

    .recycle-box.highlight strong {
        color:var(--primary);
    }

    .allocation-option {
        display:flex;
        gap:9px;
        align-items:flex-start;
        padding:11px;
        border:1px solid #edf1ee;
        border-radius:9px;
        background:#fafcfb;
        margin-bottom:9px;
    }

    .allocation-option input {
        margin-top:1px;
        accent-color:var(--primary);
    }

    .allocation-option strong {
        display:block;
        font-size: 14px;
    }

    .allocation-option span {
        display:block;
        margin-top:3px;
        color: #6d7872;
        font-size: 13px;
        line-height:1.5;
    }

    .allocation-option:last-child {
        margin-bottom:0;
    }

    .allocation-option.selected {
        background:var(--primary-soft);
        border-color: #6b7570;
    }

    .table-wrap {
        overflow-x:auto;
    }

    .recycle-table {
        width:100%;
        min-width:700px;
        border-collapse:collapse;
    }

    .recycle-table th {
        padding:10px;
        background:#fafcfb;
        border-bottom:1px solid #eef1ef;
        color: #6c7470;
        font-size: 13px;
        text-transform:uppercase;
        text-align:left;
    }

    .recycle-table td {
        padding:11px 10px;
        border-bottom:1px solid #f0f3f1;
        font-size: 14px;
    }

    .recycle-table tr:last-child td {
        border-bottom:none;
    }

    .recycled {
        color:#397955;
        font-weight:650;
    }

    .document-upload {
        border:1px dashed #cbd6d0;
        border-radius:11px;
        padding:22px;
        text-align:center;
        background:#fbfcfb;
        cursor:pointer;
    }

    .document-upload i {
        color:var(--primary);
        font-size: 23px;
    }

    .document-upload strong {
        display:block;
        margin-top:8px;
        font-size: 15px;
    }

    .document-upload span {
        display:block;
        margin-top:4px;
        color: #6c7470;
        font-size: 13px;
    }

    .principle {
        display:flex;
        gap:9px;
        padding:12px;
        background:#f7f9f8;
        border:1px solid #e5ebe8;
        border-radius:10px;
    }

    .principle i {
        color:var(--primary);
        font-size: 16px;
    }

    .principle p {
        margin:0;
        color: #6b766f;
        font-size: 13px;
        line-height:1.6;
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

        .summary-grid,
        .form-grid,
        .recycle-calculation {
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
            <i class="bi bi-arrow-repeat"></i>
        </div>

        <div>

            <div class="title">
                Qard Fund Recycling
            </div>

            <div class="meta">
                QRD-00028 · Hamid Shah · Principal Fully Recovered
            </div>

        </div>

    </div>

    <div class="actions">

        <a
            href="{{ route('qard.show', ['id' => 4]) }}"
            class="ui-btn"
        >
            <i class="bi bi-arrow-left"></i>
            Back
        </a>

        <button
            class="ui-btn success"
            onclick="previewRecycle()"
        >
            <i class="bi bi-arrow-repeat"></i>
            Recycle Principal
        </button>

    </div>

</div>


<div class="layout">

    <div>

        <!-- RECOVERY STATUS -->

        <div class="card">

            <div class="card-head">

                <div>
                    <strong>Recovery Completed</strong>

                    <span>
                        This Qard has been fully repaid and is eligible for fund recycling.
                    </span>
                </div>

                <span class="status">
                    Ready to Recycle
                </span>

            </div>

            <div class="card-body">

                <div class="summary-grid">

                    <div class="summary-box">
                        <label>Qard</label>
                        <strong>QRD-00028</strong>
                    </div>

                    <div class="summary-box">
                        <label>Borrower</label>
                        <strong>Hamid Shah</strong>
                    </div>

                    <div class="summary-box">
                        <label>Fund</label>
                        <strong>Qard-e-Hasana Fund</strong>
                    </div>

                    <div class="summary-box highlight">
                        <label>Recovered Principal</label>
                        <strong>PKR 80,000</strong>
                    </div>

                    <div class="summary-box">
                        <label>Total Repaid</label>
                        <strong>PKR 80,000</strong>
                    </div>

                    <div class="summary-box">
                        <label>Outstanding</label>
                        <strong>PKR 0</strong>
                    </div>

                    <div class="summary-box">
                        <label>Interest</label>
                        <strong>PKR 0</strong>
                    </div>

                    <div class="summary-box">
                        <label>Recovered Date</label>
                        <strong>05 Aug 2026</strong>
                    </div>

                </div>

            </div>

        </div>


        <!-- FLOW -->

        <div class="card">

            <div class="card-head">

                <div>
                    <strong>Principal Recycling Flow</strong>

                    <span>
                        The recovered principal returns to the relevant fund.
                    </span>
                </div>

            </div>

            <div class="card-body">

                <div class="flow">

                    <div class="flow-step">

                        <div class="flow-icon">
                            <i class="bi bi-person"></i>
                        </div>

                        <strong>
                            Borrower
                        </strong>

                        <span>
                            Repays principal
                        </span>

                    </div>


                    <div class="flow-arrow">
                        <i class="bi bi-arrow-right"></i>
                    </div>


                    <div class="flow-step">

                        <div class="flow-icon">
                            <i class="bi bi-cash-stack"></i>
                        </div>

                        <strong>
                            Recovery
                        </strong>

                        <span>
                            Principal recorded
                        </span>

                    </div>


                    <div class="flow-arrow">
                        <i class="bi bi-arrow-right"></i>
                    </div>


                    <div class="flow-step">

                        <div class="flow-icon">
                            <i class="bi bi-bank"></i>
                        </div>

                        <strong>
                            Fund
                        </strong>

                        <span>
                            Capital returned
                        </span>

                    </div>


                    <div class="flow-arrow">
                        <i class="bi bi-arrow-right"></i>
                    </div>


                    <div class="flow-step">

                        <div class="flow-icon">
                            <i class="bi bi-arrow-repeat"></i>
                        </div>

                        <strong>
                            Reuse
                        </strong>

                        <span>
                            New approved case
                        </span>

                    </div>

                </div>

            </div>

        </div>


        <!-- RECYCLE CALCULATION -->

        <div class="card">

            <div class="card-head">

                <div>
                    <strong>Recycling Amount</strong>

                    <span>
                        Only recovered principal is eligible for recycling.
                    </span>
                </div>

            </div>

            <div class="card-body">

                <div class="recycle-calculation">

                    <div class="recycle-box">

                        <label>
                            Original Principal
                        </label>

                        <strong>
                            PKR 80,000
                        </strong>

                    </div>


                    <div class="recycle-box">

                        <label>
                            Principal Recovered
                        </label>

                        <strong>
                            PKR 80,000
                        </strong>

                    </div>


                    <div class="recycle-box highlight">

                        <label>
                            Available To Recycle
                        </label>

                        <strong>
                            PKR 80,000
                        </strong>

                    </div>

                </div>

            </div>

        </div>


        <!-- DESTINATION -->

        <div class="card">

            <div class="card-head">

                <div>
                    <strong>Recycling Destination</strong>

                    <span>
                        Determine how the recovered principal will become available again.
                    </span>
                </div>

            </div>

            <div class="card-body">

                <label class="allocation-option selected">

                    <input
                        type="radio"
                        name="destination"
                        checked
                    >

                    <div>

                        <strong>
                            Return to Qard-e-Hasana Fund
                        </strong>

                        <span>
                            Add the recovered principal back to the Qard fund
                            so it can support another approved Qard.
                        </span>

                    </div>

                </label>


                <label class="allocation-option">

                    <input
                        type="radio"
                        name="destination"
                    >

                    <div>

                        <strong>
                            Return to Restricted Livelihood Fund
                        </strong>

                        <span>
                            Use where the original restricted funding arrangement
                            explicitly permits reuse in the same purpose area.
                        </span>

                    </div>

                </label>


                <label class="allocation-option">

                    <input
                        type="radio"
                        name="destination"
                    >

                    <div>

                        <strong>
                            Hold Pending Allocation
                        </strong>

                        <span>
                            Record the recovered principal but leave allocation
                            pending until an authorized decision is made.
                        </span>

                    </div>

                </label>

            </div>

        </div>


        <!-- RECYCLING ENTRY -->

        <div class="card">

            <div class="card-head">

                <div>
                    <strong>Fund Recycling Entry</strong>

                    <span>
                        Record the movement of recovered principal into the fund.
                    </span>

                </div>

            </div>

            <div class="card-body">

                <div class="form-grid">

                    <div>

                        <label class="label">
                            Recycling Date
                        </label>

                        <input
                            type="date"
                            class="control"
                            value="2026-08-06"
                        >

                    </div>


                    <div>

                        <label class="label">
                            Recycling Reference
                        </label>

                        <input
                            class="control"
                            value="RECYCLE-QRD-00028"
                        >

                    </div>


                    <div>

                        <label class="label">
                            Destination Fund
                        </label>

                        <select class="select">

                            <option selected>
                                Qard-e-Hasana Fund
                            </option>

                            <option>
                                Restricted Livelihood Fund
                            </option>

                        </select>

                    </div>


                    <div>

                        <label class="label">
                            Amount
                        </label>

                        <input
                            class="control"
                            value="PKR 80,000"
                        >

                    </div>


                    <div class="full">

                        <label class="label">
                            Recycling Notes
                        </label>

                        <textarea class="control">Full principal of QRD-00028 has been recovered. The recovered PKR 80,000 is being returned to the Qard-e-Hasana Fund for future approved Qard assistance.</textarea>

                    </div>

                </div>

            </div>

        </div>


        <!-- CONTROLS -->

        <div class="card">

            <div class="card-head">

                <div>
                    <strong>Recycling Controls</strong>

                    <span>
                        Verify the recovery and fund movement before posting.
                    </span>
                </div>

            </div>

            <div class="card-body">

                <div class="allocation-option selected">

                    <input
                        type="checkbox"
                        checked
                    >

                    <div>
                        <strong>Qard has zero outstanding principal</strong>
                        <span>
                            No principal remains due from the borrower.
                        </span>
                    </div>

                </div>


                <div class="allocation-option selected">

                    <input
                        type="checkbox"
                        checked
                    >

                    <div>
                        <strong>Recovered amount represents principal only</strong>
                        <span>
                            No interest or interest-like charge is being recycled.
                        </span>
                    </div>

                </div>


                <div class="allocation-option selected">

                    <input
                        type="checkbox"
                        checked
                    >

                    <div>
                        <strong>Destination fund is authorized</strong>
                        <span>
                            The selected fund is permitted to receive the recovered principal.
                        </span>
                    </div>

                </div>


                <div class="allocation-option">

                    <input
                        type="checkbox"
                    >

                    <div>
                        <strong>Financial transaction is ready to post</strong>
                        <span>
                            The accounting transaction will be posted after final authorization.
                        </span>
                    </div>

                </div>

            </div>

        </div>


        <!-- HISTORY -->

        <div class="card">

            <div class="card-head">

                <div>
                    <strong>Recovery History</strong>

                    <span>
                        Completed principal recoveries associated with this Qard.
                    </span>
                </div>

            </div>

            <div class="card-body" style="padding:0;">

                <div class="table-wrap">

                    <table class="recycle-table">

                        <thead>

                            <tr>
                                <th>Date</th>
                                <th>Reference</th>
                                <th>Principal Recovered</th>
                                <th>Outstanding</th>
                                <th>Status</th>
                            </tr>

                        </thead>

                        <tbody>

                            <tr>
                                <td>05 Aug 2026</td>
                                <td>PAY-QRD-081</td>
                                <td class="recycled">PKR 10,000</td>
                                <td>PKR 0</td>
                                <td class="recycled">Recovered</td>
                            </tr>

                            <tr>
                                <td>01 Jul 2026</td>
                                <td>PAY-QRD-069</td>
                                <td class="recycled">PKR 10,000</td>
                                <td>PKR 10,000</td>
                                <td class="recycled">Recovered</td>
                            </tr>

                            <tr>
                                <td>01 Jun 2026</td>
                                <td>PAY-QRD-057</td>
                                <td class="recycled">PKR 10,000</td>
                                <td>PKR 20,000</td>
                                <td class="recycled">Recovered</td>
                            </tr>

                            <tr>
                                <td>01 May 2026</td>
                                <td>PAY-QRD-041</td>
                                <td class="recycled">PKR 10,000</td>
                                <td>PKR 30,000</td>
                                <td class="recycled">Recovered</td>
                            </tr>

                            <tr>
                                <td>01 Apr 2026</td>
                                <td>PAY-QRD-026</td>
                                <td class="recycled">PKR 40,000</td>
                                <td>PKR 40,000</td>
                                <td class="recycled">Recovered</td>
                            </tr>

                        </tbody>

                    </table>

                </div>

            </div>

        </div>


        <!-- DOCUMENT -->

        <div class="card">

            <div class="card-head">

                <div>
                    <strong>Recycling Evidence</strong>

                    <span>
                        Supporting record for the fund movement.
                    </span>

                </div>

            </div>

            <div class="card-body">

                <label class="document-upload">

                    <i class="bi bi-cloud-arrow-up"></i>

                    <strong>
                        Upload recycling record
                    </strong>

                    <span>
                        Financial entry, reconciliation or supporting approval
                    </span>

                    <input
                        type="file"
                        hidden
                    >

                </label>

            </div>

        </div>

    </div>


    <aside>

        <div class="card">

            <div class="card-body">

                <div class="side-number">
                    PKR 80K
                </div>

                <div class="side-label">
                    Ready To Recycle
                </div>

                <div style="
                    height:1px;
                    background:#eef1ef;
                    margin:16px 0;
                "></div>

                <div class="side-row">
                    <span>Qard</span>
                    <span>QRD-00028</span>
                </div>

                <div class="side-row">
                    <span>Borrower</span>
                    <span>Hamid Shah</span>
                </div>

                <div class="side-row">
                    <span>Original</span>
                    <span>PKR 80K</span>
                </div>

                <div class="side-row">
                    <span>Recovered</span>
                    <span>PKR 80K</span>
                </div>

                <div class="side-row">
                    <span>Outstanding</span>
                    <span>PKR 0</span>
                </div>

                <div class="side-row">
                    <span>Destination</span>
                    <span>Qard Fund</span>
                </div>

            </div>

        </div>


        <div class="card">

            <div class="card-head">
                <strong>Recycling Principle</strong>
            </div>

            <div class="card-body">

                <div class="principle">

                    <i class="bi bi-arrow-repeat"></i>

                    <p>
                        Recovered principal is treated as reusable capital,
                        not as organizational income, subject to the applicable
                        restricted-fund rules.
                    </p>

                </div>

            </div>

        </div>

    </aside>

</div>

@endsection


@push('scripts')
<script>

    function previewRecycle() {

        const confirmed =
            confirm(
                'Recycle PKR 80,000 back into the selected fund?'
            );

        if (!confirmed) {
            return;
        }

        alert(
            'UI preview only.\n\n' +
            'The recovered principal would be posted back to the selected fund once backend financial functionality is connected.'
        );

    }

</script>
@endpush