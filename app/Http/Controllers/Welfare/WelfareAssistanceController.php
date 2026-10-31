<?php

namespace App\Http\Controllers\Welfare;

use App\Domain\Finance\Funds\Models\Fund;
use App\Domain\Finance\Support\Money;
use App\Domain\Organization\Services\FinanceWelfareQuery;
use App\Domain\Welfare\Applications\Models\WelfareApplication;
use App\Domain\Welfare\Assistance\Models\WelfareAssistance;
use App\Domain\Welfare\Assistance\Services\WelfareAssistanceService;
use App\Domain\Welfare\Assistance\Services\WelfareMonitoringService;
use App\Domain\Welfare\Beneficiaries\Models\Beneficiary;
use App\Http\Controllers\Controller;
use App\Http\Requests\Welfare\CloseWelfareAssistanceRequest;
use App\Http\Requests\Welfare\StoreWelfareAssistanceRequest;
use App\Http\Requests\Welfare\TransitionWelfareAssistanceRequest;
use App\Http\Requests\Welfare\UpdateWelfareAssistanceRequest;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class WelfareAssistanceController extends Controller
{
    public function index(): View
    {
        $directory = app(FinanceWelfareQuery::class);
        $filters = $directory->filters(request(), 'assistance');
        $assistances = $directory->query('assistance', $filters)->paginate(15)->withQueryString();

        $totalAssistance = WelfareAssistance::count();

        $pendingAssistance = WelfareAssistance::where(
            'status',
            'pending'
        )->count();

        $approvedAssistance = WelfareAssistance::where(
            'status',
            'approved'
        )->count();

        $thisMonthAmount = Money::sum(WelfareAssistance::query()
            ->whereYear('created_at', now()->year)
            ->whereMonth('created_at', now()->month)
            ->whereIn('status', ['approved', 'paid', 'completed', 'closed']));

        $funds = Fund::query()
            ->where('status', 'active')
            ->orderBy('name')
            ->get();

        $assistanceTypes = WelfareAssistance::query()->select('assistance_type')->distinct()->orderBy('assistance_type')->pluck('assistance_type');

        return view('welfare.assistance.index', compact(
            'assistanceTypes',
            'assistances',
            'totalAssistance',
            'pendingAssistance',
            'approvedAssistance',
            'thisMonthAmount',
            'funds'
        ));
    }

    public function create(Request $request, WelfareAssistanceService $service): View
    {
        $selection = $request->validate(['application_id' => ['nullable', 'integer']]);
        $applications = WelfareApplication::query()
            ->with(['beneficiary', 'fund', 'agreement'])
            ->where('status', 'approved')->where('is_test', false)
            ->whereHas('agreement', fn ($q) => $q->where('status', 'signed'))
            ->whereDoesntHave('assistanceIncludingArchived')
            ->orderByDesc('id')->get()
            ->filter(fn ($application) => $service->eligibilityError($application) === null);
        $selected = $applications->firstWhere('id', $selection['application_id'] ?? $request->old('application_id'));
        $selectionError = null;
        if (! $selected && ! empty($selection['application_id'])) {
            $selectionError = 'This application is unavailable. It may already have assistance or no longer meet the signed agreement requirements.';
        }

        return view('welfare.assistance.create', compact('applications', 'selected', 'selectionError'));
    }

    public function store(StoreWelfareAssistanceRequest $request, WelfareAssistanceService $service): RedirectResponse
    {
        $data = $request->validated();
        $assistance = $service->createFromApplication((int) $data['application_id'], $data, $request->user());

        return redirect()->route('welfare.assistance.show', $assistance)
            ->with('success', 'Assistance created from the signed agreement. It is pending approval; no payment was created.');
    }

    public function show(WelfareAssistance $assistance): View
    {
        $assistance->load([
            'beneficiary', 'fund', 'approvedBy', 'application.agreement', 'statusChanges.changedBy',
            'payments.fund', 'payments.recordedBy', 'payments.transaction', 'reviews.reviewedBy', 'latestReview', 'closedBy',
        ]);

        return view('welfare.assistance.show', compact('assistance'));
    }

    public function edit(WelfareAssistance $assistance): View
    {
        abort_unless(in_array($assistance->status, ['pending', 'rejected'], true), 403,
            'Only pending or rejected assistance requests can be edited.');
        $beneficiaries = collect();
        $funds = collect();
        if ($assistance->application_id === null) {
            $beneficiaries = Beneficiary::query()
                ->where(fn ($q) => $q->where(fn ($q) => $q->where('verification_status', 'verified')
                    ->where('approval_status', 'approved'))->orWhere('id', $assistance->beneficiary_id))
                ->orderBy('full_name')->get();
            $funds = Fund::query()->where('status', 'active')->orderBy('name')->get();
        }
        $assistance->load(['beneficiary', 'fund', 'application.agreement']);

        return view('welfare.assistance.edit', compact('assistance', 'beneficiaries', 'funds'));
    }

    public function update(UpdateWelfareAssistanceRequest $request, WelfareAssistance $assistance, WelfareAssistanceService $service): RedirectResponse
    {
        $service->update($assistance, $request->validated(), $request->user());

        return redirect()->route('welfare.assistance.show', $assistance)
            ->with('success', 'Assistance details updated. Its status has not changed.');
    }

    public function approve(TransitionWelfareAssistanceRequest $request, WelfareAssistance $assistance, WelfareAssistanceService $service): RedirectResponse
    {
        $service->transition($assistance, 'approved', $request->user(), $request->validated('reason'));

        return redirect()->route('welfare.assistance.show', $assistance)->with('success', 'Welfare assistance approved.');
    }

    public function reject(TransitionWelfareAssistanceRequest $request, WelfareAssistance $assistance, WelfareAssistanceService $service): RedirectResponse
    {
        $service->transition($assistance, 'rejected', $request->user(), $request->validated('reason'));

        return redirect()->route('welfare.assistance.show', $assistance)->with('success', 'Welfare assistance rejected.');
    }

    public function resubmit(TransitionWelfareAssistanceRequest $request, WelfareAssistance $assistance, WelfareAssistanceService $service): RedirectResponse
    {
        $service->transition($assistance, 'pending', $request->user(), $request->validated('reason'));

        return redirect()->route('welfare.assistance.show', $assistance)->with('success', 'Welfare assistance resubmitted for approval.');
    }

    public function close(CloseWelfareAssistanceRequest $request, WelfareAssistance $assistance,
        WelfareMonitoringService $monitoring): RedirectResponse
    {
        $changed = $monitoring->close($assistance, $request->validated('reason'), $request->user());

        return redirect()->route('welfare.assistance.show', $assistance)->with('success', $changed
            ? 'Assistance closed. Further payments are blocked and unpaid allocations have been released. Existing payments are unchanged.'
            : 'This assistance was already closed. Its original closure record is unchanged.');
    }
}
