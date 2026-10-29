@extends('layouts.app')

@section('title', 'Assign Field Verification')

@push('styles')
<link rel="stylesheet" href="{{ asset('css/views/welfare-verification-create-1.css') }}">
@endpush

@section('content')

<div class="page-header">

    <div>
        <h1>Assign Field Verification</h1>

        <p>
            Assign an authorized field officer to verify a welfare application
            before it proceeds to recommendation and approval.
        </p>
    </div>

    @if(auth()->user()?->hasPermission('welfare.view'))<a
        href="{{ route('welfare.verification.index') }}"
        class="ui-btn"
    >
        <i class="bi bi-arrow-left"></i>
        Back to Verification
    </a>@endif

</div>


<div class="layout">

    <div>

        <div class="card">

            <div class="card-head">

                <strong>Verification Assignment</strong>

                <span>
                    Select an application, officer and planned field visit.
                </span>

            </div>


            <div class="card-body">

                @if ($errors->any())

                    <div class="notice" style="background:var(--legacy-surface-fff5f4, #fff5f4);border-color:var(--legacy-border-f0d6d3, #f0d6d3);">

                        <i
                            class="bi bi-exclamation-circle"
                            style="color:var(--legacy-text-a85f58, #a85f58);"
                        ></i>

                        <div>

                            <strong>
                                Please correct the following:
                            </strong>

                            <p>
                                {{ $errors->first() }}
                            </p>

                        </div>

                    </div>

                @endif


                @if ($applications->isEmpty())

                    <div class="empty-state">

                        <i
                            class="bi bi-clipboard-check"
                            style="font-size:30px;color:var(--primary);"
                        ></i>

                        <div style="margin-top:10px;font-weight:650;">
                            No applications are currently awaiting field verification.
                        </div>

                        <div style="margin-top:5px;">
                            A welfare application must be submitted by a verified
                            and approved beneficiary before it can be assigned.
                        </div>

                    </div>

                @else

                    <form
                        method="POST"
                        action="{{ route('welfare.verification.store') }}"
                    >

                        @csrf


                        <div class="notice">

                            <i class="bi bi-shield-check"></i>

                            <div>

                                <strong>
                                    Verification control
                                </strong>

                                <p>
                                    Only applications whose beneficiary remains
                                    verified and approved can enter field verification.
                                </p>

                            </div>

                        </div>


                        <!-- APPLICATION -->

                        <div class="field">

                            <label for="application_id">
                                Welfare Application
                                <span class="required">*</span>
                            </label>

                            <select
                                name="application_id"
                                id="application_id"
                                required
                            >

                                <option value="">
                                    Select application
                                </option>

                                @foreach ($applications as $application)

                                    <option
                                        value="{{ $application->id }}"
                                        data-application-number="{{ $application->application_number }}"
                                        data-beneficiary="{{ $application->beneficiary?->full_name }}"
                                        data-beneficiary-number="{{ $application->beneficiary?->beneficiary_number }}"
                                        data-category="{{ ucfirst($application->category ?? '') }}"
                                        data-request-type="{{ str_replace('_', ' ', ucfirst($application->request_type ?? '')) }}"
                                        data-amount="{{ \App\Domain\Finance\Support\Money::format($application->requested_amount) }}"
                                        @selected(
                                            old('application_id', request('application_id'))
                                            == $application->id
                                        )
                                    >

                                        {{ $application->application_number }}

                                        ·

                                        {{ $application->beneficiary?->full_name }}

                                        ·

                                        PKR {{ \App\Domain\Finance\Support\Money::format($application->requested_amount) }}

                                    </option>

                                @endforeach

                            </select>

                            <div class="help">
                                Only submitted applications with verified and approved beneficiaries are shown.
                            </div>

                            @error('application_id')
                                <div class="error">{{ $message }}</div>
                            @enderror

                        </div>


                        <!-- OFFICER -->

                        <div class="field">

                            <label for="assigned_to">
                                Assigned Officer
                                <span class="required">*</span>
                            </label>

                            <select
                                name="assigned_to"
                                id="assigned_to"
                                required
                            >

                                <option value="">
                                    Select field officer
                                </option>

                                @foreach ($officers as $officer)

                                    <option
                                        value="{{ $officer->id }}"
                                        {{ old('assigned_to') == $officer->id ? 'selected' : '' }}
                                    >

                                        {{ $officer->name }}

                                        @if ($officer->email)
                                            · {{ $officer->email }}
                                        @endif

                                    </option>

                                @endforeach

                            </select>

                            <div class="help">
                                Only active users with beneficiary verification permission are available.
                            </div>

                            @error('assigned_to')
                                <div class="error">{{ $message }}</div>
                            @enderror

                        </div>


                        <div class="two-col">

                            <!-- VISIT DATE -->

                            <div class="field">

                                <label for="visit_date">
                                    Planned Visit Date
                                    <span class="required">*</span>
                                </label>

                                <input
                                    type="date"
                                    name="visit_date"
                                    id="visit_date"
                                    value="{{ old('visit_date', now()->toDateString()) }}"
                                    min="{{ now()->toDateString() }}"
                                    required
                                >

                                <div class="help">
                                    The date the field visit is expected to take place.
                                </div>

                                @error('visit_date')
                                    <div class="error">{{ $message }}</div>
                                @enderror

                            </div>


                            <!-- PRIORITY -->

                            <div class="field">

                                <label for="priority">
                                    Priority
                                    <span class="required">*</span>
                                </label>

                                <select
                                    name="priority"
                                    id="priority"
                                    required
                                >

                                    <option
                                        value="normal"
                                        {{ old('priority', 'normal') === 'normal' ? 'selected' : '' }}
                                    >
                                        Normal
                                    </option>

                                    <option
                                        value="high"
                                        {{ old('priority') === 'high' ? 'selected' : '' }}
                                    >
                                        High
                                    </option>

                                </select>

                                <div class="help">
                                    High priority cases will appear prominently in the verification queue.
                                </div>

                                @error('priority')
                                    <div class="error">{{ $message }}</div>
                                @enderror

                            </div>

                        </div>


                        <!-- METHOD -->

                        <div class="field">

                            <label for="method">
                                Verification Method
                            </label>

                            <select
                                name="method"
                                id="method"
                            >

                                <option
                                    value="field_visit"
                                    {{ old('method', 'field_visit') === 'field_visit' ? 'selected' : '' }}
                                >
                                    Field Visit
                                </option>

                                <option
                                    value="home_visit"
                                    {{ old('method') === 'home_visit' ? 'selected' : '' }}
                                >
                                    Home Visit
                                </option>

                                <option
                                    value="office_visit"
                                    {{ old('method') === 'office_visit' ? 'selected' : '' }}
                                >
                                    Office Visit
                                </option>

                                <option
                                    value="community_verification"
                                    {{ old('method') === 'community_verification' ? 'selected' : '' }}
                                >
                                    Community Verification
                                </option>

                            </select>

                            <div class="help">
                                Identifies how the verification is expected to be conducted.
                            </div>

                            @error('method')
                                <div class="error">{{ $message }}</div>
                            @enderror

                        </div>


                        <!-- NOTES -->

                        <div class="field">

                            <label for="notes">
                                Assignment Notes
                            </label>

                            <textarea
                                name="notes"
                                id="notes"
                                placeholder="Add instructions, visit guidance or other relevant notes for the field officer..."
                            >{{ old('notes') }}</textarea>

                            @error('notes')
                                <div class="error">{{ $message }}</div>
                            @enderror

                        </div>


                        <div class="actions">

                            @if(auth()->user()?->hasPermission('welfare.view'))<a
                                href="{{ route('welfare.verification.index') }}"
                                class="ui-btn"
                            >
                                Cancel
                            </a>@endif

                            <button
                                type="submit"
                                class="ui-btn primary"
                            >
                                <i class="bi bi-clipboard-check"></i>
                                Assign Verification
                            </button>

                        </div>

                    </form>

                @endif

            </div>

        </div>

    </div>


    <!-- RIGHT SIDE -->

    <aside>

        <div class="card">

            <div class="card-head">

                <strong>Application Preview</strong>

                <span>
                    Selected application details
                </span>

            </div>


            <div class="card-body">

                <div
                    id="applicationPreview"
                    class="application-preview"
                    style="display:none;"
                >

                    <div class="preview-row">
                        <span>Application</span>
                        <span id="previewApplication">—</span>
                    </div>

                    <div class="preview-row">
                        <span>Beneficiary</span>
                        <span id="previewBeneficiary">—</span>
                    </div>

                    <div class="preview-row">
                        <span>Beneficiary ID</span>
                        <span id="previewBeneficiaryNumber">—</span>
                    </div>

                    <div class="preview-row">
                        <span>Category</span>
                        <span id="previewCategory">—</span>
                    </div>

                    <div class="preview-row">
                        <span>Request</span>
                        <span id="previewRequestType">—</span>
                    </div>

                    <div class="preview-row">
                        <span>Amount</span>
                        <span>PKR <span id="previewAmount">—</span></span>
                    </div>

                </div>


                <div
                    id="noApplicationSelected"
                    class="empty-state"
                    style="padding:20px 5px;"
                >

                    Select an application to preview its details.

                </div>


                <div class="side-note">

                    <i class="bi bi-info-circle"></i>

                    <p>
                        Assigning a verification moves the application from
                        <strong>Submitted</strong> to the
                        <strong>Verification</strong> stage.
                    </p>

                </div>

            </div>

        </div>


        <div class="card">

            <div class="card-head">

                <strong>Workflow</strong>

            </div>

            <div class="card-body">

                <div class="preview-row">
                    <span>1. Application</span>
                    <span>Submitted</span>
                </div>

                <div class="preview-row">
                    <span>2. Verification</span>
                    <span>Next</span>
                </div>

                <div class="preview-row">
                    <span>3. Recommendation</span>
                    <span>Pending</span>
                </div>

                <div class="preview-row">
                    <span>4. Approval</span>
                    <span>Pending</span>
                </div>

            </div>

        </div>

    </aside>

