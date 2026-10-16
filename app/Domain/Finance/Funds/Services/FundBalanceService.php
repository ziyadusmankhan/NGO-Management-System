<?php

namespace App\Domain\Finance\Funds\Services;

use App\Domain\Finance\Funds\Models\Fund;
use App\Domain\Finance\Transactions\Models\Transaction;
use Illuminate\Support\Collection;

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
    public function balance(Fund $fund): float
    {
        $credits = $this->postedCredits($fund);
        $debits = $this->postedDebits($fund);

        return
            (float) $fund->opening_balance
            + $credits
            - $debits;
    }

    /**
     * Total posted credit movements for the fund.
     */
    public function postedCredits(Fund $fund): float
    {
        return (float) Transaction::query()
            ->where('fund_id', $fund->id)
            ->where('status', 'posted')
            ->where('direction', 'credit')
            ->sum('amount');
    }

    /**
     * Total posted debit movements for the fund.
     */
    public function postedDebits(Fund $fund): float
    {
        return (float) Transaction::query()
            ->where('fund_id', $fund->id)
            ->where('status', 'posted')
            ->where('direction', 'debit')
            ->sum('amount');
    }

    /**
     * Get a complete financial summary for a fund.
     */
    public function summary(Fund $fund): array
    {
        $credits = $this->postedCredits($fund);
        $debits = $this->postedDebits($fund);

        $opening = (float) $fund->opening_balance;

        return [
            'opening_balance' => $opening,
            'total_credits' => $credits,
            'total_debits' => $debits,
            'balance' => $opening + $credits - $debits,
        ];
    }

    /**
     * Get the balance immediately before a transaction.
     */
    public function balanceBeforeTransaction(
        Transaction $transaction
    ): float {
        $fund = $transaction->fund;

        if (! $fund) {
            return 0.0;
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

        $credits = (float) (clone $query)
            ->where('direction', 'credit')
            ->sum('amount');

        $debits = (float) (clone $query)
            ->where('direction', 'debit')
            ->sum('amount');

        return
            (float) $fund->opening_balance
            + $credits
            - $debits;
    }

    /**
     * Get the balance after a transaction.
     */
    public function balanceAfterTransaction(
        Transaction $transaction
    ): float {
        $before = $this->balanceBeforeTransaction($transaction);

        if ($transaction->status !== 'posted') {
            return $before;
        }

        if ($transaction->direction === 'credit') {
            return $before + (float) $transaction->amount;
        }

        if ($transaction->direction === 'debit') {
            return $before - (float) $transaction->amount;
        }

        return $before;
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