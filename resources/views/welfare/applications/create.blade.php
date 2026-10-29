@extends('layouts.app')

@section('title', 'New Welfare Application')

@push('styles')
<link rel="stylesheet" href="{{ asset('css/views/welfare-applications-create-1.css') }}">
@endpush

@section('content')

@php
    $selectedCategory = old('category', 'welfare');

    $categoryLabels = [
        'welfare' => 'General Welfare',
        'livelihood' => 'Livelihood',
        'education' => 'Education',
        'emergency' => 'Emergency',
    ];
@endphp

<form
    method="POST"
    action="{{ route('welfare.applications.store') }}"
    enctype="multipart/form-data"
>
    @csrf

    <div class="page-header">

        <div>
            <h1>New Welfare Application</h1>

            <p>
                Submit a welfare request for review and organizational consideration.
            </p>
        </div>

        <div class="actions">

            @if(auth()->user()?->hasPermission('welfare.assistance.view'))<a
                href="{{ route('welfare.applications.index') }}"
                class="ui-btn"
            >
                <i class="bi bi-arrow-left"></i>
                Back
            </a>@endif

            <button
                type="submit"
                class="ui-btn primary"
            >
                <i class="bi bi-check-lg"></i>
                Submit Application
            </button>

        </div>

    </div>


    @if ($errors->any())

        <div
            style="
                margin-bottom:18px;
                padding:14px 16px;
                background:#fff5f3;
                border:1px solid #efd8d3;
                border-radius:12px;
                color:#8f5148;
            "
        >

            <strong>
                Please correct the following:
            </strong>

            <ul style="margin:8px 0 0 18px;">

                @foreach ($errors->all() as $error)

                    <li>
                        {{ $error }}
                    </li>

                @endforeach

            </ul>

        </div>

    @endif


    <div class="layout">

        <div>

            <!-- APPLICANT -->

            <div class="card">

                <div class="card-head">

                    <strong>
                        Applicant
                    </strong>

                    <span>
                        Select an existing verified and approved beneficiary.
                    </span>

                </div>

                <div class="card-body">

                    <div class="grid">

                        <div class="group">

                            <label class="label">

                                Beneficiary
                                <span class="required">*</span>

                            </label>

                            <select
                                class="select"
                                name="beneficiary_id"
                                id="beneficiarySelect"
                                required
                            >

                                <option value="">
                                    Select beneficiary
                                </option>

                                @foreach ($beneficiaries as $beneficiary)

                                    <option
                                        value="{{ $beneficiary->id }}"
                                        @selected(
                                            old('beneficiary_id')
                                            == $beneficiary->id
                                        )
                                    >
                                        {{ $beneficiary->full_name }}
                                        —
                                        {{ $beneficiary->beneficiary_number }}
                                    </option>

                                @endforeach

                            </select>

                            <div class="help">
                                Only verified and approved beneficiaries are available.
                            </div>

                        </div>


                        <div class="group">

                            <label class="label">
                                Application ID
                            </label>

                            <input
                                class="control"
                                value="Generated on submission"
                                readonly
                            >

                        </div>

                    </div>

                </div>

            </div>


            <!-- REQUEST -->

            <div class="card">

                <div class="card-head">

                    <strong>
                        Assistance Request
                    </strong>

                    <span>
                        Define the type, purpose and requested amount of assistance.
                    </span>

                </div>

                <div class="card-body">

                    <input
                        type="hidden"
                        name="request_type"
                        id="requestType"
                        value="{{ old('request_type', 'general_welfare') }}"
                    >


                    <div class="request-grid">

                        <div class="request-option">

                            <input
                                type="radio"
                                name="category"
                                value="welfare"
                                id="requestWelfare"
                                @checked($selectedCategory === 'welfare')
                            >

                            <label for="requestWelfare">

                                <i class="bi bi-heart"></i>

                                <strong>
                                    General Welfare
                                </strong>

                                <span>
                                    Basic household support
                                </span>

                            </label>

                        </div>


                        <div class="request-option">

                            <input
                                type="radio"
                                name="category"
                                value="livelihood"
                                id="requestLivelihood"
                                @checked($selectedCategory === 'livelihood')
                            >

                            <label for="requestLivelihood">

                                <i class="bi bi-shop"></i>

                                <strong>
                                    Livelihood
                                </strong>

                                <span>
                                    Income-generating support
                                </span>

                            </label>

                        </div>


                        <div class="request-option">

                            <input
                                type="radio"
                                name="category"
                                value="education"
                                id="requestEducation"
                                @checked($selectedCategory === 'education')
                            >

                            <label for="requestEducation">

                                <i class="bi bi-mortarboard"></i>

                                <strong>
                                    Education
                                </strong>

                                <span>
                                    Fees, books or school needs
                                </span>

                            </label>

                        </div>


                        <div class="request-option">

                            <input
                                type="radio"
                                name="category"
                                value="emergency"
                                id="requestEmergency"
                                @checked($selectedCategory === 'emergency')
                            >

                            <label for="requestEmergency">

                                <i class="bi bi-exclamation-circle"></i>

                                <strong>
                                    Emergency
                                </strong>

                                <span>
                                    Urgent assistance
                                </span>

                            </label>

                        </div>

                    </div>


                    <div
                        class="grid"
                        style="margin-top:18px;"
                    >

                        <div class="group">

                            <label class="label">
                                Requested Amount
                                <span class="required">*</span>
                            </label>

                            <input
                                type="number"
                                name="requested_amount"
                                id="requestedAmount"
                                class="control"
                                value="{{ old('requested_amount') }}"
                                min="0.01"
                                step="0.01"
                                placeholder="PKR"
                                required
                            >

                        </div>


                        <div class="group">

                            <label class="label">
                                Applicant Contribution
                            </label>

                            <input
                                type="number"
                                name="applicant_contribution"
                                id="applicantContribution"
                                class="control"
                                value="{{ old('applicant_contribution', 0) }}"
                                min="0"
                                step="0.01"
                                placeholder="PKR"
                            >

                        </div>


                        <div class="group">

                            <label class="label">
                                Funding Source
                            </label>

                            <select
                                class="select"
                                name="fund_id"
                            >

                                <option value="">
                                    Select funding source
                                </option>

                                @foreach ($funds as $fund)

                                    <option
                                        value="{{ $fund->id }}"
                                        @selected(
                                            old('fund_id')
                                            == $fund->id
                                        )
                                    >
                                        {{ $fund->name }}
                                        —
                                        {{ $fund->fund_reference }}
                                    </option>

                                @endforeach

                            </select>

                        </div>


                        <div class="group full">

                            <label class="label">

                                Purpose / Request Details
                                <span class="required">*</span>

                            </label>

                            <textarea
                                class="control"
                                name="purpose_details"
                                placeholder="Explain why the assistance is required and how it will be used..."
                                required
                            >{{ old('purpose_details') }}</textarea>

                        </div>

                    </div>

                </div>

            </div>


            <!-- PRIORITY / PERIOD -->

            <div class="card">

                <div class="card-head">

                    <strong>
                        Priority & Assistance Period
                    </strong>

                    <span>
                        Help reviewers understand the urgency and duration.
                    </span>

                </div>

                <div class="card-body">

                    <div class="grid">

                        <div class="group">

                            <label class="label">
                                Priority
                            </label>

                            <div class="priority-options">

                                <div class="priority-option">

                                    <input
                                        type="radio"
                                        name="priority"
                                        value="high"
                                        id="priorityHigh"
                                        @checked(
                                            old('priority', 'normal')
                                            === 'high'
                                        )
                                    >

                                    <label for="priorityHigh">
                                        High
                                    </label>

                                </div>


                                <div class="priority-option">

                                    <input
                                        type="radio"
                                        name="priority"
                                        value="normal"
                                        id="priorityNormal"
                                        @checked(
                                            old('priority', 'normal')
                                            === 'normal'
                                        )
                                    >

                                    <label for="priorityNormal">
                                        Normal
                                    </label>

                                </div>


                                <div class="priority-option">

                                    <input
                                        type="radio"
                                        name="priority"
                                        value="low"
                                        id="priorityLow"
                                        @checked(
                                            old('priority', 'normal')
                                            === 'low'
                                        )
                                    >

                                    <label for="priorityLow">
                                        Low
                                    </label>

                                </div>

                            </div>

                        </div>


                        <div class="group">

                            <label class="label">
                                Frequency
                            </label>

                            <select
                                class="select"
                                name="frequency"
                            >

                                <option
                                    value="one_time"
                                    @selected(
                                        old('frequency')
                                        === 'one_time'
                                    )
                                >
                                    One-time
                                </option>

                                <option
                                    value="weekly"
                                    @selected(
                                        old('frequency')
                                        === 'weekly'
                                    )
                                >
                                    Weekly
                                </option>

                                <option
                                    value="monthly"
                                    @selected(
                                        old('frequency', 'monthly')
                                        === 'monthly'
                                    )
                                >
                                    Monthly
                                </option>

                                <option
                                    value="quarterly"
                                    @selected(
                                        old('frequency')
                                        === 'quarterly'
                                    )
                                >
                                    Quarterly
                                </option>

                            </select>

                        </div>


                        <div class="group">

                            <label class="label">
                                Start Date
                            </label>

                            <input
                                type="date"
                                name="start_date"
                                class="control"
                                value="{{ old('start_date', now()->toDateString()) }}"
                            >

                        </div>


                        <div class="group">

                            <label class="label">
                                Requested End Date
                            </label>

                            <input
                                type="date"
                                name="end_date"
                                class="control"
                                value="{{ old('end_date') }}"
                            >

                        </div>

                    </div>

                </div>

            </div>


            <!-- DOCUMENTS -->

            <div class="card">

                <div class="card-head">

                    <strong>
                        Supporting Documents
                    </strong>

                    <span>
                        Upload documents relevant to the request.
                    </span>

                </div>

                <div class="card-body">

                    <label class="upload">

                        <i class="bi bi-cloud-arrow-up"></i>

                        <strong>
                            Upload supporting documents
                        </strong>

                        <span>
                            PDF, JPG, PNG · CNIC, bills, receipts, education documents, etc.
                        </span>

                        <input
                            type="file"
                            name="supporting_documents[]"
                            multiple
                            accept=".pdf,.jpg,.jpeg,.png"
                            hidden
                            id="supportingDocuments"
                        >

                    </label>

                    <div
                        id="fileNames"
                        class="help"
                    >
                        No files selected.
                    </div>

                </div>

            </div>


            <!-- INTERNAL NOTES -->

            <div class="card">

                <div class="card-head">

                    <strong>
                        Internal Notes
                    </strong>

                    <span>
                        Notes for authorized reviewers and committee members.
                    </span>

                </div>

                <div class="card-body">

                    <textarea
                        class="control"
                        name="internal_notes"
                        placeholder="Add internal notes..."
                    >{{ old('internal_notes') }}</textarea>

                </div>

            </div>

        </div>


        <aside>

            <div class="preview">

                <div class="preview-label">
                    Application Preview
                </div>

                <div class="preview-icon">
                    <i class="bi bi-file-earmark-person"></i>
                </div>

                <div
                    class="preview-name"
                    id="previewApplicant"
                >
                    New Application
                </div>

                <div class="preview-sub">
                    Application number generated on submission
                </div>

                <div class="preview-divider"></div>

                <div class="preview-row">
                    <span>Type</span>

                    <span id="previewType">
                        {{ $categoryLabels[$selectedCategory] ?? 'General Welfare' }}
                    </span>
                </div>

                <div class="preview-row">
                    <span>Amount</span>

                    <span id="previewAmount">
                        Not entered
                    </span>
                </div>

                <div class="preview-row">
                    <span>Applicant Share</span>

                    <span id="previewContribution">
                        PKR 0
                    </span>
                </div>

                <div class="preview-row">
                    <span>Organization Share</span>

                    <span id="previewOrganization">
                        PKR 0
                    </span>
                </div>

                <div class="preview-row">
                    <span>Priority</span>

                    <span id="previewPriority">
                        {{ ucfirst(old('priority', 'normal')) }}
                    </span>
                </div>

                <div class="preview-row">
                    <span>Stage</span>

                    <span>
                        Submitted
                    </span>
                </div>

            </div>


            <div class="card">

                <div class="card-head">

                    <strong>
                        Workflow
                    </strong>

                    <span>
                        Application review path
                    </span>

                </div>

                <div class="card-body">

                    <div class="workflow-mini">

                        <div class="workflow-step">

                            <div class="workflow-dot">
                                1
                            </div>

                            <div>
                                <span>
                                    Application Submitted
                                </span>

                                <small>
                                    Request is formally recorded.
                                </small>
                            </div>

                        </div>


                        <div class="workflow-step">

                            <div class="workflow-dot">
                                2
                            </div>

                            <div>
                                <span>
                                    Field Verification
                                </span>

                                <small>
                                    Applicant and need are verified.
                                </small>
                            </div>

                        </div>


                        <div class="workflow-step">

                            <div class="workflow-dot">
                                3
                            </div>

                            <div>
                                <span>
                                    Recommendation
                                </span>

                                <small>
                                    Implementation Committee reviews.
                                </small>
                            </div>

                        </div>


                        <div class="workflow-step">

                            <div class="workflow-dot">
                                4
                            </div>

                            <div>
                                <span>
                                    Approval
                                </span>

                                <small>
                                    Authorized committee decides.
                                </small>
                            </div>

                        </div>

                    </div>

                </div>

            </div>


            <div class="card">

                <div class="card-body">

                    <div class="privacy-note">

                        <i class="bi bi-shield-lock"></i>

                        <p>
                            Welfare applications may contain sensitive
                            personal and financial information and should
                            remain restricted to authorized users.
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

    const beneficiarySelect =
        document.getElementById('beneficiarySelect');

    const requestedAmount =
        document.getElementById('requestedAmount');

    const applicantContribution =
        document.getElementById('applicantContribution');

    const previewApplicant =
        document.getElementById('previewApplicant');

    const previewAmount =
        document.getElementById('previewAmount');

    const previewContribution =
        document.getElementById('previewContribution');

    const previewOrganization =
        document.getElementById('previewOrganization');

    const previewType =
        document.getElementById('previewType');

    const previewPriority =
        document.getElementById('previewPriority');

    const requestType =
        document.getElementById('requestType');

    const fileInput =
        document.getElementById('supportingDocuments');

    const fileNames =
        document.getElementById('fileNames');


    function formatPKR(value) {

        return 'PKR ' + FoundationMoney.formatMinor(value);

    }


    function updateFinancialPreview() {

        const amount =
            FoundationMoney.minor(requestedAmount.value);

        const contribution =
            FoundationMoney.minor(applicantContribution.value);

        const organization =
            (amount > contribution ? amount - contribution : 0n);

        previewAmount.textContent =
            amount > 0
                ? formatPKR(amount)
                : 'Not entered';

        previewContribution.textContent =
            formatPKR(contribution);

        previewOrganization.textContent =
            formatPKR(organization);

    }


    beneficiarySelect.addEventListener(
        'change',
        function () {

            const text =
                this.options[
                    this.selectedIndex
                ]?.text || '';

            previewApplicant.textContent =
                text || 'New Application';

        }
    );


    requestedAmount.addEventListener(
        'input',
        updateFinancialPreview
    );


    applicantContribution.addEventListener(
        'input',
        updateFinancialPreview
    );


    document
        .querySelectorAll(
            'input[name="category"]'
        )
        .forEach(input => {

            input.addEventListener(
                'change',
                function () {

                    const map = {
                        welfare:
                            'general_welfare',

                        livelihood:
                            'livelihood_assistance',

                        education:
                            'education_support',

                        emergency:
                            'emergency_support',
                    };

                    requestType.value =
                        map[this.value]
                        || this.value;

                    const label =
                        document.querySelector(
                            `label[for="${this.id}"] strong`
                        );

                    previewType.textContent =
                        label
                            ? label.textContent
                            : '';

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

                    const label =
                        document.querySelector(
                            `label[for="${this.id}"]`
                        );

                    previewPriority.textContent =
                        label
                            ? label.textContent.trim()
                            : '';

                }
            );

        });


    fileInput.addEventListener(
        'change',
        function () {

            if (!this.files.length) {

                fileNames.textContent =
                    'No files selected.';

                return;

            }

            const names =
                Array.from(this.files)
                    .map(file => file.name)
                    .join(', ');

            fileNames.textContent =
                this.files.length +
                ' file(s) selected: ' +
                names;

        }
    );


    updateFinancialPreview();

</script>

@endpush