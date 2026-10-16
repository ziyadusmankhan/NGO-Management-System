@extends('layouts.app')

@section('title', 'Schedule Meeting')

@push('styles')
<style>
    .page-header {
        display:flex;
        justify-content:space-between;
        align-items:flex-start;
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
        padding:17px 20px;
        border-bottom:1px solid #eef1ef;
    }

    .card-head strong {
        font-size:15px;
    }

    .card-head span {
        display:block;
        margin-top:4px;
        color:#929d97;
        font-size:13px;
        line-height:1.5;
    }

    .card-body {
        padding:20px;
    }

    .grid {
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
        font-size:14px;
        font-weight:650;
    }

    .required {
        color:#b75d4d;
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
        font-size:14px;
        outline:none;
    }

    textarea.control {
        height:auto;
        min-height:110px;
        padding:11px 12px;
        resize:vertical;
        line-height:1.6;
    }

    .control:focus,
    .select:focus {
        border-color:#b8cbc1;
        box-shadow:0 0 0 3px rgba(23,63,53,.055);
    }

    .help {
        margin-top:5px;
        color:#98a29d;
        font-size:13px;
        line-height:1.5;
    }

    /* TYPE */

    .type-grid {
        display:grid;
        grid-template-columns:repeat(3,minmax(0,1fr));
        gap:9px;
    }

    .type-option {
        position:relative;
    }

    .type-option input {
        position:absolute;
        opacity:0;
        pointer-events:none;
    }

    .type-option label {
        min-height:72px;
        padding:10px;
        border:1px solid var(--border);
        border-radius:10px;
        display:flex;
        flex-direction:column;
        align-items:center;
        justify-content:center;
        text-align:center;
        gap:6px;
        cursor:pointer;
    }

    .type-option label i {
        font-size:17px;
    }

    .type-option label strong {
        font-size:13px;
    }

    .type-option label span {
        color:#929d97;
        font-size:12px;
    }

    .type-option input:checked + label {
        background:var(--primary-soft);
        border-color:#c7d8d0;
        color:var(--primary);
    }

    /* DATE/TIME */

    .datetime-grid {
        display:grid;
        grid-template-columns:1fr 1fr 1fr;
        gap:10px;
    }

    .datetime-box {
        padding:12px;
        background:#fafcfb;
        border:1px solid #edf1ee;
        border-radius:10px;
    }

    .datetime-box label {
        display:block;
        color:#98a29d;
        font-size:13px;
        text-transform:uppercase;
    }

    .datetime-box strong {
        display:block;
        margin-top:5px;
        font-size:14px;
        color:#46534c;
    }

    /* PARTICIPANTS */

    .participant-list {
        display:flex;
        flex-direction:column;
        gap:9px;
        max-height:320px;
        overflow:auto;
        padding-right:3px;
    }

    .participant {
        display:flex;
        align-items:center;
        gap:10px;
        padding:10px;
        border:1px solid #edf1ee;
        background:#fafcfb;
        border-radius:9px;
    }

    .participant input {
        accent-color:var(--primary);
    }

    .avatar {
        width:34px;
        height:34px;
        border-radius:9px;
        background:var(--primary-soft);
        color:var(--primary);
        display:flex;
        align-items:center;
        justify-content:center;
        font-size:13px;
        font-weight:700;
        flex-shrink:0;
    }

    .participant-info {
        flex:1;
    }

    .participant-name {
        font-size:13px;
        font-weight:650;
    }

    .participant-role {
        margin-top:3px;
        color:#98a29d;
        font-size:13px;
    }

    .participant-badge {
        padding:4px 7px;
        border-radius:20px;
        background:#eef3f8;
        color:#55758d;
        font-size:12px;
        font-weight:650;
    }

    /* AGENDA */

    .agenda-row {
        display:grid;
        grid-template-columns:35px minmax(0,1fr) 120px 34px;
        gap:8px;
        align-items:center;
        margin-bottom:9px;
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

    .agenda-input {
        width:100%;
        height:37px;
        border:1px solid var(--border);
        border-radius:8px;
        padding:0 9px;
        font-size:13px;
        outline:none;
    }

    .agenda-input:focus {
        border-color:#b8cbc1;
    }

    .agenda-remove {
        width:30px;
        height:30px;
        border:1px solid #ead8d5;
        background:#fff;
        color:#a85f58;
        border-radius:7px;
        cursor:pointer;
    }

    /* CONTROLS */

    .checklist {
        display:flex;
        flex-direction:column;
        gap:9px;
    }

    .check-item {
        display:flex;
        align-items:flex-start;
        gap:9px;
        padding:10px;
        border:1px solid #edf1ee;
        background:#fafcfb;
        border-radius:9px;
    }

    .check-item input {
        margin-top:1px;
        accent-color:var(--primary);
    }

    .check-item strong {
        display:block;
        font-size:13px;
    }

    .check-item span {
        display:block;
        margin-top:3px;
        color:#7f8b84;
        font-size:13px;
        line-height:1.5;
    }

    /* ATTACHMENT */

    .upload {
        border:1px dashed #cbd6d0;
        border-radius:11px;
        padding:23px;
        text-align:center;
        background:#fbfcfb;
        cursor:pointer;
    }

    .upload i {
        font-size:24px;
        color:var(--primary);
    }

    .upload strong {
        display:block;
        margin-top:8px;
        font-size:14px;
    }

    .upload span {
        display:block;
        margin-top:4px;
        color:#96a19b;
        font-size:13px;
    }

    /* PREVIEW */

    .preview {
        background:linear-gradient(145deg,#f8faf9,#f2f6f4);
        border:1px solid var(--border);
        border-radius:14px;
        padding:20px;
        margin-bottom:18px;
    }

    .preview-label {
        color:#93a09a;
        font-size:13px;
        text-transform:uppercase;
        letter-spacing:.8px;
    }

    .preview-icon {
        width:60px;
        height:60px;
        margin:15px 0 11px;
        border-radius:17px;
        background:var(--primary);
        color:#fff;
        display:flex;
        align-items:center;
        justify-content:center;
        font-size:20px;
    }

    .preview-name {
        font-size:14px;
        font-weight:700;
    }

    .preview-sub {
        margin-top:4px;
        color:#8b9690;
        font-size:13px;
    }

    .preview-divider {
        height:1px;
        background:#dde5e1;
        margin:16px 0;
    }

    .preview-row {
        display:flex;
        justify-content:space-between;
        gap:12px;
        padding:7px 0;
        font-size:13px;
    }

    .preview-row span:first-child {
        color:#95a09a;
    }

    .preview-row span:last-child {
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

    .flow {
        display:flex;
        flex-direction:column;
        gap:11px;
    }

    .flow-step {
        display:flex;
        align-items:center;
        gap:9px;
    }

    .flow-number {
        width:25px;
        height:25px;
        border-radius:8px;
        background:var(--primary-soft);
        color:var(--primary);
        display:flex;
        align-items:center;
        justify-content:center;
        font-size:13px;
        font-weight:700;
    }

    .flow-step span {
        font-size:13px;
        color:#58655e;
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

        .grid,
        .type-grid,
        .datetime-grid {
            grid-template-columns:1fr;
        }

        .full {
            grid-column:auto;
        }

        .agenda-row {
            grid-template-columns:30px minmax(0,1fr) 34px;
        }

        .agenda-row input[type="text"]:nth-of-type(2) {
            display:none;
        }
    }
</style>
@endpush


@section('content')

<div class="page-header">

    <div>

        <h1>Schedule Meeting</h1>

        <p>
            Create a meeting with participants, agenda items, venue and organizational context.
        </p>

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
            class="ui-btn primary"
            onclick="previewCreateMeeting()"
        >
            <i class="bi bi-calendar-check"></i>
            Schedule Meeting
        </button>

    </div>

</div>


<div class="layout">

    <div>

        <!-- BASIC INFORMATION -->

        <div class="card">

            <div class="card-head">

                <strong>Meeting Information</strong>

                <span>
                    Define the meeting and its main purpose.
                </span>

            </div>

            <div class="card-body">

                <div class="grid">

                    <div class="full">

                        <label class="label">
                            Meeting Title <span class="required">*</span>
                        </label>

                        <input
                            id="meetingTitle"
                            class="control"
                            placeholder="e.g. Monthly Executive Committee Meeting"
                        >

                    </div>


                    <div>

                        <label class="label">
                            Meeting ID
                        </label>

                        <input
                            class="control"
                            value="MTG-00040"
                            readonly
                        >

                    </div>


                    <div>

                        <label class="label">
                            Organizer
                        </label>

                        <select
                            id="organizer"
                            class="select"
                        >

                            <option>
                                General Secretary
                            </option>

                            <option>
                                Chairman
                            </option>

                            <option>
                                Treasurer
                            </option>

                            <option>
                                Committee Secretary
                            </option>

                        </select>

                    </div>


                    <div class="full">

                        <label class="label">
                            Meeting Purpose <span class="required">*</span>
                        </label>

                        <textarea
                            id="meetingPurpose"
                            class="control"
                            placeholder="Explain why this meeting is being held and what should be discussed or decided..."
                        ></textarea>

                    </div>

                </div>

            </div>

        </div>


        <!-- MEETING TYPE -->

        <div class="card">

            <div class="card-head">

                <strong>Meeting Type</strong>

                <span>
                    Select the organizational format of the meeting.
                </span>

            </div>

            <div class="card-body">

                <div class="type-grid">

                    <div class="type-option">

                        <input
                            type="radio"
                            name="meetingType"
                            id="committee"
                            value="Committee"
                            checked
                        >

                        <label for="committee">

                            <i class="bi bi-people"></i>

                            <strong>
                                Committee
                            </strong>

                            <span>
                                Formal committee meeting
                            </span>

                        </label>

                    </div>


                    <div class="type-option">

                        <input
                            type="radio"
                            name="meetingType"
                            id="general"
                            value="General"
                        >

                        <label for="general">

                            <i class="bi bi-building"></i>

                            <strong>
                                General
                            </strong>

                            <span>
                                Wider community meeting
                            </span>

                        </label>

                    </div>


                    <div class="type-option">

                        <input
                            type="radio"
                            name="meetingType"
                            id="special"
                            value="Special"
                        >

                        <label for="special">

                            <i class="bi bi-star"></i>

                            <strong>
                                Special
                            </strong>

                            <span>
                                Special-purpose session
                            </span>

                        </label>

                    </div>

                </div>

            </div>

        </div>


        <!-- DATE & VENUE -->

        <div class="card">

            <div class="card-head">

                <strong>Date, Time & Venue</strong>

                <span>
                    Set the meeting schedule and location.
                </span>

            </div>

            <div class="card-body">

                <div class="grid">

                    <div>

                        <label class="label">
                            Meeting Date <span class="required">*</span>
                        </label>

                        <input
                            id="meetingDate"
                            type="date"
                            class="control"
                            value="2026-08-24"
                        >

                    </div>


                    <div>

                        <label class="label">
                            Duration
                        </label>

                        <select
                            id="duration"
                            class="select"
                        >

                            <option value="30 minutes">
                                30 minutes
                            </option>

                            <option value="60 minutes" selected>
                                1 hour
                            </option>

                            <option value="90 minutes">
                                1 hour 30 minutes
                            </option>

                            <option value="120 minutes">
                                2 hours
                            </option>

                        </select>

                    </div>


                    <div>

                        <label class="label">
                            Start Time <span class="required">*</span>
                        </label>

                        <input
                            id="meetingTime"
                            type="time"
                            class="control"
                            value="18:00"
                        >

                    </div>


                    <div>

                        <label class="label">
                            Venue <span class="required">*</span>
                        </label>

                        <input
                            id="venue"
                            class="control"
                            value="Community Hall"
                        >

                    </div>


                    <div class="full">

                        <label class="label">
                            Venue / Location Notes
                        </label>

                        <input
                            class="control"
                            placeholder="Floor, room number, directions or other details"
                        >

                    </div>

                </div>


                <div class="datetime-grid" style="margin-top:18px;">

                    <div class="datetime-box">

                        <label>
                            Date
                        </label>

                        <strong id="previewDate">
                            24 Aug 2026
                        </strong>

                    </div>


                    <div class="datetime-box">

                        <label>
                            Time
                        </label>

                        <strong id="previewTime">
                            06:00 PM
                        </strong>

                    </div>


                    <div class="datetime-box">

                        <label>
                            Duration
                        </label>

                        <strong id="previewDuration">
                            1 hour
                        </strong>

                    </div>

                </div>

            </div>

        </div>


        <!-- COMMITTEE -->

        <div class="card">

            <div class="card-head">

                <strong>Committee / Group</strong>

                <span>
                    Select the organizational body responsible for the meeting.
                </span>

            </div>

            <div class="card-body">

                <div class="grid">

                    <div>

                        <label class="label">
                            Committee / Group <span class="required">*</span>
                        </label>

                        <select
                            id="committeeSelect"
                            class="select"
                        >

                            <option>
                                Executive Committee
                            </option>

                            <option>
                                Finance Committee
                            </option>

                            <option>
                                Welfare Committee
                            </option>

                            <option>
                                Procurement Committee
                            </option>

                            <option>
                                Community Development Committee
                            </option>

                            <option>
                                General Assembly
                            </option>

                        </select>

                    </div>


                    <div>

                        <label class="label">
                            Chairperson
                        </label>

                        <select class="select">

                            <option>
                                Naveed Alam
                            </option>

                            <option>
                                Abid Anwar
                            </option>

                            <option>
                                General Secretary
                            </option>

                        </select>

                    </div>

                </div>

            </div>

        </div>


        <!-- PARTICIPANTS -->

        <div class="card">

            <div class="card-head">

                <strong>Participants</strong>

                <span>
                    Select members who should be invited to the meeting.
                </span>

            </div>

            <div class="card-body">

                <div class="participant-list">

                    <label class="participant">

                        <input
                            type="checkbox"
                            checked
                        >

                        <div class="avatar">
                            NA
                        </div>

                        <div class="participant-info">

                            <div class="participant-name">
                                Naveed Alam
                            </div>

                            <div class="participant-role">
                                Chairman · Executive Committee
                            </div>

                        </div>

                        <span class="participant-badge">
                            Required
                        </span>

                    </label>


                    <label class="participant">

                        <input
                            type="checkbox"
                            checked
                        >

                        <div class="avatar">
                            AA
                        </div>

                        <div class="participant-info">

                            <div class="participant-name">
                                Abid Anwar
                            </div>

                            <div class="participant-role">
                                Member · Project Committee
                            </div>

                        </div>

                        <span class="participant-badge">
                            Required
                        </span>

                    </label>


                    <label class="participant">

                        <input
                            type="checkbox"
                            checked
                        >

                        <div class="avatar">
                            TR
                        </div>

                        <div class="participant-info">

                            <div class="participant-name">
                                Treasurer
                            </div>

                            <div class="participant-role">
                                Finance
                            </div>

                        </div>

                        <span class="participant-badge">
                            Required
                        </span>

                    </label>


                    <label class="participant">

                        <input
                            type="checkbox"
                        >

                        <div class="avatar">
                            SA
                        </div>

                        <div class="participant-info">

                            <div class="participant-name">
                                Salman Ali
                            </div>

                            <div class="participant-role">
                                Member
                            </div>

                        </div>

                    </label>


                    <label class="participant">

                        <input
                            type="checkbox"
                        >

                        <div class="avatar">
                            FK
                        </div>

                        <div class="participant-info">

                            <div class="participant-name">
                                Farid Khan
                            </div>

                            <div class="participant-role">
                                Member
                            </div>

                        </div>

                    </label>

                </div>

            </div>

        </div>


        <!-- AGENDA -->

        <div class="card">

            <div class="card-head">

                <strong>Meeting Agenda</strong>

                <span>
                    Define the topics that need to be discussed or decided.
                </span>

            </div>

            <div class="card-body">

                <div id="agendaBody">

                    <div class="agenda-row">

                        <div class="agenda-number">
                            1
                        </div>

                        <input
                            class="agenda-input agenda-topic"
                            value="Review previous meeting decisions"
                        >

                        <input
                            class="agenda-input agenda-owner"
                            value="Secretary"
                        >

                        <button
                            type="button"
                            class="agenda-remove"
                            onclick="removeAgenda(this)"
                        >
                            <i class="bi bi-trash"></i>
                        </button>

                    </div>


                    <div class="agenda-row">

                        <div class="agenda-number">
                            2
                        </div>

                        <input
                            class="agenda-input agenda-topic"
                            value="Review Qard and welfare cases"
                        >

                        <input
                            class="agenda-input agenda-owner"
                            value="Welfare Committee"
                        >

                        <button
                            type="button"
                            class="agenda-remove"
                            onclick="removeAgenda(this)"
                        >
                            <i class="bi bi-trash"></i>
                        </button>

                    </div>


                    <div class="agenda-row">

                        <div class="agenda-number">
                            3
                        </div>

                        <input
                            class="agenda-input agenda-topic"
                            value="Review community development projects"
                        >

                        <input
                            class="agenda-input agenda-owner"
                            value="Project Committee"
                        >

                        <button
                            type="button"
                            class="agenda-remove"
                            onclick="removeAgenda(this)"
                        >
                            <i class="bi bi-trash"></i>
                        </button>

                    </div>

                </div>


                <button
                    type="button"
                    class="ui-btn"
                    onclick="addAgenda()"
                    style="margin-top:10px;"
                >
                    <i class="bi bi-plus-lg"></i>
                    Add Agenda Item
                </button>

            </div>

        </div>


        <!-- LINKED RECORD -->

        <div class="card">

            <div class="card-head">

                <strong>Linked Organizational Record</strong>

                <span>
                    Optionally connect the meeting to a project, finance item, task or other record.
                </span>

            </div>

            <div class="card-body">

                <div class="grid">

                    <div>

                        <label class="label">
                            Link Type
                        </label>

                        <select class="select">

                            <option>
                                No linked record
                            </option>

                            <option>
                                Community Project
                            </option>

                            <option>
                                Procurement
                            </option>

                            <option>
                                Qard-e-Hasana
                            </option>

                            <option>
                                Finance
                            </option>

                            <option>
                                Task
                            </option>

                        </select>

                    </div>


                    <div>

                        <label class="label">
                            Linked Record
                        </label>

                        <select class="select">

                            <option>
                                Select record
                            </option>

                            <option>
                                PRJ-00014 · Village Walking Path Improvement
                            </option>

                            <option>
                                PRC-00042 · Walking Path Materials
                            </option>

                            <option>
                                QRD-00031 · Ahmed Khan
                            </option>

                            <option>
                                TSK-00064 · Path Survey
                            </option>

                        </select>

                    </div>

                </div>

            </div>

        </div>


        <!-- MEETING CONTROLS -->

        <div class="card">

            <div class="card-head">

                <strong>Meeting Controls</strong>

                <span>
                    Configure attendance, minutes and follow-up requirements.
                </span>

            </div>

            <div class="card-body">

                <div class="checklist">

                    <label class="check-item">

                        <input
                            type="checkbox"
                            checked
                        >

                        <div>

                            <strong>
                                Attendance must be recorded
                            </strong>

                            <span>
                                Participant attendance should be documented after the meeting.
                            </span>

                        </div>

                    </label>


                    <label class="check-item">

                        <input
                            type="checkbox"
                            checked
                        >

                        <div>

                            <strong>
                                Meeting minutes required
                            </strong>

                            <span>
                                Formal minutes should be uploaded or recorded after the meeting.
                            </span>

                        </div>

                    </label>


                    <label class="check-item">

                        <input
                            type="checkbox"
                            checked
                        >

                        <div>

                            <strong>
                                Decisions must be documented
                            </strong>

                            <span>
                                Important decisions should have an identifiable record.
                            </span>

                        </div>

                    </label>


                    <label class="check-item">

                        <input
                            type="checkbox"
                        >

                        <div>

                            <strong>
                                Follow-up tasks should be created
                            </strong>

                            <span>
                                Action items can be converted into tasks after the meeting.
                            </span>

                        </div>

                    </label>

                </div>

            </div>

        </div>


        <!-- DOCUMENTS -->

        <div class="card">

            <div class="card-head">

                <strong>Meeting Documents</strong>

                <span>
                    Attach agenda, notices or other reference material.
                </span>

            </div>

            <div class="card-body">

                <label class="upload">

                    <i class="bi bi-cloud-arrow-up"></i>

                    <strong>
                        Upload meeting documents
                    </strong>

                    <span>
                        Agenda, notices, previous minutes or supporting records
                    </span>

                    <input
                        type="file"
                        multiple
                        hidden
                    >

                </label>

            </div>

        </div>

    </div>


    <aside>

        <!-- PREVIEW -->

        <div class="preview">

            <div class="preview-label">
                Meeting Preview
            </div>

            <div class="preview-icon">
                <i class="bi bi-calendar3"></i>
            </div>

            <div
                class="preview-name"
                id="previewTitle"
            >
                New Meeting
            </div>

            <div class="preview-sub">
                MTG-00040
            </div>

            <div class="preview-divider"></div>

            <div class="preview-row">

                <span>
                    Type
                </span>

                <span id="previewType">
                    Committee
                </span>

            </div>


            <div class="preview-row">

                <span>
                    Committee
                </span>

                <span id="previewCommittee">
                    Executive Committee
                </span>

            </div>


            <div class="preview-row">

                <span>
                    Date
                </span>

                <span id="previewDateSide">
                    24 Aug 2026
                </span>

            </div>


            <div class="preview-row">

                <span>
                    Time
                </span>

                <span id="previewTimeSide">
                    06:00 PM
                </span>

            </div>


            <div class="preview-row">

                <span>
                    Venue
                </span>

                <span id="previewVenue">
                    Community Hall
                </span>

            </div>

        </div>


        <!-- PRINCIPLE -->

        <div class="card">

            <div class="card-head">
                <strong>Meeting Accountability</strong>
            </div>

            <div class="card-body">

                <div class="notice">

                    <i class="bi bi-shield-check"></i>

                    <p>
                        Important organizational decisions should remain
                        documented through attendance, agenda items, minutes,
                        decisions and follow-up actions.
                    </p>

                </div>

            </div>

        </div>


        <!-- FLOW -->

        <div class="card">

            <div class="card-head">
                <strong>Meeting Lifecycle</strong>
            </div>

            <div class="card-body">

                <div class="flow">

                    <div class="flow-step">

                        <div class="flow-number">
                            1
                        </div>

                        <span>
                            Meeting Scheduled
                        </span>

                    </div>


                    <div class="flow-step">

                        <div class="flow-number">
                            2
                        </div>

                        <span>
                            Participants Invited
                        </span>

                    </div>


                    <div class="flow-step">

                        <div class="flow-number">
                            3
                        </div>

                        <span>
                            Meeting Held
                        </span>

                    </div>


                    <div class="flow-step">

                        <div class="flow-number">
                            4
                        </div>

                        <span>
                            Attendance & Minutes
                        </span>

                    </div>


                    <div class="flow-step">

                        <div class="flow-number">
                            5
                        </div>

                        <span>
                            Decisions Recorded
                        </span>

                    </div>


                    <div class="flow-step">

                        <div class="flow-number">
                            6
                        </div>

                        <span>
                            Follow-up Tasks
                        </span>

                    </div>

                </div>

            </div>

        </div>

    </aside>

</div>

@endsection


@push('scripts')
<script>

    const meetingTitle =
        document.getElementById('meetingTitle');

    const meetingDate =
        document.getElementById('meetingDate');

    const meetingTime =
        document.getElementById('meetingTime');

    const duration =
        document.getElementById('duration');

    const venue =
        document.getElementById('venue');

    const committee =
        document.getElementById('committeeSelect');


    function formatDate(value) {

        if (!value) {
            return 'Not set';
        }

        const date =
            new Date(
                value + 'T00:00:00'
            );

        return date.toLocaleDateString(
            'en-GB',
            {
                day:'2-digit',
                month:'short',
                year:'numeric'
            }
        );

    }


    function formatTime(value) {

        if (!value) {
            return 'Not set';
        }

        const parts =
            value.split(':');

        let hour =
            Number(parts[0]);

        const minute =
            parts[1];

        const suffix =
            hour >= 12
                ? 'PM'
                : 'AM';

        hour =
            hour % 12 || 12;

        return (
            String(hour).padStart(2,'0') +
            ':' +
            minute +
            ' ' +
            suffix
        );

    }


    meetingTitle.addEventListener(
        'input',
        function () {

            document.getElementById(
                'previewTitle'
            ).textContent =
                this.value.trim() ||
                'New Meeting';

        }
    );


    meetingDate.addEventListener(
        'change',
        function () {

            const formatted =
                formatDate(this.value);

            document.getElementById(
                'previewDate'
            ).textContent =
                formatted;

            document.getElementById(
                'previewDateSide'
            ).textContent =
                formatted;

        }
    );


    meetingTime.addEventListener(
        'change',
        function () {

            const formatted =
                formatTime(this.value);

            document.getElementById(
                'previewTime'
            ).textContent =
                formatted;

            document.getElementById(
                'previewTimeSide'
            ).textContent =
                formatted;

        }
    );


    duration.addEventListener(
        'change',
        function () {

            document.getElementById(
                'previewDuration'
            ).textContent =
                this.options[
                    this.selectedIndex
                ].text;

        }
    );


    venue.addEventListener(
        'input',
        function () {

            document.getElementById(
                'previewVenue'
            ).textContent =
                this.value.trim() ||
                'Not set';

        }
    );


    committee.addEventListener(
        'change',
        function () {

            document.getElementById(
                'previewCommittee'
            ).textContent =
                this.options[
                    this.selectedIndex
                ].text;

        }
    );


    document
        .querySelectorAll(
            'input[name="meetingType"]'
        )
        .forEach(input => {

            input.addEventListener(
                'change',
                function () {

                    document.getElementById(
                        'previewType'
                    ).textContent =
                        this.value;

                }
            );

        });


    function addAgenda() {

        const body =
            document.getElementById(
                'agendaBody'
            );

        const count =
            body.querySelectorAll(
                '.agenda-row'
            ).length + 1;


        const row =
            document.createElement(
                'div'
            );

        row.className =
            'agenda-row';


        row.innerHTML = `

            <div class="agenda-number">
                ${count}
            </div>

            <input
                class="agenda-input agenda-topic"
                placeholder="Agenda topic"
            >

            <input
                class="agenda-input agenda-owner"
                placeholder="Responsible"
            >

            <button
                type="button"
                class="agenda-remove"
                onclick="removeAgenda(this)"
            >
                <i class="bi bi-trash"></i>
            </button>

        `;


        body.appendChild(row);

    }


    function removeAgenda(button) {

        const rows =
            document.querySelectorAll(
                '#agendaBody .agenda-row'
            );


        if (rows.length <= 1) {

            alert(
                'At least one agenda item is required.'
            );

            return;

        }


        button
            .closest('.agenda-row')
            .remove();


        renumberAgenda();

    }


    function renumberAgenda() {

        document
            .querySelectorAll(
                '#agendaBody .agenda-row'
            )
            .forEach(
                (row, index) => {

                    row.querySelector(
                        '.agenda-number'
                    ).textContent =
                        index + 1;

                }
            );

    }


    function previewCreateMeeting() {

        if (!meetingTitle.value.trim()) {

            alert(
                'Please enter the meeting title.'
            );

            meetingTitle.focus();

            return;

        }


        if (!meetingDate.value) {

            alert(
                'Please select the meeting date.'
            );

            meetingDate.focus();

            return;

        }


        if (!meetingTime.value) {

            alert(
                'Please select the meeting time.'
            );

            meetingTime.focus();

            return;

        }


        if (!venue.value.trim()) {

            alert(
                'Please enter the meeting venue.'
            );

            venue.focus();

            return;

        }


        alert(
            'UI preview only.\n\n' +
            'This meeting is ready to be scheduled once backend functionality is connected.'
        );

    }

</script>
@endpush