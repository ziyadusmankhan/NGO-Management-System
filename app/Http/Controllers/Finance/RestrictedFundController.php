<?php

namespace App\Http\Controllers\Finance;

use App\Domain\Finance\Funds\Models\Fund;
use App\Domain\Finance\Funds\Services\FundReportingService;
use App\Domain\Organization\Services\FinanceWelfareQuery;
use Illuminate\View\View;

class RestrictedFundController
{
    public function index(FundReportingService $reporting): View
    {
        $directory = app(FinanceWelfareQuery::class);
        $funds = $directory->query('restricted-funds', $directory->filters(request(), 'restricted-funds'))->get();
        $funds->each(fn (Fund $fund) => $fund->financial_summary = $reporting->summary($fund));
        $totals = $reporting->totals($funds);
        $totalFunds = $funds->count();
        $totalReceived = $totals['received'];
        $totalSpent = $totals['spent'];
        $totalBalance = $totals['balance'];

        return view('finance.restricted-funds.index', compact('funds', 'totalFunds', 'totalReceived', 'totalSpent', 'totalBalance'));
    }
}
