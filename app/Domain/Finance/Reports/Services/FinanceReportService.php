<?php

namespace App\Domain\Finance\Reports\Services;

use App\Domain\Finance\Support\Money;
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

        $credits = Money::sum((clone $transactionQuery)->where('direction', 'credit'));
        $debits = Money::sum((clone $transactionQuery)->where('direction', 'debit'));
        $transactionCount = (clone $transactionQuery)->count();

        $funds = Fund::query()->when($fundId,
            fn ($query) => $query->whereKey($fundId),
            fn ($query) => $query->where('status', 'active')
        )->get();
        $openingBalance = Money::total($funds->map(fn (Fund $fund) => $fund->opening_balance));
        $currentBalance = Money::total($funds->map(fn (Fund $fund) => $this->balanceService->balance($fund)));
        $restrictedBalance = Money::total(Fund::query()->where('status', 'active')
            ->whereIn('type', ['restricted', 'designated'])->get()
            ->map(fn (Fund $fund) => $this->balanceService->balance($fund)));

        return [
            'opening_balance' => $openingBalance,
            'total_credits' => $credits,
            'total_debits' => $debits,
            'net_movement' => Money::decimal(Money::add(Money::minor($credits), -Money::minor($debits))),
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