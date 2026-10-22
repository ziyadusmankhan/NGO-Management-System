@php
    $monitoringToday = app(\App\Domain\Welfare\Assistance\Services\WelfareMonitoringService::class)->today()->toDateString();
    $followUp = $assistance->latestReview;
@endphp
<div class="card mb-3"><div class="card-body">
    <h2 class="h5">Review and follow-up</h2>
    <p>Next review: <strong>{{ $assistance->next_review_date?->format('d M Y') ?? 'Not scheduled' }}</strong>
        @if($assistance->status === 'approved' && $assistance->next_review_date && $assistance->next_review_date->toDateString() <= $monitoringToday)
            <span class="badge bg-warning text-dark">{{ $assistance->next_review_date->toDateString() < $monitoringToday ? 'Overdue' : 'Due today' }}</span>
        @endif
    </p>
    @if($assistance->status === 'approved' && $assistance->end_date && $assistance->end_date->toDateString() < $monitoringToday)
        <div class="alert alert-warning">The assistance period ended on {{ $assistance->end_date->format('d M Y') }}. An approver should review this case for closure.</div>
    @endif
    @if($followUp)
        <p><strong>Latest outcome:</strong> {{ $followUp->outcome === 'no_longer_needed' ? 'Closure recommended' : \Illuminate\Support\Str::headline($followUp->outcome) }}
            · {{ $followUp->review_date->format('d M Y') }}</p>
        @if($followUp->follow_up_action)<p><strong>Follow-up action:</strong> {{ $followUp->follow_up_action }}</p>@endif
        @if($assistance->status === 'approved' && $followUp->outcome === 'no_longer_needed')
            <div class="alert alert-warning">Awaiting approver closure. The assistance remains approved and its unpaid allocation remains committed until closure is confirmed.</div>
        @endif
    @else
        <p>No review recorded yet.</p>
    @endif
    @if($assistance->status === 'closed')
        <div class="alert alert-secondary mb-0">
            <strong>Closed by {{ $assistance->closedBy?->name ?? 'Unavailable user' }}</strong>
            · {{ $assistance->closed_at?->format('d M Y H:i') }}
            <div>{{ $assistance->closure_reason }}</div>
            <div>Further payments are blocked. Existing payments and review history are retained.</div>
        </div>
    @elseif(in_array($assistance->status, ['approved', 'completed'], true) && auth()->user()->hasPermission('welfare.reviews.close'))
        <details>
            <summary class="fw-semibold">Close assistance</summary>
            <p class="mt-2">Confirm closure when support has ended or is no longer needed. This blocks further payments and releases any unpaid allocation. Existing payments are retained; there is no automatic refund.</p>
            <form method="POST" action="{{ route('welfare.assistance.close', $assistance) }}" onsubmit="return confirm('Close this assistance and release its unpaid allocation? Further payments will be blocked.');">
                @csrf
                <label for="closure-reason" class="form-label">Closure reason</label>
                <textarea id="closure-reason" name="reason" class="form-control mb-2" required maxlength="5000">{{ old('reason') }}</textarea>
                <button class="ui-btn danger" type="submit">Confirm closure</button>
            </form>
        </details>
    @endif
</div></div>
