@extends('layouts.app')

@section('title', 'Procurement')

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
    }

    .actions {
        display:flex;
        gap:8px;
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

    .main-card {
        background:#fff;
        border:1px solid var(--border);
        border-radius:14px;
        overflow:hidden;
    }

    .toolbar {
        padding:15px 17px;
        border-bottom:1px solid #eef1ef;
        display:flex;
        gap:9px;
        flex-wrap:wrap;
    }

    .search {
        position:relative;
        flex:1;
        min-width:230px;
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
        min-width:145px;
        border:1px solid var(--border);
        border-radius:9px;
        padding:0 28px 0 12px;
        background:#fff;
        color:#68756f;
        font-size:14px;
        outline:none;
    }

    .table-wrap {
        overflow-x:auto;
    }

    .procurement-table {
        width:100%;
        min-width:1180px;
        border-collapse:collapse;
    }

    .procurement-table th {
        padding:11px 18px;
        background:#fafcfb;
        border-bottom:1px solid #eef1ef;
        color:#96a19b;
        font-size:13px;
        font-weight:700;
        text-transform:uppercase;
        letter-spacing:.6px;
        text-align:left;
        white-space:nowrap;
    }

    .procurement-table td {
        padding:13px 18px;
        border-bottom:1px solid #f0f3f1;
        font-size:13px;
        vertical-align:middle;
    }

    .procurement-table tbody tr:hover {
        background:#fbfcfb;
    }

    .procurement-table tbody tr:last-child td {
        border-bottom:none;
    }

    .request-title {
        font-size:14px;
        font-weight:650;
        color:#46534c;
    }

    .request-id {
        margin-top:3px;
        color:#98a29d;
        font-size:13px;
    }

    .supplier {
        font-size:13px;
        font-weight:600;
        color:#59665f;
    }

    .amount {
        font-weight:700;
        color:#4b5851;
    }

    .amount.warning {
        color:#a8793e;
    }

    .category,
    .status,
    .approval {
        display:inline-flex;
        align-items:center;
        gap:5px;
        padding:5px 8px;
        border-radius:20px;
        font-size:13px;
        font-weight:650;
        white-space:nowrap;
    }

    .category {
        background:#eef3f8;
        color:#55758d;
    }

    .category.project {
        background:#f1eef7;
        color:#77668c;
    }

    .category.welfare {
        background:#edf7f0;
        color:#397955;
    }

    .category.business {
        background:#fff5e9;
        color:#a8793e;
    }

    .status::before,
    .approval::before {
        content:"";
        width:5px;
        height:5px;
        border-radius:50%;
    }

    .status.draft {
        background:#eef3f8;
        color:#55758d;
    }

    .status.draft::before {
        background:#6d8ea5;
    }

    .status.review {
        background:#f1eef7;
        color:#77668c;
    }

    .status.review::before {
        background:#8c77a3;
    }

    .status.approved {
        background:#edf7f0;
        color:#397955;
    }

    .status.approved::before {
        background:#4c9a6c;
    }

    .status.ordered {
        background:#eaf2f6;
        color:#4e758a;
    }

    .status.ordered::before {
        background:#5f889d;
    }

    .status.received {
        background:#edf7f0;
        color:#397955;
    }

    .status.received::before {
        background:#4c9a6c;
    }

    .approval.pending {
        background:#fff5e9;
        color:#a8793e;
    }

    .approval.pending::before {
        background:#d19a4d;
    }

    .approval.approved {
        background:#edf7f0;
        color:#397955;
    }

    .approval.approved::before {
        background:#4c9a6c;
    }

    .action {
        width:30px;
        height:30px;
        border:1px solid var(--border);
        background:#fff;
        border-radius:8px;
        display:inline-flex;
        align-items:center;
        justify-content:center;
        color:#75817b;
        text-decoration:none;
    }

    .action:hover {
        color:var(--primary);
        background:#f7faf8;
    }

    .table-footer {
        padding:14px 18px;
        border-top:1px solid #eef1ef;
        display:flex;
        justify-content:space-between;
        align-items:center;
        gap:10px;
    }

    .result {
        color:#89948f;
        font-size:13px;
    }

    .pagination {
        display:flex;
        gap:5px;
    }

    .page-btn {
        min-width:29px;
        height:29px;
        border:1px solid var(--border);
        background:#fff;
        border-radius:7px;
        color:#69756f;
        font-size:13px;
    }

    .page-btn.active {
        background:var(--primary);
        border-color:var(--primary);
        color:#fff;
    }

    .notice {
        margin-top:18px;
        display:flex;
        gap:10px;
        padding:14px 16px;
        background:#f7f9f8;
        border:1px solid #e5ebe8;
        border-radius:13px;
    }

    .notice i {
        color:var(--primary);
        font-size:16px;
    }

    .notice strong {
        display:block;
        font-size:13px;
    }

    .notice p {
        margin:4px 0 0;
        color:#77837d;
        font-size:13px;
        line-height:1.6;
    }

    @media(max-width:1100px) {
        .summary-grid {
            grid-template-columns:repeat(3,1fr);
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

        .summary-grid {
            grid-template-columns:repeat(2,1fr);
        }

        .search {
            min-width:100%;
        }

        .filter {
            flex:1;
            min-width:0;
        }

        .table-footer {
            flex-direction:column;
            align-items:flex-start;
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

    <div>

        <h1>Procurement</h1>

        <p>
            Manage purchasing requests, suppliers, approvals, orders and receiving records.
        </p>

    </div>

    <div class="actions">

        <button class="ui-btn">
            <i class="bi bi-download"></i>
            Export
        </button>

        <a
            href="{{ route('operations.procurement.create') }}"
            class="ui-btn primary"
        >
            <i class="bi bi-plus-lg"></i>
            New Procurement
        </a>

    </div>

</div>


<!-- SUMMARY -->

<div class="summary-grid">

    <div class="summary-card">
        <div class="summary-label">Total Requests</div>
        <div class="summary-value">42</div>
        <div class="summary-meta">This year</div>
    </div>

    <div class="summary-card">
        <div class="summary-label">Pending Review</div>
        <div class="summary-value">6</div>
        <div class="summary-meta">Awaiting approval</div>
    </div>

    <div class="summary-card">
        <div class="summary-label">Approved</div>
        <div class="summary-value">21</div>
        <div class="summary-meta">Ready / active orders</div>
    </div>

    <div class="summary-card">
        <div class="summary-label">Total Value</div>
        <div class="summary-value">PKR 1.86M</div>
        <div class="summary-meta">Approved / active</div>
    </div>

    <div class="summary-card">
        <div class="summary-label">Received</div>
        <div class="summary-value">14</div>
        <div class="summary-meta">Completed purchases</div>
    </div>

</div>


<div class="main-card">

    <div class="toolbar">

        <div class="search">

            <i class="bi bi-search"></i>

            <input
                id="procurementSearch"
                type="text"
                placeholder="Search request, supplier, project or reference..."
            >

        </div>


        <select
            id="categoryFilter"
            class="filter"
        >
            <option value="">All Categories</option>
            <option value="project">Community Project</option>
            <option value="welfare">Welfare</option>
            <option value="business">Small Business</option>
        </select>


        <select
            id="statusFilter"
            class="filter"
        >
            <option value="">All Statuses</option>
            <option value="draft">Draft</option>
            <option value="review">Under Review</option>
            <option value="approved">Approved</option>
            <option value="ordered">Ordered</option>
            <option value="received">Received</option>
        </select>


        <select
            id="approvalFilter"
            class="filter"
        >
            <option value="">All Approvals</option>
            <option value="pending">Pending</option>
            <option value="approved">Approved</option>
        </select>

    </div>


    <div class="table-wrap">

        <table class="procurement-table">

            <thead>

                <tr>
                    <th>Request</th>
                    <th>Category</th>
                    <th>Supplier</th>
                    <th>Purpose</th>
                    <th>Amount</th>
                    <th>Request Date</th>
                    <th>Status</th>
                    <th>Approval</th>
                    <th></th>
                </tr>

            </thead>


            <tbody id="procurementBody">

                <!-- 1 -->

                <tr
                    data-category="project"
                    data-status="review"
                    data-approval="pending"
                >

                    <td>

                        <div class="request-title">
                            Walking Path Materials
                        </div>

                        <div class="request-id">
                            PRC-00042 · PRJ-00014
                        </div>

                    </td>

                    <td>
                        <span class="category project">
                            Community Project
                        </span>
                    </td>

                    <td class="supplier">
                        Kasso Building Materials
                    </td>

                    <td>
                        Village path improvement
                    </td>

                    <td class="amount warning">
                        PKR 185,000
                    </td>

                    <td>
                        17 Aug 2026
                    </td>

                    <td>
                        <span class="status review">
                            Under Review
                        </span>
                    </td>

                    <td>
                        <span class="approval pending">
                            Pending
                        </span>
                    </td>

                    <td>

                        <a
                            href="{{ route('operations.procurement.show', ['id' => 1]) }}"
                            class="action"
                        >
                            <i class="bi bi-eye"></i>
                        </a>

                    </td>

                </tr>


                <!-- 2 -->

                <tr
                    data-category="welfare"
                    data-status="approved"
                    data-approval="approved"
                >

                    <td>

                        <div class="request-title">
                            Monthly Food Supplies
                        </div>

                        <div class="request-id">
                            PRC-00041 · WLF-00019
                        </div>

                    </td>

                    <td>
                        <span class="category welfare">
                            Welfare
                        </span>
                    </td>

                    <td class="supplier">
                        Kasso Food Supplier
                    </td>

                    <td>
                        Monthly food assistance
                    </td>

                    <td class="amount">
                        PKR 75,000
                    </td>

                    <td>
                        15 Aug 2026
                    </td>

                    <td>
                        <span class="status approved">
                            Approved
                        </span>
                    </td>

                    <td>
                        <span class="approval approved">
                            Approved
                        </span>
                    </td>

                    <td>

                        <a
                            href="{{ route('operations.procurement.show', ['id' => 2]) }}"
                            class="action"
                        >
                            <i class="bi bi-eye"></i>
                        </a>

                    </td>

                </tr>


                <!-- 3 -->

                <tr
                    data-category="business"
                    data-status="ordered"
                    data-approval="approved"
                >

                    <td>

                        <div class="request-title">
                            Grocery Store Equipment
                        </div>

                        <div class="request-id">
                            PRC-00040 · SBA-00041
                        </div>

                    </td>

                    <td>
                        <span class="category business">
                            Small Business
                        </span>
                    </td>

                    <td class="supplier">
                        Local Market Supplies
                    </td>

                    <td>
                        Kasso General Store
                    </td>

                    <td class="amount">
                        PKR 60,000
                    </td>

                    <td>
                        12 Aug 2026
                    </td>

                    <td>
                        <span class="status ordered">
                            Ordered
                        </span>
                    </td>

                    <td>
                        <span class="approval approved">
                            Approved
                        </span>
                    </td>

                    <td>

                        <a
                            href="{{ route('operations.procurement.show', ['id' => 3]) }}"
                            class="action"
                        >
                            <i class="bi bi-eye"></i>
                        </a>

                    </td>

                </tr>


                <!-- 4 -->

                <tr
                    data-category="project"
                    data-status="received"
                    data-approval="approved"
                >

                    <td>

                        <div class="request-title">
                            Water Supply Equipment
                        </div>

                        <div class="request-id">
                            PRC-00039 · PRJ-00011
                        </div>

                    </td>

                    <td>
                        <span class="category project">
                            Community Project
                        </span>
                    </td>

                    <td class="supplier">
                        KPK Water Solutions
                    </td>

                    <td>
                        Community water improvement
                    </td>

                    <td class="amount">
                        PKR 240,000
                    </td>

                    <td>
                        09 Aug 2026
                    </td>

                    <td>
                        <span class="status received">
                            Received
                        </span>
                    </td>

                    <td>
                        <span class="approval approved">
                            Approved
                        </span>
                    </td>

                    <td>

                        <a
                            href="{{ route('operations.procurement.show', ['id' => 4]) }}"
                            class="action"
                        >
                            <i class="bi bi-eye"></i>
                        </a>

                    </td>

                </tr>


                <!-- 5 -->

                <tr
                    data-category="welfare"
                    data-status="draft"
                    data-approval="pending"
                >

                    <td>

                        <div class="request-title">
                            Grave Arrangement Equipment
                        </div>

                        <div class="request-id">
                            PRC-00038 · CMT-00007
                        </div>

                    </td>

                    <td>
                        <span class="category welfare">
                            Welfare
                        </span>
                    </td>

                    <td class="supplier">
                        Local Equipment Supplier
                    </td>

                    <td>
                        Community death-related arrangements
                    </td>

                    <td class="amount warning">
                        PKR 95,000
                    </td>

                    <td>
                        06 Aug 2026
                    </td>

                    <td>
                        <span class="status draft">
                            Draft
                        </span>
                    </td>

                    <td>
                        <span class="approval pending">
                            Pending
                        </span>
                    </td>

                    <td>

                        <a
                            href="{{ route('operations.procurement.show', ['id' => 5]) }}"
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
            Showing 1–5 of 42 procurement requests
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


<div class="notice">

    <i class="bi bi-cart-check"></i>

    <div>

        <strong>
            Procurement control
        </strong>

        <p>
            Procurement requests should remain linked to an approved purpose,
            responsible fund or activity, supplier, approval record and
            supporting quotations or invoices where applicable.
        </p>

    </div>

</div>

@endsection


@push('scripts')
<script>

    const search =
        document.getElementById('procurementSearch');

    const category =
        document.getElementById('categoryFilter');

    const status =
        document.getElementById('statusFilter');

    const approval =
        document.getElementById('approvalFilter');

    const rows =
        document.querySelectorAll('#procurementBody tr');


    function filterProcurement() {

        const query =
            search.value.toLowerCase().trim();

        const categoryValue =
            category.value;

        const statusValue =
            status.value;

        const approvalValue =
            approval.value;


        rows.forEach(row => {

            const text =
                row.textContent.toLowerCase();

            const rowCategory =
                row.dataset.category;

            const rowStatus =
                row.dataset.status;

            const rowApproval =
                row.dataset.approval;


            const matchesSearch =
                !query || text.includes(query);

            const matchesCategory =
                !categoryValue ||
                rowCategory === categoryValue;

            const matchesStatus =
                !statusValue ||
                rowStatus === statusValue;

            const matchesApproval =
                !approvalValue ||
                rowApproval === approvalValue;


            row.style.display =
                matchesSearch &&
                matchesCategory &&
                matchesStatus &&
                matchesApproval
                    ? ''
                    : 'none';

        });

    }


    search.addEventListener(
        'input',
        filterProcurement
    );

    category.addEventListener(
        'change',
        filterProcurement
    );

    status.addEventListener(
        'change',
        filterProcurement
    );

    approval.addEventListener(
        'change',
        filterProcurement
    );

</script>
@endpush