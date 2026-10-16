@extends('layouts.app')

@section('title', 'Add Committee')

@push('styles')
<style>
    .form-page-header {
        display: flex;
        justify-content: space-between;
        align-items: flex-start;
        gap: 20px;
        margin-bottom: 24px;
    }

    .form-page-header h1 {
        margin: 0;
        font-size: 25px;
        font-weight: 700;
        letter-spacing: -.5px;
    }

    .form-page-header p {
        margin: 7px 0 0;
        color: var(--muted);
        font-size: 16px;
    }

    .form-actions {
        display: flex;
        gap: 8px;
    }

    .form-btn {
        height: 40px;
        padding: 0 14px;
        border-radius: 10px;
        display: inline-flex;
        align-items: center;
        justify-content: center;
        gap: 7px;
        font-size: 16px;
        font-weight: 650;
        text-decoration: none;
        cursor: pointer;
    }

    .form-btn.secondary {
        background: #fff;
        border: 1px solid var(--border);
        color: #637069;
    }

    .form-btn.primary {
        background: var(--primary);
        border: 1px solid var(--primary);
        color: #fff;
    }

    .form-layout {
        display: grid;
        grid-template-columns: minmax(0, 1fr) 310px;
        gap: 18px;
    }

    .form-card {
        background: #fff;
        border: 1px solid var(--border);
        border-radius: 14px;
        overflow: hidden;
        margin-bottom: 18px;
    }

    .form-card-header {
        padding: 17px 20px;
        border-bottom: 1px solid #eef1ef;
    }

    .form-card-title {
        font-size: 16px;
        font-weight: 700;
    }

    .form-card-description {
        margin-top: 4px;
        color: #6b7470;
        font-size: 16px;
        line-height: 1.5;
    }

    .form-card-body {
        padding: 20px;
    }

    .form-grid {
        display: grid;
        grid-template-columns: repeat(2, minmax(0, 1fr));
        gap: 17px;
    }

    .form-group.full {
        grid-column: 1 / -1;
    }

    .form-label-custom {
        display: block;
        margin-bottom: 7px;
        color: #36433d;
        font-size: 16px;
        font-weight: 650;
    }

    .required {
        color: #b35b4b;
    }

    .form-control-custom,
    .form-select-custom {
        width: 100%;
        height: 42px;
        border: 1px solid var(--border);
        border-radius: 9px;
        padding: 0 12px;
        color: var(--text);
        background: #fff;
        font-size: 16px;
        outline: none;
    }

    textarea.form-control-custom {
        height: auto;
        min-height: 105px;
        padding: 11px 12px;
        resize: vertical;
    }

    .form-control-custom:focus,
    .form-select-custom:focus {
        border-color: #6b7670;
        box-shadow: 0 0 0 3px rgba(23,63,53,.055);
    }

    .form-help {
        margin-top: 5px;
        color: #6d7571;
        font-size: 16px;
        line-height: 1.5;
    }

    /* Type selector */

    .type-options {
        display: grid;
        grid-template-columns: repeat(3, 1fr);
        gap: 9px;
    }

    .type-option {
        position: relative;
    }

    .type-option input {
        position: absolute;
        opacity: 0;
        pointer-events: none;
    }

    .type-option label {
        min-height: 68px;
        padding: 10px;
        border: 1px solid var(--border);
        border-radius: 10px;
        display: flex;
        flex-direction: column;
        align-items: center;
        justify-content: center;
        gap: 6px;
        color: #6b766f;
        cursor: pointer;
        text-align: center;
        font-size: 16px;
        transition: all .15s ease;
    }

    .type-option label i {
        font-size: 18px;
    }

    .type-option input:checked + label {
        color: var(--primary);
        background: var(--primary-soft);
        border-color: #6c7571;
    }

    /* Status */

    .status-options {
        display: flex;
        gap: 8px;
        flex-wrap: wrap;
    }

    .status-option {
        position: relative;
    }

    .status-option input {
        position: absolute;
        opacity: 0;
        pointer-events: none;
    }

    .status-option label {
        height: 33px;
        padding: 0 11px;
        display: inline-flex;
        align-items: center;
        gap: 6px;
        border: 1px solid var(--border);
        border-radius: 30px;
        color: #6b766f;
        font-size: 16px;
        cursor: pointer;
    }

    .status-option label::before {
        content: "";
        width: 6px;
        height: 6px;
        border-radius: 50%;
        background: #b1bbb6;
    }

    .status-option input:checked + label {
        background: var(--primary-soft);
        color: var(--primary);
        border-color: #6c7571;
    }

    .status-option input:checked + label::before {
        background: var(--primary);
    }

    /* Responsibilities */

    .responsibility-list {
        display: flex;
        flex-direction: column;
        gap: 9px;
    }

    .responsibility-item {
        display: flex;
        align-items: flex-start;
        gap: 9px;
    }

    .responsibility-item input {
        width: 15px;
        height: 15px;
        margin-top: 1px;
        accent-color: var(--primary);
    }

    .responsibility-item label {
        color: #67736d;
        font-size: 16px;
        line-height: 1.5;
    }

    .custom-responsibility {
        margin-top: 12px;
        display: flex;
        gap: 8px;
    }

    .custom-responsibility input {
        flex: 1;
        height: 37px;
        border: 1px solid var(--border);
        border-radius: 8px;
        padding: 0 10px;
        font-size: 16px;
        outline: none;
    }

    .small-add-btn {
        width: 37px;
        height: 37px;
        border: 1px solid var(--border);
        border-radius: 8px;
        background: #fff;
        color: var(--primary);
        cursor: pointer;
    }

    /* Reporting */

    .workflow {
        display: flex;
        flex-direction: column;
    }

    .workflow-step {
        display: flex;
        gap: 11px;
        position: relative;
        padding-bottom: 18px;
    }

    .workflow-step:last-child {
        padding-bottom: 0;
    }

    .workflow-icon {
        width: 29px;
        height: 29px;
        border-radius: 9px;
        background: var(--primary-soft);
        color: var(--primary);
        display: flex;
        align-items: center;
        justify-content: center;
        flex-shrink: 0;
        font-size: 16px;
        position: relative;
        z-index: 2;
    }

    .workflow-step:not(:last-child) .workflow-icon::after {
        content: "";
        position: absolute;
        width: 1px;
        height: 18px;
        left: 14px;
        top: 29px;
        background: #d8e2dd;
    }

    .workflow-content strong {
        display: block;
        font-size: 16px;
    }

    .workflow-content span {
        display: block;
        margin-top: 3px;
        color: #6e7772;
        font-size: 16px;
        line-height: 1.45;
    }

    /* Preview */

    .preview-card {
        background: linear-gradient(145deg, #f8faf9, #f2f6f4);
        border: 1px solid var(--border);
        border-radius: 14px;
        padding: 19px;
        margin-bottom: 18px;
    }

    .preview-label {
        color: #6d7672;
        font-size: 16px;
        text-transform: uppercase;
        letter-spacing: .8px;
    }

    .preview-icon {
        width: 55px;
        height: 55px;
        margin: 15px 0 11px;
        border-radius: 15px;
        background: var(--primary);
        color: white;
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 22px;
    }

    .preview-name {
        font-size: 17px;
        font-weight: 700;
    }

    .preview-type {
        margin-top: 5px;
        color: #6b7670;
        font-size: 16px;
    }

    .preview-divider {
        height: 1px;
        margin: 16px 0;
        background: #dde5e1;
    }

    .preview-meta {
        display: flex;
        justify-content: space-between;
        gap: 12px;
        padding: 7px 0;
        font-size: 16px;
    }

    .preview-meta span:first-child {
        color: #6e7672;
    }

    .preview-meta span:last-child {
        color: #4b5851;
        font-weight: 650;
        text-align: right;
    }

    .info-note {
        display: flex;
        gap: 9px;
        padding: 12px;
        border-radius: 10px;
        background: #f7f9f8;
        border: 1px solid #e5ebe8;
    }

    .info-note i {
        color: var(--primary);
        font-size: 17px;
    }

    .info-note p {
        margin: 0;
        color: #6b766f;
        font-size: 16px;
        line-height: 1.6;
    }

    @media (max-width: 950px) {
        .form-layout {
            grid-template-columns: 1fr;
        }
    }

    @media (max-width: 650px) {
        .form-page-header {
            flex-direction: column;
        }

        .form-actions {
            width: 100%;
        }

        .form-actions .form-btn {
            flex: 1;
        }

        .form-grid {
            grid-template-columns: 1fr;
        }

        .form-group.full {
            grid-column: auto;
        }

        .type-options {
            grid-template-columns: 1fr;
        }
    }
</style>
@endpush

@section('content')
<form
@if ($errors->any())

    <div
        style="
            margin-bottom:18px;
            padding:13px 15px;
            border-radius:10px;
            background:#fff0ef;
            border:1px solid #ead8d5;
            color:#a85f58;
            font-size:13px;
        "
    >

        <strong>
            Please correct the following:
        </strong>

        <ul style="margin:8px 0 0;padding-left:20px;">

            @foreach ($errors->all() as $error)
                <li>{{ $error }}</li>
            @endforeach

        </ul>

    </div>

@endif
    id="committeeForm"
    method="POST"
    action="{{ route('committees.store') }}"
>
    @csrf

<div class="form-page-header">

    <div>
        <h1>Add Committee</h1>

        <p>
            Create a configurable committee and define its responsibilities,
            positions and reporting relationship.
        </p>
    </div>

    <div class="form-actions">

        <a
            href="{{ route('committees.index') }}"
            class="form-btn secondary"
        >
            <i class="bi bi-arrow-left"></i>
            Back to Committees
        </a>

          <button
            type="submit"
            form="committeeForm"
            class="form-btn primary"
        >
            <i class="bi bi-check-lg"></i>
            Save Committee
        </button>
    </div>

</div>


<div class="form-layout">

    <div>

        <!-- Basic Information -->

        <div class="form-card">

            <div class="form-card-header">

                <div class="form-card-title">
                    Basic Information
                </div>

                <div class="form-card-description">
                    Define the committee's identity and purpose.
                </div>

            </div>

            <div class="form-card-body">

                <div class="form-grid">

                    <div class="form-group">

                        <label class="form-label-custom">
                            Committee Name <span class="required">*</span>
                        </label>

                        <input
                            type="text"
                            id="committeeName"
                            name="name"
                            value="{{ old('name') }}"
                            class="form-control-custom"
                            placeholder="e.g. Education Committee"
                            required
                        >

                    </div>


                    <div class="form-group">

                        <label class="form-label-custom">
                            Committee Code
                        </label>

                        <input
                            type="text"
                            class="form-control-custom"
                            value="Auto-generated on save"
                            disabled
                        >

                        <div class="form-help">
                            Assigned automatically (e.g. COM-00001).
                        </div>

                    </div>
                    <div class="form-group">

                        <label class="form-label-custom">
                            Established Date
                        </label>

                        <input
                            type="date"
                            name="established_at"
                            value="{{ old('established_at') }}"
                            class="form-control-custom"
                        >

                    </div>


                    <div class="form-group full">

                        <label class="form-label-custom">
                            Description
                        </label>

                        <textarea
                            name="description"
                            class="form-control-custom"
                            placeholder="Describe the purpose of this committee..."
                        >{{ old('description') }}</textarea>

                    </div>


                    <div class="form-group full">

                        <label class="form-label-custom">
                            Committee Type
                        </label>

                        <div class="type-options">

                            <div class="type-option">

                                <input
                                    type="radio"
                                    name="type"
                                    id="typeDecision"
                                    value="decision"
                                    @checked(old('type', 'decision') === 'decision')
                                >

                                <label for="typeDecision">
                                    <i class="bi bi-diagram-3"></i>
                                    Decision-Making
                                </label>

                            </div>


                            <div class="type-option">

                                <input
                                    type="radio"
                                    name="type"
                                    id="typeImplementation"
                                    value="implementation"
                                    @checked(old('type') === 'implementation')
                                >

                                <label for="typeImplementation">
                                    <i class="bi bi-gear-wide-connected"></i>
                                    Implementation
                                </label>

                            </div>


                            <div class="type-option">

                                <input
                                    type="radio"
                                    name="type"
                                    id="typeAdministration"
                                    value="administration"
                                    @checked(old('type') === 'administration')
                                >

                                <label for="typeAdministration">
                                    <i class="bi bi-building"></i>
                                    Administration
                                </label>

                            </div>

                        </div>

                    </div>

                </div>

            </div>

        </div>


        <!-- Responsibilities -->

        <div class="form-card">

            <div class="form-card-header">

                <div class="form-card-title">
                    Responsibilities
                </div>

                <div class="form-card-description">
                    Select the responsibilities assigned to this committee.
                </div>

            </div>

            <div class="form-card-body">

                <div class="responsibility-list">

                    <div class="responsibility-item">
                        <input type="checkbox" checked id="r1">
                        <label for="r1">
                            Review and approve projects
                        </label>
                    </div>

                    <div class="responsibility-item">
                        <input type="checkbox" checked id="r2">
                        <label for="r2">
                            Approve fund allocations
                        </label>
                    </div>

                    <div class="responsibility-item">
                        <input type="checkbox" id="r3">
                        <label for="r3">
                            Review beneficiary applications
                        </label>
                    </div>

                    <div class="responsibility-item">
                        <input type="checkbox" id="r4">
                        <label for="r4">
                            Review exceptional expenses
                        </label>
                    </div>

                    <div class="responsibility-item">
                        <input type="checkbox" id="r5">
                        <label for="r5">
                            Monitor approved projects
                        </label>
                    </div>

                    <div class="responsibility-item">
                        <input type="checkbox" id="r6">
                        <label for="r6">
                            Review financial reports
                        </label>
                    </div>

                </div>


                <div class="custom-responsibility">

                    <input
                        type="text"
                        placeholder="Add custom responsibility..."
                    >

                    <button
                        type="button"
                        class="small-add-btn"
                    >
                        <i class="bi bi-plus"></i>
                    </button>

                </div>

            </div>

        </div>


        <!-- Reporting Structure -->

        <div class="form-card">

            <div class="form-card-header">

                <div class="form-card-title">
                    Reporting Structure
                </div>

                <div class="form-card-description">
                    Define where this committee sits in the organization's
                    reporting and decision-making chain.
                </div>

            </div>

            <div class="form-card-body">

                <div class="form-grid">

                    <div class="form-group">

                        <label class="form-label-custom">
                            Reports To
                        </label>

                        <select class="form-select-custom">

                            <option>Organization Leadership</option>
                            <option>General Secretary</option>
                            <option>Decision-Making Committee</option>
                            <option>Implementation Committee</option>

                        </select>

                    </div>


                    <div class="form-group">

                        <label class="form-label-custom">
                            Primary Coordinator
                        </label>

                        <select class="form-select-custom">

                            <option>Select responsible person</option>
                            <option>Yasir Khan</option>
                            <option>Naveed Alam</option>
                            <option>Sajjad</option>
                            <option>Shahzad Khan</option>

                        </select>

                    </div>

                </div>

                <div class="workflow" style="margin-top:20px;">

                    <div class="workflow-step">

                        <div class="workflow-icon">
                            <i class="bi bi-person"></i>
                        </div>

                        <div class="workflow-content">

                            <strong>
                                Committee Members
                            </strong>

                            <span>
                                Submit proposals, reviews and recommendations.
                            </span>

                        </div>

                    </div>


                    <div class="workflow-step">

                        <div class="workflow-icon">
                            <i class="bi bi-arrow-up"></i>
                        </div>

                        <div class="workflow-content">

                            <strong>
                                General Secretary
                            </strong>

                            <span>
                                Receives and coordinates committee matters.
                            </span>

                        </div>

                    </div>


                    <div class="workflow-step">

                        <div class="workflow-icon">
                            <i class="bi bi-check2-circle"></i>
                        </div>

                        <div class="workflow-content">

                            <strong>
                                Decision / Approval
                            </strong>

                            <span>
                                Matters requiring higher authorization are
                                submitted for formal decision.
                            </span>

                        </div>

                    </div>

                </div>

            </div>

        </div>


        <!-- Status -->

        <div class="form-card">

            <div class="form-card-header">

                <div class="form-card-title">
                    Committee Status
                </div>

            </div>

            <div class="form-card-body">

                <div class="type-options">

                    <div class="type-option">

                        <input
                            type="radio"
                            name="status"
                            id="statusActive"
                            value="active"
                            @checked(old('status', 'active') === 'active')
                        >

                        <label for="statusActive">
                            <i class="bi bi-check-circle"></i>
                            Active
                        </label>

                    </div>


                    <div class="type-option">

                        <input
                            type="radio"
                            name="status"
                            id="statusInactive"
                            value="inactive"
                            @checked(old('status') === 'inactive')
                        >

                        <label for="statusInactive">
                            <i class="bi bi-dash-circle"></i>
                            Inactive
                        </label>

                    </div>

                </div>
            </div>

        </div>

    </div>


    <!-- PREVIEW -->

    <aside>

        <div class="preview-card">

            <div class="preview-label">
                Committee Preview
            </div>

            <div class="preview-icon">
                <i class="bi bi-diagram-3"></i>
            </div>

            <div
                class="preview-name"
                id="previewCommitteeName"
            >
                New Committee
            </div>

            <div class="preview-type">
                Decision-Making Committee
            </div>

            <div class="preview-divider"></div>

            <div class="preview-meta">
                <span>Code</span>
                <span>COM-009</span>
            </div>

            <div class="preview-meta">
                <span>Status</span>
                <span>Active</span>
            </div>

            <div class="preview-meta">
                <span>Members</span>
                <span>0 assigned</span>
            </div>

        </div>


        <div class="form-card">

            <div class="form-card-header">
                <div class="form-card-title">
                    Configuration
                </div>
            </div>

            <div class="form-card-body">

                <div class="info-note">

                    <i class="bi bi-sliders"></i>

                    <p>
                        Committee names, positions, responsibilities and
                        reporting relationships will eventually be managed
                        from the organization's configuration area.
                    </p>

                </div>

            </div>

        </div>

    </aside>

</div>
</form>

@endsection


@push('scripts')
<script>

    const committeeNameInput =
        document.getElementById('committeeName');

    const previewCommitteeName =
        document.getElementById('previewCommitteeName');

    committeeNameInput.addEventListener('input', function () {

        previewCommitteeName.textContent =
            this.value.trim() || 'New Committee';

    });


    function previewSaveCommittee() {

        const name =
            committeeNameInput.value.trim();

        if (!name) {

            alert('Please enter the committee name.');

            committeeNameInput.focus();

            return;

        }

        alert(
            'UI preview only.\n\n' +
            'Committee "' +
            name +
            '" is ready to be saved once backend functionality is connected.'
        );

    }

</script>
@endpush