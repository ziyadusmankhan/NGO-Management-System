<?php

namespace App\Http\Controllers\Finance;

use App\Domain\Finance\Funds\Models\Fund;
use App\Domain\Finance\Reports\Services\FinanceReportService;
use App\Domain\Finance\Transactions\Models\Transaction;
use Illuminate\Http\Request;
use Illuminate\View\View;

class FinanceReportController
{
    public function index(
        Request $request,
        FinanceReportService $reportService
    ): View {
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

        $fundRows = $reportService->fundRows();

        $transactions = Transaction::query()
            ->with('fund')
            ->where('status', 'posted')
            ->when(
                $fundId,
                fn ($query) => $query->where('fund_id', $fundId)
            )
            ->when(
                $dateFrom,
                fn ($query) => $query->whereDate(
                    'transaction_date',
                    '>=',
                    $dateFrom
                )
            )
            ->when(
                $dateTo,
                fn ($query) => $query->whereDate(
                    'transaction_date',
                    '<=',
                    $dateTo
                )
            )
            ->orderByDesc('transaction_date')
            ->orderByDesc('id')
            ->limit(20)
            ->get();

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