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
    <h1>Record Assistance Review</h1>
    <p>{{ $assistance->assistance_number }} · {{ $assistance->beneficiary?->full_name ?? 'Unavailable beneficiary' }}</p>
</div>
@if($errors->any())
    <div class="alert alert-danger" role="alert"><ul class="mb-0">@foreach($errors->all() as $error)<li>{{ $error }}</li>@endforeach</ul></div>
@endif
<div class="layout">
    <main>
        <form method="POST" action="{{ route('welfare.assistance.reviews.store', $assistance) }}">
            @csrf
            <input type="hidden" name="idempotency_key" value="{{ old('idempotency_key', (string) \Illuminate\Support\Str::uuid()) }}">
            <input type="hidden" name="expected_review_id" value="{{ old('expected_review_id', $assistance->latestReview?->id ?? 0) }}">
            <div class="card">
                <div class="card-head"><strong>Review Outcome</strong><span>Record findings and the next action. Reviews do not create payments.</span></div>
                <div class="card-body">
                    <div class="outcome-grid">
                        @foreach(['continued' => ['Continue assistance', 'The current approved assistance remains needed.'], 'follow_up_required' => ['Follow-up required', 'Record the action needed before the next review.'], 'no_longer_needed' => ['Recommend closure', 'An approver must confirm that assistance is no longer needed.']] as $value => [$label, $help])
                            <div class="outcome-option">
                                <input type="radio" id="{{ $value }}" name="outcome" value="{{ $value }}" @checked(old('outcome', 'continued') === $value) required>
                                <label for="{{ $value }}"><strong>{{ $label }}</strong><span>{{ $help }}</span></label>
                            </div>
                        @endforeach
                    </div>
                    <div class="grid mt-3">
                        <div>
                            <label class="label" for="review-date">Review date</label>
                            <input id="review-date" type="date" name="review_date" class="control" value="{{ old('review_date', $today) }}" max="{{ $today }}" required>
                        </div>
                        <div>
                            <label class="label" for="next-review">Next review date</label>
                            <input id="next-review" type="date" name="next_review_date" class="control" value="{{ old('next_review_date', $suggestedReviewDate) }}">
                            <small>Required unless recommending closure. Weekly default: 7 days; monthly: 1 calendar month.</small>
                        </div>
                        <div class="full">
                            <label class="label" for="review-findings">Findings / justification</label>
                            <textarea id="review-findings" name="notes" class="control" maxlength="5000" required>{{ old('notes') }}</textarea>
                        </div>
                        <div class="full">
                            <label class="label" for="follow-up-action">Follow-up action</label>
                            <textarea id="follow-up-action" name="follow_up_action" class="control" maxlength="5000" placeholder="What needs to happen, and who should handle it?">{{ old('follow_up_action') }}</textarea>
                            <small>Required when follow-up is needed. A later review records whether the action is resolved.</small>
                        </div>
                    </div>
                </div>
            </div>
            <div class="actions">
                <a href="{{ route('welfare.assistance.show', $assistance) }}" class="btn">Cancel</a>
                <button type="submit" class="btn primary">Record Review</button>
            </div>
        </form>
    </main>
    <aside>
        <div class="card"><div class="card-head"><strong>Current assistance</strong></div><div class="card-body">
            <div class="summary-row"><span>Fund</span><span>{{ $assistance->fund?->name ?? 'Unavailable' }}</span></div>
            <div class="summary-row"><span>Total approval</span><span>PKR {{ \App\Domain\Finance\Support\Money::format($assistance->amount ?? '0.00') }}</span></div>
            <div class="summary-row"><span>Frequency</span><span>{{ \Illuminate\Support\Str::headline($assistance->frequency ?? 'one_time') }}</span></div>
            <div class="summary-row"><span>Next review</span><span>{{ $assistance->next_review_date?->format('d M Y') ?? 'Not scheduled' }}</span></div>
            <div class="summary-row"><span>End date</span><span>{{ $assistance->end_date?->format('d M Y') ?? 'Not set' }}</span></div>
            @if($assistance->latestReview)
                <p class="mt-3"><strong>Latest findings:</strong> {{ $assistance->latestReview->notes }}</p>
                <p><strong>Latest action:</strong> {{ $assistance->latestReview->follow_up_action ?? 'None recorded' }}</p>
            @endif
        </div></div>
        <div class="notice"><p>Payment frequency does not renew the approved amount. Closure requires an approver and releases unpaid allocations while retaining existing financial history.</p></div>
    </aside>
</div>
@endsection
