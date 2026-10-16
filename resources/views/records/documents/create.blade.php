@extends('layouts.app')

@section('title', 'Upload Document')

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

    /* DOCUMENT TYPE */

    .type-grid {
        display:grid;
        grid-template-columns:repeat(4,minmax(0,1fr));
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
        min-height:76px;
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
        font-size:18px;
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

    /* ACCESS */

    .access-grid {
        display:grid;
        grid-template-columns:repeat(3,minmax(0,1fr));
        gap:9px;
    }

    .access-option {
        position:relative;
    }

    .access-option input {
        position:absolute;
        opacity:0;
        pointer-events:none;
    }

    .access-option label {
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

    .access-option label i {
        font-size:17px;
    }

    .access-option label strong {
        font-size:13px;
    }

    .access-option label span {
        color:#929d97;
        font-size:12px;
        line-height:1.4;
    }

    .access-option input:checked + label {
        background:var(--primary-soft);
        border-color:#c7d8d0;
        color:var(--primary);
    }

    /* DROPZONE */

    .dropzone {
        border:1.5px dashed #c6d2cc;
        border-radius:13px;
        padding:34px 22px;
        text-align:center;
        background:#fbfcfb;
        transition:.15s;
        cursor:pointer;
    }

    .dropzone.dragover {
        border-color:var(--primary);
        background:var(--primary-soft);
    }

    .dropzone-icon {
        width:56px;
        height:56px;
        margin:0 auto;
        border-radius:15px;
        background:var(--primary-soft);
        color:var(--primary);
        display:flex;
        align-items:center;
        justify-content:center;
        font-size:23px;
    }

    .dropzone strong {
        display:block;
        margin-top:12px;
        font-size:14px;
    }

    .dropzone p {
        margin:5px 0 0;
        color:#929d97;
        font-size:13px;
        line-height:1.5;
    }

    .dropzone button {
        margin-top:13px;
    }

    .file-info {
        display:none;
        margin-top:15px;
        padding:11px;
        border:1px solid #e4ebe7;
        background:#fff;
        border-radius:9px;
        align-items:center;
        gap:10px;
        text-align:left;
    }

    .file-info.show {
        display:flex;
    }

    .file-icon {
        width:36px;
        height:36px;
        border-radius:9px;
        background:#fff0ef;
        color:#a85f58;
        display:flex;
        align-items:center;
        justify-content:center;
        flex-shrink:0;
    }

    .file-details {
        flex:1;
        min-width:0;
    }

    .file-name {
        font-size:13px;
        font-weight:650;
        white-space:nowrap;
        overflow:hidden;
        text-overflow:ellipsis;
    }

    .file-size {
        margin-top:3px;
        color:#98a29d;
        font-size:12px;
    }

    .remove-file {
        width:29px;
        height:29px;
        border:1px solid #ead8d5;
        background:#fff;
        border-radius:7px;
        color:#a85f58;
        cursor:pointer;
    }

    /* TAGS */

    .tag-input-wrap {
        min-height:42px;
        border:1px solid var(--border);
        border-radius:9px;
        padding:6px 8px;
        display:flex;
        align-items:center;
        flex-wrap:wrap;
        gap:6px;
        background:#fff;
    }

    .tag-input-wrap:focus-within {
        border-color:#b8cbc1;
        box-shadow:0 0 0 3px rgba(23,63,53,.055);
    }

    .tag {
        display:inline-flex;
        align-items:center;
        gap:5px;
        padding:5px 7px;
        border-radius:20px;
        background:var(--primary-soft);
        color:var(--primary);
        font-size:13px;
        font-weight:650;
    }

    .tag button {
        border:0;
        background:none;
        padding:0;
        color:inherit;
        cursor:pointer;
        font-size:13px;
    }

    .tag-input {
        min-width:120px;
        flex:1;
        border:0;
        outline:none;
        font-size:13px;
        padding:5px;
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
        overflow-wrap:anywhere;
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

    /* NOTICE */

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

    /* CONTROL CHECKLIST */

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
        .access-grid {
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

        <h1>Upload Document</h1>

        <p>
            Add a document to the organization record library and associate it with the correct source.
        </p>

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
            class="ui-btn primary"
            onclick="previewUploadDocument()"
        >
            <i class="bi bi-cloud-arrow-up"></i>
            Upload Document
        </button>

    </div>

</div>


<div class="layout">

    <div>

        <!-- FILE -->

        <div class="card">

            <div class="card-head">

                <strong>Document File</strong>

                <span>
                    Select the file that should be stored in the organization record library.
                </span>

            </div>

            <div class="card-body">

                <label
                    class="dropzone"
                    id="dropzone"
                >

                    <div class="dropzone-icon">
                        <i class="bi bi-cloud-arrow-up"></i>
                    </div>

                    <strong>
                        Drop your file here
                    </strong>

                    <p>
                        or select a file from your computer
                    </p>

                    <button
                        type="button"
                        class="ui-btn"
                        onclick="document.getElementById('documentFile').click()"
                    >
                        <i class="bi bi-folder2-open"></i>
                        Choose File
                    </button>

                    <p style="margin-top:10px;">
                        PDF, DOCX, XLSX, JPG, PNG · Maximum 20 MB
                    </p>

                    <input
                        id="documentFile"
                        type="file"
                        hidden
                    >

                    <div
                        class="file-info"
                        id="fileInfo"
                    >

                        <div class="file-icon">
                            <i class="bi bi-file-earmark"></i>
                        </div>

                        <div class="file-details">

                            <div
                                class="file-name"
                                id="fileName"
                            >
                                Document.pdf
                            </div>

                            <div
                                class="file-size"
                                id="fileSize"
                            >
                                0 KB
                            </div>

                        </div>

                        <button
                            type="button"
                            class="remove-file"
                            onclick="removeFile(event)"
                        >
                            <i class="bi bi-x-lg"></i>
                        </button>

                    </div>

                </label>

            </div>

        </div>


        <!-- BASIC INFORMATION -->

        <div class="card">

            <div class="card-head">

                <strong>Document Information</strong>

                <span>
                    Give the document a clear identity and classification.
                </span>

            </div>

            <div class="card-body">

                <div class="grid">

                    <div class="full">

                        <label class="label">
                            Document Name <span class="required">*</span>
                        </label>

                        <input
                            id="documentName"
                            class="control"
                            placeholder="e.g. Donation Receipt RCT-00127"
                        >

                    </div>


                    <div>

                        <label class="label">
                            Document ID
                        </label>

                        <input
                            class="control"
                            value="DOC-00487"
                            readonly
                        >

                    </div>


                    <div>

                        <label class="label">
                            Document Date
                        </label>

                        <input
                            id="documentDate"
                            type="date"
                            class="control"
                            value="2026-08-18"
                        >

                    </div>


                    <div>

                        <label class="label">
                            Document Version
                        </label>

                        <input
                            class="control"
                            value="1.0"
                        >

                    </div>


                    <div>

                        <label class="label">
                            Document Language
                        </label>

                        <select class="select">

                            <option>
                                English
                            </option>

                            <option>
                                Urdu
                            </option>

                            <option>
                                Arabic
                            </option>

                            <option>
                                Other
                            </option>

                        </select>

                    </div>


                    <div class="full">

                        <label class="label">
                            Description
                        </label>

                        <textarea
                            id="description"
                            class="control"
                            placeholder="Describe what this document contains and why it is stored..."
                        ></textarea>

                    </div>

                </div>

            </div>

        </div>


        <!-- TYPE -->

        <div class="card">

            <div class="card-head">

                <strong>Document Type</strong>

                <span>
                    Classify the record for searching and access control.
                </span>

            </div>

            <div class="card-body">

                <div class="type-grid">

                    <div class="type-option">

                        <input
                            type="radio"
                            name="documentType"
                            id="financial"
                            value="Financial"
                            checked
                        >

                        <label for="financial">

                            <i class="bi bi-cash-stack"></i>

                            <strong>
                                Financial
                            </strong>

                            <span>
                                Donations, expenses, funds
                            </span>

                        </label>

                    </div>


                    <div class="type-option">

                        <input
                            type="radio"
                            name="documentType"
                            id="organizational"
                            value="Organizational"
                        >

                        <label for="organizational">

                            <i class="bi bi-building"></i>

                            <strong>
                                Organizational
                            </strong>

                            <span>
                                Policies, meetings, records
                            </span>

                        </label>

                    </div>


                    <div class="type-option">

                        <input
                            type="radio"
                            name="documentType"
                            id="project"
                            value="Project"
                        >

                        <label for="project">

                            <i class="bi bi-diagram-3"></i>

                            <strong>
                                Project
                            </strong>

                            <span>
                                Projects and field records
                            </span>

                        </label>

                    </div>


                    <div class="type-option">

                        <input
                            type="radio"
                            name="documentType"
                            id="general"
                            value="General"
                        >

                        <label for="general">

                            <i class="bi bi-file-earmark"></i>

                            <strong>
                                General
                            </strong>

                            <span>
                                Other organizational records
                            </span>

                        </label>

                    </div>

                </div>

            </div>

        </div>


        <!-- LINKED SOURCE -->

        <div class="card">

            <div class="card-head">

                <strong>Linked Source</strong>

                <span>
                    Connect the document to the organizational record it belongs to.
                </span>

            </div>

            <div class="card-body">

                <div class="grid">

                    <div>

                        <label class="label">
                            Source Type <span class="required">*</span>
                        </label>

                        <select
                            id="sourceType"
                            class="select"
                        >

                            <option value="Donation">
                                Donation
                            </option>

                            <option value="Expense">
                                Expense
                            </option>

                            <option value="Fund">
                                Fund
                            </option>

                            <option value="Project">
                                Community Project
                            </option>

                            <option value="Task">
                                Task
                            </option>

                            <option value="Meeting">
                                Meeting
                            </option>

                            <option value="Member">
                                Member
                            </option>

                            <option value="Beneficiary">
                                Beneficiary
                            </option>

                            <option value="Procurement">
                                Procurement
                            </option>

                            <option value="Qard">
                                Qard-e-Hasana
                            </option>

                            <option value="Small Business">
                                Small Business
                            </option>

                        </select>

                    </div>


                    <div>

                        <label class="label">
                            Source Record <span class="required">*</span>
                        </label>

                        <select
                            id="sourceRecord"
                            class="select"
                        >

                            <option>
                                DON-00127 · Zaid Ahmad
                            </option>

                            <option>
                                DON-00126 · Farid Khan Family
                            </option>

                            <option>
                                DON-00125 · Muhammad Asif
                            </option>

                        </select>

                    </div>


                    <div class="full">

                        <label class="label">
                            Source Notes
                        </label>

                        <input
                            class="control"
                            placeholder="Optional additional reference information"
                        >

                    </div>

                </div>

            </div>

        </div>


        <!-- ACCESS -->

        <div class="card">

            <div class="card-head">

                <strong>Access & Privacy</strong>

                <span>
                    Determine who should be able to view this document.
                </span>

            </div>

            <div class="card-body">

                <div class="access-grid">

                    <div class="access-option">

                        <input
                            type="radio"
                            name="access"
                            id="internal"
                            value="Internal"
                            checked
                        >

                        <label for="internal">

                            <i class="bi bi-people"></i>

                            <strong>
                                Internal
                            </strong>

                            <span>
                                Authorized organization users
                            </span>

                        </label>

                    </div>


                    <div class="access-option">

                        <input
                            type="radio"
                            name="access"
                            id="restricted"
                            value="Restricted"
                        >

                        <label for="restricted">

                            <i class="bi bi-lock"></i>

                            <strong>
                                Restricted
                            </strong>

                            <span>
                                Limited to specific roles / committees
                            </span>

                        </label>

                    </div>


                    <div class="access-option">

                        <input
                            type="radio"
                            name="access"
                            id="confidential"
                            value="Confidential"
                        >

                        <label for="confidential">

                            <i class="bi bi-shield-lock"></i>

                            <strong>
                                Confidential
                            </strong>

                            <span>
                                Highly sensitive information
                            </span>

                        </label>

                    </div>

                </div>


                <div class="help" style="margin-top:12px;">
                    Beneficiary, financial and other sensitive records should receive appropriate access restrictions.
                </div>

            </div>

        </div>


        <!-- TAGS -->

        <div class="card">

            <div class="card-head">

                <strong>Tags</strong>

                <span>
                    Add keywords that make the document easier to search later.
                </span>

            </div>

            <div class="card-body">

                <div
                    class="tag-input-wrap"
                    id="tagContainer"
                >

                    <span class="tag">

                        financial

                        <button
                            type="button"
                            onclick="this.parentElement.remove()"
                        >
                            ×
                        </button>

                    </span>


                    <span class="tag">

                        donation

                        <button
                            type="button"
                            onclick="this.parentElement.remove()"
                        >
                            ×
                        </button>

                    </span>


                    <input
                        id="tagInput"
                        class="tag-input"
                        placeholder="Type a tag and press Enter..."
                    >

                </div>

            </div>

        </div>


        <!-- CONTROLS -->

        <div class="card">

            <div class="card-head">

                <strong>Document Controls</strong>

                <span>
                    Configure record-management and audit requirements.
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
                                Keep document in audit history
                            </strong>

                            <span>
                                Upload and subsequent changes should be recorded.
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
                                Prevent deletion without authorization
                            </strong>

                            <span>
                                Important records should not be removed without appropriate permission.
                            </span>

                        </div>

                    </label>


                    <label class="check-item">

                        <input
                            type="checkbox"
                        >

                        <div>

                            <strong>
                                Require review before publication
                            </strong>

                            <span>
                                Useful for formal reports, policies and public-facing records.
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
                                Keep version history
                            </strong>

                            <span>
                                Future replacements should preserve previous versions.
                            </span>

                        </div>

                    </label>

                </div>

            </div>

        </div>

    </div>


    <aside>

        <!-- PREVIEW -->

        <div class="preview">

            <div class="preview-label">
                Document Preview
            </div>

            <div class="preview-icon">
                <i
                    class="bi bi-file-earmark-pdf"
                    id="previewIcon"
                ></i>
            </div>

            <div
                class="preview-name"
                id="previewDocumentName"
            >
                New Document
            </div>

            <div class="preview-sub">
                DOC-00487
            </div>

            <div class="preview-divider"></div>

            <div class="preview-row">

                <span>
                    Type
                </span>

                <span id="previewType">
                    Financial
                </span>

            </div>


            <div class="preview-row">

                <span>
                    Source
                </span>

                <span id="previewSource">
                    Donation
                </span>

            </div>


            <div class="preview-row">

                <span>
                    Access
                </span>

                <span id="previewAccess">
                    Internal
                </span>

            </div>


            <div class="preview-row">

                <span>
                    File
                </span>

                <span id="previewFile">
                    Not selected
                </span>

            </div>

        </div>


        <!-- PRINCIPLE -->

        <div class="card">

            <div class="card-head">
                <strong>Record Management</strong>
            </div>

            <div class="card-body">

                <div class="notice">

                    <i class="bi bi-shield-check"></i>

                    <p>
                        Documents should remain linked to their source record,
                        categorized correctly and protected according to their
                        sensitivity.
                    </p>

                </div>

            </div>

        </div>


        <!-- FLOW -->

        <div class="card">

            <div class="card-head">
                <strong>Document Lifecycle</strong>
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
                            Upload
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
                            Classify
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
                            Link to Record
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
                            Apply Access Rules
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
                            Audit & Version
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

    const fileInput =
        document.getElementById(
            'documentFile'
        );

    const dropzone =
        document.getElementById(
            'dropzone'
        );


    const fileInfo =
        document.getElementById(
            'fileInfo'
        );


    const documentName =
        document.getElementById(
            'documentName'
        );


    function formatFileSize(bytes) {

        if (!bytes) {
            return '0 KB';
        }

        if (bytes < 1024 * 1024) {

            return (
                Math.round(
                    bytes / 1024
                ) +
                ' KB'
            );

        }

        return (
            (
                bytes /
                (1024 * 1024)
            ).toFixed(2) +
            ' MB'
        );

    }


    function displayFile(file) {

        if (!file) {
            return;
        }


        fileInfo.classList.add(
            'show'
        );


        document.getElementById(
            'fileName'
        ).textContent =
            file.name;


        document.getElementById(
            'fileSize'
        ).textContent =
            formatFileSize(
                file.size
            );


        document.getElementById(
            'previewFile'
        ).textContent =
            file.name;


        if (!documentName.value.trim()) {

            documentName.value =
                file.name
                    .replace(
                        /\.[^/.]+$/,
                        ''
                    );


            document.getElementById(
                'previewDocumentName'
            ).textContent =
                documentName.value;

        }

    }


    fileInput.addEventListener(
        'change',
        function () {

            displayFile(
                this.files[0]
            );

        }
    );


    [
        'dragenter',
        'dragover'
    ].forEach(eventName => {

        dropzone.addEventListener(
            eventName,
            function (event) {

                event.preventDefault();

                dropzone.classList.add(
                    'dragover'
                );

            }
        );

    });


    [
        'dragleave',
        'drop'
    ].forEach(eventName => {

        dropzone.addEventListener(
            eventName,
            function (event) {

                event.preventDefault();

                dropzone.classList.remove(
                    'dragover'
                );

            }
        );

    });


    dropzone.addEventListener(
        'drop',
        function (event) {

            const file =
                event.dataTransfer.files[0];

            if (!file) {
                return;
            }

            fileInput.files =
                event.dataTransfer.files;

            displayFile(file);

        }
    );


    function removeFile(event) {

        event.preventDefault();
        event.stopPropagation();

        fileInput.value = '';

        fileInfo.classList.remove(
            'show'
        );

        document.getElementById(
            'previewFile'
        ).textContent =
            'Not selected';

    }


    documentName.addEventListener(
        'input',
        function () {

            document.getElementById(
                'previewDocumentName'
            ).textContent =
                this.value.trim() ||
                'New Document';

        }
    );


    document
        .querySelectorAll(
            'input[name="documentType"]'
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


    document
        .querySelectorAll(
            'input[name="access"]'
        )
        .forEach(input => {

            input.addEventListener(
                'change',
                function () {

                    document.getElementById(
                        'previewAccess'
                    ).textContent =
                        this.value;

                }
            );

        });


    document
        .getElementById('sourceType')
        .addEventListener(
            'change',
            function () {

                document.getElementById(
                    'previewSource'
                ).textContent =
                    this.value;

            }
        );


    const tagInput =
        document.getElementById(
            'tagInput'
        );


    tagInput.addEventListener(
        'keydown',
        function (event) {

            if (
                event.key !== 'Enter'
            ) {
                return;
            }

            event.preventDefault();

            const value =
                this.value.trim();

            if (!value) {
                return;
            }


            const tag =
                document.createElement(
                    'span'
                );

            tag.className =
                'tag';


            tag.innerHTML = `

                ${escapeHtml(value)}

                <button
                    type="button"
                    onclick="this.parentElement.remove()"
                >
                    ×
                </button>

            `;


            this.parentElement.insertBefore(
                tag,
                this
            );


            this.value = '';

        }
    );


    function escapeHtml(value) {

        const div =
            document.createElement(
                'div'
            );

        div.textContent =
            value;

        return div.innerHTML;

    }


    function previewUploadDocument() {

        if (
            !fileInput.files ||
            !fileInput.files.length
        ) {

            alert(
                'Please select a document file.'
            );

            return;

        }


        if (
            !documentName.value.trim()
        ) {

            alert(
                'Please enter the document name.'
            );

            documentName.focus();

            return;

        }


        if (
            fileInput.files[0].size >
            20 * 1024 * 1024
        ) {

            alert(
                'The selected file exceeds the 20 MB UI limit.'
            );

            return;

        }


        alert(
            'UI preview only.\n\n' +
            'This document is ready to be uploaded once backend file-storage functionality is connected.'
        );

    }

</script>
@endpush