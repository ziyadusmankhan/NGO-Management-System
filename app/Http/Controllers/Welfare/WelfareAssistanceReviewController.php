<?php

namespace App\Http\Controllers\Welfare;

use App\Domain\Welfare\Assistance\Models\WelfareAssistance;
use App\Domain\Welfare\Assistance\Models\WelfareAssistanceReminder;
use App\Domain\Welfare\Assistance\Services\WelfareMonitoringService;
use App\Http\Controllers\Controller;
use App\Http\Requests\Welfare\StoreWelfareAssistanceReviewRequest;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;

class WelfareAssistanceReviewController extends Controller
{
    public function index(WelfareMonitoringService $monitoring): View
    {
        $today = $monitoring->today();
        $base = WelfareAssistance::where('status', 'approved');
        $filters = [
            'overdue' => fn ($query) => $query->whereDate('next_review_date', '<', $today->toDateString()),
            'today' => fn ($query) => $query->whereDate('next_review_date', $today->toDateString()),
            'upcoming' => fn ($query) => $query->whereBetween('next_review_date', [$today->addDay()->toDateString(), $today->addDays(30)->toDateString()]),
            'none' => fn ($query) => $query->whereNull('next_review_date'),
            'follow_up' => fn ($query) => $query->whereHas('latestReview', fn ($review) => $review->where('outcome', 'follow_up_required')),
            'closure' => fn ($query) => $query->whereHas('latestReview', fn ($review) => $review->where('outcome', 'no_longer_needed')),
            'ended' => fn ($query) => $query->whereDate('end_date', '<', $today->toDateString()),
        ];
        $counts = [];
        foreach ($filters as $key => $filter) {
            $counts[$key] = $filter(clone $base)->count();
        }
        $query = (clone $base)->with(['beneficiary', 'fund', 'latestReview.reviewedBy']);
        if (isset($filters[request('filter')])) {
            $filters[request('filter')]($query);
        }
        if (request()->filled('search')) {
            $search = trim(request('search'));
            $query->where(fn ($query) => $query->where('assistance_number', 'like', "%{$search}%")
                ->orWhereHas('beneficiary', fn ($beneficiary) => $beneficiary->where('full_name', 'like', "%{$search}%")
                    ->orWhere('beneficiary_number', 'like', "%{$search}%")));
        }
        $assistances = $query->orderByRaw('CASE WHEN next_review_date IS NULL THEN 1 ELSE 0 END')
            ->orderBy('next_review_date')->orderBy('id')->paginate(15)->withQueryString();
        // The queue above always reads current dates; reminders show the scheduler's retained work items.
        $reminders = WelfareAssistanceReminder::whereNull('resolved_at')
            ->whereHas('assistance', fn ($query) => $query->whereNull('deleted_at')->where('status', 'approved'))
            ->with('assistance')->orderBy('due_date')->orderBy('id')->paginate(10, ['*'], 'reminders_page')->withQueryString();

        return view('welfare.assistance.reviews.index', compact('assistances', 'counts', 'today', 'reminders'));
    }

    public function create(WelfareAssistance $assistance, WelfareMonitoringService $monitoring): View
    {
        abort_unless($assistance->status === 'approved', 403, 'Only approved assistance can be reviewed.');
        $assistance->load(['beneficiary', 'fund', 'latestReview']);
        $suggestedReviewDate = $monitoring->suggestedReviewDate($assistance);
        $today = $monitoring->today()->toDateString();

        return view('welfare.assistance.reviews.create', compact('assistance', 'suggestedReviewDate', 'today'));
    }

    public function store(StoreWelfareAssistanceReviewRequest $request, WelfareAssistance $assistance, WelfareMonitoringService $monitoring): RedirectResponse
    {
        $review = $monitoring->recordReview($assistance, $request->validated(), $request->user());

        return redirect()->route('welfare.assistance.show', $assistance)->with('success',
            $review->outcome === 'no_longer_needed'
                ? 'Review recorded. An approver must confirm closure; the assistance remains approved until then.'
                : 'Review recorded and the next review date updated.');
    }
}
