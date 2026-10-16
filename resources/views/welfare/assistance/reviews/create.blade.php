@extends('layouts.app')

@section('title', 'Record Assistance Review')

@push('styles')
<style>

    .page-header {
        margin-bottom:24px;
    }

    .page-header h1 {
        margin:0;
        font-size:25px;
        font-weight:700;
    }

    .page-header p {
        margin:7px 0 0;
        color:var(--muted);
        font-size:15px;
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
        font-size:16px;
    }

    .card-head span {
        display:block;
        margin-top:4px;
        color:#6c7470;
        font-size:13px;
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
        min-height:120px;
        padding:11px 12px;
        resize:vertical;
        line-height:1.6;
    }

    .control:focus,
    .select:focus {
        border-color:#6b7670;
        box-shadow:0 0 0 3px rgba(23,63,53,.055);
    }

    .outcome-grid {
        display:grid;
        grid-template-columns:repeat(3,minmax(0,1fr));
        gap:10px;
    }

    .outcome-option {
        position:relative;
    }

    .outcome-option input {
        position:absolute;
        opacity:0;
        pointer-events:none;
    }

    .outcome-option label {
        min-height:88px;
        padding:12px;
        border:1px solid var(--border);
        border-radius:10px;
        display:flex;
        flex-direction:column;
        justify-content:center;
        cursor:pointer;
    }

    .outcome-option label strong {
        font-size:14px;
    }

    .outcome-option label span {
        margin-top:5px;
        color:#6d7772;
        font-size:12px;
        line-height:1.45;
    }

    .outcome-option input:checked + label {
        background:var(--primary-soft);
        border-color:#6b7570;
        color:var(--primary);
    }

    .summary-row {
        display:flex;
        justify-content:space-between;
        gap:10px;
        padding:10px 0;
        border-bottom:1px solid #eef1ef;
        font-size:13px;
    }

    .summary-row:last-child {
        border-bottom:none;
    }

    .summary-row span:first-child {
        color:#6d7571;
    }

    .summary-row span:last-child {
        color:#46534c;
        font-weight:650;
        text-align:right;
    }

    .actions {
        display:flex;
        justify-content:space-between;
        gap:9px;
        margin-top:18px;
    }

    .btn {
        min-height:40px;
        padding:0 15px;
        border-radius:10px;
        display:inline-flex;
        align-items:center;
        justify-content:center;
        gap:7px;
        border:1px solid var(--border);
        background:#fff;
        color:#637069;
        font-size:14px;
        font-weight:650;
        text-decoration:none;
        cursor:pointer;
    }

    .btn.primary {
        background:var(--primary);
        border-color:var(--primary);
        color:#fff;
    }

    .notice {
        display:flex;
        gap:10px;
        padding:13px;
        border:1px solid #e5ebe7;
        border-radius:10px;
        background:#fafcfa;
    }

    .notice i {
        color:var(--primary);
        font-size:17px;
    }

    .notice p {
        margin:0;
        color:#66716b;
        font-size:13px;
        line-height:1.55;
    }

    @media(max-width:900px) {
        .layout {
            grid-template-columns:1fr;
        }
    }

    @media(max-width:700px) {
        .grid,
        .outcome-grid {
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

    <h1>
        Record Assistance Review
    </h1>

    <p>
        Document the latest review outcome and establish the next review date.
    </p>

</div>


<div class="layout">

    <main>

        <form
            method="POST"
            action="{{ route(
                'welfare.assistance.reviews.store',
                $assistance
            ) }}"
        >

            @csrf


            <div class="card">

                <div class="card-head">

                    <strong>
                        Review Outcome
                    </strong>

                    <span>
                        Record what was decided after reviewing the beneficiary's current circumstances.
                    </span>

                </div>


                <div class="card-body">

                    <div class="outcome-grid">

                        <div class="outcome-option">

                            <input
                                type="radio"
                                id="continued"
                                name="outcome"
                                value="continued"
                                @checked(old('outcome') === 'continued')
                            >

                            <label for="continued">

                                <strong>
                                    Continued
                                </strong>

                                <span>
                                    Assistance remains appropriate and should continue.
                                </span>

                            </label>

                        </div>


                        <div class="outcome-option">

                            <input
                                type="radio"
                                id="follow_up"
                                name="outcome"
                                value="follow_up_required"
                                @checked(old('outcome') === 'follow_up_required')
                            >

                            <label for="follow_up">

                                <strong>
                                    Follow-up Required
                                </strong>

                                <span>
                                    Additional verification or monitoring is needed.
                                </span>

                            </label>

                        </div>


                        <div class="outcome-option">

                            <input
                                type="radio"
                                id="no_longer_needed"
                                name="outcome"
                                value="no_longer_needed"
                                @checked(old('outcome') === 'no_longer_needed')
                            >

                            <label for="no_longer_needed">

                                <strong>
                                    No Longer Needed
                                </strong>

                                <span>
                                    The assistance no longer requires a scheduled review.
                                </span>

                            </label>

                        </div>

                    </div>


                    @error('outcome')

                        <div style="
                            margin-top:8px;
                            color:#a34f4f;
                            font-size:13px;
                        ">
                            {{ $message }}
                        </div>

                    @enderror

                </div>

            </div>


            <div class="card">

                <div class="card-head">

                    <strong>
                        Review Dates
                    </strong>

                    <span>
                        Record when this review was completed and when the next review should occur.
                    </span>

                </div>


                <div class="card-body">

                    <div class="grid">

                        <div>

                            <label class="label">
                                Review Date
                            </label>

                            <input
                                type="date"
                                name="review_date"
                                class="control"
                                value="{{ old(
                                    'review_date',
                                    now()->toDateString()
                                ) }}"
                                required
                            >

                            @error('review_date')

                                <div style="
                                    margin-top:6px;
                                    color:#a34f4f;
                                    font-size:12px;
                                ">
                                    {{ $message }}
                                </div>

                            @enderror

                        </div>


                        <div>

                            <label class="label">
                                Next Review Date
                            </label>

                            <input
                                type="date"
                                name="next_review_date"
                                class="control"
                                value="{{ old(
                                    'next_review_date',
                                    $assistance->next_review_date?->toDateString()
                                ) }}"
                            >

                            @error('next_review_date')

                                <div style="
                                    margin-top:6px;
                                    color:#a34f4f;
                                    font-size:12px;
                                ">
                                    {{ $message }}
                                </div>

                            @enderror

                        </div>


                        <div class="full">

                            <label class="label">
                                Review Notes
                            </label>

                            <textarea
                                name="notes"
                                class="control"
                                placeholder="Record relevant findings, changes in circumstances, follow-up requirements or justification..."
                            >{{ old('notes') }}</textarea>

                            @error('notes')

                                <div style="
                                    margin-top:6px;
                                    color:#a34f4f;
                                    font-size:12px;
                                ">
                                    {{ $message }}
                                </div>

                            @enderror

                        </div>

                    </div>

                </div>

            </div>


            <div class="actions">

                <a
                    href="{{ route(
                        'welfare.assistance.show',
                        $assistance
                    ) }}"
                    class="btn"
                >
                    <i class="bi bi-arrow-left"></i>
                    Cancel
                </a>


                <button
                    type="submit"
                    class="btn primary"
                >
                    <i class="bi bi-check2-circle"></i>
                    Record Review
                </button>

            </div>

        </form>

    </main>


    <aside>

        <div class="card">

            <div class="card-head">

                <strong>
                    Assistance
                </strong>

            </div>


            <div class="card-body">

                <div class="summary-row">

                    <span>
                        Assistance
                    </span>

                    <span>
                        {{ $assistance->assistance_number }}
                    </span>

                </div>


                <div class="summary-row">

                    <span>
                        Beneficiary
                    </span>

                    <span>
                        {{ $assistance->beneficiary->full_name }}
                    </span>

                </div>


                <div class="summary-row">

                    <span>
                        Fund
                    </span>

                    <span>
                        {{ $assistance->fund?->name ?: '—' }}
                    </span>

                </div>


                <div class="summary-row">

                    <span>
                        Amount
                    </span>

                    <span>
                        PKR {{ number_format(
                            $assistance->amount,
                            0
                        ) }}
                    </span>

                </div>


                <div class="summary-row">

                    <span>
                        Current Review
                    </span>

                    <span>
                        {{ $assistance->next_review_date
                            ? $assistance->next_review_date->format('d M Y')
                            : 'No date'
                        }}
                    </span>

                </div>

            </div>

        </div>


        <div class="card">

            <div class="card-body">

                <div class="notice">

                    <i class="bi bi-shield-check"></i>

                    <p>
                        Review records preserve the review date, outcome,
                        next review date, reviewer and notes. Updating the
                        next review date does not create a finance transaction.
                    </p>

                </div>

            </div>

        </div>

    </aside>

</div>

@endsection