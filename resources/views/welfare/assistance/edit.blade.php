@extends('layouts.app')

@section('title', 'Edit Welfare Assistance')

@section('content')

<div class="page-header mb-4">
    <div>
        <h1>Edit Welfare Assistance</h1>
        <p>{{ $assistance->assistance_number }}</p>
    </div>
</div>

@if($errors->any())
    <div class="alert alert-danger">
        <ul class="mb-0">
            @foreach($errors->all() as $error)
                <li>{{ $error }}</li>
            @endforeach
        </ul>
    </div>
@endif

<div class="card">

    <div class="card-body">

        <form
            method="POST"
            action="{{ route('welfare.assistance.update', $assistance) }}"
        >
            @csrf
            @method('PUT')

            <div class="row g-4">
                @if($assistance->application_id)
                    <div class="col-12">
                        <div class="alert alert-info">The beneficiary, fund, amount, purpose and schedule come from the signed agreement and application. Only review dates and administrative notes can be edited.</div>
                        <p><strong>{{ $assistance->beneficiary?->full_name }}</strong> · {{ $assistance->fund?->name }} · PKR {{ $assistance->amount }}</p>
                        <p>{{ $assistance->purpose_notes }}</p>
                    </div>
                @else

                <div class="col-12">
                    <label class="form-label">Beneficiary</label>

                    <select
                        name="beneficiary_id"
                        class="form-select"
                        required
                    >

                        @foreach($beneficiaries as $beneficiary)
                            <option
                                value="{{ $beneficiary->id }}"
                                {{ old(
                                    'beneficiary_id',
                                    $assistance->beneficiary_id
                                ) == $beneficiary->id ? 'selected' : '' }}
                            >
                                {{ $beneficiary->beneficiary_number }}
                                — {{ $beneficiary->full_name }}
                            </option>
                        @endforeach

                    </select>
                </div>

                <div class="col-md-6">
                    <label class="form-label">Assistance Type</label>

                    <select
                        name="assistance_type"
                        class="form-select"
                        required
                    >
                        @foreach([
                            'monthly_assistance' => 'Monthly Assistance',
                            'food_ration' => 'Food / Ration',
                            'medical_assistance' => 'Medical Assistance',
                            'education_support' => 'Education Support',
                            'emergency_assistance' => 'Emergency Assistance',
                            'one_time_support' => 'One-Time Support',
                        ] as $value => $label)

                            <option
                                value="{{ $value }}"
                                {{ old(
                                    'assistance_type',
                                    $assistance->assistance_type
                                ) === $value ? 'selected' : '' }}
                            >
                                {{ $label }}
                            </option>

                        @endforeach
                    </select>
                </div>

                <div class="col-md-6">
                    <label class="form-label">Amount (PKR)</label>

                    <input
                        type="number"
                        name="amount"
                        class="form-control"
                        step="0.01"
                        min="0.01"
                        required
                        value="{{ old('amount', $assistance->amount) }}"
                    >
                </div>

                <div class="col-md-6">
                    <label class="form-label">Frequency</label>

                    <select name="frequency" class="form-select">

                        @foreach([
                            'one_time' => 'One Time',
                            'weekly' => 'Weekly',
                            'monthly' => 'Monthly',
                            'quarterly' => 'Quarterly',
                            'yearly' => 'Yearly',
                        ] as $value => $label)

                            <option
                                value="{{ $value }}"
                                {{ old(
                                    'frequency',
                                    $assistance->frequency
                                ) === $value ? 'selected' : '' }}
                            >
                                {{ $label }}
                            </option>

                        @endforeach

                    </select>
                </div>

                <div class="col-md-6">
                    <label class="form-label">Fund</label>

                    <select name="fund_id" class="form-select">

                        <option value="">Unassigned</option>

                        @foreach($funds as $fund)
                            <option
                                value="{{ $fund->id }}"
                                {{ old(
                                    'fund_id',
                                    $assistance->fund_id
                                ) == $fund->id ? 'selected' : '' }}
                            >
                                {{ $fund->name }}
                            </option>
                        @endforeach

                    </select>
                </div>

                <div class="col-md-4">
                    <label class="form-label">Start Date</label>

                    <input
                        type="date"
                        name="start_date"
                        class="form-control"
                        value="{{ old(
                            'start_date',
                            $assistance->start_date?->format('Y-m-d')
                        ) }}"
                    >
                </div>

                <div class="col-md-4">
                    <label class="form-label">End Date</label>

                    <input
                        type="date"
                        name="end_date"
                        class="form-control"
                        value="{{ old(
                            'end_date',
                            $assistance->end_date?->format('Y-m-d')
                        ) }}"
                    >
                </div>

                @endif

                <div class="col-md-4">
                    <label class="form-label">Next Review Date</label>

                    <input
                        type="date"
                        name="next_review_date"
                        class="form-control"
                        value="{{ old(
                            'next_review_date',
                            $assistance->next_review_date?->format('Y-m-d')
                        ) }}"
                    >
                </div>

                @if(!$assistance->application_id)
                <div class="col-12">
                    <label class="form-label">Purpose Notes</label>

                    <textarea
                        name="purpose_notes"
                        rows="4"
                        class="form-control"
                    >{{ old('purpose_notes', $assistance->purpose_notes) }}</textarea>
                </div>

                @endif

                <div class="col-12">
                    <label class="form-label">Internal Notes</label>

                    <textarea
                        name="internal_notes"
                        rows="3"
                        class="form-control"
                    >{{ old('internal_notes', $assistance->internal_notes) }}</textarea>
                </div>

                <div class="col-12">
                    <label class="form-label">Notes</label>

                    <textarea
                        name="notes"
                        rows="3"
                        class="form-control"
                    >{{ old('notes', $assistance->notes) }}</textarea>
                </div>

                @if(!$assistance->application_id)
                <div class="col-12">
                    <div class="form-check">

                        <input
                            type="checkbox"
                            name="has_supporting_document"
                            value="1"
                            class="form-check-input"
                            id="has_supporting_document"
                            {{ old(
                                'has_supporting_document',
                                $assistance->has_supporting_document
                            ) ? 'checked' : '' }}
                        >

                        <label
                            class="form-check-label"
                            for="has_supporting_document"
                        >
                            Supporting document available
                        </label>

                    </div>
                </div>

                @endif

            </div>

            <div class="d-flex justify-content-end gap-2 mt-4">

                <a
                    href="{{ route('welfare.assistance.show', $assistance) }}"
                    class="ui-btn"
                >
                    Cancel
                </a>

                <button type="submit" class="ui-btn primary">
                    Update Assistance
                </button>

            </div>

        </form>

    </div>

</div>

@endsection