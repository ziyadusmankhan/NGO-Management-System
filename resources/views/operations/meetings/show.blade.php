@extends('layouts.app')

@section('title', 'Meeting Details')

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

    .ui-btn.success {
        background:#edf7f0;
        border-color:#d5e8da;
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

    .status,
    .type-badge,
    .attendance-badge {
        display:inline-flex;
        align-items:center;
        gap:5px;
        padding:5px 8px;
        border-radius:20px;
        font-size:13px;
        font-weight:650;
        white-space:nowrap;
    }

    .status.completed {
        background:#edf7f0;
        color:#397955;
    }

    .status.completed::before,
    .attendance-badge.present::before,
    .attendance-badge.absent::before {
        content:"";
        width:5px;
        height:5px;
        border-radius:50%;
    }

    .status.completed::before {
        background:#4c9a6c;
    }

    .type-badge {
        background:#f1eef7;
        color:#77668c;
    }

    .attendance-badge.present {
        background:#edf7f0;
        color:#397955;
    }

    .attendance-badge.present::before {
        background:#4c9a6c;
    }

    .attendance-badge.absent {
        background:#fff0ef;
        color:#a85f58;
    }

    .attendance-badge.absent::before {
        background:#b96b63;
    }

    .attendance-badge.late {
        background:#fff5e9;
        color:#a8793e;
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
        border-color:#d5e3dd;
    }

    .summary-box label {
        display:block;
        color:#98a29d;
        font-size:13px;
        text-transform:uppercase;
        letter-spacing:.5px;
    }

    .summary-box strong {
        display:block;
        margin-top:5px;
        font-size:15px;
    }

    .summary-box.highlight strong {
        color:var(--primary);
    }

    /* DESCRIPTION */

    .description {
        color:#66736c;
        font-size:13px;
        line-height:1.75;
    }

    /* AGENDA */

    .agenda-item {
        display:grid;
        grid-template-columns:35px minmax(0,1fr) 150px;
        gap:11px;
        align-items:start;
        padding:12px 0;
        border-bottom:1px solid #f0f3f1;
    }

    .agenda-item:last-child {
        border-bottom:none;
    }

    .agenda-number {
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

    .agenda-topic {
        font-size:14px;
        font-weight:650;
    }

    .agenda-note {
        margin-top:4px;
        color:#89948e;
        font-size:13px;
        line-height:1.5;
    }

    .agenda-owner {
        color:#7b8781;
        font-size:13px;
        text-align:right;
    }

    /* ATTENDANCE */

    .attendance-table {
        width:100%;
        min-width:620px;
        border-collapse:collapse;
    }

    .attendance-table th {
        padding:10px;
        background:#fafcfb;
        border-bottom:1px solid #eef1ef;
        color:#96a19b;
        font-size:13px;
        text-transform:uppercase;
        letter-spacing:.5px;
        text-align:left;
    }

    .attendance-table td {
        padding:11px 10px;
        border-bottom:1px solid #f0f3f1;
        font-size:13px;
    }

    .attendance-table tr:last-child td {
        border-bottom:none;
    }

    .person {
        display:flex;
        align-items:center;
        gap:9px;
    }

    .avatar {
        width:32px;
        height:32px;
        border-radius:9px;
        background:var(--primary-soft);
        color:var(--primary);
        display:flex;
        align-items:center;
        justify-content:center;
        font-size:13px;
        font-weight:700;
    }

    .person-name {
        font-size:13px;
        font-weight:650;
    }

    .person-role {
        margin-top:3px;
        color:#98a29d;
        font-size:12px;
    }

    .table-wrap {
        overflow-x:auto;
    }

    /* DECISIONS */

    .decision {
        padding:13px;
        margin-bottom:9px;
        border:1px solid #edf1ee;
        background:#fafcfb;
        border-radius:10px;
    }

    .decision:last-child {
        margin-bottom:0;
    }

    .decision-top {
        display:flex;
        justify-content:space-between;
        gap:10px;
        align-items:flex-start;
    }

    .decision-number {
        color:var(--primary);
        font-size:13px;
        font-weight:700;
    }

    .decision-title {
        margin-top:5px;
        font-size:14px;
        font-weight:650;
    }

    .decision-text {
        margin-top:6px;
        color:#6e7a73;
        font-size:13px;
        line-height:1.6;
    }

    .decision-status {
        padding:5px 8px;
        border-radius:20px;
        background:#edf7f0;
        color:#397955;
        font-size:13px;
        font-weight:650;
        white-space:nowrap;
    }

    /* ACTION ITEMS */

    .action-item {
        display:grid;
        grid-template-columns:24px minmax(0,1fr) 120px 95px;
        gap:10px;
        align-items:center;
        padding:11px 0;
        border-bottom:1px solid #f0f3f1;
    }

    .action-item:last-child {
        border-bottom:none;
    }

    .action-check {
        width:23px;
        height:23px;
        border-radius:7px;
        background:var(--primary-soft);
        color:var(--primary);
        display:flex;
        align-items:center;
        justify-content:center;
        font-size:13px;
    }

    .action-title {
        font-size:13px;
        font-weight:650;
    }

    .action-owner,
    .action-due {
        color:#7e8983;
        font-size:13px;
    }

    /* DOCUMENTS */

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
        width:34px;
        height:34px;
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
        font-size:13px;
    }

    .document-info span {
        display:block;
        margin-top:3px;
        color:#96a19b;
        font-size:13px;
    }

    .doc-btn {
        width:29px;
        height:29px;
        border:1px solid var(--border);
        background:#fff;
        border-radius:7px;
        color:#748079;
    }

    /* TIMELINE */

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
        font-size:14px;
        font-weight:650;
    }

    .timeline-meta {
        margin-top:3px;
        color:#929d97;
        font-size:13px;
    }

    .timeline-note {
        margin-top:6px;
        color:#6d7972;
        font-size:13px;
        line-height:1.5;
    }

    /* SIDEBAR */

    .side-number {
        text-align:center;
        font-size:30px;
        font-weight:700;
        color:var(--primary);
    }

    .side-label {
        text-align:center;
        margin-top:3px;
        color:#8d9892;
        font-size:13px;
    }

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
        .agenda-item,
        .action-item {
            grid-template-columns:1fr;
        }

        .agenda-owner {
            text-align:left;
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
                MTG-00037
            </div>

            <div class="meta">
                Qard & Welfare Case Review · Welfare Committee · 15 Aug 2026
            </div>

        </div>

    </div>

    <div class="actions">

        <a
            href="{{ route('operations.meetings.index') }}"
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

        <a
            href="{{ route('operations.meetings.create') }}"
            class="ui-btn primary"
        >
            <i class="bi bi-pencil"></i>
            Edit
        </a>

    </div>

</div>


<div class="layout">

    <div>

        <!-- OVERVIEW -->

        <div class="card">

            <div class="card-head">

                <div>
                    <strong>Meeting Overview</strong>

                    <span>
                        Meeting information, status and attendance.
                    </span>
                </div>

                <span class="status completed">
                    Completed
                </span>

            </div>

            <div class="card-body">

                <div class="summary-grid">

                    <div class="summary-box highlight">
                        <label>Meeting Date</label>
                        <strong>15 Aug 2026</strong>
                    </div>

                    <div class="summary-box">
                        <label>Time</label>
                        <strong>06:00 PM</strong>
                    </div>

                    <div class="summary-box">
                        <label>Duration</label>
                        <strong>1 hr 20 min</strong>
                    </div>

                    <div class="summary-box">
                        <label>Type</label>
                        <strong>Committee</strong>
                    </div>

                    <div class="summary-box">
                        <label>Committee</label>
                        <strong>Welfare Committee</strong>
                    </div>

                    <div class="summary-box">
                        <label>Invited</label>
                        <strong>7</strong>
                    </div>

                    <div class="summary-box">
                        <label>Attended</label>
                        <strong>6</strong>
                    </div>

                    <div class="summary-box">
                        <label>Decisions</label>
                        <strong>4</strong>
                    </div>

                </div>

            </div>

        </div>


        <!-- PURPOSE -->

        <div class="card">

            <div class="card-head">

                <div>
                    <strong>Meeting Purpose</strong>

                    <span>
                        Reason the meeting was held.
                    </span>
                </div>

                <span class="type-badge">
                    Welfare Committee
                </span>

            </div>

            <div class="card-body">

                <div class="description">

                    The meeting was conducted to review pending welfare
                    applications and Qard-e-Hasana cases, verify beneficiary
                    circumstances, review repayment progress, and determine
                    which cases should proceed to the next stage.

                </div>

            </div>

        </div>


        <!-- AGENDA -->

        <div class="card">

            <div class="card-head">

                <div>
                    <strong>Agenda</strong>

                    <span>
                        Topics discussed during the meeting.
                    </span>
                </div>

            </div>

            <div class="card-body">

                <div class="agenda-item">

                    <div class="agenda-number">
                        1
                    </div>

                    <div>

                        <div class="agenda-topic">
                            Review previous meeting decisions
                        </div>

                        <div class="agenda-note">
                            Follow-up on outstanding decisions.
                        </div>

                    </div>

                    <div class="agenda-owner">
                        Secretary
                    </div>

                </div>


                <div class="agenda-item">

                    <div class="agenda-number">
                        2
                    </div>

                    <div>

                        <div class="agenda-topic">
                            Review welfare applications
                        </div>

                        <div class="agenda-note">
                            Verify vulnerable households and current needs.
                        </div>

                    </div>

                    <div class="agenda-owner">
                        Welfare Committee
                    </div>

                </div>


                <div class="agenda-item">

                    <div class="agenda-number">
                        3
                    </div>

                    <div>

                        <div class="agenda-topic">
                            Review Qard-e-Hasana cases
                        </div>

                        <div class="agenda-note">
                            Discuss new applications, repayments and recoveries.
                        </div>

                    </div>

                    <div class="agenda-owner">
                        Finance Committee
                    </div>

                </div>


                <div class="agenda-item">

                    <div class="agenda-number">
                        4
                    </div>

                    <div>

                        <div class="agenda-topic">
                            Approve next actions
                        </div>

                        <div class="agenda-note">
                            Assign responsible people and follow-up deadlines.
                        </div>

                    </div>

                    <div class="agenda-owner">
                        Chairperson
                    </div>

                </div>

            </div>

        </div>


        <!-- ATTENDANCE -->

        <div class="card">

            <div class="card-head">

                <div>
                    <strong>Attendance</strong>

                    <span>
                        Attendance record for invited participants.
                    </span>
                </div>

                <span class="attendance-badge present">
                    86% Attendance
                </span>

            </div>

            <div class="card-body" style="padding:0;">

                <div class="table-wrap">

                    <table class="attendance-table">

                        <thead>

                            <tr>
                                <th>Participant</th>
                                <th>Role</th>
                                <th>Attendance</th>
                                <th>Check-in</th>
                                <th>Notes</th>
                            </tr>

                        </thead>

                        <tbody>

                            <tr>

                                <td>

                                    <div class="person">

                                        <div class="avatar">
                                            NA
                                        </div>

                                        <div>

                                            <div class="person-name">
                                                Naveed Alam
                                            </div>

                                            <div class="person-role">
                                                Chairman
                                            </div>

                                        </div>

                                    </div>

                                </td>

                                <td>
                                    Committee Chair
                                </td>

                                <td>
                                    <span class="attendance-badge present">
                                        Present
                                    </span>
                                </td>

                                <td>
                                    05:57 PM
                                </td>

                                <td>
                                    On time
                                </td>

                            </tr>


                            <tr>

                                <td>

                                    <div class="person">

                                        <div class="avatar">
                                            AA
                                        </div>

                                        <div>

                                            <div class="person-name">
                                                Abid Anwar
                                            </div>

                                            <div class="person-role">
                                                Member
                                            </div>

                                        </div>

                                    </div>

                                </td>

                                <td>
                                    Committee Member
                                </td>

                                <td>
                                    <span class="attendance-badge present">
                                        Present
                                    </span>
                                </td>

                                <td>
                                    06:01 PM
                                </td>

                                <td>
                                    — 
                                </td>

                            </tr>


                            <tr>

                                <td>

                                    <div class="person">

                                        <div class="avatar">
                                            TR
                                        </div>

                                        <div>

                                            <div class="person-name">
                                                Treasurer
                                            </div>

                                            <div class="person-role">
                                                Finance
                                            </div>

                                        </div>

                                    </div>

                                </td>

                                <td>
                                    Treasurer
                                </td>

                                <td>
                                    <span class="attendance-badge present">
                                        Present
                                    </span>
                                </td>

                                <td>
                                    05:58 PM
                                </td>

                                <td>
                                    — 
                                </td>

                            </tr>


                            <tr>

                                <td>

                                    <div class="person">

                                        <div class="avatar">
                                            SA
                                        </div>

                                        <div>

                                            <div class="person-name">
                                                Salman Ali
                                            </div>

                                            <div class="person-role">
                                                Member
                                            </div>

                                        </div>

                                    </div>

                                </td>

                                <td>
                                    Committee Member
                                </td>

                                <td>
                                    <span class="attendance-badge present">
                                        Present
                                    </span>
                                </td>

                                <td>
                                    06:04 PM
                                </td>

                                <td>
                                    — 
                                </td>

                            </tr>


                            <tr>

                                <td>

                                    <div class="person">

                                        <div class="avatar">
                                            FK
                                        </div>

                                        <div>

                                            <div class="person-name">
                                                Farid Khan
                                            </div>

                                            <div class="person-role">
                                                Member
                                            </div>

                                        </div>

                                    </div>

                                </td>

                                <td>
                                    Committee Member
                                </td>

                                <td>
                                    <span class="attendance-badge absent">
                                        Absent
                                    </span>
                                </td>

                                <td>
                                    —
                                </td>

                                <td>
                                    Prior notice
                                </td>

                            </tr>

                        </tbody>

                    </table>

                </div>

            </div>

        </div>


        <!-- DECISIONS -->

        <div class="card">

            <div class="card-head">

                <div>
                    <strong>Decisions</strong>

                    <span>
                        Decisions recorded during the meeting.
                    </span>
                </div>

            </div>

            <div class="card-body">

                <div class="decision">

                    <div class="decision-top">

                        <div>

                            <div class="decision-number">
                                DEC-001
                            </div>

                            <div class="decision-title">
                                Approve two welfare applications
                            </div>

                        </div>

                        <span class="decision-status">
                            Approved
                        </span>

                    </div>

                    <div class="decision-text">
                        Two verified households were approved for immediate
                        assistance from the applicable welfare fund.
                    </div>

                </div>


                <div class="decision">

                    <div class="decision-top">

                        <div>

                            <div class="decision-number">
                                DEC-002
                            </div>

                            <div class="decision-title">
                                Approve one Qard-e-Hasana application
                            </div>

                        </div>

                        <span class="decision-status">
                            Approved
                        </span>

                    </div>

                    <div class="decision-text">
                        The applicant was approved subject to the agreed
                        repayment schedule and required documentation.
                    </div>

                </div>


                <div class="decision">

                    <div class="decision-top">

                        <div>

                            <div class="decision-number">
                                DEC-003
                            </div>

                            <div class="decision-title">
                                Continue monitoring outstanding Qard cases
                            </div>

                        </div>

                        <span class="decision-status">
                            Approved
                        </span>

                    </div>

                    <div class="decision-text">
                        Existing Qard cases will continue to be monitored
                        through repayment and recovery records.
                    </div>

                </div>


                <div class="decision">

                    <div class="decision-top">

                        <div>

                            <div class="decision-number">
                                DEC-004
                            </div>

                            <div class="decision-title">
                                Schedule follow-up beneficiary verification
                            </div>

                        </div>

                        <span class="decision-status">
                            Approved
                        </span>

                    </div>

                    <div class="decision-text">
                        Follow-up verification will be completed before the
                        next review meeting.
                    </div>

                </div>

            </div>

        </div>


        <!-- ACTION ITEMS -->

        <div class="card">

            <div class="card-head">

                <div>
                    <strong>Action Items</strong>

                    <span>
                        Follow-up work created from meeting decisions.
                    </span>
                </div>

                <button class="ui-btn primary">
                    <i class="bi bi-plus-lg"></i>
                    Add Action
                </button>

            </div>

            <div class="card-body">

                <div class="action-item">

                    <div class="action-check">
                        <i class="bi bi-check"></i>
                    </div>

                    <div class="action-title">
                        Complete beneficiary verification
                    </div>

                    <div class="action-owner">
                        Naveed Alam
                    </div>

                    <div class="action-due">
                        20 Aug 2026
                    </div>

                </div>


                <div class="action-item">

                    <div class="action-check">
                        <i class="bi bi-clock"></i>
                    </div>

                    <div class="action-title">
                        Prepare Qard approval documents
                    </div>

                    <div class="action-owner">
                        Treasurer
                    </div>

                    <div class="action-due">
                        21 Aug 2026
                    </div>

                </div>


                <div class="action-item">

                    <div class="action-check">
                        <i class="bi bi-clock"></i>
                    </div>

                    <div class="action-title">
                        Schedule next welfare review
                    </div>

                    <div class="action-owner">
                        Secretary
                    </div>

                    <div class="action-due">
                        25 Aug 2026
                    </div>

                </div>

            </div>

        </div>


        <!-- MINUTES -->

        <div class="card">

            <div class="card-head">

                <div>
                    <strong>Meeting Minutes</strong>

                    <span>
                        Official record of the meeting discussion.
                    </span>
                </div>

                <button class="ui-btn">
                    <i class="bi bi-download"></i>
                    Download
                </button>

            </div>

            <div class="card-body">

                <div class="description">

                    The committee reviewed the outstanding welfare and
                    Qard-e-Hasana applications. Beneficiary verification
                    records were considered, and approved cases were
                    identified for the next stage.

                    <br><br>

                    The committee emphasized proper documentation,
                    responsible fund usage and continued monitoring of
                    Qard repayments. Follow-up tasks were assigned to the
                    relevant members.

                    <br><br>

                    The meeting concluded with agreement on the recorded
                    decisions and next actions.

                </div>

            </div>

        </div>


        <!-- DOCUMENTS -->

        <div class="card">

            <div class="card-head">

                <div>
                    <strong>Meeting Documents</strong>

                    <span>
                        Minutes, agenda and supporting records.
                    </span>
                </div>

            </div>

            <div class="card-body">

                <div class="document-row">

                    <div class="document-icon">
                        <i class="bi bi-file-earmark-pdf"></i>
                    </div>

                    <div class="document-info">

                        <strong>
                            Official Meeting Minutes
                        </strong>

                        <span>
                            PDF · 15 Aug 2026
                        </span>

                    </div>

                    <button class="doc-btn">
                        <i class="bi bi-eye"></i>
                    </button>

                    <button class="doc-btn">
                        <i class="bi bi-download"></i>
                    </button>

                </div>


                <div class="document-row">

                    <div class="document-icon">
                        <i class="bi bi-file-earmark-text"></i>
                    </div>

                    <div class="document-info">

                        <strong>
                            Meeting Agenda
                        </strong>

                        <span>
                            4 agenda items
                        </span>

                    </div>

                    <button class="doc-btn">
                        <i class="bi bi-eye"></i>
                    </button>

                </div>


                <div class="document-row">

                    <div class="document-icon">
                        <i class="bi bi-paperclip"></i>
                    </div>

                    <div class="document-info">

                        <strong>
                            Beneficiary Review Sheet
                        </strong>

                        <span>
                            Supporting document
                        </span>

                    </div>

                    <button class="doc-btn">
                        <i class="bi bi-eye"></i>
                    </button>

                </div>

            </div>

        </div>


        <!-- ACTIVITY -->

        <div class="card">

            <div class="card-head">

                <div>
                    <strong>Meeting Activity</strong>

                    <span>
                        Complete meeting lifecycle.
                    </span>
                </div>

            </div>

            <div class="card-body">

                <div class="timeline-item">

                    <div class="timeline-dot"></div>

                    <div>

                        <div class="timeline-title">
                            Meeting minutes finalized
                        </div>

                        <div class="timeline-meta">
                            16 Aug 2026 · Secretary
                        </div>

                    </div>

                </div>


                <div class="timeline-item">

                    <div class="timeline-dot"></div>

                    <div>

                        <div class="timeline-title">
                            Decisions recorded
                        </div>

                        <div class="timeline-meta">
                            15 Aug 2026 · Chairman
                        </div>

                        <div class="timeline-note">
                            Four decisions and three follow-up actions were recorded.
                        </div>

                    </div>

                </div>


                <div class="timeline-item">

                    <div class="timeline-dot"></div>

                    <div>

                        <div class="timeline-title">
                            Meeting completed
                        </div>

                        <div class="timeline-meta">
                            15 Aug 2026 · 07:20 PM
                        </div>

                    </div>

                </div>


                <div class="timeline-item">

                    <div class="timeline-dot"></div>

                    <div>

                        <div class="timeline-title">
                            Meeting started
                        </div>

                        <div class="timeline-meta">
                            15 Aug 2026 · 06:00 PM
                        </div>

                    </div>

                </div>


                <div class="timeline-item">

                    <div class="timeline-dot"></div>

                    <div>

                        <div class="timeline-title">
                            Meeting scheduled
                        </div>

                        <div class="timeline-meta">
                            10 Aug 2026 · Secretary
                        </div>

                    </div>

                </div>

            </div>

        </div>

    </div>


    <aside>

        <div class="card">

            <div class="card-body">

                <div class="side-number">
                    86%
                </div>

                <div class="side-label">
                    Attendance
                </div>

                <div style="
                    height:1px;
                    background:#eef1ef;
                    margin:16px 0;
                "></div>

                <div class="side-row">
                    <span>Status</span>
                    <span>Completed</span>
                </div>

                <div class="side-row">
                    <span>Type</span>
                    <span>Committee</span>
                </div>

                <div class="side-row">
                    <span>Committee</span>
                    <span>Welfare Committee</span>
                </div>

                <div class="side-row">
                    <span>Invited</span>
                    <span>7</span>
                </div>

                <div class="side-row">
                    <span>Attended</span>
                    <span>6</span>
                </div>

                <div class="side-row">
                    <span>Decisions</span>
                    <span>4</span>
                </div>

                <div class="side-row">
                    <span>Action Items</span>
                    <span>3</span>
                </div>

            </div>

        </div>


        <div class="card">

            <div class="card-head">
                <strong>Meeting Record</strong>
            </div>

            <div class="card-body">

                <div class="notice">

                    <i class="bi bi-shield-check"></i>

                    <p>
                        Attendance, minutes, decisions and follow-up actions
                        provide a traceable record of organizational consultation
                        and decision-making.
                    </p>

                </div>

            </div>

        </div>


        <div class="card">

            <div class="card-head">
                <strong>Linked Records</strong>
            </div>

            <div class="card-body">

                <div class="document-row">

                    <div class="document-icon">
                        <i class="bi bi-check2-square"></i>
                    </div>

                    <div class="document-info">

                        <strong>
                            3 Follow-up Tasks
                        </strong>

                        <span>
                            Created from meeting decisions
                        </span>

                    </div>

                    <button class="doc-btn">
                        <i class="bi bi-arrow-right"></i>
                    </button>

                </div>


                <div class="document-row">

                    <div class="document-icon">
                        <i class="bi bi-wallet2"></i>
                    </div>

                    <div class="document-info">

                        <strong>
                            Qard & Welfare Cases
                        </strong>

                        <span>
                            Reviewed during meeting
                        </span>

                    </div>

                    <button class="doc-btn">
                        <i class="bi bi-arrow-right"></i>
                    </button>

                </div>

            </div>

        </div>

    </aside>

</div>

@endsection