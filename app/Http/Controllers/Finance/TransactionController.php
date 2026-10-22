<?php

namespace App\Http\Controllers\Finance;

use App\Domain\Finance\Support\Money;
use App\Domain\Finance\Funds\Models\Fund;
use App\Domain\Finance\Funds\Services\FundBalanceService;
use App\Domain\Finance\Transactions\Models\Transaction;
use Illuminate\Http\Request;
use Illuminate\View\View;

class TransactionController
{
    public function index(
        Request $request,
        FundBalanceService $balanceService
    ): View {
        $query = Transaction::query()
            ->with('fund');

        /*
         * Search
         */
        if ($request->filled('search')) {

            $search = trim($request->search);

            $query->where(function ($q) use ($search) {

                $q->where(
                    'transaction_number',
                    'like',
                    "%{$search}%"
                )
                ->orWhere(
                    'reference',
                    'like',
                    "%{$search}%"
                )
                ->orWhere(
                    'description',
                    'like',
                    "%{$search}%"
                )
                ->orWhereHas('fund', function ($fundQuery) use ($search) {
                    $fundQuery
                        ->where('name', 'like', "%{$search}%")
                        ->orWhere(
                            'fund_reference',
                            'like',
                            "%{$search}%"
                        );
                });
            });
        }

        /*
         * Filters
         */
        if ($request->filled('fund_id')) {
            $query->where(
                'fund_id',
                $request->fund_id
            );
        }

        if ($request->filled('type')) {
            $query->where(
                'type',
                $request->type
            );
        }

        if ($request->filled('direction')) {
            $query->where(
                'direction',
                $request->direction
            );
        }

        if ($request->filled('status')) {
            $query->where(
                'status',
                $request->status
            );
        }

        if ($request->filled('date_from')) {
            $query->whereDate(
                'transaction_date',
                '>=',
                $request->date_from
            );
        }

        if ($request->filled('date_to')) {
            $query->whereDate(
                'transaction_date',
                '<=',
                $request->date_to
            );
        }

        $transactions = $query
            ->orderBy('transaction_date')
            ->orderBy('id')
            ->paginate(15)
            ->withQueryString();

        /*
         * Running balance
         *
         * The FundBalanceService is now the single source of truth
         * for transaction running balances.
         */
        foreach ($transactions as $transaction) {

            if (! $transaction->fund) {
                $transaction->running_balance = null;
                continue;
            }

            $transaction->running_balance =
                $balanceService->balanceAfterTransaction(
                    $transaction
                );
        }

        /*
         * Summary
         */
        $summaryQuery = Transaction::query()
            ->where('status', 'posted');

        if ($request->filled('fund_id')) {
            $summaryQuery->where(
                'fund_id',
                $request->fund_id
            );
        }

        if ($request->filled('type')) {
            $summaryQuery->where(
                'type',
                $request->type
            );
        }

        if ($request->filled('direction')) {
            $summaryQuery->where(
                'direction',
                $request->direction
            );
        }

        if ($request->filled('date_from')) {
            $summaryQuery->whereDate(
                'transaction_date',
                '>=',
                $request->date_from
            );
        }

        if ($request->filled('date_to')) {
            $summaryQuery->whereDate(
                'transaction_date',
                '<=',
                $request->date_to
            );
        }

        $totalCredits = Money::sum((clone $summaryQuery)
            ->where('direction', 'credit'));

        $totalDebits = Money::sum((clone $summaryQuery)
            ->where('direction', 'debit'));

        $transactionCount = (clone $summaryQuery)
            ->count();

        $netMovement =
            Money::decimal(Money::add(Money::minor($totalCredits), -Money::minor($totalDebits)));

        $funds = Fund::query()
            ->where('status', 'active')
            ->orderBy('name')
            ->get();

        return view(
            'finance.transactions.index',
            compact(
                'transactions',
                'funds',
                'totalCredits',
                'totalDebits',
                'netMovement',
                'transactionCount'
            )
        );
    }

    public function show(
        Transaction $transaction,
        FundBalanceService $balanceService
    ): View {
        $transaction->load([
            'fund',
            'recordedBy',
            'source',
        ]);

        $runningBalance = null;

        if ($transaction->fund) {
            $runningBalance =
                $balanceService->balanceAfterTransaction(
                    $transaction
                );
        }

        return view(
            'finance.transactions.show',
            compact(
                'transaction',
                'runningBalance'
            )
        );
    }
}