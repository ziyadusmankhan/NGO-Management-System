@extends('layouts.app')

@section('title', 'Audit Trail')

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

    .audit-table {
        width:100%;
        min-width:1200px;
        border-collapse:collapse;
    }

    .audit-table th {
        padding:11px 17px;
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

    .audit-table td {
        padding:13px 17px;
        border-bottom:1px solid #f0f3f1;
        font-size:13px;
        vertical-align:middle;
    }

    .audit-table tbody tr:hover {
        background:#fbfcfb;
    }

    .audit-table tbody tr:last-child td {
        border-bottom:none;
    }

    .event {
        display:flex;
        align-items:flex-start;
        gap:9px;
    }

    .event-icon {
        width:33px;
        height:33px;
        border-radius:9px;
        background:var(--primary-soft);
        color:var(--primary);
        display:flex;
        align-items:center;
        justify-content:center;
        flex-shrink:0;
        font-size:15px;
    }

    .event-title {
        font-size:13px;
        font-weight:650;
        color:#46534c;
    }

    .event-description {
        margin-top:3px;
        color:#8c9791;
        font-size:12px;
        line-height:1.45;
    }

    .event-id {
        margin-top:3px;
        color:#98a29d;
        font-size:12px;
    }

    .actor {
        display:flex;
        align-items:center;
        gap:8px;
    }

    .avatar {
        width:30px;
        height:30px;
        border-radius:8px;
        background:var(--primary-soft);
        color:var(--primary);
        display:flex;
        align-items:center;
        justify-content:center;
        font-size:13px;
        font-weight:700;
    }

    .actor-name {
        font-size:13px;
        font-weight:650;
    }

    .actor-role {
        margin-top:3px;
        color:#98a29d;
        font-size:12px;
    }

    .module,
    .action-type,
    .result {
        display:inline-flex;
        align-items:center;
        gap:5px;
        padding:5px 8px;
        border-radius:20px;
        font-size:13px;
        font-weight:650;
        white-space:nowrap;
    }

    .module.finance {
        background:#edf7f0;
        color:#397955;
    }

    .module.operations {
        background:#eef3f8;
        color:#55758d;
    }

    .module.records {
        background:#f1eef7;
        color:#77668c;
    }

    .module.organization {
        background:#fff5e9;
        color:#a8793e;
    }

    .action-type.create {
        background:#edf7f0;
        color:#397955;
    }

    .action-type.update {
        background:#eef3f8;
        color:#55758d;
    }

    .action-type.approve {
        background:#f1eef7;
        color:#77668c;
    }

    .action-type.delete {
        background:#fff0ef;
        color:#a85f58;
    }

    .action-type.login {
        background:#faf4e8;
        color:#9b7641;
    }

    .result.success {
        background:#edf7f0;
        color:#397955;
    }

    .result.success::before,
    .result.warning::before {
        content:"";
        width:5px;
        height:5px;
        border-radius:50%;
    }

    .result.success::before {
        background:#4c9a6c;
    }

    .result.warning {
        background:#fff5e9;
        color:#a8793e;
    }

    .result.warning::before {
        background:#d19a4d;
    }

    .timestamp {
        color:#68756f;
        white-space:nowrap;
    }

    .ip {
        color:#89948e;
        font-size:13px;
        white-space:nowrap;
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
        cursor:pointer;
    }

    .action:hover {
        color:var(--primary);
        background:#f7faf8;
    }

    .table-footer {
        padding:14px 17px;
        border-top:1px solid #eef1ef;
        display:flex;
        justify-content:space-between;
        align-items:center;
        gap:10px;
    }

    .result-count {
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

    /* SIDEBAR */

    .side-card {
        padding:18px;
    }

    .side-number {
        text-align:center;
        font-size:30px;
        font-weight:700;
        color:var(--primary);
    }

    .side-label {
        margin-top:3px;
        text-align:center;
        color:#8d9892;
        font-size:13px;
    }

    .side-row {
        display:flex;
        justify-content:space-between;
        gap:12px;
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

    .timeline-item {
        display:flex;
        gap:10px;
        padding-bottom:17px;
        position:relative;
    }

    .timeline-item:last-child {
        padding-bottom:0;
    }

    .timeline-dot {
        width:8px;
        height:8px;
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
        left:3px;
        top:8px;
        background:#dce4e0;
    }

    .timeline-title {
        font-size:13px;
        font-weight:650;
    }

    .timeline-meta {
        margin-top:3px;
        color:#96a19b;
        font-size:12px;
    }

    .timeline-note {
        margin-top:5px;
        color:#75817b;
        font-size:12px;
        line-height:1.5;
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

    @media(max-width:1050px) {
        .layout {
            grid-template-columns:1fr;
        }

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

        <h1>Audit Trail</h1>

        <p>
            Complete record of important system activity, changes, approvals and organizational actions.
        </p>

    </div>

    <div class="actions">

        <button
            class="ui-btn"
            onclick="previewExportAudit()"
        >
            <i class="bi bi-download"></i>
            Export Log
        </button>

        <button
            class="ui-btn primary"
            onclick="previewIntegrity()"
        >
            <i class="bi bi-shield-check"></i>
            Check Integrity
        </button>

    </div>

</div>


<!-- SUMMARY -->

<div class="summary-grid">

    <div class="summary-card">
        <div class="summary-label">Total Events</div>
        <div class="summary-value">2,846</div>
        <div class="summary-meta">All recorded activity</div>
    </div>

    <div class="summary-card">
        <div class="summary-label">Today</div>
        <div class="summary-value">48</div>
        <div class="summary-meta">Events recorded today</div>
    </div>

    <div class="summary-card">
        <div class="summary-label">Updates</div>
        <div class="summary-value">1,284</div>
        <div class="summary-meta">Record modifications</div>
    </div>

    <div class="summary-card">
        <div class="summary-label">Approvals</div>
        <div class="summary-value">426</div>
        <div class="summary-meta">Authorized actions</div>
    </div>

    <div class="summary-card">
        <div class="summary-label">Warnings</div>
        <div class="summary-value">7</div>
        <div class="summary-meta">Require review</div>
    </div>

</div>


<div class="layout">

    <div>

        <div class="card">

            <!-- TOOLBAR -->

            <div class="toolbar">

                <div class="search">

                    <i class="bi bi-search"></i>

                    <input
                        id="auditSearch"
                        type="text"
                        placeholder="Search user, action, record, reference or IP..."
                    >

                </div>


                <select
                    id="moduleFilter"
                    class="filter"
                >

                    <option value="">
                        All Modules
                    </option>

                    <option value="finance">
                        Finance
                    </option>

                    <option value="operations">
                        Operations
                    </option>

                    <option value="records">
                        Records
                    </option>

                    <option value="organization">
                        Organization
                    </option>

                </select>


                <select
                    id="actionFilter"
                    class="filter"
                >

                    <option value="">
                        All Actions
                    </option>

                    <option value="create">
                        Create
                    </option>

                    <option value="update">
                        Update
                    </option>

                    <option value="approve">
                        Approve
                    </option>

                    <option value="delete">
                        Delete
                    </option>

                    <option value="login">
                        Login
                    </option>

                </select>


                <select
                    id="resultFilter"
                    class="filter"
                >

                    <option value="">
                        All Results
                    </option>

                    <option value="success">
                        Success
                    </option>

                    <option value="warning">
                        Warning
                    </option>

                </select>

            </div>


            <!-- TABLE -->

            <div class="table-wrap">

                <table class="audit-table">

                    <thead>

                        <tr>
                            <th>Event</th>
                            <th>User</th>
                            <th>Module</th>
                            <th>Action</th>
                            <th>Result</th>
                            <th>Timestamp</th>
                            <th>IP Address</th>
                            <th></th>
                        </tr>

                    </thead>


                    <tbody id="auditBody">

                        <!-- 1 -->

                        <tr
                            data-module="finance"
                            data-action="create"
                            data-result="success"
                        >

                            <td>

                                <div class="event">

                                    <div class="event-icon">
                                        <i class="bi bi-cash-coin"></i>
                                    </div>

                                    <div>

                                        <div class="event-title">
                                            Donation recorded
                                        </div>

                                        <div class="event-description">
                                            New donation of PKR 100,000 added to General Fund.
                                        </div>

                                        <div class="event-id">
                                            DON-00127
                                        </div>

                                    </div>

                                </div>

                            </td>

                            <td>

                                <div class="actor">

                                    <div class="avatar">
                                        TR
                                    </div>

                                    <div>

                                        <div class="actor-name">
                                            Treasurer
                                        </div>

                                        <div class="actor-role">
                                            Finance
                                        </div>

                                    </div>

                                </div>

                            </td>

                            <td>
                                <span class="module finance">
                                    Finance
                                </span>
                            </td>

                            <td>
                                <span class="action-type create">
                                    Create
                                </span>
                            </td>

                            <td>
                                <span class="result success">
                                    Success
                                </span>
                            </td>

                            <td class="timestamp">
                                18 Aug 2026<br>
                                11:42 AM
                            </td>

                            <td class="ip">
                                192.168.1.20
                            </td>

                            <td>
                                <button
                                    class="action"
                                    onclick="previewEvent('DON-00127')"
                                >
                                    <i class="bi bi-eye"></i>
                                </button>
                            </td>

                        </tr>


                        <!-- 2 -->

                        <tr
                            data-module="operations"
                            data-action="update"
                            data-result="success"
                        >

                            <td>

                                <div class="event">

                                    <div class="event-icon">
                                        <i class="bi bi-check2-square"></i>
                                    </div>

                                    <div>

                                        <div class="event-title">
                                            Task progress updated
                                        </div>

                                        <div class="event-description">
                                            Task progress changed from 45% to 65%.
                                        </div>

                                        <div class="event-id">
                                            TSK-00064
                                        </div>

                                    </div>

                                </div>

                            </td>

                            <td>

                                <div class="actor">

                                    <div class="avatar">
                                        AA
                                    </div>

                                    <div>

                                        <div class="actor-name">
                                            Abid Anwar
                                        </div>

                                        <div class="actor-role">
                                            Project Committee
                                        </div>

                                    </div>

                                </div>

                            </td>

                            <td>
                                <span class="module operations">
                                    Operations
                                </span>
                            </td>

                            <td>
                                <span class="action-type update">
                                    Update
                                </span>
                            </td>

                            <td>
                                <span class="result success">
                                    Success
                                </span>
                            </td>

                            <td class="timestamp">
                                18 Aug 2026<br>
                                10:25 AM
                            </td>

                            <td class="ip">
                                192.168.1.24
                            </td>

                            <td>
                                <button
                                    class="action"
                                    onclick="previewEvent('TSK-00064')"
                                >
                                    <i class="bi bi-eye"></i>
                                </button>
                            </td>

                        </tr>


                        <!-- 3 -->

                        <tr
                            data-module="records"
                            data-action="create"
                            data-result="success"
                        >

                            <td>

                                <div class="event">

                                    <div class="event-icon">
                                        <i class="bi bi-file-earmark-arrow-up"></i>
                                    </div>

                                    <div>

                                        <div class="event-title">
                                            Document uploaded
                                        </div>

                                        <div class="event-description">
                                            New financial document uploaded and linked to donation.
                                        </div>

                                        <div class="event-id">
                                            DOC-00486
                                        </div>

                                    </div>

                                </div>

                            </td>

                            <td>

                                <div class="actor">

                                    <div class="avatar">
                                        TR
                                    </div>

                                    <div>

                                        <div class="actor-name">
                                            Treasurer
                                        </div>

                                        <div class="actor-role">
                                            Finance
                                        </div>

                                    </div>

                                </div>

                            </td>

                            <td>
                                <span class="module records">
                                    Records
                                </span>
                            </td>

                            <td>
                                <span class="action-type create">
                                    Create
                                </span>
                            </td>

                            <td>
                                <span class="result success">
                                    Success
                                </span>
                            </td>

                            <td class="timestamp">
                                18 Aug 2026<br>
                                10:12 AM
                            </td>

                            <td class="ip">
                                192.168.1.20
                            </td>

                            <td>
                                <button
                                    class="action"
                                    onclick="previewEvent('DOC-00486')"
                                >
                                    <i class="bi bi-eye"></i>
                                </button>
                            </td>

                        </tr>


                        <!-- 4 -->

                        <tr
                            data-module="finance"
                            data-action="approve"
                            data-result="success"
                        >

                            <td>

                                <div class="event">

                                    <div class="event-icon">
                                        <i class="bi bi-check-circle"></i>
                                    </div>

                                    <div>

                                        <div class="event-title">
                                            Expense approved
                                        </div>

                                        <div class="event-description">
                                            Welfare expense approved for PKR 35,000.
                                        </div>

                                        <div class="event-id">
                                            EXP-00083
                                        </div>

                                    </div>

                                </div>

                            </td>

                            <td>

                                <div class="actor">

                                    <div class="avatar">
                                        FC
                                    </div>

                                    <div>

                                        <div class="actor-name">
                                            Finance Committee
                                        </div>

                                        <div class="actor-role">
                                            Committee
                                        </div>

                                    </div>

                                </div>

                            </td>

                            <td>
                                <span class="module finance">
                                    Finance
                                </span>
                            </td>

                            <td>
                                <span class="action-type approve">
                                    Approve
                                </span>
                            </td>

                            <td>
                                <span class="result success">
                                    Success
                                </span>
                            </td>

                            <td class="timestamp">
                                17 Aug 2026<br>
                                06:43 PM
                            </td>

                            <td class="ip">
                                192.168.1.31
                            </td>

                            <td>
                                <button
                                    class="action"
                                    onclick="previewEvent('EXP-00083')"
                                >
                                    <i class="bi bi-eye"></i>
                                </button>
                            </td>

                        </tr>


                        <!-- 5 -->

                        <tr
                            data-module="operations"
                            data-action="approve"
                            data-result="warning"
                        >

                            <td>

                                <div class="event">

                                    <div class="event-icon">
                                        <i class="bi bi-cart-check"></i>
                                    </div>

                                    <div>

                                        <div class="event-title">
                                            Procurement approval attempted
                                        </div>

                                        <div class="event-description">
                                            Approval could not be completed because authorization was pending.
                                        </div>

                                        <div class="event-id">
                                            PRC-00042
                                        </div>

                                    </div>

                                </div>

                            </td>

                            <td>

                                <div class="actor">

                                    <div class="avatar">
                                        NA
                                    </div>

                                    <div>

                                        <div class="actor-name">
                                            Naveed Alam
                                        </div>

                                        <div class="actor-role">
                                            Chairman
                                        </div>

                                    </div>

                                </div>

                            </td>

                            <td>
                                <span class="module operations">
                                    Operations
                                </span>
                            </td>

                            <td>
                                <span class="action-type approve">
                                    Approve
                                </span>
                            </td>

                            <td>
                                <span class="result warning">
                                    Warning
                                </span>
                            </td>

                            <td class="timestamp">
                                17 Aug 2026<br>
                                05:28 PM
                            </td>

                            <td class="ip">
                                192.168.1.22
                            </td>

                            <td>
                                <button
                                    class="action"
                                    onclick="previewEvent('PRC-00042')"
                                >
                                    <i class="bi bi-eye"></i>
                                </button>
                            </td>

                        </tr>


                        <!-- 6 -->

                        <tr
                            data-module="organization"
                            data-action="update"
                            data-result="success"
                        >

                            <td>

                                <div class="event">

                                    <div class="event-icon">
                                        <i class="bi bi-people"></i>
                                    </div>

                                    <div>

                                        <div class="event-title">
                                            Member record updated
                                        </div>

                                        <div class="event-description">
                                            Contact information and committee assignment updated.
                                        </div>

                                        <div class="event-id">
                                            MEM-00031
                                        </div>

                                    </div>

                                </div>

                            </td>

                            <td>

                                <div class="actor">

                                    <div class="avatar">
                                        GS
                                    </div>

                                    <div>

                                        <div class="actor-name">
                                            General Secretary
                                        </div>

                                        <div class="actor-role">
                                            Administration
                                        </div>

                                    </div>

                                </div>

                            </td>

                            <td>
                                <span class="module organization">
                                    Organization
                                </span>
                            </td>

                            <td>
                                <span class="action-type update">
                                    Update
                                </span>
                            </td>

                            <td>
                                <span class="result success">
                                    Success
                                </span>
                            </td>

                            <td class="timestamp">
                                17 Aug 2026<br>
                                03:14 PM
                            </td>

                            <td class="ip">
                                192.168.1.18
                            </td>

                            <td>
                                <button
                                    class="action"
                                    onclick="previewEvent('MEM-00031')"
                                >
                                    <i class="bi bi-eye"></i>
                                </button>
                            </td>

                        </tr>


                        <!-- 7 -->

                        <tr
                            data-module="records"
                            data-action="delete"
                            data-result="success"
                        >

                            <td>

                                <div class="event">

                                    <div class="event-icon">
                                        <i class="bi bi-trash"></i>
                                    </div>

                                    <div>

                                        <div class="event-title">
                                            Document archived
                                        </div>

                                        <div class="event-description">
                                            Old document version archived after replacement.
                                        </div>

                                        <div class="event-id">
                                            DOC-00412
                                        </div>

                                    </div>

                                </div>

                            </td>

                            <td>

                                <div class="actor">

                                    <div class="avatar">
                                        GS
                                    </div>

                                    <div>

                                        <div class="actor-name">
                                            General Secretary
                                        </div>

                                        <div class="actor-role">
                                            Administration
                                        </div>

                                    </div>

                                </div>

                            </td>

                            <td>
                                <span class="module records">
                                    Records
                                </span>
                            </td>

                            <td>
                                <span class="action-type delete">
                                    Archive
                                </span>
                            </td>

                            <td>
                                <span class="result success">
                                    Success
                                </span>
                            </td>

                            <td class="timestamp">
                                16 Aug 2026<br>
                                01:42 PM
                            </td>

                            <td class="ip">
                                192.168.1.18
                            </td>

                            <td>
                                <button
                                    class="action"
                                    onclick="previewEvent('DOC-00412')"
                                >
                                    <i class="bi bi-eye"></i>
                                </button>
                            </td>

                        </tr>


                        <!-- 8 -->

                        <tr
                            data-module="organization"
                            data-action="login"
                            data-result="warning"
                        >

                            <td>

                                <div class="event">

                                    <div class="event-icon">
                                        <i class="bi bi-shield-exclamation"></i>
                                    </div>

                                    <div>

                                        <div class="event-title">
                                            Failed login attempt
                                        </div>

                                        <div class="event-description">
                                            Login attempt failed due to invalid credentials.
                                        </div>

                                        <div class="event-id">
                                            AUTH-00873
                                        </div>

                                    </div>

                                </div>

                            </td>

                            <td>

                                <div class="actor">

                                    <div class="avatar">
                                        ??
                                    </div>

                                    <div>

                                        <div class="actor-name">
                                            Unknown User
                                        </div>

                                        <div class="actor-role">
                                            Unauthenticated
                                        </div>

                                    </div>

                                </div>

                            </td>

                            <td>
                                <span class="module organization">
                                    Organization
                                </span>
                            </td>

                            <td>
                                <span class="action-type login">
                                    Login
                                </span>
                            </td>

                            <td>
                                <span class="result warning">
                                    Warning
                                </span>
                            </td>

                            <td class="timestamp">
                                16 Aug 2026<br>
                                09:17 AM
                            </td>

                            <td class="ip">
                                192.168.1.44
                            </td>

                            <td>
                                <button
                                    class="action"
                                    onclick="previewEvent('AUTH-00873')"
                                >
                                    <i class="bi bi-eye"></i>
                                </button>
                            </td>

                        </tr>

                    </tbody>

                </table>

            </div>


            <div class="table-footer">

                <div class="result-count">
                    Showing 1–8 of 2,846 audit events
                </div>

                <div class="pagination">

                    <button class="page-btn active">
                        1
                    </button>

                    <button class="page-btn">
                        2
                    </button>

                    <button class="page-btn">
                        3
                    </button>

                    <button class="page-btn">
                        4
                    </button>

                    <button class="page-btn">
                        <i class="bi bi-chevron-right"></i>
                    </button>

                </div>

            </div>

        </div>

    </div>


    <aside>

        <!-- SUMMARY -->

        <div class="card">

            <div class="side-card">

                <div class="side-number">
                    2,846
                </div>

                <div class="side-label">
                    Total Audit Events
                </div>

                <div style="
                    height:1px;
                    background:#eef1ef;
                    margin:16px 0;
                "></div>

                <div class="side-row">
                    <span>Today</span>
                    <span>48</span>
                </div>

                <div class="side-row">
                    <span>Creates</span>
                    <span>412</span>
                </div>

                <div class="side-row">
                    <span>Updates</span>
                    <span>1,284</span>
                </div>

                <div class="side-row">
                    <span>Approvals</span>
                    <span>426</span>
                </div>

                <div class="side-row">
                    <span>Warnings</span>
                    <span>7</span>
                </div>

            </div>

        </div>


        <!-- ACTIVITY -->

        <div class="card">

            <div class="card-head">

                <div>
                    <strong>Recent Activity</strong>

                    <span>
                        Latest important system events.
                    </span>
                </div>

            </div>

            <div class="side-card">

                <div class="timeline-item">

                    <div class="timeline-dot"></div>

                    <div>

                        <div class="timeline-title">
                            Donation recorded
                        </div>

                        <div class="timeline-meta">
                            11:42 AM · Treasurer
                        </div>

                    </div>

                </div>


                <div class="timeline-item">

                    <div class="timeline-dot"></div>

                    <div>

                        <div class="timeline-title">
                            Task updated
                        </div>

                        <div class="timeline-meta">
                            10:25 AM · Abid Anwar
                        </div>

                    </div>

                </div>


                <div class="timeline-item">

                    <div class="timeline-dot"></div>

                    <div>

                        <div class="timeline-title">
                            Document uploaded
                        </div>

                        <div class="timeline-meta">
                            10:12 AM · Treasurer
                        </div>

                    </div>

                </div>


                <div class="timeline-item">

                    <div class="timeline-dot"></div>

                    <div>

                        <div class="timeline-title">
                            Expense approved
                        </div>

                        <div class="timeline-meta">
                            09:48 AM · Finance Committee
                        </div>

                    </div>

                </div>

            </div>

        </div>


        <!-- INTEGRITY -->

        <div class="card">

            <div class="card-head">

                <div>
                    <strong>Audit Integrity</strong>

                    <span>
                        Illustrative system checks.
                    </span>
                </div>

            </div>

            <div class="side-card">

                <div class="notice">

                    <i class="bi bi-shield-check"></i>

                    <p>
                        Audit records should be append-only and protected
                        from unauthorized modification or deletion.
                    </p>

                </div>


                <div style="
                    margin-top:13px;
                ">

                    <div class="side-row">

                        <span>
                            Event continuity
                        </span>

                        <span>
                            Valid
                        </span>

                    </div>


                    <div class="side-row">

                        <span>
                            Missing events
                        </span>

                        <span>
                            0
                        </span>

                    </div>


                    <div class="side-row">

                        <span>
                            Integrity status
                        </span>

                        <span>
                            Healthy
                        </span>

                    </div>

                </div>

            </div>

        </div>


        <!-- RETENTION -->

        <div class="card">

            <div class="card-head">
                <strong>Audit Policy</strong>
            </div>

            <div class="side-card">

                <div class="side-row">

                    <span>
                        Retention
                    </span>

                    <span>
                        7 Years
                    </span>

                </div>

                <div class="side-row">

                    <span>
                        Deletion
                    </span>

                    <span>
                        Restricted
                    </span>

                </div>

                <div class="side-row">

                    <span>
                        Export
                    </span>

                    <span>
                        Authorized
                    </span>

                </div>

                <div class="side-row">

                    <span>
                        Review
                    </span>

                    <span>
                        Periodic
                    </span>

                </div>

            </div>

        </div>

    </aside>

</div>

@endsection


@push('scripts')
<script>

    const search =
        document.getElementById(
            'auditSearch'
        );

    const moduleFilter =
        document.getElementById(
            'moduleFilter'
        );

    const actionFilter =
        document.getElementById(
            'actionFilter'
        );

    const resultFilter =
        document.getElementById(
            'resultFilter'
        );

    const rows =
        document.querySelectorAll(
            '#auditBody tr'
        );


    function filterAudit() {

        const query =
            search.value
                .toLowerCase()
                .trim();

        const moduleValue =
            moduleFilter.value;

        const actionValue =
            actionFilter.value;

        const resultValue =
            resultFilter.value;


        rows.forEach(row => {

            const text =
                row.textContent
                    .toLowerCase();

            const rowModule =
                row.dataset.module;

            const rowAction =
                row.dataset.action;

            const rowResult =
                row.dataset.result;


            const matchesSearch =
                !query ||
                text.includes(query);

            const matchesModule =
                !moduleValue ||
                rowModule === moduleValue;

            const matchesAction =
                !actionValue ||
                rowAction === actionValue;

            const matchesResult =
                !resultValue ||
                rowResult === resultValue;


            row.style.display =
                matchesSearch &&
                matchesModule &&
                matchesAction &&
                matchesResult
                    ? ''
                    : 'none';

        });

    }


    search.addEventListener(
        'input',
        filterAudit
    );

    moduleFilter.addEventListener(
        'change',
        filterAudit
    );

    actionFilter.addEventListener(
        'change',
        filterAudit
    );

    resultFilter.addEventListener(
        'change',
        filterAudit
    );


    function previewEvent(reference) {

        alert(
            'UI preview only.\n\n' +
            'Detailed audit-event information for ' +
            reference +
            ' will be available when the audit backend is connected.'
        );

    }


    function previewExportAudit() {

        alert(
            'UI preview only.\n\n' +
            'The audit log export will be connected to the backend and authorization system later.'
        );

    }


    function previewIntegrity() {

        alert(
            'UI preview only.\n\n' +
            'Audit integrity verification will be performed by the backend once the audit system is implemented.'
        );

    }

</script>
@endpush