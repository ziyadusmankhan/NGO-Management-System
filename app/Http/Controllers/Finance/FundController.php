<?php

namespace App\Http\Controllers\Finance;

use App\Domain\Business\Models\BusinessMovement;
use App\Domain\Finance\Donations\Models\Donation;
use App\Domain\Finance\Expenses\Models\Expense;
use App\Domain\Finance\Funds\Models\Fund;
use App\Domain\Finance\Funds\Services\FundPurposeService;
use App\Domain\Finance\Funds\Services\FundReportingService;
use App\Domain\Finance\Funds\Services\FundService;
use App\Domain\Organization\Services\FinanceWelfareQuery;
use App\Domain\Procurement\Models\ProcurementMovement;
use App\Domain\Projects\Models\ProjectMovement;
use App\Domain\Qard\Models\QardMovement;
use App\Domain\Welfare\Assistance\Models\WelfareAssistancePayment;
use App\Http\Controllers\Controller;
use App\Http\Requests\Finance\StoreFundRequest;
use App\Http\Requests\Finance\UpdateFundRequest;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Collection;
use Illuminate\View\View;

class FundController extends Controller
{
    public function index(FundReportingService $reporting): View
    {
        $directory = app(FinanceWelfareQuery::class);
        $filters = $directory->filters(request(), 'funds');
        $funds = $directory->query('funds', $filters)->paginate(12)->withQueryString();
        $funds->getCollection()->each(fn (Fund $fund) => $fund->financial_summary = $reporting->summary($fund));
        $active = Fund::where('status', 'active')->get();
        $active->each(fn (Fund $fund) => $fund->financial_summary = $reporting->summary($fund));
        $totals = $reporting->totals($active);
        $totalFunds = $active->count();
        $totalBalance = $totals['balance'];
        $allocated = $totals['allocated'];
        $available = $totals['available'];
        $restrictedBalance = $reporting->totals($active->filter(fn ($fund) => app(FundPurposeService::class)->isRestricted($fund)))['balance'];

        return view('finance.funds.index', compact('funds', 'totalFunds', 'totalBalance', 'restrictedBalance', 'allocated', 'available'));
    }

    public function create(): View
    {
        return view('finance.funds.create');
    }

    public function store(StoreFundRequest $request, FundService $service): RedirectResponse
    {
        $fund = $service->create($request->validated(), $request->user());

        return redirect()->route('finance.funds.show', $fund)->with('success', "Fund {$fund->fund_reference} was created successfully.");
    }

    public function edit(Fund $fund): View
    {
        $designationLocked = app(FundService::class)->hasActivity($fund);
        $summary = app(FundReportingService::class)->summary($fund);

        return view('finance.funds.edit', compact('fund', 'designationLocked', 'summary'));
    }

    public function update(UpdateFundRequest $request, Fund $fund, FundService $service): RedirectResponse
    {
        $service->update($fund, $request->validated(), $request->user());

        return redirect()->route('finance.funds.show', $fund)->with('success', 'Fund updated successfully.');
    }

    public function destroy(Fund $fund, FundService $service): RedirectResponse
    {
        $service->archive($fund, request()->user());

        return redirect()->route('finance.funds.index')->with('success', "{$fund->fund_reference} was archived successfully.");
    }

    public function show(Fund $fund, FundReportingService $reporting): View
    {
        $allocations = $reporting->allocations($fund);
        $summary = $reporting->summary($fund, $allocations);
        $transactions = $reporting->ledger($fund);
        $this->attachSources($transactions);
        $changes = $fund->changes()->with('changedBy')->get();

        return view('finance.funds.show', compact('fund', 'summary', 'allocations', 'transactions', 'changes'));
    }

    private function attachSources(Collection $transactions): void
    {
        $classes = [
            Donation::class,
            Expense::class,
            WelfareAssistancePayment::class,
            QardMovement::class,
            BusinessMovement::class,
            ProjectMovement::class,
            ProcurementMovement::class,
        ];
        foreach ($classes as $class) {
            $model = new $class;
            $group = $transactions->where('source_type', $model->getMorphClass());
            $query = $class::query()->whereIn('id', $group->pluck('source_id'));
            if ($model instanceof WelfareAssistancePayment) {
                $query->with(['assistance' => fn ($q) => $q->withTrashed(), 'voidedBy']);
            } elseif ($model instanceof ProcurementMovement) {
                $query->with('procurement');
            } elseif ($model instanceof ProjectMovement) {
                $query->with('project');
            } elseif ($model instanceof BusinessMovement) {
                $query->with('application');
            } elseif ($model instanceof QardMovement) {
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
                if ($source instanceof Donation) {
                    $transaction->source_label = $source->donation_number;
                    $transaction->source_url = $source->trashed() ? null : route('finance.donations.show', $source);
                } elseif ($source instanceof Expense) {
                    $transaction->source_label = $source->expense_number;
                    $transaction->source_url = $source->trashed() ? null : route('finance.expenses.show', $source);
                } elseif ($source instanceof ProcurementMovement) {
                    $transaction->source_label = $source->procurement->number.' · '.$source->kind.' #'.$source->id;
                    $transaction->source_url = request()->user()->hasPermission('procurement.view') ? route('operations.procurement.show', $source->procurement) : null;
                    $transaction->void_reason = $source->void_reason;
                    $transaction->voided_at = $source->voided_at;
                } elseif ($source instanceof ProjectMovement) {
                    $transaction->source_label = $source->project->number.' · '.$source->kind.' #'.$source->id;
                    $transaction->source_url = request()->user()->hasPermission('projects.view') ? route('projects.show', $source->project) : null;
                    $transaction->void_reason = $source->void_reason;
                    $transaction->voided_at = $source->voided_at;
                } elseif ($source instanceof BusinessMovement) {
                    $transaction->source_label = $source->application?->number.' · '.$source->kind.' #'.$source->id;
                    $transaction->source_url = request()->user()->hasPermission('business.view') ? route('business.applications.show', $source->application) : null;
                    $transaction->void_reason = $source->void_reason;
                    $transaction->voided_at = $source->voided_at;
                } elseif ($source instanceof QardMovement) {
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
