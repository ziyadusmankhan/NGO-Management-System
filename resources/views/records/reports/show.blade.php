@extends('layouts.app')

@section('title', 'Report Preview')

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
        font-size:22px;
    }

    .title {
        font-size:23px;
        font-weight:700;
        letter-spacing:-.4px;
    }

    .meta {
        margin-top:4px;
        color:#8d9993;
        font-size:14px;
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
        font-size:14px;
        font-weight:650;
        text-decoration:none;
        cursor:pointer;
    }

    .ui-btn.primary {
        background:var(--primary);
        border-color:var(--primary);
        color:#fff;
    }

    .layout {
        display:grid;
        grid-template-columns:minmax(0,1fr) 285px;
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
        font-size:15px;
    }

    .card-head span {
        display:block;
        margin-top:4px;
        color:#929d97;
        font-size:13px;
    }

    .card-body {
        padding:18px 19px;
    }

    /* FILTERS */

    .filters {
        display:grid;
        grid-template-columns:1.3fr 1fr 1fr 1fr;
        gap:9px;
    }

    .label {
        display:block;
        margin-bottom:6px;
        color:#7f8b84;
        font-size:13px;
        text-transform:uppercase;
        letter-spacing:.4px;
    }

    .control,
    .select {
        width:100%;
        height:39px;
        border:1px solid var(--border);
        border-radius:8px;
        padding:0 10px;
        background:#fff;
        color:var(--text);
        font-size:13px;
        outline:none;
    }

    .control:focus,
    .select:focus {
        border-color:#b8cbc1;
        box-shadow:0 0 0 3px rgba(23,63,53,.05);
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
        border-color:#d5e3dd;
    }

    .summary-box label {
        display:block;
        color:#98a29d;
        font-size:13px;
        text-transform:uppercase;
    }

    .summary-box strong {
        display:block;
        margin-top:5px;
        font-size:15px;
    }

    .summary-box.highlight strong {
        color:var(--primary);
    }

    /* REPORT HEADER */

    .report-header {
        text-align:center;
        padding:8px 20px 22px;
        border-bottom:1px solid #eef1ef;
    }

    .report-header small {
        display:block;
        color:#96a19b;
        font-size:13px;
        text-transform:uppercase;
        letter-spacing:1px;
    }

    .report-header h2 {
        margin:7px 0 0;
        font-size:20px;
        font-weight:700;
    }

    .report-header p {
        margin:5px 0 0;
        color:#87938d;
        font-size:13px;
    }

    /* CHART */

    .chart {
        height:220px;
        display:flex;
        align-items:flex-end;
        gap:11px;
        padding:14px 5px 4px;
        border-bottom:1px solid #eef1ef;
    }

    .bar-wrap {
        flex:1;
        height:100%;
        display:flex;
        flex-direction:column;
        align-items:center;
        justify-content:flex-end;
        gap:5px;
    }

    .bar-value {
        color:#7c8882;
        font-size:12px;
    }

    .bar {
        width:100%;
        max-width:38px;
        border-radius:7px 7px 3px 3px;
        background:var(--primary);
        opacity:.86;
    }

    .bar-label {
        color:#98a29d;
        font-size:12px;
    }

    .legend {
        display:flex;
        justify-content:center;
        gap:18px;
        margin-top:12px;
    }

    .legend-item {
        display:flex;
        align-items:center;
        gap:5px;
        color:#77837d;
        font-size:13px;
    }

    .legend-dot {
        width:8px;
        height:8px;
        border-radius:3px;
        background:var(--primary);
    }

    /* TABLE */

    .table-wrap {
        overflow-x:auto;
    }

    .report-table {
        width:100%;
        min-width:780px;
        border-collapse:collapse;
    }

    .report-table th {
        padding:10px 11px;
        background:#fafcfb;
        border-bottom:1px solid #eef1ef;
        color:#96a19b;
        font-size:13px;
        text-transform:uppercase;
        letter-spacing:.5px;
        text-align:left;
    }

    .report-table td {
        padding:11px;
        border-bottom:1px solid #f0f3f1;
        font-size:13px;
    }

    .report-table tbody tr:last-child td {
        border-bottom:none;
    }

    .positive {
        color:#397955;
        font-weight:650;
    }

    .negative {
        color:#a85f58;
        font-weight:650;
    }

    .neutral {
        color:#68756f;
        font-weight:650;
    }

    .total-row td {
        background:#fafcfb;
        font-weight:700;
    }

    /* SIDE */

    .side-row {
        display:flex;
        justify-content:space-between;
        gap:10px;
        padding:9px 0;
        border-bottom:1px solid #f0f3f1;
        font-size:13px;
    }

    .side-row:last-child {
        border-bottom:none;
    }

    .side-row span:first-child {
        color:#98a29d;
    }

    .side-row span:last-child {
        color:#4a5750;
        font-weight:650;
        text-align:right;
    }

    .side-number {
        text-align:center;
        font-size:27px;
        font-weight:700;
        color:var(--primary);
    }

    .side-label {
        margin-top:4px;
        text-align:center;
        color:#8d9892;
        font-size:13px;
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
        font-size:14px;
    }

    .notice p {
        margin:0;
        color:#748079;
        font-size:13px;
        line-height:1.6;
    }

    /* CHECKLIST */

    .check {
        display:flex;
        align-items:center;
        justify-content:space-between;
        gap:10px;
        padding:9px 0;
        border-bottom:1px solid #f0f3f1;
    }

    .check:last-child {
        border-bottom:none;
    }

    .check span:first-child {
        color:#7c8882;
        font-size:13px;
    }

    .check span:last-child {
        padding:4px 7px;
        border-radius:20px;
        background:#edf7f0;
        color:#397955;
        font-size:12px;
        font-weight:650;
    }

    @media(max-width:1000px) {
        .layout {
            grid-template-columns:1fr;
        }
    }

    @media(max-width:750px) {
        .page-header {
            flex-direction:column;
        }

        .actions {
            width:100%;
        }

        .actions .ui-btn {
            flex:1;
        }

        .filters,
        .summary-grid {
            grid-template-columns:1fr 1fr;
        }
    }

    @media(max-width:480px) {
        .filters,
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
            <i class="bi bi-file-earmark-bar-graph"></i>
        </div>

        <div>

            <div class="title">
                Monthly Financial Summary
            </div>

            <div class="meta">
                RPT-000128 · August 2026 · Generated 18 Aug 2026
            </div>

        </div>

    </div>

    <div class="actions">

        <a
            href="{{ route('records.reports.index') }}"
            class="ui-btn"
        >
            <i class="bi bi-arrow-left"></i>
            Back
        </a>

        <button
            class="ui-btn"
            onclick="window.print()"
        >
            <i class="bi bi-printer"></i>
            Print
        </button>

        <button
            class="ui-btn"
            onclick="previewExport('Excel')"
        >
            <i class="bi bi-file-earmark-spreadsheet"></i>
            Excel
        </button>

        <button
            class="ui-btn primary"
            onclick="previewExport('PDF')"
        >
            <i class="bi bi-file-earmark-pdf"></i>
            PDF
        </button>

    </div>

</div>


<div class="layout">

    <div>

        <!-- FILTERS -->

        <div class="card">

            <div class="card-head">

                <div>
                    <strong>Report Parameters</strong>

                    <span>
                        Select the reporting period and scope.
                    </span>
                </div>

                <button
                    class="ui-btn primary"
                    onclick="previewRefresh()"
                >
                    <i class="bi bi-arrow-clockwise"></i>
                    Refresh
                </button>

            </div>

            <div class="card-body">

                <div class="filters">

                    <div>

                        <label class="label">
                            Report
                        </label>

                        <select
                            id="reportSelect"
                            class="select"
                        >

                            <option selected>
                                Monthly Financial Summary
                            </option>

                            <option>
                                Fund Performance Report
                            </option>

                            <option>
                                Donation & Expense Report
                            </option>

                            <option>
                                Welfare Assistance Report
                            </option>

                        </select>

                    </div>


                    <div>

                        <label class="label">
                            From
                        </label>

                        <input
                            id="fromDate"
                            type="date"
                            class="control"
                            value="2026-08-01"
                        >

                    </div>


                    <div>

                        <label class="label">
                            To
                        </label>

                        <input
                            id="toDate"
                            type="date"
                            class="control"
                            value="2026-08-18"
                        >

                    </div>


                    <div>

                        <label class="label">
                            Fund
                        </label>

                        <select
                            class="select"
                        >

                            <option>
                                All Funds
                            </option>

                            <option>
                                General Fund
                            </option>

                            <option>
                                Community Development Fund
                            </option>

                            <option>
                                Education Fund
                            </option>

                            <option>
                                Emergency Fund
                            </option>

                        </select>

                    </div>

                </div>

            </div>

        </div>


        <!-- REPORT -->

        <div class="card">

            <div class="card-body">

                <div class="report-header">

                    <small>
                        Tanzeem Falah Naujawanan Kasso
                    </small>

                    <h2>
                        Monthly Financial Summary
                    </h2>

                    <p>
                        Reporting period: 01 Aug 2026 — 18 Aug 2026
                    </p>

                </div>


                <!-- SUMMARY -->

                <div style="margin-top:18px;">

                    <div class="summary-grid">

                        <div class="summary-box highlight">
                            <label>Total Received</label>
                            <strong>PKR 420,000</strong>
                        </div>

                        <div class="summary-box">
                            <label>Total Expenses</label>
                            <strong>PKR 286,000</strong>
                        </div>

                        <div class="summary-box">
                            <label>Net Movement</label>
                            <strong>PKR 134,000</strong>
                        </div>

                        <div class="summary-box">
                            <label>Closing Balance</label>
                            <strong>PKR 3.46M</strong>
                        </div>

                    </div>

                </div>


                <!-- TREND -->

                <div style="margin-top:22px;">

                    <div style="
                        display:flex;
                        justify-content:space-between;
                        align-items:center;
                        gap:10px;
                    ">

                        <div>

                            <div style="
                                font-size:14px;
                                font-weight:700;
                            ">
                                Financial Movement
                            </div>

                            <div style="
                                margin-top:3px;
                                color:#929d97;
                                font-size:13px;
                            ">
                                Monthly inflow versus expenditure
                            </div>

                        </div>

                    </div>


                    <div class="chart">

                        <div class="bar-wrap">
                            <div class="bar-value">180K</div>
                            <div class="bar" style="height:38%;"></div>
                            <div class="bar-label">Jan</div>
                        </div>

                        <div class="bar-wrap">
                            <div class="bar-value">240K</div>
                            <div class="bar" style="height:50%;"></div>
                            <div class="bar-label">Feb</div>
                        </div>

                        <div class="bar-wrap">
                            <div class="bar-value">210K</div>
                            <div class="bar" style="height:44%;"></div>
                            <div class="bar-label">Mar</div>
                        </div>

                        <div class="bar-wrap">
                            <div class="bar-value">310K</div>
                            <div class="bar" style="height:65%;"></div>
                            <div class="bar-label">Apr</div>
                        </div>

                        <div class="bar-wrap">
                            <div class="bar-value">285K</div>
                            <div class="bar" style="height:60%;"></div>
                            <div class="bar-label">May</div>
                        </div>

                        <div class="bar-wrap">
                            <div class="bar-value">340K</div>
                            <div class="bar" style="height:71%;"></div>
                            <div class="bar-label">Jun</div>
                        </div>

                        <div class="bar-wrap">
                            <div class="bar-value">395K</div>
                            <div class="bar" style="height:83%;"></div>
                            <div class="bar-label">Jul</div>
                        </div>

                        <div class="bar-wrap">
                            <div class="bar-value">420K</div>
                            <div class="bar" style="height:88%;"></div>
                            <div class="bar-label">Aug</div>
                        </div>

                    </div>

                    <div class="legend">

                        <div class="legend-item">
                            <span class="legend-dot"></span>
                            Financial activity
                        </div>

                    </div>

                </div>


                <!-- BREAKDOWN -->

                <div style="margin-top:24px;">

                    <div style="
                        font-size:14px;
                        font-weight:700;
                        margin-bottom:10px;
                    ">
                        Fund Breakdown
                    </div>


                    <div class="table-wrap">

                        <table class="report-table">

                            <thead>

                                <tr>
                                    <th>Fund</th>
                                    <th>Opening</th>
                                    <th>Received</th>
                                    <th>Expenses</th>
                                    <th>Allocated</th>
                                    <th>Closing</th>
                                </tr>

                            </thead>

                            <tbody>

                                <tr>

                                    <td>
                                        General Fund
                                    </td>

                                    <td>
                                        PKR 940K
                                    </td>

                                    <td class="positive">
                                        +PKR 100K
                                    </td>

                                    <td class="negative">
                                        -PKR 35K
                                    </td>

                                    <td>
                                        PKR 0
                                    </td>

                                    <td class="neutral">
                                        PKR 1.01M
                                    </td>

                                </tr>


                                <tr>

                                    <td>
                                        Community Development
                                    </td>

                                    <td>
                                        PKR 350K
                                    </td>

                                    <td class="positive">
                                        +PKR 120K
                                    </td>

                                    <td class="negative">
                                        -PKR 75K
                                    </td>

                                    <td>
                                        PKR 80K
                                    </td>

                                    <td class="neutral">
                                        PKR 315K
                                    </td>

                                </tr>


                                <tr>

                                    <td>
                                        Education Fund
                                    </td>

                                    <td>
                                        PKR 300K
                                    </td>

                                    <td class="positive">
                                        +PKR 60K
                                    </td>

                                    <td class="negative">
                                        -PKR 22K
                                    </td>

                                    <td>
                                        PKR 50K
                                    </td>

                                    <td class="neutral">
                                        PKR 288K
                                    </td>

                                </tr>


                                <tr>

                                    <td>
                                        Emergency Fund
                                    </td>

                                    <td>
                                        PKR 170K
                                    </td>

                                    <td class="positive">
                                        +PKR 90K
                                    </td>

                                    <td class="negative">
                                        -PKR 18.5K
                                    </td>

                                    <td>
                                        PKR 20K
                                    </td>

                                    <td class="neutral">
                                        PKR 221.5K
                                    </td>

                                </tr>


                                <tr class="total-row">

                                    <td>
                                        Total
                                    </td>

                                    <td>
                                        PKR 1.76M
                                    </td>

                                    <td>
                                        PKR 370K
                                    </td>

                                    <td>
                                        PKR 150.5K
                                    </td>

                                    <td>
                                        PKR 150K
                                    </td>

                                    <td>
                                        PKR 1.83M
                                    </td>

                                </tr>

                            </tbody>

                        </table>

                    </div>

                </div>


                <!-- EXPENSE CATEGORY -->

                <div style="margin-top:24px;">

                    <div style="
                        font-size:14px;
                        font-weight:700;
                        margin-bottom:10px;
                    ">
                        Expense Categories
                    </div>


                    <div class="table-wrap">

                        <table class="report-table">

                            <thead>

                                <tr>
                                    <th>Category</th>
                                    <th>Transactions</th>
                                    <th>Amount</th>
                                    <th>Share</th>
                                </tr>

                            </thead>

                            <tbody>

                                <tr>
                                    <td>Welfare</td>
                                    <td>18</td>
                                    <td>PKR 142K</td>
                                    <td>49.7%</td>
                                </tr>

                                <tr>
                                    <td>Community Projects</td>
                                    <td>7</td>
                                    <td>PKR 86K</td>
                                    <td>30.1%</td>
                                </tr>

                                <tr>
                                    <td>Education</td>
                                    <td>5</td>
                                    <td>PKR 34K</td>
                                    <td>11.9%</td>
                                </tr>

                                <tr>
                                    <td>Administration</td>
                                    <td>4</td>
                                    <td>PKR 24K</td>
                                    <td>8.3%</td>
                                </tr>

                                <tr class="total-row">
                                    <td>Total</td>
                                    <td>34</td>
                                    <td>PKR 286K</td>
                                    <td>100%</td>
                                </tr>

                            </tbody>

                        </table>

                    </div>

                </div>


                <!-- NOTES -->

                <div style="margin-top:24px;">

                    <div style="
                        font-size:14px;
                        font-weight:700;
                        margin-bottom:8px;
                    ">
                        Report Notes
                    </div>

                    <div style="
                        padding:13px;
                        border:1px solid #edf1ee;
                        background:#fafcfb;
                        border-radius:10px;
                        color:#6d7972;
                        font-size:13px;
                        line-height:1.7;
                    ">

                        This report provides an illustrative financial summary
                        for the selected period. Final values should be generated
                        from verified financial transactions, fund balances and
                        approved records.

                    </div>

                </div>

            </div>

        </div>

    </div>


    <aside>

        <!-- REPORT INFO -->

        <div class="card">

            <div class="card-body">

                <div class="side-number">
                    RPT-000128
                </div>

                <div class="side-label">
                    Report Reference
                </div>

                <div style="
                    height:1px;
                    background:#eef1ef;
                    margin:16px 0;
                "></div>

                <div class="side-row">
                    <span>Type</span>
                    <span>Financial</span>
                </div>

                <div class="side-row">
                    <span>Period</span>
                    <span>Aug 2026</span>
                </div>

                <div class="side-row">
                    <span>Generated</span>
                    <span>18 Aug 2026</span>
                </div>

                <div class="side-row">
                    <span>Generated By</span>
                    <span>Treasurer</span>
                </div>

                <div class="side-row">
                    <span>Records</span>
                    <span>214</span>
                </div>

            </div>

        </div>


        <!-- VALIDATION -->

        <div class="card">

            <div class="card-head">

                <div>
                    <strong>Report Validation</strong>

                    <span>
                        Illustrative data-quality checks.
                    </span>
                </div>

            </div>

            <div class="card-body">

                <div class="check">

                    <span>
                        Financial records
                    </span>

                    <span>
                        Verified
                    </span>

                </div>


                <div class="check">

                    <span>
                        Fund balances
                    </span>

                    <span>
                        Verified
                    </span>

                </div>


                <div class="check">

                    <span>
                        Expense references
                    </span>

                    <span>
                        Verified
                    </span>

                </div>


                <div class="check">

                    <span>
                        Missing records
                    </span>

                    <span>
                        0
                    </span>

                </div>

            </div>

        </div>


        <!-- EXPORT -->

        <div class="card">

            <div class="card-head">
                <strong>Export Report</strong>
            </div>

            <div class="card-body">

                <button
                    class="ui-btn primary"
                    style="width:100%;"
                    onclick="previewExport('PDF')"
                >
                    <i class="bi bi-file-earmark-pdf"></i>
                    Export PDF
                </button>


                <button
                    class="ui-btn"
                    style="width:100%;margin-top:8px;"
                    onclick="previewExport('Excel')"
                >
                    <i class="bi bi-file-earmark-spreadsheet"></i>
                    Export Excel
                </button>


                <button
                    class="ui-btn"
                    style="width:100%;margin-top:8px;"
                    onclick="window.print()"
                >
                    <i class="bi bi-printer"></i>
                    Print Report
                </button>

            </div>

        </div>


        <div class="notice">

            <i class="bi bi-info-circle"></i>

            <p>
                Report values shown here are UI placeholders. Once the
                backend is connected, this screen should calculate all
                values directly from the verified database records.
            </p>

        </div>

    </aside>

</div>

@endsection


@push('scripts')
<script>

    function previewRefresh() {

        alert(
            'UI preview only.\n\n' +
            'The report will refresh from live database records once the reporting backend is connected.'
        );

    }


    function previewExport(format) {

        alert(
            'UI preview only.\n\n' +
            'Exporting this report as ' +
            format +
            ' will be connected during backend/reporting implementation.'
        );

    }

</script>
@endpush