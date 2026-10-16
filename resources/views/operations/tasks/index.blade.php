@extends('layouts.app')

@section('title', 'Tasks')

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

    .task-table {
        width:100%;
        min-width:1180px;
        border-collapse:collapse;
    }

    .task-table th {
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

    .task-table td {
        padding:13px 18px;
        border-bottom:1px solid #f0f3f1;
        font-size:13px;
        vertical-align:middle;
    }

    .task-table tbody tr:hover {
        background:#fbfcfb;
    }

    .task-table tbody tr:last-child td {
        border-bottom:none;
    }

    .task-title {
        font-size:14px;
        font-weight:650;
        color:#46534c;
    }

    .task-id {
        margin-top:3px;
        color:#98a29d;
        font-size:13px;
    }

    .assignee {
        display:flex;
        align-items:center;
        gap:8px;
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
        flex-shrink:0;
    }

    .assignee-name {
        font-size:13px;
        font-weight:600;
    }

    .assignee-role {
        margin-top:2px;
        color:#98a29d;
        font-size:12px;
    }

    .priority,
    .status,
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

    .priority.high {
        background:#fff0ef;
        color:#a85f58;
    }

    .priority.high::before,
    .priority.medium::before,
    .priority.low::before,
    .status.todo::before,
    .status.progress::before,
    .status.completed::before,
    .status.overdue::before {
        content:"";
        width:5px;
        height:5px;
        border-radius:50%;
    }

    .priority.high::before {
        background:#b96b63;
    }

    .priority.medium {
        background:#fff5e9;
        color:#a8793e;
    }

    .priority.medium::before {
        background:#d19a4d;
    }

    .priority.low {
        background:#edf7f0;
        color:#397955;
    }

    .priority.low::before {
        background:#4c9a6c;
    }

    .status.todo {
        background:#eef3f8;
        color:#55758d;
    }

    .status.todo::before {
        background:#6d8ea5;
    }

    .status.progress {
        background:#f1eef7;
        color:#77668c;
    }

    .status.progress::before {
        background:#8c77a3;
    }

    .status.completed {
        background:#edf7f0;
        color:#397955;
    }

    .status.completed::before {
        background:#4c9a6c;
    }

    .status.overdue {
        background:#fff0ef;
        color:#a85f58;
    }

    .status.overdue::before {
        background:#b96b63;
    }

    .category {
        background:#f5f7f6;
        color:#68756f;
        border:1px solid #e8edeb;
    }

    .date {
        color:#68756f;
        white-space:nowrap;
    }

    .progress-wrap {
        width:100px;
    }

    .progress {
        height:6px;
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

    .progress-label {
        margin-top:4px;
        color:#8d9892;
        font-size:12px;
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

        <h1>Tasks</h1>

        <p>
            Assign, track and monitor organizational work across committees, projects and activities.
        </p>

    </div>

    <div class="actions">

        <button class="ui-btn">
            <i class="bi bi-download"></i>
            Export
        </button>

        <a
            href="{{ route('operations.tasks.create') }}"
            class="ui-btn primary"
        >
            <i class="bi bi-plus-lg"></i>
            New Task
        </a>

    </div>

</div>


<!-- SUMMARY -->

<div class="summary-grid">

    <div class="summary-card">
        <div class="summary-label">Total Tasks</div>
        <div class="summary-value">64</div>
        <div class="summary-meta">All organizational tasks</div>
    </div>

    <div class="summary-card">
        <div class="summary-label">In Progress</div>
        <div class="summary-value">18</div>
        <div class="summary-meta">Currently active</div>
    </div>

    <div class="summary-card">
        <div class="summary-label">Completed</div>
        <div class="summary-value">39</div>
        <div class="summary-meta">Successfully closed</div>
    </div>

    <div class="summary-card">
        <div class="summary-label">Due Soon</div>
        <div class="summary-value">7</div>
        <div class="summary-meta">Next 7 days</div>
    </div>

    <div class="summary-card">
        <div class="summary-label">Overdue</div>
        <div class="summary-value">3</div>
        <div class="summary-meta">Require attention</div>
    </div>

</div>


<div class="main-card">

    <div class="toolbar">

        <div class="search">

            <i class="bi bi-search"></i>

            <input
                id="taskSearch"
                type="text"
                placeholder="Search task, assignee, committee or project..."
            >

        </div>


        <select
            id="categoryFilter"
            class="filter"
        >
            <option value="">All Categories</option>
            <option value="community">Community Project</option>
            <option value="welfare">Welfare</option>
            <option value="finance">Finance</option>
            <option value="admin">Administration</option>
            <option value="followup">Follow-up</option>
        </select>


        <select
            id="priorityFilter"
            class="filter"
        >
            <option value="">All Priorities</option>
            <option value="high">High</option>
            <option value="medium">Medium</option>
            <option value="low">Low</option>
        </select>


        <select
            id="statusFilter"
            class="filter"
        >
            <option value="">All Statuses</option>
            <option value="todo">To Do</option>
            <option value="progress">In Progress</option>
            <option value="completed">Completed</option>
            <option value="overdue">Overdue</option>
        </select>

    </div>


    <div class="table-wrap">

        <table class="task-table">

            <thead>

                <tr>
                    <th>Task</th>
                    <th>Category</th>
                    <th>Assignee</th>
                    <th>Priority</th>
                    <th>Due Date</th>
                    <th>Progress</th>
                    <th>Status</th>
                    <th></th>
                </tr>

            </thead>


            <tbody id="taskBody">

                <!-- Task 1 -->

                <tr
                    data-category="community"
                    data-priority="high"
                    data-status="progress"
                >

                    <td>

                        <div class="task-title">
                            Complete village walking path survey
                        </div>

                        <div class="task-id">
                            TSK-00064
                        </div>

                    </td>

                    <td>
                        <span class="category">
                            Community Project
                        </span>
                    </td>

                    <td>

                        <div class="assignee">

                            <div class="avatar">
                                AA
                            </div>

                            <div>

                                <div class="assignee-name">
                                    Abid Anwar
                                </div>

                                <div class="assignee-role">
                                    Project Committee
                                </div>

                            </div>

                        </div>

                    </td>

                    <td>
                        <span class="priority high">
                            High
                        </span>
                    </td>

                    <td class="date">
                        20 Aug 2026
                    </td>

                    <td>

                        <div class="progress-wrap">

                            <div class="progress">
                                <span style="width:65%"></span>
                            </div>

                            <div class="progress-label">
                                65% complete
                            </div>

                        </div>

                    </td>

                    <td>
                        <span class="status progress">
                            In Progress
                        </span>
                    </td>

                    <td>

                        <a
                            href="{{ route('operations.tasks.show', ['id' => 1]) }}"
                            class="action"
                        >
                            <i class="bi bi-eye"></i>
                        </a>

                    </td>

                </tr>


                <!-- Task 2 -->

                <tr
                    data-category="welfare"
                    data-priority="medium"
                    data-status="todo"
                >

                    <td>

                        <div class="task-title">
                            Verify vulnerable family list
                        </div>

                        <div class="task-id">
                            TSK-00063
                        </div>

                    </td>

                    <td>
                        <span class="category">
                            Welfare
                        </span>
                    </td>

                    <td>

                        <div class="assignee">

                            <div class="avatar">
                                NA
                            </div>

                            <div>

                                <div class="assignee-name">
                                    Naveed Alam
                                </div>

                                <div class="assignee-role">
                                    Welfare Committee
                                </div>

                            </div>

                        </div>

                    </td>

                    <td>
                        <span class="priority medium">
                            Medium
                        </span>
                    </td>

                    <td class="date">
                        22 Aug 2026
                    </td>

                    <td>

                        <div class="progress-wrap">

                            <div class="progress">
                                <span style="width:20%"></span>
                            </div>

                            <div class="progress-label">
                                20% complete
                            </div>

                        </div>

                    </td>

                    <td>
                        <span class="status todo">
                            To Do
                        </span>
                    </td>

                    <td>

                        <a
                            href="{{ route('operations.tasks.show', ['id' => 2]) }}"
                            class="action"
                        >
                            <i class="bi bi-eye"></i>
                        </a>

                    </td>

                </tr>


                <!-- Task 3 -->

                <tr
                    data-category="finance"
                    data-priority="high"
                    data-status="todo"
                >

                    <td>

                        <div class="task-title">
                            Reconcile Qard recovery records
                        </div>

                        <div class="task-id">
                            TSK-00062
                        </div>

                    </td>

                    <td>
                        <span class="category">
                            Finance
                        </span>
                    </td>

                    <td>

                        <div class="assignee">

                            <div class="avatar">
                                TR
                            </div>

                            <div>

                                <div class="assignee-name">
                                    Treasurer
                                </div>

                                <div class="assignee-role">
                                    Finance
                                </div>

                            </div>

                        </div>

                    </td>

                    <td>
                        <span class="priority high">
                            High
                        </span>
                    </td>

                    <td class="date">
                        19 Aug 2026
                    </td>

                    <td>

                        <div class="progress-wrap">

                            <div class="progress">
                                <span style="width:10%"></span>
                            </div>

                            <div class="progress-label">
                                10% complete
                            </div>

                        </div>

                    </td>

                    <td>
                        <span class="status todo">
                            To Do
                        </span>
                    </td>

                    <td>

                        <a
                            href="{{ route('operations.tasks.show', ['id' => 3]) }}"
                            class="action"
                        >
                            <i class="bi bi-eye"></i>
                        </a>

                    </td>

                </tr>


                <!-- Task 4 -->

                <tr
                    data-category="admin"
                    data-priority="low"
                    data-status="completed"
                >

                    <td>

                        <div class="task-title">
                            Update organization member records
                        </div>

                        <div class="task-id">
                            TSK-00061
                        </div>

                    </td>

                    <td>
                        <span class="category">
                            Administration
                        </span>
                    </td>

                    <td>

                        <div class="assignee">

                            <div class="avatar">
                                SI
                            </div>

                            <div>

                                <div class="assignee-name">
                                    Secretary
                                </div>

                                <div class="assignee-role">
                                    Administration
                                </div>

                            </div>

                        </div>

                    </td>

                    <td>
                        <span class="priority low">
                            Low
                        </span>
                    </td>

                    <td class="date">
                        16 Aug 2026
                    </td>

                    <td>

                        <div class="progress-wrap">

                            <div class="progress">
                                <span style="width:100%"></span>
                            </div>

                            <div class="progress-label">
                                100% complete
                            </div>

                        </div>

                    </td>

                    <td>
                        <span class="status completed">
                            Completed
                        </span>
                    </td>

                    <td>

                        <a
                            href="{{ route('operations.tasks.show', ['id' => 4]) }}"
                            class="action"
                        >
                            <i class="bi bi-eye"></i>
                        </a>

                    </td>

                </tr>


                <!-- Task 5 -->

                <tr
                    data-category="followup"
                    data-priority="medium"
                    data-status="overdue"
                >

                    <td>

                        <div class="task-title">
                            Follow up on small business monitoring
                        </div>

                        <div class="task-id">
                            TSK-00060
                        </div>

                    </td>

                    <td>
                        <span class="category">
                            Follow-up
                        </span>
                    </td>

                    <td>

                        <div class="assignee">

                            <div class="avatar">
                                AR
                            </div>

                            <div>

                                <div class="assignee-name">
                                    Ali Rahman
                                </div>

                                <div class="assignee-role">
                                    Implementation Committee
                                </div>

                            </div>

                        </div>

                    </td>

                    <td>
                        <span class="priority medium">
                            Medium
                        </span>
                    </td>

                    <td class="date">
                        15 Aug 2026
                    </td>

                    <td>

                        <div class="progress-wrap">

                            <div class="progress">
                                <span style="width:55%"></span>
                            </div>

                            <div class="progress-label">
                                55% complete
                            </div>

                        </div>

                    </td>

                    <td>
                        <span class="status overdue">
                            Overdue
                        </span>
                    </td>

                    <td>

                        <a
                            href="{{ route('operations.tasks.show', ['id' => 5]) }}"
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
            Showing 1–5 of 64 tasks
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

    <i class="bi bi-check2-square"></i>

    <div>

        <strong>
            Task management
        </strong>

        <p>
            Tasks can be assigned to members or committees, linked to
            organizational activities and followed through completion.
            Overdue work should remain visible for follow-up and accountability.
        </p>

    </div>

</div>

@endsection


@push('scripts')
<script>

    const search =
        document.getElementById('taskSearch');

    const category =
        document.getElementById('categoryFilter');

    const priority =
        document.getElementById('priorityFilter');

    const status =
        document.getElementById('statusFilter');

    const rows =
        document.querySelectorAll('#taskBody tr');


    function filterTasks() {

        const query =
            search.value.toLowerCase().trim();

        const categoryValue =
            category.value;

        const priorityValue =
            priority.value;

        const statusValue =
            status.value;


        rows.forEach(row => {

            const text =
                row.textContent.toLowerCase();

            const rowCategory =
                row.dataset.category;

            const rowPriority =
                row.dataset.priority;

            const rowStatus =
                row.dataset.status;


            const matchesSearch =
                !query || text.includes(query);

            const matchesCategory =
                !categoryValue ||
                rowCategory === categoryValue;

            const matchesPriority =
                !priorityValue ||
                rowPriority === priorityValue;

            const matchesStatus =
                !statusValue ||
                rowStatus === statusValue;


            row.style.display =
                matchesSearch &&
                matchesCategory &&
                matchesPriority &&
                matchesStatus
                    ? ''
                    : 'none';

        });

    }


    search.addEventListener(
        'input',
        filterTasks
    );

    category.addEventListener(
        'change',
        filterTasks
    );

    priority.addEventListener(
        'change',
        filterTasks
    );

    status.addEventListener(
        'change',
        filterTasks
    );

</script>
@endpush