<?php

namespace App\Http\Controllers\Finance;

use App\Http\Controllers\Controller;
use App\Domain\Finance\Funds\Models\Fund;
use Illuminate\View\View;

class FundController extends Controller
{
    public function index(): View
    {
        $query = Fund::query()
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
                    ->orWhere('fund_reference', 'like', "%{$search}%")
                    ->orWhere('code', 'like', "%{$search}%")
                    ->orWhere('purpose', 'like', "%{$search}%");
            });
        }

        if (request()->filled('type')) {
            $query->where('type', request('type'));
        }

        if (request()->filled('status')) {
            $query->where('status', request('status'));
        }

        $funds = $query
            ->latest('id')
            ->paginate(12)
            ->withQueryString();

        /*
         * Summary cards calculation
         */
        $activeFunds = Fund::query()
            ->where('status', 'active')
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
            ], 'amount')
            ->get();

        $totalFunds = $activeFunds->count();

        $totalBalance = $activeFunds->sum(function ($fund) {
            return (float) $fund->opening_balance
                + (float) ($fund->received_donations ?? 0)
                - (float) ($fund->spent_expenses ?? 0);
        });

        $restrictedBalance = $activeFunds
            ->whereIn('type', [
                'restricted',
                'designated',
            ])
            ->sum(function ($fund) {
                return (float) $fund->opening_balance
                    + (float) ($fund->received_donations ?? 0)
                    - (float) ($fund->spent_expenses ?? 0);
            });

        $allocated = 0;
        $available = $totalBalance - $allocated;

        return view(
            'finance.funds.index',
            compact(
                'funds',
                'totalFunds',
                'totalBalance',
                'restrictedBalance',
                'allocated',
                'available'
            )
        );
    }

    public function show(Fund $fund): View
    {
        $received = $fund->donations()
            ->whereIn('status', ['received', 'restricted'])
            ->sum('amount');

        $spent = $fund->expenses()
        ->whereIn('status', [
            'approved',
            'paid',
        ])
        ->sum('amount');

        $allocated = 0;
        $recovered = 0;
        $refunded = 0;

        $balance =
        (float) $fund->opening_balance
        + (float) $received
        - (float) $spent
        - $allocated
        + $recovered
        + $refunded;

        return view('finance.funds.show', compact(
            'fund',
            'received',
            'spent',
            'allocated',
            'recovered',
            'refunded',
            'balance'
        ));
    }
}