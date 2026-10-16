@extends('layouts.app')

@section('title', 'Create Task')

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
        min-height:115px;
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

    /* CATEGORY */

    .category-grid {
        display:grid;
        grid-template-columns:repeat(3,minmax(0,1fr));
        gap:9px;
    }

    .category-option {
        position:relative;
    }

    .category-option input {
        position:absolute;
        opacity:0;
        pointer-events:none;
    }

    .category-option label {
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

    .category-option label i {
        font-size:17px;
    }

    .category-option label strong {
        font-size:13px;
    }

    .category-option label span {
        color:#929d97;
        font-size:12px;
    }

    .category-option input:checked + label {
        background:var(--primary-soft);
        border-color:#c7d8d0;
        color:var(--primary);
    }

    /* PRIORITY */

    .priority-grid {
        display:grid;
        grid-template-columns:repeat(3,minmax(0,1fr));
        gap:9px;
    }

    .priority-option {
        position:relative;
    }

    .priority-option input {
        position:absolute;
        opacity:0;
        pointer-events:none;
    }

    .priority-option label {
        min-height:58px;
        padding:10px;
        border:1px solid var(--border);
        border-radius:10px;
        display:flex;
        align-items:center;
        justify-content:center;
        text-align:center;
        cursor:pointer;
        font-size:13px;
        font-weight:650;
    }

    .priority-option input:checked + label {
        background:var(--primary-soft);
        border-color:#c7d8d0;
        color:var(--primary);
    }

    /* ASSIGNEES */

    .assignee-box {
        display:flex;
        gap:10px;
        align-items:center;
        padding:12px;
        margin-top:9px;
        border:1px solid #edf1ee;
        background:#fafcfb;
        border-radius:9px;
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

    .assignee-info {
        flex:1;
    }

    .assignee-name {
        font-size:13px;
        font-weight:650;
    }

    .assignee-role {
        margin-top:3px;
        color:#98a29d;
        font-size:13px;
    }

    /* CHECKLIST */

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

    .check-item span {
        color:#65726c;
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
        .category-grid,
        .priority-grid {
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

    <div>

        <h1>Create Task</h1>

        <p>
            Assign and organize work for members, committees and organizational activities.
        </p>

    </div>

    <div class="actions">

        <a
            href="{{ route('operations.tasks.index') }}"
            class="ui-btn"
        >
            <i class="bi bi-arrow-left"></i>
            Back
        </a>

        <button
            class="ui-btn primary"
            onclick="previewCreateTask()"
        >
            <i class="bi bi-check-lg"></i>
            Create Task
        </button>

    </div>

</div>


<div class="layout">

    <div>

        <!-- BASIC INFORMATION -->

        <div class="card">

            <div class="card-head">

                <strong>Task Information</strong>

                <span>
                    Define the task and what needs to be accomplished.
                </span>

            </div>

            <div class="card-body">

                <div class="grid">

                    <div class="full">

                        <label class="label">
                            Task Title <span class="required">*</span>
                        </label>

                        <input
                            id="taskTitle"
                            class="control"
                            placeholder="Enter a clear task title"
                        >

                    </div>


                    <div>

                        <label class="label">
                            Task ID
                        </label>

                        <input
                            class="control"
                            value="TSK-00065"
                            readonly
                        >

                    </div>


                    <div>

                        <label class="label">
                            Status
                        </label>

                        <select
                            id="taskStatus"
                            class="select"
                        >

                            <option value="todo" selected>
                                To Do
                            </option>

                            <option value="progress">
                                In Progress
                            </option>

                            <option value="completed">
                                Completed
                            </option>

                        </select>

                    </div>


                    <div class="full">

                        <label class="label">
                            Task Description <span class="required">*</span>
                        </label>

                        <textarea
                            id="taskDescription"
                            class="control"
                            placeholder="Describe the work, expected result and relevant instructions..."
                        ></textarea>

                        <div class="help">
                            Keep the description specific enough that the assigned person knows what completion means.
                        </div>

                    </div>

                </div>

            </div>

        </div>


        <!-- CATEGORY -->

        <div class="card">

            <div class="card-head">

                <strong>Task Category</strong>

                <span>
                    Classify the task for easier organization and reporting.
                </span>

            </div>

            <div class="card-body">

                <div class="category-grid">

                    <div class="category-option">

                        <input
                            type="radio"
                            name="category"
                            id="community"
                            value="Community Project"
                            checked
                        >

                        <label for="community">

                            <i class="bi bi-buildings"></i>

                            <strong>
                                Community Project
                            </strong>

                            <span>
                                Infrastructure / development
                            </span>

                        </label>

                    </div>


                    <div class="category-option">

                        <input
                            type="radio"
                            name="category"
                            id="welfare"
                            value="Welfare"
                        >

                        <label for="welfare">

                            <i class="bi bi-heart-handshake"></i>

                            <strong>
                                Welfare
                            </strong>

                            <span>
                                Beneficiary support
                            </span>

                        </label>

                    </div>


                    <div class="category-option">

                        <input
                            type="radio"
                            name="category"
                            id="finance"
                            value="Finance"
                        >

                        <label for="finance">

                            <i class="bi bi-cash-stack"></i>

                            <strong>
                                Finance
                            </strong>

                            <span>
                                Financial activity
                            </span>

                        </label>

                    </div>


                    <div class="category-option">

                        <input
                            type="radio"
                            name="category"
                            id="admin"
                            value="Administration"
                        >

                        <label for="admin">

                            <i class="bi bi-briefcase"></i>

                            <strong>
                                Administration
                            </strong>

                            <span>
                                Internal organization
                            </span>

                        </label>

                    </div>


                    <div class="category-option">

                        <input
                            type="radio"
                            name="category"
                            id="followup"
                            value="Follow-up"
                        >

                        <label for="followup">

                            <i class="bi bi-arrow-repeat"></i>

                            <strong>
                                Follow-up
                            </strong>

                            <span>
                                Monitoring / follow-up
                            </span>

                        </label>

                    </div>


                    <div class="category-option">

                        <input
                            type="radio"
                            name="category"
                            id="other"
                            value="Other"
                        >

                        <label for="other">

                            <i class="bi bi-three-dots"></i>

                            <strong>
                                Other
                            </strong>

                            <span>
                                Other organizational work
                            </span>

                        </label>

                    </div>

                </div>

            </div>

        </div>


        <!-- ASSIGNMENT -->

        <div class="card">

            <div class="card-head">

                <strong>Assignment</strong>

                <span>
                    Assign the task to a responsible member or committee.
                </span>

            </div>

            <div class="card-body">

                <div class="grid">

                    <div>

                        <label class="label">
                            Assignee Type
                        </label>

                        <select
                            id="assigneeType"
                            class="select"
                        >

                            <option value="member" selected>
                                Individual Member
                            </option>

                            <option value="committee">
                                Committee
                            </option>

                        </select>

                    </div>


                    <div>

                        <label class="label">
                            Assignee <span class="required">*</span>
                        </label>

                        <select
                            id="assignee"
                            class="select"
                        >

                            <option value="">
                                Select assignee
                            </option>

                            <option value="abid">
                                Abid Anwar
                            </option>

                            <option value="naveed">
                                Naveed Alam
                            </option>

                            <option value="ali">
                                Ali Rahman
                            </option>

                            <option value="treasurer">
                                Treasurer
                            </option>

                        </select>

                    </div>

                </div>


                <div id="assigneePreview" style="display:none;">

                    <div class="assignee-box">

                        <div
                            class="avatar"
                            id="assigneeAvatar"
                        >
                            AA
                        </div>

                        <div class="assignee-info">

                            <div
                                class="assignee-name"
                                id="assigneeName"
                            >
                                Abid Anwar
                            </div>

                            <div
                                class="assignee-role"
                                id="assigneeRole"
                            >
                                Member
                            </div>

                        </div>

                        <i class="bi bi-person-check" style="
                            color:var(--primary);
                            font-size:14px;
                        "></i>

                    </div>

                </div>

            </div>

        </div>


        <!-- DEADLINE & PRIORITY -->

        <div class="card">

            <div class="card-head">

                <strong>Schedule & Priority</strong>

                <span>
                    Define when the work should be completed and its urgency.
                </span>

            </div>

            <div class="card-body">

                <div class="grid">

                    <div>

                        <label class="label">
                            Start Date
                        </label>

                        <input
                            id="startDate"
                            type="date"
                            class="control"
                            value="2026-08-17"
                        >

                    </div>


                    <div>

                        <label class="label">
                            Due Date <span class="required">*</span>
                        </label>

                        <input
                            id="dueDate"
                            type="date"
                            class="control"
                            value="2026-08-25"
                        >

                    </div>

                </div>


                <div style="margin-top:18px;">

                    <label class="label">
                        Priority
                    </label>

                    <div class="priority-grid">

                        <div class="priority-option">

                            <input
                                type="radio"
                                name="priority"
                                id="high"
                                value="High"
                            >

                            <label for="high">
                                High
                            </label>

                        </div>


                        <div class="priority-option">

                            <input
                                type="radio"
                                name="priority"
                                id="medium"
                                value="Medium"
                                checked
                            >

                            <label for="medium">
                                Medium
                            </label>

                        </div>


                        <div class="priority-option">

                            <input
                                type="radio"
                                name="priority"
                                id="low"
                                value="Low"
                            >

                            <label for="low">
                                Low
                            </label>

                        </div>

                    </div>

                </div>

            </div>

        </div>


        <!-- LINKED ACTIVITY -->

        <div class="card">

            <div class="card-head">

                <strong>Linked Activity</strong>

                <span>
                    Optionally connect the task to a project, application or organizational activity.
                </span>

            </div>

            <div class="card-body">

                <div class="grid">

                    <div>

                        <label class="label">
                            Link Type
                        </label>

                        <select
                            id="linkType"
                            class="select"
                        >

                            <option value="">
                                No linked record
                            </option>

                            <option value="project">
                                Community Project
                            </option>

                            <option value="business">
                                Small Business Assistance
                            </option>

                            <option value="qard">
                                Qard-e-Hasana
                            </option>

                            <option value="beneficiary">
                                Beneficiary
                            </option>

                            <option value="finance">
                                Finance Record
                            </option>

                        </select>

                    </div>


                    <div>

                        <label class="label">
                            Linked Record
                        </label>

                        <select class="select">

                            <option>
                                Select linked record
                            </option>

                            <option>
                                PRJ-00014 · Village Path Repair
                            </option>

                            <option>
                                SBA-00041 · Kasso General Store
                            </option>

                            <option>
                                QRD-00031 · Ahmed Khan
                            </option>

                        </select>

                    </div>


                    <div class="full">

                        <label class="label">
                            Task Outcome
                        </label>

                        <textarea
                            class="control"
                            placeholder="What should be true when this task is completed?"
                        ></textarea>

                    </div>

                </div>

            </div>

        </div>


        <!-- CHECKLIST -->

        <div class="card">

            <div class="card-head">

                <strong>Task Controls</strong>

                <span>
                    Optional controls for accountability and completion.
                </span>

            </div>

            <div class="card-body">

                <div class="checklist">

                    <label class="check-item">

                        <input
                            type="checkbox"
                            checked
                        >

                        <span>
                            Assignee must provide completion notes.
                        </span>

                    </label>


                    <label class="check-item">

                        <input
                            type="checkbox"
                            checked
                        >

                        <span>
                            Supporting documents may be attached to the task.
                        </span>

                    </label>


                    <label class="check-item">

                        <input
                            type="checkbox"
                        >

                        <span>
                            Committee review required before task closure.
                        </span>

                    </label>


                    <label class="check-item">

                        <input
                            type="checkbox"
                        >

                        <span>
                            Create follow-up task when this task is completed.
                        </span>

                    </label>

                </div>

            </div>

        </div>


        <!-- ATTACHMENTS -->

        <div class="card">

            <div class="card-head">

                <strong>Supporting Documents</strong>

                <span>
                    Attach instructions, reference documents or other relevant files.
                </span>

            </div>

            <div class="card-body">

                <label class="upload">

                    <i class="bi bi-cloud-arrow-up"></i>

                    <strong>
                        Upload task documents
                    </strong>

                    <span>
                        PDF, images, spreadsheets or other supporting files
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
                Task Preview
            </div>

            <div class="preview-icon">
                <i class="bi bi-check2-square"></i>
            </div>

            <div
                class="preview-name"
                id="previewTitle"
            >
                New Task
            </div>

            <div class="preview-sub">
                TSK-00065
            </div>

            <div class="preview-divider"></div>

            <div class="preview-row">

                <span>
                    Category
                </span>

                <span id="previewCategory">
                    Community Project
                </span>

            </div>


            <div class="preview-row">

                <span>
                    Assignee
                </span>

                <span id="previewAssignee">
                    Not assigned
                </span>

            </div>


            <div class="preview-row">

                <span>
                    Priority
                </span>

                <span id="previewPriority">
                    Medium
                </span>

            </div>


            <div class="preview-row">

                <span>
                    Due
                </span>

                <span id="previewDue">
                    25 Aug 2026
                </span>

            </div>


            <div class="preview-row">

                <span>
                    Status
                </span>

                <span id="previewStatus">
                    To Do
                </span>

            </div>

        </div>


        <!-- PRINCIPLE -->

        <div class="card">

            <div class="card-head">
                <strong>Task Accountability</strong>
            </div>

            <div class="card-body">

                <div class="notice">

                    <i class="bi bi-shield-check"></i>

                    <p>
                        Tasks should have a clear owner, deadline and expected
                        outcome so organizational work remains visible and
                        accountable.
                    </p>

                </div>

            </div>

        </div>


        <!-- FLOW -->

        <div class="card">

            <div class="card-head">
                <strong>Task Lifecycle</strong>
            </div>

            <div class="card-body">

                <div style="
                    display:flex;
                    flex-direction:column;
                    gap:11px;
                ">

                    <div style="
                        display:flex;
                        gap:9px;
                        align-items:center;
                    ">

                        <div style="
                            width:25px;
                            height:25px;
                            border-radius:8px;
                            background:var(--primary-soft);
                            color:var(--primary);
                            display:flex;
                            align-items:center;
                            justify-content:center;
                            font-size:13px;
                        ">
                            1
                        </div>

                        <span style="font-size:13px;">
                            Task Created
                        </span>

                    </div>


                    <div style="
                        display:flex;
                        gap:9px;
                        align-items:center;
                    ">

                        <div style="
                            width:25px;
                            height:25px;
                            border-radius:8px;
                            background:var(--primary-soft);
                            color:var(--primary);
                            display:flex;
                            align-items:center;
                            justify-content:center;
                            font-size:13px;
                        ">
                            2
                        </div>

                        <span style="font-size:13px;">
                            Assigned
                        </span>

                    </div>


                    <div style="
                        display:flex;
                        gap:9px;
                        align-items:center;
                    ">

                        <div style="
                            width:25px;
                            height:25px;
                            border-radius:8px;
                            background:var(--primary-soft);
                            color:var(--primary);
                            display:flex;
                            align-items:center;
                            justify-content:center;
                            font-size:13px;
                        ">
                            3
                        </div>

                        <span style="font-size:13px;">
                            Work In Progress
                        </span>

                    </div>


                    <div style="
                        display:flex;
                        gap:9px;
                        align-items:center;
                    ">

                        <div style="
                            width:25px;
                            height:25px;
                            border-radius:8px;
                            background:var(--primary-soft);
                            color:var(--primary);
                            display:flex;
                            align-items:center;
                            justify-content:center;
                            font-size:13px;
                        ">
                            4
                        </div>

                        <span style="font-size:13px;">
                            Review
                        </span>

                    </div>


                    <div style="
                        display:flex;
                        gap:9px;
                        align-items:center;
                    ">

                        <div style="
                            width:25px;
                            height:25px;
                            border-radius:8px;
                            background:var(--primary-soft);
                            color:var(--primary);
                            display:flex;
                            align-items:center;
                            justify-content:center;
                            font-size:13px;
                        ">
                            5
                        </div>

                        <span style="font-size:13px;">
                            Completed
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

    const taskTitle =
        document.getElementById('taskTitle');

    const taskStatus =
        document.getElementById('taskStatus');

    const assignee =
        document.getElementById('assignee');

    const assigneeType =
        document.getElementById('assigneeType');

    const dueDate =
        document.getElementById('dueDate');


    taskTitle.addEventListener('input', function () {

        document.getElementById('previewTitle')
            .textContent =
                this.value.trim() || 'New Task';

    });


    taskStatus.addEventListener('change', function () {

        const text =
            this.options[this.selectedIndex]?.text || '';

        document.getElementById('previewStatus')
            .textContent = text;

    });


    assignee.addEventListener('change', function () {

        const option =
            this.options[this.selectedIndex];

        const text =
            option?.text || '';

        document.getElementById('previewAssignee')
            .textContent =
                text || 'Not assigned';


        const preview =
            document.getElementById(
                'assigneePreview'
            );


        if (!this.value) {

            preview.style.display =
                'none';

            return;

        }


        preview.style.display =
            'block';


        const firstName =
            text
                .split(' ')
                .map(word => word[0])
                .join('')
                .substring(0,2)
                .toUpperCase();


        document.getElementById('assigneeAvatar')
            .textContent =
                firstName;


        document.getElementById('assigneeName')
            .textContent =
                text;


        document.getElementById('assigneeRole')
            .textContent =
                assigneeType.value === 'committee'
                    ? 'Committee'
                    : 'Member';

    });


    assigneeType.addEventListener('change', function () {

        document.getElementById('assigneeRole')
            .textContent =
                this.value === 'committee'
                    ? 'Committee'
                    : 'Member';

    });


    dueDate.addEventListener('change', function () {

        if (!this.value) {
            return;
        }

        const date =
            new Date(
                this.value + 'T00:00:00'
            );

        document.getElementById('previewDue')
            .textContent =
                date.toLocaleDateString(
                    'en-GB',
                    {
                        day:'2-digit',
                        month:'short',
                        year:'numeric'
                    }
                );

    });


    document
        .querySelectorAll(
            'input[name="category"]'
        )
        .forEach(input => {

            input.addEventListener(
                'change',
                function () {

                    document.getElementById(
                        'previewCategory'
                    ).textContent =
                        this.value;

                }
            );

        });


    document
        .querySelectorAll(
            'input[name="priority"]'
        )
        .forEach(input => {

            input.addEventListener(
                'change',
                function () {

                    document.getElementById(
                        'previewPriority'
                    ).textContent =
                        this.value;

                }
            );

        });


    function previewCreateTask() {

        if (!taskTitle.value.trim()) {

            alert(
                'Please enter a task title.'
            );

            taskTitle.focus();

            return;

        }


        if (!assignee.value) {

            alert(
                'Please select an assignee.'
            );

            assignee.focus();

            return;

        }


        if (!dueDate.value) {

            alert(
                'Please select a due date.'
            );

            dueDate.focus();

            return;

        }


        alert(
            'UI preview only.\n\n' +
            'This task is ready to be created once backend functionality is connected.'
        );

    }

</script>
@endpush