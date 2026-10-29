@extends('layouts.app')

@section('title', 'Add Committee')

@push('styles')
<link rel="stylesheet" href="{{ asset('css/views/committees-create-1.css') }}">
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

        @if(auth()->user()?->hasPermission('committees.view'))<a
            href="{{ route('committees.index') }}"
            class="form-btn secondary"
        >
            <i class="bi bi-arrow-left"></i>
            Back to Committees
        </a>@endif

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