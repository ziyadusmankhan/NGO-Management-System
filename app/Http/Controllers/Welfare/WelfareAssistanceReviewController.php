<?php

namespace App\Http\Controllers\Welfare;

use App\Domain\Organization\Services\FinanceWelfareQuery;
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
        $directory = app(FinanceWelfareQuery::class);
        $filters = $directory->reviewFilters();
        $counts = [];
        foreach ($filters as $key => $filter) {
            $counts[$key] = $filter(clone $base)->count();
        }
        $assistances = $directory->query('reviews', $directory->filters(request(), 'reviews'))->paginate(15)->withQueryString();
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
