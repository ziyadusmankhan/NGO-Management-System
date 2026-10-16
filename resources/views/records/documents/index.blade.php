@extends('layouts.app')

@section('title', 'Documents')

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

    .document-table {
        width:100%;
        min-width:1200px;
        border-collapse:collapse;
    }

    .document-table th {
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

    .document-table td {
        padding:13px 18px;
        border-bottom:1px solid #f0f3f1;
        font-size:13px;
        vertical-align:middle;
    }

    .document-table tbody tr:hover {
        background:#fbfcfb;
    }

    .document-table tbody tr:last-child td {
        border-bottom:none;
    }

    .document {
        display:flex;
        align-items:center;
        gap:10px;
    }

    .document-icon {
        width:38px;
        height:38px;
        border-radius:10px;
        display:flex;
        align-items:center;
        justify-content:center;
        font-size:16px;
        flex-shrink:0;
    }

    .document-icon.pdf {
        background:#fff0ef;
        color:#a85f58;
    }

    .document-icon.image {
        background:#eef3f8;
        color:#55758d;
    }

    .document-icon.word {
        background:#eef3f8;
        color:#55758d;
    }

    .document-icon.sheet {
        background:#edf7f0;
        color:#397955;
    }

    .document-icon.other {
        background:#f1eef7;
        color:#77668c;
    }

    .document-name {
        font-size:14px;
        font-weight:650;
        color:#46534c;
    }

    .document-id {
        margin-top:3px;
        color:#98a29d;
        font-size:13px;
    }

    .document-type,
    .access,
    .source {
        display:inline-flex;
        align-items:center;
        gap:5px;
        padding:5px 8px;
        border-radius:20px;
        font-size:13px;
        font-weight:650;
        white-space:nowrap;
    }

    .document-type {
        background:#eef3f8;
        color:#55758d;
    }

    .document-type.financial {
        background:#edf7f0;
        color:#397955;
    }

    .document-type.organizational {
        background:#f1eef7;
        color:#77668c;
    }

    .document-type.project {
        background:#fff5e9;
        color:#a8793e;
    }

    .access.internal {
        background:#f5f7f6;
        color:#68756f;
    }

    .access.restricted {
        background:#fff0ef;
        color:#a85f58;
    }

    .access.confidential {
        background:#f1eef7;
        color:#77668c;
    }

    .source {
        background:#fafcfb;
        border:1px solid #e7ece9;
        color:#68756f;
    }

    .size,
    .date {
        color:#68756f;
        white-space:nowrap;
    }

    .uploader {
        font-size:13px;
        font-weight:600;
    }

    .uploader-role {
        margin-top:3px;
        color:#98a29d;
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

        <h1>Documents</h1>

        <p>
            Central library for organizational, financial, project and supporting records.
        </p>

    </div>

    <div class="actions">

        <button class="ui-btn">
            <i class="bi bi-folder-plus"></i>
            New Folder
        </button>

        <a
            href="{{ route('records.documents.create') }}"
            class="ui-btn primary"
        >
            <i class="bi bi-cloud-arrow-up"></i>
            Upload Document
        </a>

    </div>

</div>


<!-- SUMMARY -->

<div class="summary-grid">

    <div class="summary-card">
        <div class="summary-label">Total Documents</div>
        <div class="summary-value">486</div>
        <div class="summary-meta">All organizational records</div>
    </div>

    <div class="summary-card">
        <div class="summary-label">Financial Records</div>
        <div class="summary-value">138</div>
        <div class="summary-meta">Donations, expenses and funds</div>
    </div>

    <div class="summary-card">
        <div class="summary-label">Project Records</div>
        <div class="summary-value">96</div>
        <div class="summary-meta">Projects and procurement</div>
    </div>

    <div class="summary-card">
        <div class="summary-label">Restricted</div>
        <div class="summary-value">42</div>
        <div class="summary-meta">Limited-access records</div>
    </div>

    <div class="summary-card">
        <div class="summary-label">Uploaded This Month</div>
        <div class="summary-value">31</div>
        <div class="summary-meta">August 2026</div>
    </div>

</div>


<div class="main-card">

    <div class="toolbar">

        <div class="search">

            <i class="bi bi-search"></i>

            <input
                id="documentSearch"
                type="text"
                placeholder="Search document, reference, uploader or source..."
            >

        </div>


        <select
            id="typeFilter"
            class="filter"
        >
            <option value="">All Document Types</option>
            <option value="financial">Financial</option>
            <option value="organizational">Organizational</option>
            <option value="project">Project</option>
            <option value="general">General</option>
        </select>


        <select
            id="accessFilter"
            class="filter"
        >
            <option value="">All Access Levels</option>
            <option value="internal">Internal</option>
            <option value="restricted">Restricted</option>
            <option value="confidential">Confidential</option>
        </select>


        <select
            id="fileFilter"
            class="filter"
        >
            <option value="">All File Types</option>
            <option value="pdf">PDF</option>
            <option value="image">Image</option>
            <option value="word">Document</option>
            <option value="sheet">Spreadsheet</option>
        </select>

    </div>


    <div class="table-wrap">

        <table class="document-table">

            <thead>

                <tr>
                    <th>Document</th>
                    <th>Type</th>
                    <th>Source</th>
                    <th>Access</th>
                    <th>Uploaded By</th>
                    <th>Size</th>
                    <th>Date</th>
                    <th></th>
                </tr>

            </thead>


            <tbody id="documentBody">

                <!-- 1 -->

                <tr
                    data-type="financial"
                    data-access="internal"
                    data-file="pdf"
                >

                    <td>

                        <div class="document">

                            <div class="document-icon pdf">
                                <i class="bi bi-file-earmark-pdf"></i>
                            </div>

                            <div>

                                <div class="document-name">
                                    Donation Receipt RCT-00127
                                </div>

                                <div class="document-id">
                                    DOC-00486 · DON-00127
                                </div>

                            </div>

                        </div>

                    </td>

                    <td>
                        <span class="document-type financial">
                            Financial
                        </span>
                    </td>

                    <td>
                        <span class="source">
                            Donation
                        </span>
                    </td>

                    <td>
                        <span class="access internal">
                            Internal
                        </span>
                    </td>

                    <td>

                        <div class="uploader">
                            Treasurer
                        </div>

                        <div class="uploader-role">
                            Finance
                        </div>

                    </td>

                    <td class="size">
                        245 KB
                    </td>

                    <td class="date">
                        16 Aug 2026
                    </td>

                    <td>

                        <a
                            href="{{ route('records.documents.show', ['id' => 1]) }}"
                            class="action"
                        >
                            <i class="bi bi-eye"></i>
                        </a>

                    </td>

                </tr>


                <!-- 2 -->

                <tr
                    data-type="project"
                    data-access="internal"
                    data-file="pdf"
                >

                    <td>

                        <div class="document">

                            <div class="document-icon pdf">
                                <i class="bi bi-file-earmark-pdf"></i>
                            </div>

                            <div>

                                <div class="document-name">
                                    Village Walking Path Survey
                                </div>

                                <div class="document-id">
                                    DOC-00485 · PRJ-00014
                                </div>

                            </div>

                        </div>

                    </td>

                    <td>
                        <span class="document-type project">
                            Project
                        </span>
                    </td>

                    <td>
                        <span class="source">
                            Community Project
                        </span>
                    </td>

                    <td>
                        <span class="access internal">
                            Internal
                        </span>
                    </td>

                    <td>

                        <div class="uploader">
                            Abid Anwar
                        </div>

                        <div class="uploader-role">
                            Project Committee
                        </div>

                    </td>

                    <td class="size">
                        1.8 MB
                    </td>

                    <td class="date">
                        18 Aug 2026
                    </td>

                    <td>

                        <a
                            href="{{ route('records.documents.show', ['id' => 2]) }}"
                            class="action"
                        >
                            <i class="bi bi-eye"></i>
                        </a>

                    </td>

                </tr>


                <!-- 3 -->

                <tr
                    data-type="organizational"
                    data-access="restricted"
                    data-file="pdf"
                >

                    <td>

                        <div class="document">

                            <div class="document-icon pdf">
                                <i class="bi bi-file-earmark-pdf"></i>
                            </div>

                            <div>

                                <div class="document-name">
                                    Committee Meeting Minutes
                                </div>

                                <div class="document-id">
                                    DOC-00484 · MTG-00037
                                </div>

                            </div>

                        </div>

                    </td>

                    <td>
                        <span class="document-type organizational">
                            Organizational
                        </span>
                    </td>

                    <td>
                        <span class="source">
                            Meeting
                        </span>
                    </td>

                    <td>
                        <span class="access restricted">
                            Restricted
                        </span>
                    </td>

                    <td>

                        <div class="uploader">
                            Secretary
                        </div>

                        <div class="uploader-role">
                            Administration
                        </div>

                    </td>

                    <td class="size">
                        680 KB
                    </td>

                    <td class="date">
                        16 Aug 2026
                    </td>

                    <td>

                        <a
                            href="{{ route('records.documents.show', ['id' => 3]) }}"
                            class="action"
                        >
                            <i class="bi bi-eye"></i>
                        </a>

                    </td>

                </tr>


                <!-- 4 -->

                <tr
                    data-type="financial"
                    data-access="confidential"
                    data-file="sheet"
                >

                    <td>

                        <div class="document">

                            <div class="document-icon sheet">
                                <i class="bi bi-file-earmark-spreadsheet"></i>
                            </div>

                            <div>

                                <div class="document-name">
                                    Qard Recovery Register
                                </div>

                                <div class="document-id">
                                    DOC-00483 · QRD-REGISTER
                                </div>

                            </div>

                        </div>

                    </td>

                    <td>
                        <span class="document-type financial">
                            Financial
                        </span>
                    </td>

                    <td>
                        <span class="source">
                            Qard-e-Hasana
                        </span>
                    </td>

                    <td>
                        <span class="access confidential">
                            Confidential
                        </span>
                    </td>

                    <td>

                        <div class="uploader">
                            Treasurer
                        </div>

                        <div class="uploader-role">
                            Finance
                        </div>

                    </td>

                    <td class="size">
                        94 KB
                    </td>

                    <td class="date">
                        15 Aug 2026
                    </td>

                    <td>

                        <a
                            href="{{ route('records.documents.show', ['id' => 4]) }}"
                            class="action"
                        >
                            <i class="bi bi-eye"></i>
                        </a>

                    </td>

                </tr>


                <!-- 5 -->

                <tr
                    data-type="project"
                    data-access="internal"
                    data-file="image"
                >

                    <td>

                        <div class="document">

                            <div class="document-icon image">
                                <i class="bi bi-image"></i>
                            </div>

                            <div>

                                <div class="document-name">
                                    Community Water Project Photos
                                </div>

                                <div class="document-id">
                                    DOC-00482 · PRJ-00011
                                </div>

                            </div>

                        </div>

                    </td>

                    <td>
                        <span class="document-type project">
                            Project
                        </span>
                    </td>

                    <td>
                        <span class="source">
                            Project
                        </span>
                    </td>

                    <td>
                        <span class="access internal">
                            Internal
                        </span>
                    </td>

                    <td>

                        <div class="uploader">
                            Project Committee
                        </div>

                        <div class="uploader-role">
                            Field Team
                        </div>

                    </td>

                    <td class="size">
                        4.6 MB
                    </td>

                    <td class="date">
                        12 Aug 2026
                    </td>

                    <td>

                        <a
                            href="{{ route('records.documents.show', ['id' => 5]) }}"
                            class="action"
                        >
                            <i class="bi bi-eye"></i>
                        </a>

                    </td>

                </tr>


                <!-- 6 -->

                <tr
                    data-type="organizational"
                    data-access="restricted"
                    data-file="word"
                >

                    <td>

                        <div class="document">

                            <div class="document-icon word">
                                <i class="bi bi-file-earmark-word"></i>
                            </div>

                            <div>

                                <div class="document-name">
                                    Organization Constitution
                                </div>

                                <div class="document-id">
                                    DOC-00481 · ORG-CONST
                                </div>

                            </div>

                        </div>

                    </td>

                    <td>
                        <span class="document-type organizational">
                            Organizational
                        </span>
                    </td>

                    <td>
                        <span class="source">
                            Organization
                        </span>
                    </td>

                    <td>
                        <span class="access restricted">
                            Restricted
                        </span>
                    </td>

                    <td>

                        <div class="uploader">
                            General Secretary
                        </div>

                        <div class="uploader-role">
                            Administration
                        </div>

                    </td>

                    <td class="size">
                        320 KB
                    </td>

                    <td class="date">
                        01 Aug 2026
                    </td>

                    <td>

                        <a
                            href="{{ route('records.documents.show', ['id' => 6]) }}"
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
            Showing 1–6 of 486 documents
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

    <i class="bi bi-folder2-open"></i>

    <div>

        <strong>
            Document management
        </strong>

        <p>
            Organizational records should remain linked to their source,
            categorized appropriately and protected according to their
            access level. Financial, beneficiary and confidential records
            should receive stronger access controls.
        </p>

    </div>

</div>

@endsection


@push('scripts')
<script>

    const search =
        document.getElementById('documentSearch');

    const type =
        document.getElementById('typeFilter');

    const access =
        document.getElementById('accessFilter');

    const file =
        document.getElementById('fileFilter');

    const rows =
        document.querySelectorAll(
            '#documentBody tr'
        );


    function filterDocuments() {

        const query =
            search.value.toLowerCase().trim();

        const typeValue =
            type.value;

        const accessValue =
            access.value;

        const fileValue =
            file.value;


        rows.forEach(row => {

            const text =
                row.textContent.toLowerCase();

            const rowType =
                row.dataset.type;

            const rowAccess =
                row.dataset.access;

            const rowFile =
                row.dataset.file;


            const matchesSearch =
                !query ||
                text.includes(query);

            const matchesType =
                !typeValue ||
                rowType === typeValue;

            const matchesAccess =
                !accessValue ||
                rowAccess === accessValue;

            const matchesFile =
                !fileValue ||
                rowFile === fileValue;


            row.style.display =
                matchesSearch &&
                matchesType &&
                matchesAccess &&
                matchesFile
                    ? ''
                    : 'none';

        });

    }


    search.addEventListener(
        'input',
        filterDocuments
    );

    type.addEventListener(
        'change',
        filterDocuments
    );

    access.addEventListener(
        'change',
        filterDocuments
    );

    file.addEventListener(
        'change',
        filterDocuments
    );

</script>
@endpush