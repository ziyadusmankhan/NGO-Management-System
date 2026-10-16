@extends('layouts.app')

@section('title', 'New Welfare Assistance')

@push('styles')
<style>
    .page-header {
        display:flex;
        justify-content:space-between;
        align-items:flex-start;
        gap:20px;
        margin-bottom:22px;
    }

    .page-header h1 {
        margin:0;
        font-size:25px;
        font-weight:700;
    }

    .page-header p {
        margin:6px 0 0;
        color:var(--muted);
    }

    .actions {
        display:flex;
        gap:8px;
    }

    .ui-btn {
        display:inline-flex;
        align-items:center;
        justify-content:center;
        gap:7px;
        height:40px;
        padding:0 14px;
        border:1px solid var(--border);
        border-radius:9px;
        background:#fff;
        color:#596760;
        text-decoration:none;
        font-weight:650;
    }

    .ui-btn.primary {
        background:var(--primary);
        border-color:var(--primary);
        color:#fff;
    }

    .card {
        background:#fff;
        border:1px solid var(--border);
        border-radius:14px;
        overflow:hidden;
        margin-bottom:18px;
    }

    .card-head {
        padding:18px 20px;
        border-bottom:1px solid var(--border);
    }

    .card-head strong {
        font-size:16px;
    }

    .card-head span {
        display:block;
        margin-top:4px;
        color:var(--muted);
        font-size:14px;
    }

    .card-body {
        padding:22px;
    }

    .grid {
        display:grid;
        grid-template-columns:repeat(2,minmax(0,1fr));
        gap:18px;
    }

    .full {
        grid-column:1 / -1;
    }

    .form-label {
        font-size:13px;
        font-weight:650;
    }

    .beneficiary-preview {
        margin-top:10px;
        padding:12px;
        border:1px solid #e7ece9;
        border-radius:10px;
        background:#fafcfb;
        color:#596760;
        font-size:14px;
    }

    .notice {
        display:flex;
        gap:10px;
        padding:13px;
        border-radius:10px;
        background:#f7f9f8;
        border:1px solid #e4ebe7;
        color:#66726c;
        font-size:14px;
        line-height:1.55;
    }

    @media(max-width:700px) {
        .page-header {
            flex-direction:column;
        }

        .grid {
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
        <h1>New Welfare Assistance</h1>
        <p>Create an assistance request for an already approved beneficiary.</p>
    </div>

    <div class="actions">
        <a
            href="{{ route('welfare.assistance.index') }}"
            class="ui-btn"
        >
            <i class="bi bi-arrow-left"></i>
            Back
        </a>
    </div>
</div>

@if($errors->any())
    <div class="alert alert-danger">
        <strong>Please correct the following:</strong>

        <ul class="mb-0 mt-2">
            @foreach($errors->all() as $error)
                <li>{{ $error }}</li>
            @endforeach
        </ul>
    </div>
@endif

<div class="card">
    <div class="card-head">
        <strong>Assistance Details</strong>
        <span>
            Only verified and approved beneficiaries can receive assistance.
        </span>
    </div>

    <div class="card-body">

        <form
            method="POST"
            action="{{ route('welfare.assistance.store') }}"
        >
            @csrf

            <div class="grid">

                <div class="full">
                    <label class="form-label">
                        Beneficiary <span class="text-danger">*</span>
                    </label>

                    <select
                        name="beneficiary_id"
                        id="beneficiary_id"
                        class="form-select"
                        required
                    >
                        <option value="">Select approved beneficiary</option>

                        @foreach($beneficiaries as $beneficiary)
                            <option
                                value="{{ $beneficiary->id }}"
                                {{ old('beneficiary_id') == $beneficiary->id ? 'selected' : '' }}
                            >
                                {{ $beneficiary->beneficiary_number }}
                                — {{ $beneficiary->full_name }}
                                @if($beneficiary->location)
                                    · {{ $beneficiary->location }}
                                @endif
                            </option>
                        @endforeach
                    </select>

                    <div class="beneficiary-preview" id="beneficiaryPreview">
                        Select a beneficiary to review their basic information.
                    </div>
                </div>

                <div>
                    <label class="form-label">
                        Assistance Type <span class="text-danger">*</span>
                    </label>

                    <select name="assistance_type" class="form-select" required>
                        <option value="">Select type</option>
                        <option value="monthly_assistance">Monthly Assistance</option>
                        <option value="food_ration">Food / Ration</option>
                        <option value="medical_assistance">Medical Assistance</option>
                        <option value="education_support">Education Support</option>
                        <option value="emergency_assistance">Emergency Assistance</option>
                        <option value="one_time_support">One-Time Support</option>
                    </select>
                </div>

                <div>
                    <label class="form-label">
                        Amount (PKR) <span class="text-danger">*</span>
                    </label>

                    <input
                        type="number"
                        name="amount"
                        class="form-control"
                        min="0.01"
                        step="0.01"
                        value="{{ old('amount') }}"
                        required
                    >
                </div>

                <div>
                    <label class="form-label">Frequency</label>

                    <select name="frequency" class="form-select">
                        <option value="">Select frequency</option>
                        <option value="one_time">One Time</option>
                        <option value="weekly">Weekly</option>
                        <option value="monthly">Monthly</option>
                        <option value="quarterly">Quarterly</option>
                        <option value="yearly">Yearly</option>
                    </select>
                </div>

                <div>
                    <label class="form-label">Fund</label>

                    <select name="fund_id" class="form-select">
                        <option value="">Select fund</option>

                        @foreach($funds as $fund)
                            <option
                                value="{{ $fund->id }}"
                                {{ old('fund_id') == $fund->id ? 'selected' : '' }}
                            >
                                {{ $fund->name }}
                            </option>
                        @endforeach
                    </select>
                </div>

                <div>
                    <label class="form-label">Start Date</label>

                    <input
                        type="date"
                        name="start_date"
                        class="form-control"
                        value="{{ old('start_date') }}"
                    >
                </div>

                <div>
                    <label class="form-label">End Date</label>

                    <input
                        type="date"
                        name="end_date"
                        class="form-control"
                        value="{{ old('end_date') }}"
                    >
                </div>

                <div>
                    <label class="form-label">Next Review Date</label>

                    <input
                        type="date"
                        name="next_review_date"
                        class="form-control"
                        value="{{ old('next_review_date') }}"
                    >
                </div>

                <div class="full">
                    <label class="form-label">Purpose / Assistance Notes</label>

                    <textarea
                        name="purpose_notes"
                        rows="4"
                        class="form-control"
                        placeholder="Describe why this assistance is being requested..."
                    >{{ old('purpose_notes') }}</textarea>
                </div>

                <div class="full">
                    <label class="form-label">Internal Notes</label>

                    <textarea
                        name="internal_notes"
                        rows="3"
                        class="form-control"
                    >{{ old('internal_notes') }}</textarea>
                </div>

                <div class="full">
                    <label class="form-label">General Notes</label>

                    <textarea
                        name="notes"
                        rows="3"
                        class="form-control"
                    >{{ old('notes') }}</textarea>
                </div>

                <div class="full">
                    <div class="form-check">
                        <input
                            type="checkbox"
                            name="has_supporting_document"
                            value="1"
                            class="form-check-input"
                            id="has_supporting_document"
                            {{ old('has_supporting_document') ? 'checked' : '' }}
                        >

                        <label
                            class="form-check-label"
                            for="has_supporting_document"
                        >
                            Supporting document is available
                        </label>
                    </div>
                </div>

                <div class="full">
                    <div class="notice">
                        <i class="bi bi-info-circle"></i>

                        <div>
                            This creates an assistance request with
                            <strong>Pending</strong> status. Approval does not
                            create a finance transaction; actual payment will
                            be connected to Finance in the payment stage.
                        </div>
                    </div>
                </div>

            </div>

            <div class="d-flex justify-content-end gap-2 mt-4 pt-4 border-top">

                <a
                    href="{{ route('welfare.assistance.index') }}"
                    class="ui-btn"
                >
                    Cancel
                </a>

                <button
                    type="submit"
                    class="ui-btn primary"
                >
                    <i class="bi bi-send"></i>
                    Create Assistance Request
                </button>

            </div>

        </form>

    </div>
</div>

@endsection