<?php

namespace App\Http\Controllers\Finance;

use App\Http\Controllers\Controller;
use App\Domain\Finance\Funds\Models\Fund;
use App\Http\Requests\Finance\StoreFundRequest;
use App\Http\Requests\Finance\UpdateFundRequest;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;

class FundController extends Controller
{
    public function index(\App\Domain\Finance\Funds\Services\FundReportingService $reporting): View
    {
        $query = Fund::query();
        if (request()->filled('search')) {
            $search = trim(request('search'));
            $query->where(fn ($q) => $q->where('name', 'like', "%{$search}%")
                ->orWhere('fund_reference', 'like', "%{$search}%")->orWhere('code', 'like', "%{$search}%")
                ->orWhere('purpose', 'like', "%{$search}%"));
        }
        if (request('status') === 'archived') {
            $query->onlyTrashed();
        }
        foreach (['type', 'status'] as $filter) {
            if (request()->filled($filter)) {
                if ($filter === 'status' && request('status') === 'archived') {
                    continue;
                }
                $query->where($filter, request($filter));
            }
        }
        $funds = $query->latest('id')->paginate(12)->withQueryString();
        $funds->getCollection()->each(fn (Fund $fund) => $fund->financial_summary = $reporting->summary($fund));
        $active = Fund::where('status', 'active')->get();
        $active->each(fn (Fund $fund) => $fund->financial_summary = $reporting->summary($fund));
        $totals = $reporting->totals($active);
        $totalFunds = $active->count();
        $totalBalance = $totals['balance'];
        $allocated = $totals['allocated'];
        $available = $totals['available'];
        $restrictedBalance = $reporting->totals($active->filter(fn ($fund) =>
            app(\App\Domain\Finance\Funds\Services\FundPurposeService::class)->isRestricted($fund)))['balance'];

        return view('finance.funds.index', compact('funds', 'totalFunds', 'totalBalance', 'restrictedBalance', 'allocated', 'available'));
    }

    public function create(): View
    {
        return view('finance.funds.create');
    }

    public function store(StoreFundRequest $request, \App\Domain\Finance\Funds\Services\FundService $service): RedirectResponse
    {
        $fund = $service->create($request->validated(), $request->user());

        return redirect()->route('finance.funds.show', $fund)->with('success', "Fund {$fund->fund_reference} was created successfully.");
    }

    public function edit(Fund $fund): View
    {
        $designationLocked = app(\App\Domain\Finance\Funds\Services\FundService::class)->hasActivity($fund);
        $summary = app(\App\Domain\Finance\Funds\Services\FundReportingService::class)->summary($fund);

        return view('finance.funds.edit', compact('fund', 'designationLocked', 'summary'));
    }

    public function update(UpdateFundRequest $request, Fund $fund, \App\Domain\Finance\Funds\Services\FundService $service): RedirectResponse
    {
        $service->update($fund, $request->validated(), $request->user());

        return redirect()->route('finance.funds.show', $fund)->with('success', 'Fund updated successfully.');
    }

    public function destroy(Fund $fund, \App\Domain\Finance\Funds\Services\FundService $service): RedirectResponse
    {
        $service->archive($fund, request()->user());

        return redirect()->route('finance.funds.index')->with('success', "{$fund->fund_reference} was archived successfully.");
    }

    public function show(Fund $fund, \App\Domain\Finance\Funds\Services\FundReportingService $reporting): View
    {
        $allocations = $reporting->allocations($fund);
        $summary = $reporting->summary($fund, $allocations);
        $transactions = $reporting->ledger($fund);
        $this->attachSources($transactions);
        $changes = $fund->changes()->with('changedBy')->get();

        return view('finance.funds.show', compact('fund', 'summary', 'allocations', 'transactions', 'changes'));
    }

    private function attachSources(\Illuminate\Support\Collection $transactions): void
    {
        $classes = [
            \App\Domain\Finance\Donations\Models\Donation::class,
            \App\Domain\Finance\Expenses\Models\Expense::class,
            \App\Domain\Welfare\Assistance\Models\WelfareAssistancePayment::class,
            \App\Domain\Qard\Models\QardMovement::class,
        ];
        foreach ($classes as $class) {
            $model = new $class;
            $group = $transactions->where('source_type', $model->getMorphClass());
            $query = $class::query()->whereIn('id', $group->pluck('source_id'));
            if ($model instanceof \App\Domain\Welfare\Assistance\Models\WelfareAssistancePayment) {
                $query->with(['assistance' => fn ($q) => $q->withTrashed(), 'voidedBy']);
            } elseif ($model instanceof \App\Domain\Qard\Models\QardMovement) {
                $query->with('loan');
            } else {
                $query->withTrashed();
            }
            $sources = $query->get()->keyBy('id');
            foreach ($group as $transaction) {
                $source = $sources->get($transaction->source_id);
                if (! $source) {
                    continue;
                }
                if ($source instanceof \App\Domain\Finance\Donations\Models\Donation) {
                    $transaction->source_label = $source->donation_number;
                    $transaction->source_url = $source->trashed() ? null : route('finance.donations.show', $source);
                } elseif ($source instanceof \App\Domain\Finance\Expenses\Models\Expense) {
                    $transaction->source_label = $source->expense_number;
                    $transaction->source_url = $source->trashed() ? null : route('finance.expenses.show', $source);
                } elseif ($source instanceof \App\Domain\Qard\Models\QardMovement) {
                    $transaction->source_label = $source->loan?->number.' · '.$source->kind.' #'.$source->id;
                    $transaction->source_url = request()->user()->hasPermission('qard.view') ? route('qard.show', $source->loan) : null;
                    $transaction->void_reason = $source->void_reason;
                    $transaction->voided_at = $source->voided_at;
                } else {
                    $transaction->source_label = $source->payment_number;
                    $transaction->source_url = $source->assistance && ! $source->assistance->trashed()
                        && request()->user()->hasPermission('welfare.assistance.view')
                        ? route('welfare.assistance.payments.show', [$source->assistance, $source]) : null;
                    $transaction->void_reason = $source->void_reason;
                    $transaction->void_actor = $source->voidedBy?->name;
                    $transaction->voided_at = $source->voided_at;
                    $transaction->assistance_number = $source->assistance?->assistance_number;
                }
            }
        }
    }
}
