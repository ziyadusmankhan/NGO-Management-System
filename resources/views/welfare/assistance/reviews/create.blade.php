@extends('layouts.app')

@section('title', 'Record Assistance Review')

@push('styles')
<link rel="stylesheet" href="{{ asset('css/views/welfare-assistance-reviews-create-1.css') }}">
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
                @if(auth()->user()?->hasPermission('welfare.assistance.view'))<a href="{{ route('welfare.assistance.show', $assistance) }}" class="btn">Cancel</a>@endif
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
