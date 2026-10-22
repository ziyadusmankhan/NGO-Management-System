<?php

namespace App\Domain\Finance\Funds\Services;

use App\Domain\Finance\Funds\Models\Fund;
use App\Domain\Finance\Transactions\Models\Transaction;
use Illuminate\Support\Collection;
use App\Domain\Finance\Support\Money;

class FundBalanceService
{
    /**
     * Calculate the current balance of a fund.
     *
     * Formula:
     *
     * Opening Balance
     * + Posted Credits
     * - Posted Debits
     */
    public function balance(Fund $fund): string
    {
        return Money::decimal($this->balanceMinor($fund));
    }

    public function balanceMinor(Fund $fund): int
    {
        return Money::add(Money::minor($fund->opening_balance), Money::add(
            Money::minor($this->postedCredits($fund)), -Money::minor($this->postedDebits($fund))
        ));
    }

    public function postedCredits(Fund $fund): string
    {
        return Money::decimal(Money::sumMinor(Transaction::query()
            ->where('fund_id', $fund->id)->where('status', 'posted')->where('direction', 'credit')));
    }

    public function postedDebits(Fund $fund): string
    {
        return Money::decimal(Money::sumMinor(Transaction::query()
            ->where('fund_id', $fund->id)->where('status', 'posted')->where('direction', 'debit')));
    }

    public function summary(Fund $fund): array
    {
        $credits = $this->postedCredits($fund);
        $debits = $this->postedDebits($fund);

        return [
            'opening_balance' => $fund->opening_balance,
            'total_credits' => $credits,
            'total_debits' => $debits,
            'balance' => Money::decimal(Money::add(Money::minor($fund->opening_balance),
                Money::add(Money::minor($credits), -Money::minor($debits)))),
        ];
    }

    /**
     * Get the balance immediately before a transaction.
     */
    public function balanceBeforeTransaction(
        Transaction $transaction
    ): string {
        $fund = $transaction->fund;

        if (! $fund) {
            return '0.00';
        }

        $query = Transaction::query()
            ->where('fund_id', $fund->id)
            ->where('status', 'posted')
            ->where(function ($query) use ($transaction) {
                $query
                    ->where(
                        'transaction_date',
                        '<',
                        $transaction->transaction_date
                    )
                    ->orWhere(function ($query) use ($transaction) {
                        $query
                            ->where(
                                'transaction_date',
                                '=',
                                $transaction->transaction_date
                            )
                            ->where(
                                'id',
                                '<',
                                $transaction->id
                            );
                    });
            });

        $credits = Money::sumMinor((clone $query)->where('direction', 'credit'));
        $debits = Money::sumMinor((clone $query)->where('direction', 'debit'));

        return Money::decimal(Money::add(Money::minor($fund->opening_balance), Money::add($credits, -$debits)));
    }

    /** Get the balance after a transaction without rounding through floats. */
    public function balanceAfterTransaction(Transaction $transaction): string
    {
        $before = Money::minor($this->balanceBeforeTransaction($transaction));
        $movement = ! $transaction->trashed() && $transaction->status === 'posted' ? Money::minor($transaction->amount) : 0;

        return Money::decimal(Money::add($before, match ($transaction->direction) {
            'credit' => $movement,
            'debit' => -$movement,
            default => 0,
        }));
    }

    /**
     * Return all active funds with their financial summaries.
     */
    public function activeFundSummaries(): Collection
    {
        return Fund::query()
            ->where('status', 'active')
            ->orderBy('name')
            ->get()
            ->map(function (Fund $fund) {
                $summary = $this->summary($fund);

                $fund->financial_opening_balance =
                    $summary['opening_balance'];

                $fund->financial_credits =
                    $summary['total_credits'];

                $fund->financial_debits =
                    $summary['total_debits'];

                $fund->financial_balance =
                    $summary['balance'];

                return $fund;
            });
    }
}
