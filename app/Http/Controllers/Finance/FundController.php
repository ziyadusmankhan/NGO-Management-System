<?php

namespace App\Http\Controllers\Finance;

use App\Http\Controllers\Controller;
use App\Domain\Finance\Funds\Models\Fund;
use App\Domain\Finance\Funds\Services\FundBalanceService;
use App\Http\Requests\Finance\StoreFundRequest;
use App\Http\Requests\Finance\UpdateFundRequest;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

class FundController extends Controller
{
    public function index(FundBalanceService $balanceService): View
    {
        $query = Fund::query();

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
        * Calculate financial values from the central transaction ledger.
        */
        $activeFunds = Fund::query()
            ->where('status', 'active')
            ->get();

        $totalFunds = $activeFunds->count();

        $totalBalance = $activeFunds->sum(
            fn (Fund $fund) => $balanceService->balance($fund)
        );

        $restrictedBalance = $activeFunds
            ->whereIn('type', [
                'restricted',
                'designated',
            ])
            ->sum(
                fn (Fund $fund) => $balanceService->balance($fund)
            );

        $allocated = 0;

        $available = $totalBalance - $allocated;

        /*
        * Attach financial values to the paginated funds so the Blade
        * can display the current ledger figures.
        */
        $funds->getCollection()->transform(
            function (Fund $fund) use ($balanceService) {
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
            }
        );

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

    public function create(): View
    {
        return view('finance.funds.create');
    }

    public function store(StoreFundRequest $request): RedirectResponse
    {
        $validated = $request->validated();

        $fund = DB::transaction(function () use ($validated) {

            $nextId =
                (
                    Fund::withTrashed()
                        ->max('id')
                    ?? 0
                ) + 1;

            $fundReference =
                'FUND-' .
                str_pad(
                    $nextId,
                    5,
                    '0',
                    STR_PAD_LEFT
                );

            return Fund::create(
                array_merge($validated, [
                    'fund_reference' => $fundReference,
                ])
            );
        });

        return redirect()
            ->route('finance.funds.show', $fund)
            ->with(
                'success',
                "Fund {$fund->fund_reference} was created successfully."
            );
    }

    public function edit(Fund $fund): View
    {
        return view(
            'finance.funds.edit',
            compact('fund')
        );
    }

    public function update(
        UpdateFundRequest $request,
        Fund $fund
    ): RedirectResponse {

        $validated = $request->validated();

        $fund->update($validated);

        return redirect()
            ->route('finance.funds.show', $fund)
            ->with(
                'success',
                'Fund updated successfully.'
            );
    }

    public function destroy(Fund $fund): RedirectResponse
    {
        $fund->delete();

        return redirect()
            ->route('finance.funds.index')
            ->with(
                'success',
                "{$fund->fund_reference} was archived successfully."
            );
    }

    public function show(
        Fund $fund,
        FundBalanceService $balanceService
    ): View {
        $summary = $balanceService->summary($fund);

        $received = $summary['total_credits'];
        $spent = $summary['total_debits'];

        $allocated = 0;
        $recovered = 0;
        $refunded = 0;

        $balance =
            $summary['balance']
            - $allocated
            + $recovered
            + $refunded;

        /*
        * Load the actual financial ledger for this fund.
        *
        * Opening balance is represented separately because it is
        * stored on the fund itself rather than as a transaction.
        */
        $transactions = $fund->transactions()
            ->orderBy('transaction_date')
            ->orderBy('id')
            ->get();

        foreach ($transactions as $transaction) {
            $transaction->running_balance =
                $balanceService->balanceAfterTransaction($transaction);
        }

        $recentTransactions = $fund->transactions()
            ->orderByDesc('transaction_date')
            ->orderByDesc('id')
            ->limit(8)
            ->get();

        return view(
            'finance.funds.show',
            compact(
                'fund',
                'received',
                'spent',
                'allocated',
                'recovered',
                'refunded',
                'balance',
                'transactions',
                'recentTransactions'
            )
        );
    }
}