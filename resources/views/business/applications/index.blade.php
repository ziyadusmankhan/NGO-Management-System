@extends('layouts.app')

@section('title', 'Small Business Applications')

@push('styles')
<style>
    .page-header {
        display: flex;
        justify-content: space-between;
        align-items: flex-end;
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

    .ui-btn.primary:hover {
        background: var(--primary-light);
    }

    /* =========================================
       SUMMARY
    ========================================= */

    .summary-grid {
        display: grid;
        grid-template-columns: repeat(5, minmax(0, 1fr));
        gap: 12px;
        margin-bottom: 18px;
    }

    .summary-card {
        background: #fff;
        border: 1px solid var(--border);
        border-radius: 13px;
        padding: 15px;
    }

    .summary-label {
        color: var(--muted);
        font-size: 14px;
    }

    .summary-value {
        margin-top: 6px;
        font-size: 21px;
        font-weight: 700;
    }

    .summary-meta {
        margin-top: 4px;
        color: #6d7572;
        font-size: 13px;
    }

    /* =========================================
       MAIN CARD
    ========================================= */

    .main-card {
        background: #fff;
        border: 1px solid var(--border);
        border-radius: 14px;
        overflow: hidden;
    }

    .toolbar {
        padding: 15px 17px;
        border-bottom: 1px solid #eef1ef;

        display: flex;
        align-items: center;
        gap: 9px;
        flex-wrap: wrap;
    }

    .search {
        position: relative;
        flex: 1;
        min-width: 240px;
    }

    .search i {
        position: absolute;
        left: 13px;
        top: 50%;
        transform: translateY(-50%);
        color: #6f7672;
        font-size: 16px;
    }

    .search input {
        width: 100%;
        height: 40px;
        border: 1px solid var(--border);
        border-radius: 9px;
        padding: 0 12px 0 36px;
        background: #fafcfa;
        outline: none;
        font-size: 15px;
    }

    .search input:focus {
        background: #fff;
        border-color: #6e7772;
        box-shadow: 0 0 0 3px rgba(23,63,53,.06);
    }

    .filter {
        height: 40px;
        min-width: 145px;
        border: 1px solid var(--border);
        border-radius: 9px;
        padding: 0 28px 0 12px;
        background: #fff;
        color: #68756f;
        font-size: 15px;
        outline: none;
    }

    /* =========================================
       TABLE
    ========================================= */

    .table-wrap {
        overflow-x: auto;
    }

    .business-table {
        width: 100%;
        min-width: 1120px;
        border-collapse: collapse;
    }

    .business-table th {
        padding: 11px 18px;
        background: #fafcfb;
        border-bottom: 1px solid #eef1ef;
        color: #6c7470;
        font-size: 13px;
        font-weight: 700;
        text-transform: uppercase;
        letter-spacing: .6px;
        text-align: left;
        white-space: nowrap;
    }

    .business-table td {
        padding: 13px 18px;
        border-bottom: 1px solid #f0f3f1;
        font-size: 14px;
        vertical-align: middle;
    }

    .business-table tbody tr:hover {
        background: #fbfcfb;
    }

    .business-table tbody tr:last-child td {
        border-bottom: none;
    }

    .applicant {
        display: flex;
        align-items: center;
        gap: 9px;
    }

    .avatar {
        width: 35px;
        height: 35px;
        border-radius: 10px;
        background: var(--primary-soft);
        color: var(--primary);
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 14px;
        font-weight: 700;
        flex-shrink: 0;
    }

    .applicant-name {
        font-size: 15px;
        font-weight: 650;
    }

    .applicant-id {
        margin-top: 3px;
        color: #6d7571;
        font-size: 13px;
    }

    .business-name {
        font-weight: 650;
        color: #4b5851;
    }

    .business-type {
        margin-top: 3px;
        color: #6d7571;
        font-size: 13px;
    }

    .amount {
        font-weight: 700;
        color: #4b5851;
    }

    .stage {
        display: inline-flex;
        align-items: center;
        gap: 5px;
        padding: 5px 8px;
        border-radius: 20px;
        font-size: 13px;
        font-weight: 650;
        white-space: nowrap;
    }

    .stage::before {
        content: "";
        width: 5px;
        height: 5px;
        border-radius: 50%;
    }

    .stage.submitted {
        background: #eef3f8;
        color: #55758d;
    }

    .stage.submitted::before {
        background: #6d8ea5;
    }

    .stage.verification {
        background: #fff5e9;
        color: #976d38;
    }

    .stage.verification::before {
        background: #d19a4d;
    }

    .stage.viability {
        background: #f1eef7;
        color: #77668c;
    }

    .stage.viability::before {
        background: #8c77a3;
    }

    .stage.approval {
        background: #f4efe4;
        color: #876a30;
    }

    .stage.approval::before {
        background: #c89b3c;
    }

    .stage.approved {
        background: #edf7f0;
        color: #397955;
    }

    .stage.approved::before {
        background: #4c9a6c;
    }

    .stage.rejected {
        background: #fff0ef;
        color: #a85f58;
    }

    .stage.rejected::before {
        background: #b96b63;
    }

    .risk-badge {
        display: inline-flex;
        padding: 4px 7px;
        border-radius: 20px;
        font-size: 13px;
        font-weight: 650;
    }

    .risk-low {
        background: #edf7f0;
        color: #397955;
    }

    .risk-medium {
        background: #fff5e9;
        color: #976d38;
    }

    .risk-high {
        background: #fff0ef;
        color: #a85f58;
    }

    .action {
        width: 30px;
        height: 30px;
        border: 1px solid var(--border);
        background: #fff;
        border-radius: 8px;
        display: inline-flex;
        align-items: center;
        justify-content: center;
        color: #6c7771;
        text-decoration: none;
    }

    .action:hover {
        color: var(--primary);
        background: #f7faf8;
    }

    /* =========================================
       FOOTER
    ========================================= */

    .table-footer {
        padding: 14px 18px;
        border-top: 1px solid #eef1ef;
        display: flex;
        justify-content: space-between;
        align-items: center;
        gap: 10px;
    }

    .result {
        color: #6e7672;
        font-size: 13px;
    }

    .pagination {
        display: flex;
        gap: 5px;
    }

    .page-btn {
        min-width: 29px;
        height: 29px;
        border: 1px solid var(--border);
        background: #fff;
        border-radius: 7px;
        color: #69756f;
        font-size: 13px;
    }

    .page-btn.active {
        background: var(--primary);
        border-color: var(--primary);
        color: #fff;
    }

    /* =========================================
       INFO BANNER
    ========================================= */

    .info-banner {
        margin-top: 18px;
        padding: 14px 16px;

        display: flex;
        gap: 10px;

        background: #f7f9f8;
        border: 1px solid #e5ebe8;
        border-radius: 13px;
    }

    .info-banner i {
        color: var(--primary);
        font-size: 17px;
    }

    .info-banner strong {
        display: block;
        font-size: 14px;
    }

    .info-banner p {
        margin: 4px 0 0;
        color: #6b7670;
        font-size: 13px;
        line-height: 1.6;
    }

    @media (max-width: 1100px) {
        .summary-grid {
            grid-template-columns: repeat(3, 1fr);
        }
    }

    @media (max-width: 700px) {
        .page-header {
            flex-direction: column;
            align-items: flex-start;
        }

        .actions {
            width: 100%;
        }

        .actions .ui-btn {
            flex: 1;
        }

        .summary-grid {
            grid-template-columns: repeat(2, 1fr);
        }

        .search {
            min-width: 100%;
        }

        .filter {
            flex: 1;
            min-width: 0;
        }

        .table-footer {
            flex-direction: column;
            align-items: flex-start;
        }
    }

    @media (max-width: 450px) {
        .summary-grid {
            grid-template-columns: 1fr;
        }
    }
</style>
@endpush


@section('content')

<div class="page-header">

    <div>
        <h1>Small Business Assistance</h1>

        <p>
            Manage livelihood applications, viability reviews, approvals,
            agreements, disbursements and business monitoring.
        </p>
    </div>

    <div class="actions">

        <button class="ui-btn">
            <i class="bi bi-download"></i>
            Export
        </button>

        <a
            href="{{ route('business.applications.create') }}"
            class="ui-btn primary"
        >
            <i class="bi bi-plus-lg"></i>
            New Application
        </a>

    </div>

</div>


<!-- SUMMARY -->

<div class="summary-grid">

    <div class="summary-card">
        <div class="summary-label">Total Applications</div>
        <div class="summary-value">72</div>
        <div class="summary-meta">This year</div>
    </div>

    <div class="summary-card">
        <div class="summary-label">Under Review</div>
        <div class="summary-value">14</div>
        <div class="summary-meta">Verification / viability</div>
    </div>

    <div class="summary-card">
        <div class="summary-label">Awaiting Approval</div>
        <div class="summary-value">6</div>
        <div class="summary-meta">Decision required</div>
    </div>

    <div class="summary-card">
        <div class="summary-label">Approved</div>
        <div class="summary-value">41</div>
        <div class="summary-meta">Applications approved</div>
    </div>

    <div class="summary-card">
        <div class="summary-label">Businesses Active</div>
        <div class="summary-value">34</div>
        <div class="summary-meta">Currently monitored</div>
    </div>

</div>


<!-- TABLE -->

<div class="main-card">

    <div class="toolbar">

        <div class="search">

            <i class="bi bi-search"></i>

            <input
                id="businessSearch"
                type="text"
                placeholder="Search applicant, business, application ID..."
            >

        </div>


        <select
            id="stageFilter"
            class="filter"
        >
            <option value="">All Stages</option>
            <option value="submitted">Submitted</option>
            <option value="verification">Verification</option>
            <option value="viability">Viability Review</option>
            <option value="approval">Approval</option>
            <option value="approved">Approved</option>
            <option value="rejected">Rejected</option>
        </select>


        <select
            id="businessTypeFilter"
            class="filter"
        >
            <option value="">All Business Types</option>
            <option value="retail">Retail</option>
            <option value="services">Services</option>
            <option value="agriculture">Agriculture</option>
            <option value="livestock">Livestock</option>
            <option value="craft">Craft / Home Business</option>
        </select>


        <select
            id="riskFilter"
            class="filter"
        >
            <option value="">All Risk Levels</option>
            <option value="low">Low</option>
            <option value="medium">Medium</option>
            <option value="high">High</option>
        </select>

    </div>


    <div class="table-wrap">

        <table class="business-table">

            <thead>

                <tr>
                    <th>Applicant</th>
                    <th>Business</th>
                    <th>Type</th>
                    <th>Requested</th>
                    <th>Stage</th>
                    <th>Risk</th>
                    <th>Submitted</th>
                    <th></th>
                </tr>

            </thead>


            <tbody id="businessBody">


                <!-- 1 -->

                <tr
                    data-stage="viability"
                    data-type="retail"
                    data-risk="medium"
                >

                    <td>

                        <div class="applicant">

                            <div class="avatar">
                                RK
                            </div>

                            <div>

                                <div class="applicant-name">
                                    Rahim Khan
                                </div>

                                <div class="applicant-id">
                                    SBA-00041 · BEN-00063
                                </div>

                            </div>

                        </div>

                    </td>

                    <td>

                        <div class="business-name">
                            Kasso General Store
                        </div>

                        <div class="business-type">
                            Household retail setup
                        </div>

                    </td>

                    <td>
                        Retail
                    </td>

                    <td class="amount">
                        PKR 80,000
                    </td>

                    <td>

                        <span class="stage viability">
                            Viability Review
                        </span>

                    </td>

                    <td>

                        <span class="risk-badge risk-medium">
                            Medium
                        </span>

                    </td>

                    <td>
                        12 Aug 2026
                    </td>

                    <td>

                        <a
                            href="{{ route('business.applications.show', ['id' => 1]) }}"
                            class="action"
                        >
                            <i class="bi bi-eye"></i>
                        </a>

                    </td>

                </tr>


                <!-- 2 -->

                <tr
                    data-stage="verification"
                    data-type="livestock"
                    data-risk="low"
                >

                    <td>

                        <div class="applicant">

                            <div class="avatar">
                                NA
                            </div>

                            <div>

                                <div class="applicant-name">
                                    Noor Alam
                                </div>

                                <div class="applicant-id">
                                    SBA-00040 · BEN-00071
                                </div>

                            </div>

                        </div>

                    </td>

                    <td>

                        <div class="business-name">
                            Small Goat Unit
                        </div>

                        <div class="business-type">
                            Livestock livelihood
                        </div>

                    </td>

                    <td>
                        Livestock
                    </td>

                    <td class="amount">
                        PKR 120,000
                    </td>

                    <td>

                        <span class="stage verification">
                            Verification
                        </span>

                    </td>

                    <td>

                        <span class="risk-badge risk-low">
                            Low
                        </span>

                    </td>

                    <td>
                        10 Aug 2026
                    </td>

                    <td>

                        <a
                            href="{{ route('business.applications.show', ['id' => 2]) }}"
                            class="action"
                        >
                            <i class="bi bi-eye"></i>
                        </a>

                    </td>

                </tr>


                <!-- 3 -->

                <tr
                    data-stage="approval"
                    data-type="services"
                    data-risk="medium"
                >

                    <td>

                        <div class="applicant">

                            <div class="avatar">
                                IH
                            </div>

                            <div>

                                <div class="applicant-name">
                                    Imad Hussain
                                </div>

                                <div class="applicant-id">
                                    SBA-00039 · BEN-00078
                                </div>

                            </div>

                        </div>

                    </td>

                    <td>

                        <div class="business-name">
                            Mobile Repair Service
                        </div>

                        <div class="business-type">
                            Technical services
                        </div>

                    </td>

                    <td>
                        Services
                    </td>

                    <td class="amount">
                        PKR 65,000
                    </td>

                    <td>

                        <span class="stage approval">
                            Approval
                        </span>

                    </td>

                    <td>

                        <span class="risk-badge risk-medium">
                            Medium
                        </span>

                    </td>

                    <td>
                        08 Aug 2026
                    </td>

                    <td>

                        <a
                            href="{{ route('business.applications.show', ['id' => 3]) }}"
                            class="action"
                        >
                            <i class="bi bi-eye"></i>
                        </a>

                    </td>

                </tr>


                <!-- 4 -->

                <tr
                    data-stage="approved"
                    data-type="agriculture"
                    data-risk="low"
                >

                    <td>

                        <div class="applicant">

                            <div class="avatar">
                                AS
                            </div>

                            <div>

                                <div class="applicant-name">
                                    Abdul Samad
                                </div>

                                <div class="applicant-id">
                                    SBA-00038 · BEN-00069
                                </div>

                            </div>

                        </div>

                    </td>

                    <td>

                        <div class="business-name">
                            Vegetable Farming
                        </div>

                        <div class="business-type">
                            Agriculture
                        </div>

                    </td>

                    <td>
                        Agriculture
                    </td>

                    <td class="amount">
                        PKR 150,000
                    </td>

                    <td>

                        <span class="stage approved">
                            Approved
                        </span>

                    </td>

                    <td>

                        <span class="risk-badge risk-low">
                            Low
                        </span>

                    </td>

                    <td>
                        05 Aug 2026
                    </td>

                    <td>

                        <a
                            href="{{ route('business.applications.show', ['id' => 4]) }}"
                            class="action"
                        >
                            <i class="bi bi-eye"></i>
                        </a>

                    </td>

                </tr>


                <!-- 5 -->

                <tr
                    data-stage="submitted"
                    data-type="craft"
                    data-risk="high"
                >

                    <td>

                        <div class="applicant">

                            <div class="avatar">
                                FK
                            </div>

                            <div>

                                <div class="applicant-name">
                                    Farooq Khan
                                </div>

                                <div class="applicant-id">
                                    SBA-00037 · BEN-00083
                                </div>

                            </div>

                        </div>

                    </td>

                    <td>

                        <div class="business-name">
                            Home Tailoring Unit
                        </div>

                        <div class="business-type">
                            Home-based craft
                        </div>

                    </td>

                    <td>
                        Craft / Home
                    </td>

                    <td class="amount">
                        PKR 45,000
                    </td>

                    <td>

                        <span class="stage submitted">
                            Submitted
                        </span>

                    </td>

                    <td>

                        <span class="risk-badge risk-high">
                            High
                        </span>

                    </td>

                    <td>
                        03 Aug 2026
                    </td>

                    <td>

                        <a
                            href="{{ route('business.applications.show', ['id' => 5]) }}"
                            class="action"
                        >
                            <i class="bi bi-eye"></i>
                        </a>

                    </td>

                </tr>

            </tbody>

        </table>

    </div>


    <div class="table-footer">

        <div class="result">
            Showing 1–5 of 72 applications
        </div>

        <div class="pagination">

            <button class="page-btn active">1</button>
            <button class="page-btn">2</button>
            <button class="page-btn">3</button>
            <button class="page-btn">4</button>

            <button class="page-btn">
                <i class="bi bi-chevron-right"></i>
            </button>

        </div>

    </div>

</div>


<div class="info-banner">

    <i class="bi bi-shop"></i>

    <div>

        <strong>
            Sustainable livelihood workflow
        </strong>

        <p>
            Applications move through verification, viability assessment,
            recommendation, approval, agreement, disbursement and monitoring.
            The objective is to help capable people become economically
            self-reliant rather than providing only recurring assistance.
        </p>

    </div>

</div>

@endsection


@push('scripts')
<script>

    const search =
        document.getElementById('businessSearch');

    const stage =
        document.getElementById('stageFilter');

    const type =
        document.getElementById('businessTypeFilter');

    const risk =
        document.getElementById('riskFilter');

    const rows =
        document.querySelectorAll('#businessBody tr');


    function filterBusinessApplications() {

        const query =
            search.value.toLowerCase().trim();

        const stageValue =
            stage.value;

        const typeValue =
            type.value;

        const riskValue =
            risk.value;


        rows.forEach(row => {

            const text =
                row.textContent.toLowerCase();

            const rowStage =
                row.dataset.stage;

            const rowType =
                row.dataset.type;

            const rowRisk =
                row.dataset.risk;


            const matchesSearch =
                !query || text.includes(query);

            const matchesStage =
                !stageValue ||
                rowStage === stageValue;

            const matchesType =
                !typeValue ||
                rowType === typeValue;

            const matchesRisk =
                !riskValue ||
                rowRisk === riskValue;


            row.style.display =
                matchesSearch &&
                matchesStage &&
                matchesType &&
                matchesRisk
                    ? ''
                    : 'none';

        });

    }


    search.addEventListener(
        'input',
        filterBusinessApplications
    );

    stage.addEventListener(
        'change',
        filterBusinessApplications
    );

    type.addEventListener(
        'change',
        filterBusinessApplications
    );

    risk.addEventListener(
        'change',
        filterBusinessApplications
    );

</script>
@endpush