@extends('layouts.app')

@section('title', 'Task Details')

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
    .priority,
    .category {
        display:inline-flex;
        align-items:center;
        gap:5px;
        padding:5px 8px;
        border-radius:20px;
        font-size:13px;
        font-weight:650;
        white-space:nowrap;
    }

    .status::before,
    .priority::before {
        content:"";
        width:5px;
        height:5px;
        border-radius:50%;
    }

    .status.progress {
        background:#f1eef7;
        color:#77668c;
    }

    .status.progress::before {
        background:#8c77a3;
    }

    .priority.high {
        background:#fff0ef;
        color:#a85f58;
    }

    .priority.high::before {
        background:#b96b63;
    }

    .category {
        background:#eef3f8;
        color:#55758d;
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

    .description {
        color:#66736c;
        font-size:13px;
        line-height:1.75;
    }

    .progress-top {
        display:flex;
        justify-content:space-between;
        gap:10px;
        font-size:13px;
    }

    .progress-top span:first-child {
        color:#8a9690;
    }

    .progress-top span:last-child {
        color:var(--primary);
        font-weight:650;
    }

    .progress {
        height:8px;
        margin-top:7px;
        background:#edf1ee;
        border-radius:20px;
        overflow:hidden;
    }

    .progress span {
        display:block;
        height:100%;
        width:65%;
        background:var(--primary);
        border-radius:20px;
    }

    .assignee {
        display:flex;
        align-items:center;
        gap:10px;
    }

    .avatar {
        width:42px;
        height:42px;
        border-radius:11px;
        background:var(--primary-soft);
        color:var(--primary);
        display:flex;
        align-items:center;
        justify-content:center;
        font-size:14px;
        font-weight:700;
    }

    .assignee-name {
        font-size:14px;
        font-weight:650;
    }

    .assignee-role {
        margin-top:3px;
        color:#96a19b;
        font-size:13px;
    }

    .info-grid {
        display:grid;
        grid-template-columns:repeat(2,minmax(0,1fr));
        gap:16px;
    }

    .info label {
        display:block;
        margin-bottom:5px;
        color:#98a29d;
        font-size:13px;
        text-transform:uppercase;
    }

    .info strong {
        display:block;
        color:#46534c;
        font-size:14px;
    }

    .info p {
        margin:0;
        color:#68756e;
        font-size:13px;
        line-height:1.65;
    }

    .full {
        grid-column:1/-1;
    }

    .check-item {
        display:flex;
        align-items:flex-start;
        gap:9px;
        padding:10px;
        margin-bottom:9px;
        border:1px solid #edf1ee;
        border-radius:9px;
        background:#fafcfb;
    }

    .check-item:last-child {
        margin-bottom:0;
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

    .comment {
        padding:12px 0;
        border-bottom:1px solid #f0f3f1;
    }

    .comment:last-child {
        border-bottom:none;
    }

    .comment-top {
        display:flex;
        align-items:center;
        gap:9px;
    }

    .comment-avatar {
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

    .comment-name {
        font-size:13px;
        font-weight:650;
    }

    .comment-date {
        margin-top:2px;
        color:#9aa39e;
        font-size:12px;
    }

    .comment-text {
        margin-top:7px;
        color:#6c7872;
        font-size:13px;
        line-height:1.6;
    }

    .comment-form {
        margin-top:15px;
    }

    .control {
        width:100%;
        min-height:90px;
        padding:10px 12px;
        border:1px solid var(--border);
        border-radius:9px;
        background:#fff;
        color:var(--text);
        font-size:13px;
        resize:vertical;
        outline:none;
    }

    .control:focus {
        border-color:#b8cbc1;
        box-shadow:0 0 0 3px rgba(23,63,53,.055);
    }

    .comment-submit {
        margin-top:8px;
        display:flex;
        justify-content:flex-end;
    }

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

    .linked-record {
        padding:13px;
        background:#fafcfb;
        border:1px solid #edf1ee;
        border-radius:10px;
        display:flex;
        gap:10px;
        align-items:center;
    }

    .linked-icon {
        width:34px;
        height:34px;
        border-radius:9px;
        background:var(--primary-soft);
        color:var(--primary);
        display:flex;
        align-items:center;
        justify-content:center;
    }

    .linked-info {
        flex:1;
    }

    .linked-info strong {
        display:block;
        font-size:13px;
    }

    .linked-info span {
        display:block;
        margin-top:3px;
        color:#96a19b;
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
        .info-grid {
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
            <i class="bi bi-check2-square"></i>
        </div>

        <div>

            <div class="title">
                TSK-00064
            </div>

            <div class="meta">
                Complete village walking path survey · Abid Anwar
            </div>

        </div>

    </div>

    <div class="actions">

        <a
            href="{{ route('operations.tasks.index') }}"
            class="ui-btn"
        >
            <i class="bi bi-arrow-left"></i>
            Back
        </a>

        <a
            href="{{ route('operations.tasks.create') }}"
            class="ui-btn primary"
        >
            <i class="bi bi-pencil"></i>
            Edit Task
        </a>

        <button
            class="ui-btn success"
            onclick="previewCompleteTask()"
        >
            <i class="bi bi-check-lg"></i>
            Mark Complete
        </button>

    </div>

</div>


<div class="layout">

    <div>

        <!-- OVERVIEW -->

        <div class="card">

            <div class="card-head">

                <div>
                    <strong>Task Overview</strong>

                    <span>
                        Current task status and progress.
                    </span>
                </div>

                <span class="status progress">
                    In Progress
                </span>

            </div>

            <div class="card-body">

                <div class="summary-grid">

                    <div class="summary-box highlight">
                        <label>Progress</label>
                        <strong>65%</strong>
                    </div>

                    <div class="summary-box">
                        <label>Priority</label>
                        <strong>High</strong>
                    </div>

                    <div class="summary-box">
                        <label>Category</label>
                        <strong>Community Project</strong>
                    </div>

                    <div class="summary-box">
                        <label>Task ID</label>
                        <strong>TSK-00064</strong>
                    </div>

                    <div class="summary-box">
                        <label>Start Date</label>
                        <strong>17 Aug 2026</strong>
                    </div>

                    <div class="summary-box">
                        <label>Due Date</label>
                        <strong>20 Aug 2026</strong>
                    </div>

                    <div class="summary-box">
                        <label>Created By</label>
                        <strong>Secretary</strong>
                    </div>

                    <div class="summary-box">
                        <label>Last Updated</label>
                        <strong>18 Aug 2026</strong>
                    </div>

                </div>


                <div style="margin-top:18px;">

                    <div class="progress-top">

                        <span>
                            Completion
                        </span>

                        <span>
                            65%
                        </span>

                    </div>

                    <div class="progress">
                        <span></span>
                    </div>

                </div>

            </div>

        </div>


        <!-- DESCRIPTION -->

        <div class="card">

            <div class="card-head">

                <div>
                    <strong>Task Description</strong>

                    <span>
                        Instructions and expected outcome.
                    </span>
                </div>

                <span class="category">
                    Community Project
                </span>

            </div>

            <div class="card-body">

                <div class="description">

                    The task is to complete a field survey of the village
                    walking paths that require repair or improvement. The
                    survey should identify damaged sections, approximate
                    measurements, priority areas and any access concerns.

                    <br><br>

                    The completed survey will be used by the Community
                    Development Committee to prepare the project scope,
                    estimated cost and procurement requirements.

                </div>

            </div>

        </div>


        <!-- ASSIGNMENT -->

        <div class="card">

            <div class="card-head">

                <div>
                    <strong>Assignment</strong>

                    <span>
                        Responsible person and organizational context.
                    </span>
                </div>

            </div>

            <div class="card-body">

                <div class="assignee">

                    <div class="avatar">
                        AA
                    </div>

                    <div style="flex:1;">

                        <div class="assignee-name">
                            Abid Anwar
                        </div>

                        <div class="assignee-role">
                            Project Committee · Individual Assignee
                        </div>

                    </div>

                    <a
                        href="{{ route('members.show', ['id' => 1]) }}"
                        class="ui-btn"
                    >
                        View Member
                    </a>

                </div>

            </div>

        </div>


        <!-- LINKED RECORD -->

        <div class="card">

            <div class="card-head">

                <div>
                    <strong>Linked Activity</strong>

                    <span>
                        Organizational record connected to this task.
                    </span>
                </div>

            </div>

            <div class="card-body">

                <div class="linked-record">

                    <div class="linked-icon">
                        <i class="bi bi-buildings"></i>
                    </div>

                    <div class="linked-info">

                        <strong>
                            PRJ-00014 · Village Walking Path Improvement
                        </strong>

                        <span>
                            Community Development Project
                        </span>

                    </div>

                    <button class="ui-btn">
                        Open
                    </button>

                </div>

            </div>

        </div>


        <!-- CHECKLIST -->

        <div class="card">

            <div class="card-head">

                <div>
                    <strong>Task Checklist</strong>

                    <span>
                        Work items associated with completion.
                    </span>
                </div>

            </div>

            <div class="card-body">

                <label class="check-item">

                    <input
                        type="checkbox"
                        checked
                    >

                    <div>

                        <strong>
                            Visit identified walking paths
                        </strong>

                        <span>
                            Field inspection completed.
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
                            Record damaged sections
                        </strong>

                        <span>
                            Priority areas have been documented.
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
                            Capture approximate measurements
                        </strong>

                        <span>
                            Initial measurements recorded.
                        </span>

                    </div>

                </label>


                <label class="check-item">

                    <input
                        type="checkbox"
                    >

                    <div>

                        <strong>
                            Submit final survey report
                        </strong>

                        <span>
                            Final report is still pending.
                        </span>

                    </div>

                </label>

            </div>

        </div>


        <!-- COMMENTS -->

        <div class="card">

            <div class="card-head">

                <div>
                    <strong>Comments & Updates</strong>

                    <span>
                        Discussion and progress updates.
                    </span>
                </div>

            </div>

            <div class="card-body">

                <div class="comment">

                    <div class="comment-top">

                        <div class="comment-avatar">
                            AA
                        </div>

                        <div>

                            <div class="comment-name">
                                Abid Anwar
                            </div>

                            <div class="comment-date">
                                18 Aug 2026 · 10:25 AM
                            </div>

                        </div>

                    </div>

                    <div class="comment-text">
                        Field inspection is mostly complete. Two additional
                        sections near the eastern side of the village require
                        measurements.
                    </div>

                </div>


                <div class="comment">

                    <div class="comment-top">

                        <div class="comment-avatar">
                            NA
                        </div>

                        <div>

                            <div class="comment-name">
                                Naveed Alam
                            </div>

                            <div class="comment-date">
                                17 Aug 2026 · 05:40 PM
                            </div>

                        </div>

                    </div>

                    <div class="comment-text">
                        Please include approximate material requirements in
                        the final survey report.
                    </div>

                </div>


                <div class="comment-form">

                    <textarea
                        id="commentText"
                        class="control"
                        placeholder="Write an update or comment..."
                    ></textarea>

                    <div class="comment-submit">

                        <button
                            class="ui-btn primary"
                            onclick="previewComment()"
                        >
                            <i class="bi bi-send"></i>
                            Add Comment
                        </button>

                    </div>

                </div>

            </div>

        </div>


        <!-- DOCUMENTS -->

        <div class="card">

            <div class="card-head">

                <div>
                    <strong>Task Documents</strong>

                    <span>
                        Files and evidence associated with the task.
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
                            Preliminary Survey Notes
                        </strong>

                        <span>
                            PDF · 18 Aug 2026
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
                        <i class="bi bi-images"></i>
                    </div>

                    <div class="document-info">
                        <strong>
                            Field Photos
                        </strong>

                        <span>
                            8 images · 18 Aug 2026
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
                    <strong>Task Activity</strong>

                    <span>
                        Complete history of task events.
                    </span>
                </div>

            </div>

            <div class="card-body">

                <div class="timeline-item">

                    <div class="timeline-dot"></div>

                    <div>

                        <div class="timeline-title">
                            Task progress updated to 65%
                        </div>

                        <div class="timeline-meta">
                            18 Aug 2026 · Abid Anwar
                        </div>

                    </div>

                </div>


                <div class="timeline-item">

                    <div class="timeline-dot"></div>

                    <div>

                        <div class="timeline-title">
                            Field survey started
                        </div>

                        <div class="timeline-meta">
                            17 Aug 2026 · Abid Anwar
                        </div>

                    </div>

                </div>


                <div class="timeline-item">

                    <div class="timeline-dot"></div>

                    <div>

                        <div class="timeline-title">
                            Task assigned
                        </div>

                        <div class="timeline-meta">
                            17 Aug 2026 · Secretary
                        </div>

                    </div>

                </div>


                <div class="timeline-item">

                    <div class="timeline-dot"></div>

                    <div>

                        <div class="timeline-title">
                            Task created
                        </div>

                        <div class="timeline-meta">
                            17 Aug 2026 · Secretary
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
                    65%
                </div>

                <div class="side-label">
                    Task Progress
                </div>

                <div style="
                    height:1px;
                    background:#eef1ef;
                    margin:16px 0;
                "></div>

                <div class="side-row">
                    <span>Status</span>
                    <span>In Progress</span>
                </div>

                <div class="side-row">
                    <span>Priority</span>
                    <span>High</span>
                </div>

                <div class="side-row">
                    <span>Assignee</span>
                    <span>Abid Anwar</span>
                </div>

                <div class="side-row">
                    <span>Due Date</span>
                    <span>20 Aug 2026</span>
                </div>

                <div class="side-row">
                    <span>Days Remaining</span>
                    <span>2</span>
                </div>

            </div>

        </div>


        <div class="card">

            <div class="card-head">
                <strong>Completion Control</strong>
            </div>

            <div class="card-body">

                <div class="notice">

                    <i class="bi bi-check2-circle"></i>

                    <p>
                        The task should only be marked complete when the
                        required checklist items and expected outcome have
                        been satisfied.
                    </p>

                </div>

                <div style="margin-top:12px;">

                    <button
                        class="ui-btn success"
                        style="width:100%;"
                        onclick="previewCompleteTask()"
                    >
                        <i class="bi bi-check-lg"></i>
                        Mark Task Complete
                    </button>

                </div>

            </div>

        </div>

    </aside>

</div>

@endsection


@push('scripts')
<script>

    function previewComment() {

        const comment =
            document.getElementById('commentText');

        if (!comment.value.trim()) {

            alert(
                'Please enter a comment or update.'
            );

            comment.focus();

            return;

        }

        alert(
            'UI preview only.\n\n' +
            'The comment would be added to the task activity once backend functionality is connected.'
        );

    }


    function previewCompleteTask() {

        const confirmed =
            confirm(
                'Mark TSK-00064 as completed?'
            );

        if (!confirmed) {
            return;
        }

        alert(
            'UI preview only.\n\n' +
            'The task would be marked complete and its activity history updated once backend functionality is connected.'
        );

    }

</script>
@endpush