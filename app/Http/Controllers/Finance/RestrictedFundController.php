<?php

namespace App\Http\Controllers\Finance;

use App\Domain\Finance\Funds\Models\Fund;
use App\Domain\Finance\Funds\Services\FundReportingService;
use Illuminate\View\View;

class RestrictedFundController
{
    public function index(FundReportingService $reporting): View
    {
        $query = Fund::query()->where(fn ($q) => $q->whereIn('type', ['restricted', 'designated'])->orWhere('restrict_spending', true));
        if (request()->filled('search')) {
            $search = trim(request('search'));
            $query->where(fn ($q) => $q->where('name', 'like', "%{$search}%")
                ->orWhere('fund_reference', 'like', "%{$search}%")->orWhere('purpose', 'like', "%{$search}%"));
        }
        $funds = $query->where('status', 'active')->orderBy('name')->get();
        $funds->each(fn (Fund $fund) => $fund->financial_summary = $reporting->summary($fund));
        $totals = $reporting->totals($funds);
        $totalFunds = $funds->count();
        $totalReceived = $totals['received'];
        $totalSpent = $totals['spent'];
        $totalBalance = $totals['balance'];

        return view('finance.restricted-funds.index', compact('funds', 'totalFunds', 'totalReceived', 'totalSpent', 'totalBalance'));
    }
}
