<?php

namespace App\Domain\Finance\Reports\Services;

use App\Domain\Finance\Funds\Models\Fund;
use App\Domain\Finance\Funds\Services\FundBalanceService;
use App\Domain\Finance\Transactions\Models\Transaction;
use Illuminate\Support\Collection;

class FinanceReportService
{
    public function __construct(
        private FundBalanceService $balanceService
    ) {
    }

    public function overview(
        ?int $fundId = null,
        ?string $dateFrom = null,
        ?string $dateTo = null
    ): array {
        $transactionQuery = Transaction::query()
            ->where('status', 'posted');

        if ($fundId) {
            $transactionQuery->where('fund_id', $fundId);
        }

        if ($dateFrom) {
            $transactionQuery->whereDate(
                'transaction_date',
                '>=',
                $dateFrom
            );
        }

        if ($dateTo) {
            $transactionQuery->whereDate(
                'transaction_date',
                '<=',
                $dateTo
            );
        }

        $credits = (float) (clone $transactionQuery)
            ->where('direction', 'credit')
            ->sum('amount');

        $debits = (float) (clone $transactionQuery)
            ->where('direction', 'debit')
            ->sum('amount');

        $transactionCount = (clone $transactionQuery)->count();

        if ($fundId) {
            $fund = Fund::find($fundId);

            $openingBalance = $fund
                ? (float) $fund->opening_balance
                : 0.0;

            $currentBalance = $fund
                ? $this->balanceService->balance($fund)
                : 0.0;
        } else {
            $activeFunds = Fund::query()
                ->where('status', 'active')
                ->get();

            $openingBalance = $activeFunds->sum(
                fn (Fund $fund) =>
                    (float) $fund->opening_balance
            );

            $currentBalance = $activeFunds->sum(
                fn (Fund $fund) =>
                    $this->balanceService->balance($fund)
            );
        }

        $restrictedBalance = Fund::query()
            ->where('status', 'active')
            ->whereIn('type', [
                'restricted',
                'designated',
            ])
            ->get()
            ->sum(
                fn (Fund $fund) =>
                    $this->balanceService->balance($fund)
            );

        return [
            'opening_balance' => $openingBalance,
            'total_credits' => $credits,
            'total_debits' => $debits,
            'net_movement' => $credits - $debits,
            'current_balance' => $currentBalance,
            'restricted_balance' => $restrictedBalance,
            'transaction_count' => $transactionCount,
        ];
    }

    public function fundRows(): Collection
    {
        return Fund::query()
            ->where('status', 'active')
            ->orderBy('name')
            ->get()
            ->map(function (Fund $fund) {

                $summary =
                    $this->balanceService->summary($fund);

                return [
                    'fund' => $fund,
                    'opening_balance' =>
                        $summary['opening_balance'],
                    'credits' =>
                        $summary['total_credits'],
                    'debits' =>
                        $summary['total_debits'],
                    'balance' =>
                        $summary['balance'],
                ];
            });
    }
}