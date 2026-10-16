@extends('layouts.app')

@section('title', 'Document Details')

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
        overflow-wrap:anywhere;
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

    .type-badge,
    .access-badge {
        display:inline-flex;
        align-items:center;
        gap:5px;
        padding:5px 8px;
        border-radius:20px;
        font-size:13px;
        font-weight:650;
        white-space:nowrap;
    }

    .type-badge {
        background:#edf7f0;
        color:#397955;
    }

    .access-badge.internal {
        background:#f5f7f6;
        color:#68756f;
    }

    .access-badge.restricted {
        background:#fff0ef;
        color:#a85f58;
    }

    .access-badge.confidential {
        background:#f1eef7;
        color:#77668c;
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
        font-size:14px;
    }

    .summary-box.highlight strong {
        color:var(--primary);
    }

    .info-grid {
        display:grid;
        grid-template-columns:repeat(2,minmax(0,1fr));
        gap:17px;
    }

    .info label {
        display:block;
        margin-bottom:5px;
        color:#98a29d;
        font-size:13px;
        text-transform:uppercase;
        letter-spacing:.5px;
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

    /* FILE PREVIEW */

    .file-preview {
        min-height:300px;
        border:1px solid #e6ece9;
        border-radius:12px;
        background:linear-gradient(145deg,#fafcfb,#f4f7f5);
        display:flex;
        align-items:center;
        justify-content:center;
        flex-direction:column;
        text-align:center;
        padding:30px;
    }

    .file-preview-icon {
        width:74px;
        height:74px;
        border-radius:19px;
        background:#fff0ef;
        color:#a85f58;
        display:flex;
        align-items:center;
        justify-content:center;
        font-size:31px;
    }

    .file-preview strong {
        margin-top:14px;
        font-size:15px;
    }

    .file-preview span {
        margin-top:5px;
        color:#929d97;
        font-size:13px;
    }

    .preview-actions {
        margin-top:15px;
        display:flex;
        gap:7px;
    }

    /* SOURCE */

    .source-box {
        display:flex;
        align-items:center;
        gap:11px;
        padding:13px;
        border:1px solid #edf1ee;
        background:#fafcfb;
        border-radius:10px;
    }

    .source-icon {
        width:38px;
        height:38px;
        border-radius:10px;
        background:var(--primary-soft);
        color:var(--primary);
        display:flex;
        align-items:center;
        justify-content:center;
        flex-shrink:0;
    }

    .source-info {
        flex:1;
    }

    .source-info strong {
        display:block;
        font-size:14px;
    }

    .source-info span {
        display:block;
        margin-top:3px;
        color:#96a19b;
        font-size:13px;
    }

    /* TAGS */

    .tags {
        display:flex;
        flex-wrap:wrap;
        gap:7px;
    }

    .tag {
        padding:6px 9px;
        border-radius:20px;
        background:var(--primary-soft);
        color:var(--primary);
        font-size:13px;
        font-weight:650;
    }

    /* VERSION */

    .version {
        display:flex;
        align-items:center;
        gap:10px;
        padding:11px 0;
        border-bottom:1px solid #f0f3f1;
    }

    .version:last-child {
        border-bottom:none;
    }

    .version-badge {
        width:35px;
        height:35px;
        border-radius:9px;
        background:#f4f7f5;
        color:var(--primary);
        display:flex;
        align-items:center;
        justify-content:center;
        font-size:13px;
        font-weight:700;
    }

    .version-info {
        flex:1;
    }

    .version-info strong {
        display:block;
        font-size:13px;
    }

    .version-info span {
        display:block;
        margin-top:3px;
        color:#96a19b;
        font-size:13px;
    }

    .current-version {
        padding:5px 8px;
        border-radius:20px;
        background:#edf7f0;
        color:#397955;
        font-size:12px;
        font-weight:650;
    }

    /* ACTIVITY */

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

    /* CONTROL */

    .control-row {
        display:flex;
        justify-content:space-between;
        gap:15px;
        align-items:center;
        padding:10px 0;
        border-bottom:1px solid #f0f3f1;
    }

    .control-row:last-child {
        border-bottom:none;
    }

    .control-label {
        color:#8d9892;
        font-size:13px;
    }

    .control-value {
        color:#4a5750;
        font-size:13px;
        font-weight:650;
        text-align:right;
    }

    /* SIDEBAR */

    .side-number {
        text-align:center;
        font-size:27px;
        font-weight:700;
        color:var(--primary);
        overflow-wrap:anywhere;
    }

    .side-label {
        text-align:center;
        margin-top:4px;
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
        .info-grid {
            grid-template-columns:1fr;
        }

        .full {
            grid-column:auto;
        }

        .preview-actions {
            flex-direction:column;
            width:100%;
        }
    }
</style>
@endpush


@section('content')

<div class="page-header">

    <div class="identity">

        <div class="icon-box">
            <i class="bi bi-file-earmark-pdf"></i>
        </div>

        <div>

            <div class="title">
                Donation Receipt RCT-00127
            </div>

            <div class="meta">
                DOC-00486 · Financial · Uploaded 16 Aug 2026
            </div>

        </div>

    </div>

    <div class="actions">

        <a
            href="{{ route('records.documents.index') }}"
            class="ui-btn"
        >
            <i class="bi bi-arrow-left"></i>
            Back
        </a>

        <button
            class="ui-btn"
            onclick="previewView()"
        >
            <i class="bi bi-eye"></i>
            Open
        </button>

        <button
            class="ui-btn primary"
            onclick="previewDownload()"
        >
            <i class="bi bi-download"></i>
            Download
        </button>

    </div>

</div>


<div class="layout">

    <div>

        <!-- OVERVIEW -->

        <div class="card">

            <div class="card-head">

                <div>
                    <strong>Document Overview</strong>

                    <span>
                        Identity, classification and current file information.
                    </span>
                </div>

                <span class="type-badge">
                    Financial
                </span>

            </div>

            <div class="card-body">

                <div class="summary-grid">

                    <div class="summary-box highlight">
                        <label>File Size</label>
                        <strong>245 KB</strong>
                    </div>

                    <div class="summary-box">
                        <label>Version</label>
                        <strong>1.0</strong>
                    </div>

                    <div class="summary-box">
                        <label>Format</label>
                        <strong>PDF</strong>
                    </div>

                    <div class="summary-box">
                        <label>Access</label>
                        <strong>Internal</strong>
                    </div>

                    <div class="summary-box">
                        <label>Document ID</label>
                        <strong>DOC-00486</strong>
                    </div>

                    <div class="summary-box">
                        <label>Document Date</label>
                        <strong>16 Aug 2026</strong>
                    </div>

                    <div class="summary-box">
                        <label>Uploaded By</label>
                        <strong>Treasurer</strong>
                    </div>

                    <div class="summary-box">
                        <label>Uploaded</label>
                        <strong>16 Aug 2026</strong>
                    </div>

                </div>

            </div>

        </div>


        <!-- FILE PREVIEW -->

        <div class="card">

            <div class="card-head">

                <div>
                    <strong>File Preview</strong>

                    <span>
                        Preview or open the stored document.
                    </span>
                </div>

                <span class="access-badge internal">
                    Internal
                </span>

            </div>

            <div class="card-body">

                <div class="file-preview">

                    <div class="file-preview-icon">
                        <i class="bi bi-file-earmark-pdf"></i>
                    </div>

                    <strong>
                        Donation Receipt RCT-00127
                    </strong>

                    <span>
                        PDF · 245 KB · Version 1.0
                    </span>

                    <div class="preview-actions">

                        <button
                            class="ui-btn"
                            onclick="previewView()"
                        >
                            <i class="bi bi-eye"></i>
                            Open Preview
                        </button>

                        <button
                            class="ui-btn primary"
                            onclick="previewDownload()"
                        >
                            <i class="bi bi-download"></i>
                            Download
                        </button>

                    </div>

                </div>

            </div>

        </div>


        <!-- DOCUMENT INFORMATION -->

        <div class="card">

            <div class="card-head">

                <div>
                    <strong>Document Information</strong>

                    <span>
                        Metadata and description.
                    </span>
                </div>

            </div>

            <div class="card-body">

                <div class="info-grid">

                    <div class="info">

                        <label>Document Name</label>

                        <strong>
                            Donation Receipt RCT-00127
                        </strong>

                    </div>


                    <div class="info">

                        <label>Document Type</label>

                        <strong>
                            Financial
                        </strong>

                    </div>


                    <div class="info">

                        <label>Language</label>

                        <strong>
                            English
                        </strong>

                    </div>


                    <div class="info">

                        <label>Version</label>

                        <strong>
                            1.0
                        </strong>

                    </div>


                    <div class="info">

                        <label>Uploaded By</label>

                        <strong>
                            Treasurer
                        </strong>

                    </div>


                    <div class="info">

                        <label>Access Level</label>

                        <strong>
                            Internal
                        </strong>

                    </div>


                    <div class="info full">

                        <label>Description</label>

                        <p>
                            Official donation receipt associated with
                            donation DON-00127 from Zaid Ahmad. The document
                            confirms receipt of PKR 100,000 for the organization.
                        </p>

                    </div>

                </div>

            </div>

        </div>


        <!-- LINKED SOURCE -->

        <div class="card">

            <div class="card-head">

                <div>
                    <strong>Linked Source Record</strong>

                    <span>
                        Organizational record this document belongs to.
                    </span>
                </div>

            </div>

            <div class="card-body">

                <div class="source-box">

                    <div class="source-icon">
                        <i class="bi bi-cash-coin"></i>
                    </div>

                    <div class="source-info">

                        <strong>
                            DON-00127 · Zaid Ahmad
                        </strong>

                        <span>
                            Donation · PKR 100,000 · General Fund
                        </span>

                    </div>

                    <a
                        href="{{ route('finance.donations.show', ['id' => 1]) }}"
                        class="ui-btn"
                    >
                        Open Record
                    </a>

                </div>

            </div>

        </div>


        <!-- TAGS -->

        <div class="card">

            <div class="card-head">

                <div>
                    <strong>Tags</strong>

                    <span>
                        Search keywords associated with this document.
                    </span>
                </div>

            </div>

            <div class="card-body">

                <div class="tags">

                    <span class="tag">
                        financial
                    </span>

                    <span class="tag">
                        donation
                    </span>

                    <span class="tag">
                        receipt
                    </span>

                    <span class="tag">
                        general-fund
                    </span>

                </div>

            </div>

        </div>


        <!-- VERSION HISTORY -->

        <div class="card">

            <div class="card-head">

                <div>
                    <strong>Version History</strong>

                    <span>
                        Previous versions of this document.
                    </span>
                </div>

            </div>

            <div class="card-body">

                <div class="version">

                    <div class="version-badge">
                        1.0
                    </div>

                    <div class="version-info">

                        <strong>
                            Current version
                        </strong>

                        <span>
                            Uploaded 16 Aug 2026 · Treasurer · 245 KB
                        </span>

                    </div>

                    <span class="current-version">
                        Current
                    </span>

                </div>


                <div class="version">

                    <div class="version-badge">
                        —
                    </div>

                    <div class="version-info">

                        <strong>
                            No previous versions
                        </strong>

                        <span>
                            This is the original uploaded record.
                        </span>

                    </div>

                </div>

            </div>

        </div>


        <!-- ACTIVITY -->

        <div class="card">

            <div class="card-head">

                <div>
                    <strong>Document Activity</strong>

                    <span>
                        Record of important document events.
                    </span>
                </div>

            </div>

            <div class="card-body">

                <div class="timeline-item">

                    <div class="timeline-dot"></div>

                    <div>

                        <div class="timeline-title">
                            Document uploaded
                        </div>

                        <div class="timeline-meta">
                            16 Aug 2026 · Treasurer
                        </div>

                        <div class="timeline-note">
                            Original donation receipt uploaded and linked to DON-00127.
                        </div>

                    </div>

                </div>


                <div class="timeline-item">

                    <div class="timeline-dot"></div>

                    <div>

                        <div class="timeline-title">
                            Document classified
                        </div>

                        <div class="timeline-meta">
                            16 Aug 2026 · System
                        </div>

                    </div>

                </div>


                <div class="timeline-item">

                    <div class="timeline-dot"></div>

                    <div>

                        <div class="timeline-title">
                            Source record linked
                        </div>

                        <div class="timeline-meta">
                            16 Aug 2026 · Treasurer
                        </div>

                    </div>

                </div>

            </div>

        </div>


        <!-- CONTROL -->

        <div class="card">

            <div class="card-head">

                <div>
                    <strong>Document Controls</strong>

                    <span>
                        Current protection and record-management settings.
                    </span>
                </div>

            </div>

            <div class="card-body">

                <div class="control-row">

                    <span class="control-label">
                        Audit History
                    </span>

                    <span class="control-value">
                        Enabled
                    </span>

                </div>


                <div class="control-row">

                    <span class="control-label">
                        Version History
                    </span>

                    <span class="control-value">
                        Enabled
                    </span>

                </div>


                <div class="control-row">

                    <span class="control-label">
                        Authorized Deletion
                    </span>

                    <span class="control-value">
                        Required
                    </span>

                </div>


                <div class="control-row">

                    <span class="control-label">
                        Access
                    </span>

                    <span class="control-value">
                        Internal Users
                    </span>

                </div>

            </div>

        </div>

    </div>


    <aside>

        <div class="card">

            <div class="card-body">

                <div class="side-number">
                    DOC-00486
                </div>

                <div class="side-label">
                    Document Reference
                </div>

                <div style="
                    height:1px;
                    background:#eef1ef;
                    margin:16px 0;
                "></div>

                <div class="side-row">

                    <span>
                        Type
                    </span>

                    <span>
                        Financial
                    </span>

                </div>


                <div class="side-row">

                    <span>
                        Format
                    </span>

                    <span>
                        PDF
                    </span>

                </div>


                <div class="side-row">

                    <span>
                        Size
                    </span>

                    <span>
                        245 KB
                    </span>

                </div>


                <div class="side-row">

                    <span>
                        Version
                    </span>

                    <span>
                        1.0
                    </span>

                </div>


                <div class="side-row">

                    <span>
                        Access
                    </span>

                    <span>
                        Internal
                    </span>

                </div>


                <div class="side-row">

                    <span>
                        Uploaded By
                    </span>

                    <span>
                        Treasurer
                    </span>

                </div>

            </div>

        </div>


        <div class="card">

            <div class="card-head">
                <strong>Privacy & Access</strong>
            </div>

            <div class="card-body">

                <div class="notice">

                    <i class="bi bi-shield-check"></i>

                    <p>
                        This record is classified as internal. Access should
                        be limited to authorized organization users according
                        to their assigned permissions.
                    </p>

                </div>

            </div>

        </div>


        <div class="card">

            <div class="card-head">
                <strong>Quick Actions</strong>
            </div>

            <div class="card-body">

                <button
                    class="ui-btn primary"
                    style="width:100%;"
                    onclick="previewDownload()"
                >
                    <i class="bi bi-download"></i>
                    Download Document
                </button>


                <button
                    class="ui-btn"
                    style="width:100%;margin-top:8px;"
                    onclick="previewView()"
                >
                    <i class="bi bi-eye"></i>
                    Open Preview
                </button>


                <a
                    href="{{ route('records.documents.create') }}"
                    class="ui-btn"
                    style="width:100%;margin-top:8px;"
                >
                    <i class="bi bi-upload"></i>
                    Upload New Version
                </a>

            </div>

        </div>

    </aside>

</div>

@endsection


@push('scripts')
<script>

    function previewView() {

        alert(
            'UI preview only.\n\n' +
            'The document viewer will be connected when backend file storage and document preview are implemented.'
        );

    }


    function previewDownload() {

        alert(
            'UI preview only.\n\n' +
            'The document download will be connected when backend file storage is implemented.'
        );

    }

</script>
@endpush