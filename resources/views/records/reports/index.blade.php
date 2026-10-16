@extends('layouts.app')

@section('title', 'Reports')

@push('styles')
<style>
    .page-header {
        display:flex;
        justify-content:space-between;
        align-items:flex-end;
        gap:20px;
        margin-bottom:24px;
    }

    .page-header h1 {
        margin:0;
        font-size:25px;
        font-weight:700;
        letter-spacing:-.5px;
    }

    .page-header p {
        margin:7px 0 0;
        color:var(--muted);
        font-size:15px;
        line-height:1.5;
    }

    .actions {
        display:flex;
        gap:8px;
        flex-wrap:wrap;
    }

    .ui-btn {
        height:40px;
        padding:0 14px;
        border-radius:10px;
        display:inline-flex;
        align-items:center;
        justify-content:center;
        gap:7px;
        font-size:14px;
        font-weight:650;
        border:1px solid var(--border);
        background:#fff;
        color:#637069;
        text-decoration:none;
        cursor:pointer;
    }

    .ui-btn.primary {
        background:var(--primary);
        border-color:var(--primary);
        color:#fff;
    }

    .summary-grid {
        display:grid;
        grid-template-columns:repeat(5,minmax(0,1fr));
        gap:12px;
        margin-bottom:18px;
    }

    .summary-card {
        background:#fff;
        border:1px solid var(--border);
        border-radius:13px;
        padding:15px;
    }

    .summary-label {
        color:var(--muted);
        font-size:13px;
    }

    .summary-value {
        margin-top:6px;
        font-size:20px;
        font-weight:700;
    }

    .summary-meta {
        margin-top:4px;
        color:#98a39e;
        font-size:13px;
    }

    .toolbar-card {
        background:#fff;
        border:1px solid var(--border);
        border-radius:14px;
        padding:14px 16px;
        margin-bottom:18px;
    }

    .toolbar {
        display:flex;
        gap:9px;
        flex-wrap:wrap;
    }

    .search {
        position:relative;
        flex:1;
        min-width:240px;
    }

    .search i {
        position:absolute;
        left:13px;
        top:50%;
        transform:translateY(-50%);
        color:#9aa49f;
        font-size:14px;
    }

    .search input {
        width:100%;
        height:40px;
        border:1px solid var(--border);
        border-radius:9px;
        padding:0 12px 0 36px;
        background:#fafcfa;
        outline:none;
        font-size:14px;
    }

    .search input:focus {
        background:#fff;
        border-color:#bdcec5;
        box-shadow:0 0 0 3px rgba(23,63,53,.06);
    }

    .filter {
        height:40px;
        min-width:150px;
        border:1px solid var(--border);
        border-radius:9px;
        padding:0 28px 0 12px;
        background:#fff;
        color:#68756f;
        font-size:14px;
        outline:none;
    }

    .layout {
        display:grid;
        grid-template-columns:minmax(0,1.5fr) minmax(280px,.8fr);
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

    /* REPORT CARDS */

    .report-grid {
        display:grid;
        grid-template-columns:repeat(2,minmax(0,1fr));
        gap:11px;
    }

    .report-card {
        position:relative;
        padding:15px;
        border:1px solid #edf1ee;
        border-radius:11px;
        background:#fafcfb;
        cursor:pointer;
        transition:.15s;
    }

    .report-card:hover {
        transform:translateY(-1px);
        box-shadow:0 5px 16px rgba(25,45,38,.04);
    }

    .report-top {
        display:flex;
        justify-content:space-between;
        align-items:flex-start;
        gap:10px;
    }

    .report-icon {
        width:40px;
        height:40px;
        border-radius:11px;
        background:var(--primary-soft);
        color:var(--primary);
        display:flex;
        align-items:center;
        justify-content:center;
        font-size:16px;
    }

    .report-badge {
        padding:5px 8px;
        border-radius:20px;
        background:#eef3f8;
        color:#55758d;
        font-size:12px;
        font-weight:650;
    }

    .report-title {
        margin-top:13px;
        font-size:14px;
        font-weight:700;
    }

    .report-description {
        margin-top:4px;
        min-height:28px;
        color:#8a9690;
        font-size:13px;
        line-height:1.5;
    }

    .report-meta {
        margin-top:13px;
        display:flex;
        justify-content:space-between;
        gap:8px;
        padding-top:10px;
        border-top:1px solid #e9efec;
        color:#98a29d;
        font-size:12px;
    }

    .report-meta strong {
        color:#58655e;
        font-size:13px;
    }

    /* CHART */

    .chart {
        height:190px;
        display:flex;
        align-items:flex-end;
        gap:10px;
        padding:16px 6px 6px;
    }

    .bar-wrap {
        flex:1;
        height:100%;
        display:flex;
        flex-direction:column;
        justify-content:flex-end;
        align-items:center;
        gap:6px;
    }

    .bar {
        width:100%;
        max-width:35px;
        border-radius:7px 7px 3px 3px;
        background:var(--primary);
        opacity:.88;
    }

    .bar-value {
        font-size:12px;
        color:#7c8882;
    }

    .bar-label {
        font-size:12px;
        color:#98a29d;
    }

    /* DONUT-LIKE */

    .distribution {
        display:flex;
        align-items:center;
        gap:18px;
    }

    .donut {
        width:125px;
        height:125px;
        border-radius:50%;
        background:conic-gradient(
            var(--primary) 0 40%,
            #88a59a 40% 65%,
            #b0c3ba 65% 82%,
            #d5dfda 82% 100%
        );
        position:relative;
        flex-shrink:0;
    }

    .donut::after {
        content:"";
        position:absolute;
        width:69px;
        height:69px;
        background:#fff;
        border-radius:50%;
        left:28px;
        top:28px;
    }

    .legend {
        flex:1;
    }

    .legend-row {
        display:flex;
        align-items:center;
        gap:8px;
        margin-bottom:9px;
        font-size:13px;
    }

    .legend-row:last-child {
        margin-bottom:0;
    }

    .legend-dot {
        width:8px;
        height:8px;
        border-radius:3px;
        background:var(--primary);
    }

    .legend-row:nth-child(2) .legend-dot {
        opacity:.75;
    }

    .legend-row:nth-child(3) .legend-dot {
        opacity:.55;
    }

    .legend-row:nth-child(4) .legend-dot {
        opacity:.30;
    }

    .legend-label {
        flex:1;
        color:#68756f;
    }

    .legend-value {
        color:#4a5750;
        font-weight:650;
    }

    /* RECENT REPORTS */

    .recent-report {
        display:flex;
        align-items:center;
        gap:10px;
        padding:10px 0;
        border-bottom:1px solid #f0f3f1;
    }

    .recent-report:last-child {
        border-bottom:none;
    }

    .recent-icon {
        width:34px;
        height:34px;
        border-radius:9px;
        background:#f4f7f5;
        color:var(--primary);
        display:flex;
        align-items:center;
        justify-content:center;
    }

    .recent-info {
        flex:1;
    }

    .recent-info strong {
        display:block;
        font-size:13px;
    }

    .recent-info span {
        display:block;
        margin-top:3px;
        color:#98a29d;
        font-size:12px;
    }

    .recent-action {
        width:29px;
        height:29px;
        border:1px solid var(--border);
        background:#fff;
        border-radius:7px;
        color:#748079;
    }

    /* HEALTH */

    .health-row {
        margin-bottom:14px;
    }

    .health-row:last-child {
        margin-bottom:0;
    }

    .health-top {
        display:flex;
        justify-content:space-between;
        gap:10px;
        font-size:13px;
    }

    .health-top span:first-child {
        color:#7f8b84;
    }

    .health-top span:last-child {
        color:var(--primary);
        font-weight:650;
    }

    .progress {
        height:6px;
        margin-top:6px;
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

    @media(max-width:1100px) {
        .summary-grid {
            grid-template-columns:repeat(3,1fr);
        }

        .layout {
            grid-template-columns:1fr;
        }
    }

    @media(max-width:700px) {
        .page-header {
            flex-direction:column;
            align-items:flex-start;
        }

        .actions {
            width:100%;
        }

        .actions .ui-btn {
            flex:1;
        }

        .summary-grid,
        .report-grid {
            grid-template-columns:1fr 1fr;
        }

        .search {
            min-width:100%;
        }

        .filter {
            flex:1;
            min-width:0;
        }

        .distribution {
            flex-direction:column;
            align-items:flex-start;
        }
    }

    @media(max-width:480px) {
        .summary-grid,
        .report-grid {
            grid-template-columns:1fr;
        }
    }
</style>
@endpush


@section('content')

<div class="page-header">

    <div>

        <h1>Reports</h1>

        <p>
            Generate and review organizational, financial, welfare and operational reports.
        </p>

    </div>

    <div class="actions">

        <button
            class="ui-btn"
            onclick="previewExport()"
        >
            <i class="bi bi-download"></i>
            Export
        </button>

        <button
            class="ui-btn primary"
            onclick="previewGenerate()"
        >
            <i class="bi bi-file-earmark-bar-graph"></i>
            Generate Report
        </button>

    </div>

</div>


<!-- SUMMARY -->

<div class="summary-grid">

    <div class="summary-card">
        <div class="summary-label">Reports Generated</div>
        <div class="summary-value">128</div>
        <div class="summary-meta">This year</div>
    </div>

    <div class="summary-card">
        <div class="summary-label">Financial Reports</div>
        <div class="summary-value">46</div>
        <div class="summary-meta">Funds, donations, expenses</div>
    </div>

    <div class="summary-card">
        <div class="summary-label">Welfare Reports</div>
        <div class="summary-value">27</div>
        <div class="summary-meta">Beneficiary activity</div>
    </div>

    <div class="summary-card">
        <div class="summary-label">Project Reports</div>
        <div class="summary-value">24</div>
        <div class="summary-meta">Community development</div>
    </div>

    <div class="summary-card">
        <div class="summary-label">Latest Report</div>
        <div class="summary-value">18 Aug</div>
        <div class="summary-meta">Monthly finance summary</div>
    </div>

</div>


<!-- FILTER -->

<div class="toolbar-card">

    <div class="toolbar">

        <div class="search">

            <i class="bi bi-search"></i>

            <input
                id="reportSearch"
                type="text"
                placeholder="Search report, category or period..."
            >

        </div>


        <select
            id="categoryFilter"
            class="filter"
        >

            <option value="">
                All Categories
            </option>

            <option value="financial">
                Financial
            </option>

            <option value="welfare">
                Welfare
            </option>

            <option value="projects">
                Projects
            </option>

            <option value="operations">
                Operations
            </option>

            <option value="organizational">
                Organizational
            </option>

        </select>


        <select
            id="periodFilter"
            class="filter"
        >

            <option value="">
                All Periods
            </option>

            <option value="monthly">
                Monthly
            </option>

            <option value="quarterly">
                Quarterly
            </option>

            <option value="annual">
                Annual
            </option>

            <option value="custom">
                Custom
            </option>

        </select>

    </div>

</div>


<div class="layout">

    <div>

        <!-- REPORT LIBRARY -->

        <div class="card">

            <div class="card-head">

                <div>
                    <strong>Report Library</strong>

                    <span>
                        Available reports for organizational analysis.
                    </span>
                </div>

                <span>
                    10 report templates
                </span>

            </div>

            <div class="card-body">

                <div
                    class="report-grid"
                    id="reportGrid"
                >

                    <!-- Financial -->

                    <div
                        class="report-card"
                        data-category="financial"
                        data-period="monthly"
                        onclick="previewReport('Monthly Financial Summary')"
                    >

                        <div class="report-top">

                            <div class="report-icon">
                                <i class="bi bi-cash-stack"></i>
                            </div>

                            <span class="report-badge">
                                Monthly
                            </span>

                        </div>

                        <div class="report-title">
                            Monthly Financial Summary
                        </div>

                        <div class="report-description">
                            Donations, expenses, fund balances and financial movement.
                        </div>

                        <div class="report-meta">

                            <span>
                                Latest
                            </span>

                            <strong>
                                Aug 2026
                            </strong>

                        </div>

                    </div>


                    <div
                        class="report-card"
                        data-category="financial"
                        data-period="quarterly"
                        onclick="previewReport('Fund Performance Report')"
                    >

                        <div class="report-top">

                            <div class="report-icon">
                                <i class="bi bi-wallet2"></i>
                            </div>

                            <span class="report-badge">
                                Quarterly
                            </span>

                        </div>

                        <div class="report-title">
                            Fund Performance Report
                        </div>

                        <div class="report-description">
                            Opening balances, receipts, allocations, expenses and closing balances.
                        </div>

                        <div class="report-meta">

                            <span>
                                Period
                            </span>

                            <strong>
                                Q3 2026
                            </strong>

                        </div>

                    </div>


                    <div
                        class="report-card"
                        data-category="financial"
                        data-period="annual"
                        onclick="previewReport('Donation & Expense Report')"
                    >

                        <div class="report-top">

                            <div class="report-icon">
                                <i class="bi bi-bar-chart"></i>
                            </div>

                            <span class="report-badge">
                                Annual
                            </span>

                        </div>

                        <div class="report-title">
                            Donation & Expense Report
                        </div>

                        <div class="report-description">
                            Annual comparison of incoming donations and organizational spending.
                        </div>

                        <div class="report-meta">

                            <span>
                                Period
                            </span>

                            <strong>
                                FY 2026
                            </strong>

                        </div>

                    </div>


                    <!-- Welfare -->

                    <div
                        class="report-card"
                        data-category="welfare"
                        data-period="monthly"
                        onclick="previewReport('Welfare Assistance Report')"
                    >

                        <div class="report-top">

                            <div class="report-icon">
                                <i class="bi bi-heart-handshake"></i>
                            </div>

                            <span class="report-badge">
                                Monthly
                            </span>

                        </div>

                        <div class="report-title">
                            Welfare Assistance Report
                        </div>

                        <div class="report-description">
                            Assistance delivered to vulnerable families, elderly and disabled people.
                        </div>

                        <div class="report-meta">

                            <span>
                                Latest
                            </span>

                            <strong>
                                Aug 2026
                            </strong>

                        </div>

                    </div>


                    <div
                        class="report-card"
                        data-category="welfare"
                        data-period="quarterly"
                        onclick="previewReport('Qard-e-Hasana Report')"
                    >

                        <div class="report-top">

                            <div class="report-icon">
                                <i class="bi bi-arrow-repeat"></i>
                            </div>

                            <span class="report-badge">
                                Quarterly
                            </span>

                        </div>

                        <div class="report-title">
                            Qard-e-Hasana Report
                        </div>

                        <div class="report-description">
                            Issuance, outstanding principal, recoveries and active cases.
                        </div>

                        <div class="report-meta">

                            <span>
                                Period
                            </span>

                            <strong>
                                Q3 2026
                            </strong>

                        </div>

                    </div>


                    <div
                        class="report-card"
                        data-category="welfare"
                        data-period="annual"
                        onclick="previewReport('Beneficiary Support Report')"
                    >

                        <div class="report-top">

                            <div class="report-icon">
                                <i class="bi bi-people"></i>
                            </div>

                            <span class="report-badge">
                                Annual
                            </span>

                        </div>

                        <div class="report-title">
                            Beneficiary Support Report
                        </div>

                        <div class="report-description">
                            Beneficiary categories, assistance history and support outcomes.
                        </div>

                        <div class="report-meta">

                            <span>
                                Period
                            </span>

                            <strong>
                                FY 2026
                            </strong>

                        </div>

                    </div>


                    <!-- Projects -->

                    <div
                        class="report-card"
                        data-category="projects"
                        data-period="quarterly"
                        onclick="previewReport('Community Projects Report')"
                    >

                        <div class="report-top">

                            <div class="report-icon">
                                <i class="bi bi-buildings"></i>
                            </div>

                            <span class="report-badge">
                                Quarterly
                            </span>

                        </div>

                        <div class="report-title">
                            Community Projects Report
                        </div>

                        <div class="report-description">
                            Project status, budgets, procurement, expenses and completion.
                        </div>

                        <div class="report-meta">

                            <span>
                                Period
                            </span>

                            <strong>
                                Q3 2026
                            </strong>

                        </div>

                    </div>


                    <div
                        class="report-card"
                        data-category="projects"
                        data-period="monthly"
                        onclick="previewReport('Procurement Report')"
                    >

                        <div class="report-top">

                            <div class="report-icon">
                                <i class="bi bi-cart-check"></i>
                            </div>

                            <span class="report-badge">
                                Monthly
                            </span>

                        </div>

                        <div class="report-title">
                            Procurement Report
                        </div>

                        <div class="report-description">
                            Requests, suppliers, approvals, purchase orders and received items.
                        </div>

                        <div class="report-meta">

                            <span>
                                Latest
                            </span>

                            <strong>
                                Aug 2026
                            </strong>

                        </div>

                    </div>


                    <!-- Operations -->

                    <div
                        class="report-card"
                        data-category="operations"
                        data-period="monthly"
                        onclick="previewReport('Operations Activity Report')"
                    >

                        <div class="report-top">

                            <div class="report-icon">
                                <i class="bi bi-activity"></i>
                            </div>

                            <span class="report-badge">
                                Monthly
                            </span>

                        </div>

                        <div class="report-title">
                            Operations Activity Report
                        </div>

                        <div class="report-description">
                            Tasks, meetings, procurement activity and organizational workload.
                        </div>

                        <div class="report-meta">

                            <span>
                                Latest
                            </span>

                            <strong>
                                Aug 2026
                            </strong>

                        </div>

                    </div>


                    <!-- Organizational -->

                    <div
                        class="report-card"
                        data-category="organizational"
                        data-period="annual"
                        onclick="previewReport('Organization Annual Report')"
                    >

                        <div class="report-top">

                            <div class="report-icon">
                                <i class="bi bi-building"></i>
                            </div>

                            <span class="report-badge">
                                Annual
                            </span>

                        </div>

                        <div class="report-title">
                            Organization Annual Report
                        </div>

                        <div class="report-description">
                            Comprehensive annual overview of finances, welfare, projects and activities.
                        </div>

                        <div class="report-meta">

                            <span>
                                Period
                            </span>

                            <strong>
                                FY 2026
                            </strong>

                        </div>

                    </div>

                </div>

            </div>

        </div>


        <!-- FINANCIAL TREND -->

        <div class="card">

            <div class="card-head">

                <div>
                    <strong>Monthly Financial Trend</strong>

                    <span>
                        Illustrative monthly inflow and organizational spending position.
                    </span>
                </div>

                <span>
                    Jan–Aug 2026
                </span>

            </div>

            <div class="card-body">

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

            </div>

        </div>

    </div>


    <div>

        <!-- DISTRIBUTION -->

        <div class="card">

            <div class="card-head">

                <div>
                    <strong>Fund Distribution</strong>

                    <span>
                        Current balance by major fund category.
                    </span>
                </div>

            </div>

            <div class="card-body">

                <div class="distribution">

                    <div class="donut"></div>

                    <div class="legend">

                        <div class="legend-row">

                            <span class="legend-dot"></span>

                            <span class="legend-label">
                                General Fund
                            </span>

                            <span class="legend-value">
                                40%
                            </span>

                        </div>


                        <div class="legend-row">

                            <span class="legend-dot"></span>

                            <span class="legend-label">
                                Restricted Funds
                            </span>

                            <span class="legend-value">
                                25%
                            </span>

                        </div>


                        <div class="legend-row">

                            <span class="legend-dot"></span>

                            <span class="legend-label">
                                Qard-e-Hasana
                            </span>

                            <span class="legend-value">
                                17%
                            </span>

                        </div>


                        <div class="legend-row">

                            <span class="legend-dot"></span>

                            <span class="legend-label">
                                Other
                            </span>

                            <span class="legend-value">
                                18%
                            </span>

                        </div>

                    </div>

                </div>

            </div>

        </div>


        <!-- RECENT -->

        <div class="card">

            <div class="card-head">

                <div>
                    <strong>Recent Reports</strong>

                    <span>
                        Previously generated reports.
                    </span>
                </div>

            </div>

            <div class="card-body">

                <div class="recent-report">

                    <div class="recent-icon">
                        <i class="bi bi-file-earmark-bar-graph"></i>
                    </div>

                    <div class="recent-info">

                        <strong>
                            Monthly Financial Summary
                        </strong>

                        <span>
                            Generated 18 Aug 2026
                        </span>

                    </div>

                    <button class="recent-action">
                        <i class="bi bi-arrow-right"></i>
                    </button>

                </div>


                <div class="recent-report">

                    <div class="recent-icon">
                        <i class="bi bi-heart-handshake"></i>
                    </div>

                    <div class="recent-info">

                        <strong>
                            Welfare Assistance Report
                        </strong>

                        <span>
                            Generated 16 Aug 2026
                        </span>

                    </div>

                    <button class="recent-action">
                        <i class="bi bi-arrow-right"></i>
                    </button>

                </div>


                <div class="recent-report">

                    <div class="recent-icon">
                        <i class="bi bi-buildings"></i>
                    </div>

                    <div class="recent-info">

                        <strong>
                            Community Projects Report
                        </strong>

                        <span>
                            Generated 14 Aug 2026
                        </span>

                    </div>

                    <button class="recent-action">
                        <i class="bi bi-arrow-right"></i>
                    </button>

                </div>


                <div class="recent-report">

                    <div class="recent-icon">
                        <i class="bi bi-cart-check"></i>
                    </div>

                    <div class="recent-info">

                        <strong>
                            Procurement Report
                        </strong>

                        <span>
                            Generated 12 Aug 2026
                        </span>

                    </div>

                    <button class="recent-action">
                        <i class="bi bi-arrow-right"></i>
                    </button>

                </div>

            </div>

        </div>


        <!-- REPORT HEALTH -->

        <div class="card">

            <div class="card-head">

                <div>
                    <strong>Data Coverage</strong>

                    <span>
                        Illustrative completeness indicators.
                    </span>
                </div>

            </div>

            <div class="card-body">

                <div class="health-row">

                    <div class="health-top">

                        <span>
                            Financial Records
                        </span>

                        <span>
                            96%
                        </span>

                    </div>

                    <div class="progress">
                        <span style="width:96%;"></span>
                    </div>

                </div>


                <div class="health-row">

                    <div class="health-top">

                        <span>
                            Beneficiary Records
                        </span>

                        <span>
                            91%
                        </span>

                    </div>

                    <div class="progress">
                        <span style="width:91%;"></span>
                    </div>

                </div>


                <div class="health-row">

                    <div class="health-top">

                        <span>
                            Project Records
                        </span>

                        <span>
                            88%
                        </span>

                    </div>

                    <div class="progress">
                        <span style="width:88%;"></span>
                    </div>

                </div>


                <div class="health-row">

                    <div class="health-top">

                        <span>
                            Meeting Records
                        </span>

                        <span>
                            94%
                        </span>

                    </div>

                    <div class="progress">
                        <span style="width:94%;"></span>
                    </div>

                </div>

            </div>

        </div>


        <!-- NOTICE -->

        <div class="notice">

            <i class="bi bi-info-circle"></i>

            <p>
                Report figures shown in this UI are placeholders. Once the
                database and backend are connected, these reports should be
                generated directly from verified organizational records.
            </p>

        </div>

    </div>

</div>

@endsection


@push('scripts')
<script>

    const search =
        document.getElementById('reportSearch');

    const category =
        document.getElementById('categoryFilter');

    const period =
        document.getElementById('periodFilter');

    const cards =
        document.querySelectorAll(
            '#reportGrid .report-card'
        );


    function filterReports() {

        const query =
            search.value.toLowerCase().trim();

        const categoryValue =
            category.value;

        const periodValue =
            period.value;


        cards.forEach(card => {

            const text =
                card.textContent.toLowerCase();

            const cardCategory =
                card.dataset.category;

            const cardPeriod =
                card.dataset.period;


            const matchesSearch =
                !query ||
                text.includes(query);

            const matchesCategory =
                !categoryValue ||
                cardCategory === categoryValue;

            const matchesPeriod =
                !periodValue ||
                cardPeriod === periodValue;


            card.style.display =
                matchesSearch &&
                matchesCategory &&
                matchesPeriod
                    ? ''
                    : 'none';

        });

    }


    search.addEventListener(
        'input',
        filterReports
    );

    category.addEventListener(
        'change',
        filterReports
    );

    period.addEventListener(
        'change',
        filterReports
    );


    function previewReport(name) {

        alert(
            'UI preview only.\n\n' +
            '"' + name + '" will open a generated report preview once the reporting backend is connected.'
        );

    }


    function previewGenerate() {

        alert(
            'UI preview only.\n\n' +
            'The report generator will be connected to the organization database and reporting engine later.'
        );

    }


    function previewExport() {

        alert(
            'UI preview only.\n\n' +
            'Export to PDF / Excel will be connected when the reporting backend is implemented.'
        );

    }

</script>
@endpush