</div>

@endsection


@push('scripts')
<script>

    const applicationSelect =
        document.getElementById('application_id');

    const applicationPreview =
        document.getElementById('applicationPreview');

    const noApplicationSelected =
        document.getElementById('noApplicationSelected');

    const previewApplication =
        document.getElementById('previewApplication');

    const previewBeneficiary =
        document.getElementById('previewBeneficiary');

    const previewBeneficiaryNumber =
        document.getElementById('previewBeneficiaryNumber');

    const previewCategory =
        document.getElementById('previewCategory');

    const previewRequestType =
        document.getElementById('previewRequestType');

    const previewAmount =
        document.getElementById('previewAmount');


    function updateApplicationPreview() {

        if (!applicationSelect) {
            return;
        }

        const option =
            applicationSelect.options[
                applicationSelect.selectedIndex
            ];

        if (
            !option ||
            !option.value
        ) {

            applicationPreview.style.display = 'none';

            noApplicationSelected.style.display = '';

            return;
        }

        previewApplication.textContent =
            option.dataset.applicationNumber || '—';

        previewBeneficiary.textContent =
            option.dataset.beneficiary || '—';

        previewBeneficiaryNumber.textContent =
            option.dataset.beneficiaryNumber || '—';

        previewCategory.textContent =
            option.dataset.category || '—';

        previewRequestType.textContent =
            option.dataset.requestType || '—';

        previewAmount.textContent =
            option.dataset.amount || '—';

        applicationPreview.style.display = '';

        noApplicationSelected.style.display = 'none';

    }


    if (applicationSelect) {

        applicationSelect.addEventListener(
            'change',
            updateApplicationPreview
        );

        updateApplicationPreview();

    }

</script>
@endpush