<?php

namespace App\Http\Controllers\Finance;

use App\Domain\Finance\Funds\Models\Fund;
use App\Domain\Finance\Reports\Services\FinanceReportService;
use App\Domain\Organization\Services\FinanceWelfareQuery;
use Illuminate\Http\Request;
use Illuminate\View\View;

class FinanceReportController
{
    public function index(
        Request $request,
        FinanceReportService $reportService
    ): View {
        $directory = app(FinanceWelfareQuery::class);
        $filters = $directory->filters($request, 'finance-reports');
        $fundId = $request->filled('fund_id')
            ? (int) $request->fund_id
            : null;

        $dateFrom = $request->filled('date_from')
            ? $request->date_from
            : null;

        $dateTo = $request->filled('date_to')
            ? $request->date_to
            : null;

        $overview = $reportService->overview(
            $fundId,
            $dateFrom,
            $dateTo
        );

        $fundRows = $reportService->fundRows($fundId);

        $transactions = $directory->query('finance-reports', $filters)->limit(20)->get();

        $funds = Fund::query()
            ->where('status', 'active')
            ->orderBy('name')
            ->get();

        return view(
            'finance.reports.index',
            compact(
                'overview',
                'fundRows',
                'transactions',
                'funds',
                'fundId',
                'dateFrom',
                'dateTo'
            )
        );
    }
}
