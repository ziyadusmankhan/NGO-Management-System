<?php

namespace App\Http\Controllers\Finance;

use App\Domain\Finance\Funds\Models\Fund;
use Illuminate\View\View;

class RestrictedFundController
{
    public function index(): View
    {
        $query = Fund::query()
            ->whereIn('type', [
                'restricted',
                'designated',
            ])
            ->withSum([
                'donations as received_donations' => function ($query) {
                    $query->whereIn('status', [
                        'received',
                        'restricted',
                    ]);
                },
            ], 'amount')
            ->withSum([
                'expenses as spent_expenses' => function ($query) {
                    $query->whereIn('status', [
                        'approved',
                        'paid',
                    ]);
                },
            ], 'amount');

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

        $totalFunds = $funds->count();

        $totalReceived = $funds->sum(
            fn ($fund) => (float) ($fund->received_donations ?? 0)
        );

        $totalSpent = $funds->sum(
            fn ($fund) => (float) ($fund->spent_expenses ?? 0)
        );

        $totalBalance = $funds->sum(function ($fund) {
            return (float) $fund->opening_balance
                + (float) ($fund->received_donations ?? 0)
                - (float) ($fund->spent_expenses ?? 0);
        });

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