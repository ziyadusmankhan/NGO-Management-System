<?php

namespace App\Http\Controllers\Finance;

use App\Domain\Finance\Funds\Models\Fund;
use App\Domain\Finance\Funds\Services\FundBalanceService;
use Illuminate\View\View;

class RestrictedFundController
{
    public function index(
        FundBalanceService $balanceService
    ): View {
        $query = Fund::query()
            ->whereIn('type', [
                'restricted',
                'designated',
            ]);

        if (request()->filled('search')) {
            $search = trim(request('search'));

            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                    ->orWhere(
                        'fund_reference',
                        'like',
                        "%{$search}%"
                    )
                    ->orWhere(
                        'purpose',
                        'like',
                        "%{$search}%"
                    );
            });
        }

        $funds = $query
            ->where('status', 'active')
            ->orderBy('name')
            ->get();

        /*
         * Financial figures come from the central ledger service.
         */
        $funds->transform(function (Fund $fund) use ($balanceService) {
            $summary = $balanceService->summary($fund);

            $fund->financial_opening_balance =
                $summary['opening_balance'];

            $fund->financial_received =
                $summary['total_credits'];

            $fund->financial_spent =
                $summary['total_debits'];

            $fund->financial_balance =
                $summary['balance'];

            return $fund;
        });

        $totalFunds = $funds->count();

        $totalReceived = $funds->sum(
            fn (Fund $fund) =>
                (float) $fund->financial_received
        );

        $totalSpent = $funds->sum(
            fn (Fund $fund) =>
                (float) $fund->financial_spent
        );

        $totalBalance = $funds->sum(
            fn (Fund $fund) =>
                (float) $fund->financial_balance
        );

        return view(
            'finance.restricted-funds.index',
            compact(
                'funds',
                'totalFunds',
                'totalReceived',
                'totalSpent',
                'totalBalance'
            )
        );
    }
}