@extends('layouts.app')

@section('title', 'Meetings')

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

    .meeting-table {
        width:100%;
        min-width:1200px;
        border-collapse:collapse;
    }

    .meeting-table th {
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

    .meeting-table td {
        padding:13px 18px;
        border-bottom:1px solid #f0f3f1;
        font-size:13px;
        vertical-align:middle;
    }

    .meeting-table tbody tr:hover {
        background:#fbfcfb;
    }

    .meeting-table tbody tr:last-child td {
        border-bottom:none;
    }

    .meeting-title {
        font-size:14px;
        font-weight:650;
        color:#46534c;
    }

    .meeting-id {
        margin-top:3px;
        color:#98a29d;
        font-size:13px;
    }

    .date-box {
        display:flex;
        align-items:center;
        gap:9px;
    }

    .date-icon {
        width:34px;
        height:34px;
        border-radius:9px;
        background:var(--primary-soft);
        color:var(--primary);
        display:flex;
        align-items:center;
        justify-content:center;
    }

    .date-main {
        font-size:13px;
        font-weight:650;
    }

    .date-sub {
        margin-top:3px;
        color:#98a29d;
        font-size:12px;
    }

    .committee {
        font-size:13px;
        font-weight:600;
    }

    .committee-sub {
        margin-top:3px;
        color:#98a29d;
        font-size:12px;
    }

    .meeting-type,
    .status,
    .attendance {
        display:inline-flex;
        align-items:center;
        gap:5px;
        padding:5px 8px;
        border-radius:20px;
        font-size:13px;
        font-weight:650;
        white-space:nowrap;
    }

    .meeting-type {
        background:#eef3f8;
        color:#55758d;
    }

    .meeting-type.committee {
        background:#f1eef7;
        color:#77668c;
    }

    .meeting-type.general {
        background:#edf7f0;
        color:#397955;
    }

    .status.scheduled {
        background:#eef3f8;
        color:#55758d;
    }

    .status.scheduled::before,
    .status.completed::before,
    .status.cancelled::before,
    .attendance.good::before,
    .attendance.partial::before {
        content:"";
        width:5px;
        height:5px;
        border-radius:50%;
    }

    .status.scheduled::before {
        background:#6d8ea5;
    }

    .status.completed {
        background:#edf7f0;
        color:#397955;
    }

    .status.completed::before {
        background:#4c9a6c;
    }

    .status.cancelled {
        background:#fff0ef;
        color:#a85f58;
    }

    .status.cancelled::before {
        background:#b96b63;
    }

    .attendance.good {
        background:#edf7f0;
        color:#397955;
    }

    .attendance.good::before {
        background:#4c9a6c;
    }

    .attendance.partial {
        background:#fff5e9;
        color:#a8793e;
    }

    .attendance.partial::before {
        background:#d19a4d;
    }

    .participants {
        color:#68756f;
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

        <h1>Meetings</h1>

        <p>
            Schedule, manage and document organizational meetings, committees and decisions.
        </p>

    </div>

    <div class="actions">

        <button class="ui-btn">
            <i class="bi bi-calendar3"></i>
            Calendar
        </button>

        <button class="ui-btn">
            <i class="bi bi-download"></i>
            Export
        </button>

        <a
            href="{{ route('operations.meetings.create') }}"
            class="ui-btn primary"
        >
            <i class="bi bi-plus-lg"></i>
            Schedule Meeting
        </a>

    </div>

</div>


<!-- SUMMARY -->

<div class="summary-grid">

    <div class="summary-card">
        <div class="summary-label">Total Meetings</div>
        <div class="summary-value">38</div>
        <div class="summary-meta">This year</div>
    </div>

    <div class="summary-card">
        <div class="summary-label">Upcoming</div>
        <div class="summary-value">5</div>
        <div class="summary-meta">Next 30 days</div>
    </div>

    <div class="summary-card">
        <div class="summary-label">Completed</div>
        <div class="summary-value">29</div>
        <div class="summary-meta">This year</div>
    </div>

    <div class="summary-card">
        <div class="summary-label">Decisions Recorded</div>
        <div class="summary-value">74</div>
        <div class="summary-meta">Approved resolutions</div>
    </div>

    <div class="summary-card">
        <div class="summary-label">Avg. Attendance</div>
        <div class="summary-value">82%</div>
        <div class="summary-meta">Across completed meetings</div>
    </div>

</div>


<div class="main-card">

    <div class="toolbar">

        <div class="search">

            <i class="bi bi-search"></i>

            <input
                id="meetingSearch"
                type="text"
                placeholder="Search meeting, committee, organizer or topic..."
            >

        </div>


        <select
            id="typeFilter"
            class="filter"
        >
            <option value="">All Types</option>
            <option value="committee">Committee</option>
            <option value="general">General</option>
        </select>


        <select
            id="statusFilter"
            class="filter"
        >
            <option value="">All Statuses</option>
            <option value="scheduled">Scheduled</option>
            <option value="completed">Completed</option>
            <option value="cancelled">Cancelled</option>
        </select>


        <select
            id="attendanceFilter"
            class="filter"
        >
            <option value="">All Attendance</option>
            <option value="good">Good</option>
            <option value="partial">Partial</option>
        </select>

    </div>


    <div class="table-wrap">

        <table class="meeting-table">

            <thead>

                <tr>
                    <th>Meeting</th>
                    <th>Date & Time</th>
                    <th>Committee</th>
                    <th>Type</th>
                    <th>Participants</th>
                    <th>Attendance</th>
                    <th>Status</th>
                    <th></th>
                </tr>

            </thead>


            <tbody id="meetingBody">

                <!-- Meeting 1 -->

                <tr
                    data-type="committee"
                    data-status="scheduled"
                    data-attendance="good"
                >

                    <td>

                        <div class="meeting-title">
                            Monthly Executive Committee Meeting
                        </div>

                        <div class="meeting-id">
                            MTG-00039
                        </div>

                    </td>

                    <td>

                        <div class="date-box">

                            <div class="date-icon">
                                <i class="bi bi-calendar3"></i>
                            </div>

                            <div>

                                <div class="date-main">
                                    24 Aug 2026
                                </div>

                                <div class="date-sub">
                                    06:00 PM · Community Hall
                                </div>

                            </div>

                        </div>

                    </td>

                    <td>

                        <div class="committee">
                            Executive Committee
                        </div>

                        <div class="committee-sub">
                            9 members
                        </div>

                    </td>

                    <td>
                        <span class="meeting-type committee">
                            Committee
                        </span>
                    </td>

                    <td class="participants">
                        9 invited
                    </td>

                    <td>
                        <span class="attendance good">
                            Expected
                        </span>
                    </td>

                    <td>
                        <span class="status scheduled">
                            Scheduled
                        </span>
                    </td>

                    <td>

                        <a
                            href="{{ route('operations.meetings.show', ['id' => 1]) }}"
                            class="action"
                        >
                            <i class="bi bi-eye"></i>
                        </a>

                    </td>

                </tr>


                <!-- Meeting 2 -->

                <tr
                    data-type="committee"
                    data-status="scheduled"
                    data-attendance="good"
                >

                    <td>

                        <div class="meeting-title">
                            Finance & Fund Review
                        </div>

                        <div class="meeting-id">
                            MTG-00038
                        </div>

                    </td>

                    <td>

                        <div class="date-box">

                            <div class="date-icon">
                                <i class="bi bi-calendar3"></i>
                            </div>

                            <div>

                                <div class="date-main">
                                    21 Aug 2026
                                </div>

                                <div class="date-sub">
                                    05:30 PM · Organization Office
                                </div>

                            </div>

                        </div>

                    </td>

                    <td>

                        <div class="committee">
                            Finance Committee
                        </div>

                        <div class="committee-sub">
                            5 members
                        </div>

                    </td>

                    <td>
                        <span class="meeting-type committee">
                            Committee
                        </span>
                    </td>

                    <td class="participants">
                        5 invited
                    </td>

                    <td>
                        <span class="attendance good">
                            Expected
                        </span>
                    </td>

                    <td>
                        <span class="status scheduled">
                            Scheduled
                        </span>
                    </td>

                    <td>

                        <a
                            href="{{ route('operations.meetings.show', ['id' => 2]) }}"
                            class="action"
                        >
                            <i class="bi bi-eye"></i>
                        </a>

                    </td>

                </tr>


                <!-- Meeting 3 -->

                <tr
                    data-type="committee"
                    data-status="completed"
                    data-attendance="good"
                >

                    <td>

                        <div class="meeting-title">
                            Qard & Welfare Case Review
                        </div>

                        <div class="meeting-id">
                            MTG-00037
                        </div>

                    </td>

                    <td>

                        <div class="date-box">

                            <div class="date-icon">
                                <i class="bi bi-calendar3"></i>
                            </div>

                            <div>

                                <div class="date-main">
                                    15 Aug 2026
                                </div>

                                <div class="date-sub">
                                    06:00 PM · Community Hall
                                </div>

                            </div>

                        </div>

                    </td>

                    <td>

                        <div class="committee">
                            Welfare Committee
                        </div>

                        <div class="committee-sub">
                            7 members
                        </div>

                    </td>

                    <td>
                        <span class="meeting-type committee">
                            Committee
                        </span>
                    </td>

                    <td class="participants">
                        6 attended
                    </td>

                    <td>
                        <span class="attendance good">
                            86%
                        </span>
                    </td>

                    <td>
                        <span class="status completed">
                            Completed
                        </span>
                    </td>

                    <td>

                        <a
                            href="{{ route('operations.meetings.show', ['id' => 3]) }}"
                            class="action"
                        >
                            <i class="bi bi-eye"></i>
                        </a>

                    </td>

                </tr>


                <!-- Meeting 4 -->

                <tr
                    data-type="general"
                    data-status="completed"
                    data-attendance="good"
                >

                    <td>

                        <div class="meeting-title">
                            Village Development Planning Session
                        </div>

                        <div class="meeting-id">
                            MTG-00036
                        </div>

                    </td>

                    <td>

                        <div class="date-box">

                            <div class="date-icon">
                                <i class="bi bi-calendar3"></i>
                            </div>

                            <div>

                                <div class="date-main">
                                    10 Aug 2026
                                </div>

                                <div class="date-sub">
                                    07:00 PM · Community Hall
                                </div>

                            </div>

                        </div>

                    </td>

                    <td>

                        <div class="committee">
                            General Assembly
                        </div>

                        <div class="committee-sub">
                            Community meeting
                        </div>

                    </td>

                    <td>
                        <span class="meeting-type general">
                            General
                        </span>
                    </td>

                    <td class="participants">
                        32 attended
                    </td>

                    <td>
                        <span class="attendance good">
                            91%
                        </span>
                    </td>

                    <td>
                        <span class="status completed">
                            Completed
                        </span>
                    </td>

                    <td>

                        <a
                            href="{{ route('operations.meetings.show', ['id' => 4]) }}"
                            class="action"
                        >
                            <i class="bi bi-eye"></i>
                        </a>

                    </td>

                </tr>


                <!-- Meeting 5 -->

                <tr
                    data-type="committee"
                    data-status="cancelled"
                    data-attendance="partial"
                >

                    <td>

                        <div class="meeting-title">
                            Procurement Review Meeting
                        </div>

                        <div class="meeting-id">
                            MTG-00035
                        </div>

                    </td>

                    <td>

                        <div class="date-box">

                            <div class="date-icon">
                                <i class="bi bi-calendar3"></i>
                            </div>

                            <div>

                                <div class="date-main">
                                    06 Aug 2026
                                </div>

                                <div class="date-sub">
                                    05:00 PM · Organization Office
                                </div>

                            </div>

                        </div>

                    </td>

                    <td>

                        <div class="committee">
                            Procurement Committee
                        </div>

                        <div class="committee-sub">
                            6 members
                        </div>

                    </td>

                    <td>
                        <span class="meeting-type committee">
                            Committee
                        </span>
                    </td>

                    <td class="participants">
                        2 expected
                    </td>

                    <td>
                        <span class="attendance partial">
                            Low
                        </span>
                    </td>

                    <td>
                        <span class="status cancelled">
                            Cancelled
                        </span>
                    </td>

                    <td>

                        <a
                            href="{{ route('operations.meetings.show', ['id' => 5]) }}"
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
            Showing 1–5 of 38 meetings
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

    <i class="bi bi-calendar-check"></i>

    <div>

        <strong>
            Meeting management
        </strong>

        <p>
            Meetings provide a structured place for consultation, decisions,
            attendance, minutes and follow-up actions. Important decisions
            should remain documented and traceable to the meeting where they were made.
        </p>

    </div>

</div>

@endsection


@push('scripts')
<script>

    const search =
        document.getElementById('meetingSearch');

    const type =
        document.getElementById('typeFilter');

    const status =
        document.getElementById('statusFilter');

    const attendance =
        document.getElementById('attendanceFilter');

    const rows =
        document.querySelectorAll('#meetingBody tr');


    function filterMeetings() {

        const query =
            search.value.toLowerCase().trim();

        const typeValue =
            type.value;

        const statusValue =
            status.value;

        const attendanceValue =
            attendance.value;


        rows.forEach(row => {

            const text =
                row.textContent.toLowerCase();

            const rowType =
                row.dataset.type;

            const rowStatus =
                row.dataset.status;

            const rowAttendance =
                row.dataset.attendance;


            const matchesSearch =
                !query || text.includes(query);

            const matchesType =
                !typeValue ||
                rowType === typeValue;

            const matchesStatus =
                !statusValue ||
                rowStatus === statusValue;

            const matchesAttendance =
                !attendanceValue ||
                rowAttendance === attendanceValue;


            row.style.display =
                matchesSearch &&
                matchesType &&
                matchesStatus &&
                matchesAttendance
                    ? ''
                    : 'none';

        });

    }


    search.addEventListener(
        'input',
        filterMeetings
    );

    type.addEventListener(
        'change',
        filterMeetings
    );

    status.addEventListener(
        'change',
        filterMeetings
    );

    attendance.addEventListener(
        'change',
        filterMeetings
    );

</script>
@endpush