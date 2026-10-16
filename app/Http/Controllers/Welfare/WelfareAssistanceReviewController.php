<?php

namespace App\Http\Controllers\Welfare;

use App\Domain\Welfare\Assistance\Models\WelfareAssistance;
use App\Domain\Welfare\Assistance\Models\WelfareAssistanceReview;
use App\Http\Controllers\Controller;
use App\Http\Requests\Welfare\StoreWelfareAssistanceReviewRequest;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

class WelfareAssistanceReviewController extends Controller
{
    public function index(): View
    {
        $baseQuery = WelfareAssistance::query()
            ->where('status', 'approved');

        $today = now()->toDateString();

        $overdue = (clone $baseQuery)
            ->whereNotNull('next_review_date')
            ->whereDate('next_review_date', '<', $today)
            ->count();

        $dueToday = (clone $baseQuery)
            ->whereDate('next_review_date', $today)
            ->count();

        $upcoming = (clone $baseQuery)
            ->whereNotNull('next_review_date')
            ->whereBetween('next_review_date', [
                now()->addDay()->toDateString(),
                now()->addDays(30)->toDateString(),
            ])
            ->count();

        $noReviewDate = (clone $baseQuery)
            ->whereNull('next_review_date')
            ->count();

        $query = WelfareAssistance::query()
            ->where('status', 'approved')
            ->with([
                'beneficiary',
                'fund',
                'reviews.reviewedBy',
            ]);

        $filter = request('filter');

        if ($filter === 'overdue') {

            $query->whereNotNull('next_review_date')
                ->whereDate(
                    'next_review_date',
                    '<',
                    $today
                );

        } elseif ($filter === 'today') {

            $query->whereDate(
                'next_review_date',
                $today
            );

        } elseif ($filter === 'upcoming') {

            $query->whereNotNull('next_review_date')
                ->whereBetween('next_review_date', [
                    now()->addDay()->toDateString(),
                    now()->addDays(30)->toDateString(),
                ]);

        } elseif ($filter === 'none') {

            $query->whereNull('next_review_date');
        }

        if (request()->filled('search')) {

            $search = trim(
                request('search')
            );

            $query->where(function ($q) use ($search) {

                $q->where(
                    'assistance_number',
                    'like',
                    "%{$search}%"
                );

                $q->orWhereHas(
                    'beneficiary',
                    function ($beneficiaryQuery) use ($search) {

                        $beneficiaryQuery
                            ->where(
                                'full_name',
                                'like',
                                "%{$search}%"
                            )
                            ->orWhere(
                                'beneficiary_number',
                                'like',
                                "%{$search}%"
                            );
                    }
                );

            });
        }

        $assistances = $query
            ->orderByRaw(
                'CASE
                    WHEN next_review_date IS NULL THEN 3
                    WHEN next_review_date < ? THEN 0
                    WHEN next_review_date = ? THEN 1
                    ELSE 2
                 END',
                [
                    $today,
                    $today,
                ]
            )
            ->orderBy('next_review_date')
            ->orderBy('id')
            ->paginate(15)
            ->withQueryString();

        return view(
            'welfare.assistance.reviews.index',
            compact(
                'assistances',
                'overdue',
                'dueToday',
                'upcoming',
                'noReviewDate'
            )
        );
    }

    public function create(
        WelfareAssistance $assistance
    ): View {

        if ($assistance->status !== 'approved') {
            abort(
                403,
                'Only approved assistance can be reviewed.'
            );
        }

        $assistance->load([
            'beneficiary',
            'fund',
        ]);

        return view(
            'welfare.assistance.reviews.create',
            compact('assistance')
        );
    }

    public function store(
        StoreWelfareAssistanceReviewRequest $request,
        WelfareAssistance $assistance
    ): RedirectResponse {

        if ($assistance->status !== 'approved') {
            return back()
                ->withErrors([
                    'status' =>
                        'Only approved assistance can be reviewed.',
                ]);
        }

        $data = $request->validated();

        DB::transaction(function () use (
            $data,
            $assistance
        ) {

            WelfareAssistanceReview::create([
                'assistance_id' =>
                    $assistance->id,

                'reviewed_by' =>
                    auth()->id(),

                'review_date' =>
                    $data['review_date'],

                'outcome' =>
                    $data['outcome'],

                'next_review_date' =>
                    $data['next_review_date'] ?? null,

                'notes' =>
                    $data['notes'] ?? null,
            ]);

            $assistance->update([
                'next_review_date' =>
                    $data['next_review_date'] ?? null,
            ]);
        });

        return redirect()
            ->route(
                'welfare.assistance.show',
                $assistance
            )
            ->with(
                'success',
                'Welfare assistance review recorded successfully.'
            );
    }
}