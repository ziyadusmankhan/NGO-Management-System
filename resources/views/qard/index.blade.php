@extends('layouts.app')

@section('title', 'Qard-e-Hasana')

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
        font-size: 25px;
        font-weight:700;
        letter-spacing:-.5px;
    }

    .page-header p {
        margin:7px 0 0;
        color:var(--muted);
        font-size: 16px;
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
        font-size: 15px;
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

    /* SUMMARY */

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
        font-size: 14px;
    }

    .summary-value {
        margin-top:6px;
        font-size: 20px;
        font-weight:700;
    }

    .summary-meta {
        margin-top:4px;
        color: #6d7572;
        font-size: 13px;
    }

    /* MAIN */

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
        color: #6f7672;
        font-size: 16px;
    }

    .search input {
        width:100%;
        height:40px;
        border:1px solid var(--border);
        border-radius:9px;
        padding:0 12px 0 36px;
        background:#fafcfa;
        outline:none;
        font-size: 15px;
    }

    .filter {
        height:40px;
        min-width:145px;
        border:1px solid var(--border);
        border-radius:9px;
        padding:0 28px 0 12px;
        background:#fff;
        color:#68756f;
        font-size: 15px;
        outline:none;
    }

    /* TABLE */

    .table-wrap {
        overflow-x:auto;
    }

    .qard-table {
        width:100%;
        min-width:1100px;
        border-collapse:collapse;
    }

    .qard-table th {
        padding:11px 18px;
        background:#fafcfb;
        border-bottom:1px solid #eef1ef;
        color: #6c7470;
        font-size: 13px;
        font-weight:700;
        text-transform:uppercase;
        letter-spacing:.6px;
        text-align:left;
        white-space:nowrap;
    }

    .qard-table td {
        padding:13px 18px;
        border-bottom:1px solid #f0f3f1;
        font-size: 14px;
        vertical-align:middle;
    }

    .qard-table tbody tr:hover {
        background:#fbfcfb;
    }

    .qard-table tbody tr:last-child td {
        border-bottom:none;
    }

    .person {
        display:flex;
        align-items:center;
        gap:9px;
    }

    .avatar {
        width:35px;
        height:35px;
        border-radius:10px;
        background:var(--primary-soft);
        color:var(--primary);
        display:flex;
        align-items:center;
        justify-content:center;
        font-size: 14px;
        font-weight:700;
        flex-shrink:0;
    }

    .person-name {
        font-size: 15px;
        font-weight:650;
    }

    .person-id {
        margin-top:3px;
        color: #6d7571;
        font-size: 13px;
    }

    .amount {
        font-weight:700;
        color:#4b5851;
    }

    .amount.recovered {
        color:#397955;
    }

    .amount.outstanding {
        color: #976d38;
    }

    .status {
        display:inline-flex;
        align-items:center;
        gap:5px;
        padding:5px 8px;
        border-radius:20px;
        font-size: 13px;
        font-weight:650;
        white-space:nowrap;
    }

    .status::before {
        content:"";
        width:5px;
        height:5px;
        border-radius:50%;
    }

    .status.active {
        background:#edf7f0;
        color:#397955;
    }

    .status.active::before {
        background:#4c9a6c;
    }

    .status.pending {
        background:#fff5e9;
        color: #976d38;
    }

    .status.pending::before {
        background:#d19a4d;
    }

    .status.completed {
        background:#eef3f8;
        color:#55758d;
    }

    .status.completed::before {
        background:#6d8ea5;
    }

    .status.overdue {
        background:#fff0ef;
        color:#a85f58;
    }

    .status.overdue::before {
        background:#b96b63;
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
        color: #6e7772;
        font-size: 12px;
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
        color: #6c7771;
        text-decoration:none;
    }

    .action:hover {
        color:var(--primary);
        background:#f7faf8;
    }

    /* FOOTER */

    .table-footer {
        padding:14px 18px;
        border-top:1px solid #eef1ef;
        display:flex;
        justify-content:space-between;
        align-items:center;
        gap:10px;
    }

    .result {
        color: #6e7672;
        font-size: 13px;
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
        font-size: 13px;
    }

    .page-btn.active {
        background:var(--primary);
        border-color:var(--primary);
        color:#fff;
    }

    /* NOTICE */

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
        font-size: 17px;
    }

    .notice strong {
        display:block;
        font-size: 14px;
    }

    .notice p {
        margin:4px 0 0;
        color: #6b7670;
        font-size: 13px;
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

        <h1>Qard-e-Hasana</h1>

        <p>
            Manage interest-free principal-only loans, repayments,
            recoveries and fund recycling.
        </p>

    </div>

    <div class="actions">

        <button class="ui-btn">
            <i class="bi bi-download"></i>
            Export
        </button>

        <a
            href="{{ route('qard.create') }}"
            class="ui-btn primary"
        >
            <i class="bi bi-plus-lg"></i>
            New Qard
        </a>

    </div>

</div>


<!-- SUMMARY -->

<div class="summary-grid">

    <div class="summary-card">
        <div class="summary-label">Active Qards</div>
        <div class="summary-value">38</div>
        <div class="summary-meta">Currently being repaid</div>
    </div>

    <div class="summary-card">
        <div class="summary-label">Principal Issued</div>
        <div class="summary-value">PKR 2.84M</div>
        <div class="summary-meta">Total issued</div>
    </div>

    <div class="summary-card">
        <div class="summary-label">Principal Recovered</div>
        <div class="summary-value">PKR 1.76M</div>
        <div class="summary-meta">Recovered to date</div>
    </div>

    <div class="summary-card">
        <div class="summary-label">Outstanding</div>
        <div class="summary-value">PKR 1.08M</div>
        <div class="summary-meta">Remaining principal</div>
    </div>

    <div class="summary-card">
        <div class="summary-label">Overdue</div>
        <div class="summary-value">5</div>
        <div class="summary-meta">Require follow-up</div>
    </div>

</div>


<div class="main-card">

    <div class="toolbar">

        <div class="search">

            <i class="bi bi-search"></i>

            <input
                id="qardSearch"
                type="text"
                placeholder="Search borrower, Qard ID or purpose..."
            >

        </div>


        <select
            id="statusFilter"
            class="filter"
        >
            <option value="">All Statuses</option>
            <option value="active">Active</option>
            <option value="pending">Pending</option>
            <option value="completed">Completed</option>
            <option value="overdue">Overdue</option>
        </select>


        <select
            id="purposeFilter"
            class="filter"
        >
            <option value="">All Purposes</option>
            <option value="business">Business</option>
            <option value="education">Education</option>
            <option value="medical">Medical</option>
            <option value="emergency">Emergency</option>
            <option value="livelihood">Livelihood</option>
        </select>

    </div>


    <div class="table-wrap">

        <table class="qard-table">

            <thead>

                <tr>
                    <th>Borrower</th>
                    <th>Purpose</th>
                    <th>Principal</th>
                    <th>Recovered</th>
                    <th>Outstanding</th>
                    <th>Repayment</th>
                    <th>Status</th>
                    <th>Next Due</th>
                    <th></th>
                </tr>

            </thead>


            <tbody id="qardBody">

                <!-- 1 -->

                <tr
                    data-status="active"
                    data-purpose="business"
                >

                    <td>

                        <div class="person">

                            <div class="avatar">
                                AK
                            </div>

                            <div>

                                <div class="person-name">
                                    Ahmed Khan
                                </div>

                                <div class="person-id">
                                    QRD-00031 · BEN-00091
                                </div>

                            </div>

                        </div>

                    </td>

                    <td>
                        Business setup
                    </td>

                    <td class="amount">
                        PKR 100,000
                    </td>

                    <td class="amount recovered">
                        PKR 40,000
                    </td>

                    <td class="amount outstanding">
                        PKR 60,000
                    </td>

                    <td>

                        <div class="progress-wrap">

                            <div class="progress">
                                <span style="width:40%"></span>
                            </div>

                            <div class="progress-label">
                                4 / 10 installments
                            </div>

                        </div>

                    </td>

                    <td>

                        <span class="status active">
                            Active
                        </span>

                    </td>

                    <td>
                        01 Sep 2026
                    </td>

                    <td>

                        <a
                            href="{{ route('qard.show', ['id' => 1]) }}"
                            class="action"
                        >
                            <i class="bi bi-eye"></i>
                        </a>

                    </td>

                </tr>


                <!-- 2 -->

                <tr
                    data-status="active"
                    data-purpose="education"
                >

                    <td>

                        <div class="person">

                            <div class="avatar">
                                FM
                            </div>

                            <div>

                                <div class="person-name">
                                    Farzana Malik
                                </div>

                                <div class="person-id">
                                    QRD-00030 · BEN-00087
                                </div>

                            </div>

                        </div>

                    </td>

                    <td>
                        Education
                    </td>

                    <td class="amount">
                        PKR 60,000
                    </td>

                    <td class="amount recovered">
                        PKR 30,000
                    </td>

                    <td class="amount outstanding">
                        PKR 30,000
                    </td>

                    <td>

                        <div class="progress-wrap">

                            <div class="progress">
                                <span style="width:50%"></span>
                            </div>

                            <div class="progress-label">
                                5 / 10 installments
                            </div>

                        </div>

                    </td>

                    <td>

                        <span class="status active">
                            Active
                        </span>

                    </td>

                    <td>
                        05 Sep 2026
                    </td>

                    <td>

                        <a
                            href="{{ route('qard.show', ['id' => 2]) }}"
                            class="action"
                        >
                            <i class="bi bi-eye"></i>
                        </a>

                    </td>

                </tr>


                <!-- 3 -->

                <tr
                    data-status="overdue"
                    data-purpose="medical"
                >

                    <td>

                        <div class="person">

                            <div class="avatar">
                                MN
                            </div>

                            <div>

                                <div class="person-name">
                                    Muhammad Noor
                                </div>

                                <div class="person-id">
                                    QRD-00029 · BEN-00079
                                </div>

                            </div>

                        </div>

                    </td>

                    <td>
                        Medical
                    </td>

                    <td class="amount">
                        PKR 50,000
                    </td>

                    <td class="amount recovered">
                        PKR 20,000
                    </td>

                    <td class="amount outstanding">
                        PKR 30,000
                    </td>

                    <td>

                        <div class="progress-wrap">

                            <div class="progress">
                                <span style="width:40%"></span>
                            </div>

                            <div class="progress-label">
                                4 / 10 installments
                            </div>

                        </div>

                    </td>

                    <td>

                        <span class="status overdue">
                            Overdue
                        </span>

                    </td>

                    <td>
                        10 Aug 2026
                    </td>

                    <td>

                        <a
                            href="{{ route('qard.show', ['id' => 3]) }}"
                            class="action"
                        >
                            <i class="bi bi-eye"></i>
                        </a>

                    </td>

                </tr>


                <!-- 4 -->

                <tr
                    data-status="completed"
                    data-purpose="livelihood"
                >

                    <td>

                        <div class="person">

                            <div class="avatar">
                                HS
                            </div>

                            <div>

                                <div class="person-name">
                                    Hamid Shah
                                </div>

                                <div class="person-id">
                                    QRD-00028 · BEN-00065
                                </div>

                            </div>

                        </div>

                    </td>

                    <td>
                        Livelihood
                    </td>

                    <td class="amount">
                        PKR 80,000
                    </td>

                    <td class="amount recovered">
                        PKR 80,000
                    </td>

                    <td class="amount recovered">
                        PKR 0
                    </td>

                    <td>

                        <div class="progress-wrap">

                            <div class="progress">
                                <span style="width:100%"></span>
                            </div>

                            <div class="progress-label">
                                8 / 8 installments
                            </div>

                        </div>

                    </td>

                    <td>

                        <span class="status completed">
                            Completed
                        </span>

                    </td>

                    <td>
                        —
                    </td>

                    <td>

                        <a
                            href="{{ route('qard.show', ['id' => 4]) }}"
                            class="action"
                        >
                            <i class="bi bi-eye"></i>
                        </a>

                    </td>

                </tr>


                <!-- 5 -->

                <tr
                    data-status="pending"
                    data-purpose="emergency"
                >

                    <td>

                        <div class="person">

                            <div class="avatar">
                                YA
                            </div>

                            <div>

                                <div class="person-name">
                                    Yasir Ahmad
                                </div>

                                <div class="person-id">
                                    QRD-00027 · BEN-00094
                                </div>

                            </div>

                        </div>

                    </td>

                    <td>
                        Emergency
                    </td>

                    <td class="amount">
                        PKR 35,000
                    </td>

                    <td class="amount recovered">
                        PKR 0
                    </td>

                    <td class="amount outstanding">
                        PKR 35,000
                    </td>

                    <td>

                        <div class="progress-wrap">

                            <div class="progress">
                                <span style="width:0%"></span>
                            </div>

                            <div class="progress-label">
                                Not started
                            </div>

                        </div>

                    </td>

                    <td>

                        <span class="status pending">
                            Pending
                        </span>

                    </td>

                    <td>
                        01 Sep 2026
                    </td>

                    <td>

                        <a
                            href="{{ route('qard.show', ['id' => 5]) }}"
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
            Showing 1–5 of 38 active Qards
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

    <i class="bi bi-arrow-repeat"></i>

    <div>

        <strong>
            Principal-only recovery
        </strong>

        <p>
            Qard-e-Hasana is interest-free. Recovered amounts represent
            principal only and can return to the relevant fund so the same
            capital can support another deserving person.
        </p>

    </div>

</div>

@endsection


@push('scripts')
<script>

    const search =
        document.getElementById('qardSearch');

    const status =
        document.getElementById('statusFilter');

    const purpose =
        document.getElementById('purposeFilter');

    const rows =
        document.querySelectorAll('#qardBody tr');


    function filterQards() {

        const query =
            search.value.toLowerCase().trim();

        const statusValue =
            status.value;

        const purposeValue =
            purpose.value;


        rows.forEach(row => {

            const text =
                row.textContent.toLowerCase();

            const rowStatus =
                row.dataset.status;

            const rowPurpose =
                row.dataset.purpose;


            const matchesSearch =
                !query || text.includes(query);

            const matchesStatus =
                !statusValue ||
                rowStatus === statusValue;

            const matchesPurpose =
                !purposeValue ||
                rowPurpose === purposeValue;


            row.style.display =
                matchesSearch &&
                matchesStatus &&
                matchesPurpose
                    ? ''
                    : 'none';

        });

    }


    search.addEventListener(
        'input',
        filterQards
    );

    status.addEventListener(
        'change',
        filterQards
    );

    purpose.addEventListener(
        'change',
        filterQards
    );

</script>
@endpush