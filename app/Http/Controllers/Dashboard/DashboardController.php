<?php

namespace App\Http\Controllers\Dashboard;

use App\Domain\Finance\Support\Money;
use App\Domain\Finance\Donations\Models\Donation;
use App\Domain\Finance\Expenses\Models\Expense;
use App\Domain\Finance\Funds\Models\Fund;
use App\Domain\Finance\Funds\Services\FundBalanceService;
use App\Domain\Finance\Transactions\Models\Transaction;
use App\Domain\Users\Models\User;
use App\Domain\Members\Models\Member;
use App\Domain\Committees\Models\Committee;
use App\Http\Controllers\Controller;
use Illuminate\View\View;

class DashboardController extends Controller
{
    public function index(
        FundBalanceService $balanceService
    ): View {
        /*
         * ==========================================
         * ORGANIZATION
         * ==========================================
         */

        $totalMembers = Member::query()->count();

        $activeMembers = Member::query()
            ->where('status', 'active')
            ->count();

        $totalCommittees = Committee::query()->count();


        /*
         * ==========================================
         * FINANCE — SOURCE OF TRUTH
         * ==========================================
         *
         * Financial movement comes from posted
         * transactions, while fund balances come
         * from the centralized FundBalanceService.
         */

        $postedCredits = Transaction::query()
            ->where('direction', 'credit')
            ->where('status', 'posted');

        $postedDebits = Transaction::query()
            ->where('direction', 'debit')
            ->where('status', 'posted');


        /*
         * Donations this month
         */
        $donationsThisMonth = Money::sum((clone $postedCredits)
            ->where('type', 'donation')
            ->whereBetween('transaction_date', [
                now()->startOfMonth()->toDateString(),
                now()->endOfMonth()->toDateString(),
            ]));


        /*
         * Donation count this month
         */
        $donationsCountThisMonth = (clone $postedCredits)
            ->where('type', 'donation')
            ->whereBetween('transaction_date', [
                now()->startOfMonth()->toDateString(),
                now()->endOfMonth()->toDateString(),
            ])
            ->count();


        /*
         * Overall fund balances
         */
        $activeFunds = Fund::query()
            ->where('status', 'active')
            ->orderBy('name')
            ->get();

        $fundBalances = $activeFunds->mapWithKeys(
            function (Fund $fund) use ($balanceService) {
                return [
                    $fund->id => $balanceService->balance($fund),
                ];
            }
        );


        $totalFundBalance = Money::total($fundBalances);


        /*
         * Restricted / unrestricted fund balances
         */
        $restrictedFundBalance = 0;
        $unrestrictedFundBalance = 0;

        foreach ($activeFunds as $fund) {
            $balance = Money::minor($fundBalances[$fund->id] ?? 0);

            if (
                in_array(
                    $fund->type,
                    ['restricted', 'designated'],
                    true
                )
            ) {
                $restrictedFundBalance = Money::add($restrictedFundBalance, $balance);
            } else {
                $unrestrictedFundBalance = Money::add($unrestrictedFundBalance, $balance);
            }
        }


        /*
         * Total posted expenses
         */
        $restrictedFundBalance = Money::decimal($restrictedFundBalance);
        $unrestrictedFundBalance = Money::decimal($unrestrictedFundBalance);

        $totalExpenses = Money::sum((clone $postedDebits)
            ->where('type', 'expense'));


        /*
         * ==========================================
         * FUND OVERVIEW
         * ==========================================
         */

        $fundOverview = $activeFunds->map(
            function (Fund $fund) use ($fundBalances) {
                return [
                    'id' => $fund->id,
                    'name' => $fund->name,
                    'type' => $fund->type,
                    'balance' => $fundBalances[$fund->id] ?? '0.00',
                ];
            }
        );


        /*
         * ==========================================
         * PROJECTS
         * ==========================================
         *
         * Projects are not implemented yet, so
         * we intentionally leave these at zero
         * instead of displaying fake data.
         */

        $activeProjects = 0;

        $projectStatusCounts = [
            'draft' => 0,
            'review' => 0,
            'approved' => 0,
            'in_progress' => 0,
            'completed' => 0,
        ];


        /*
         * ==========================================
         * RECENT TRANSACTIONS
         * ==========================================
         */

        $recentTransactions = Transaction::query()
            ->with([
                'fund',
                'source',
            ])
            ->where('status', 'posted')
            ->latest('transaction_date')
            ->latest('id')
            ->limit(5)
            ->get();


        /*
         * ==========================================
         * PENDING EXPENSES
         * ==========================================
         */

        $pendingExpenses = Expense::query()
            ->where('status', 'pending')
            ->count();


        /*
         * ==========================================
         * PENDING DONATIONS
         * ==========================================
         */

        $pendingDonations = Donation::query()
            ->where('status', 'pending')
            ->count();


        /*
         * ==========================================
         * VIEW
         * ==========================================
         */

        return view(
            'dashboard.index',
            compact(
                'totalMembers',
                'activeMembers',
                'totalCommittees',
                'donationsThisMonth',
                'donationsCountThisMonth',
                'totalFundBalance',
                'restrictedFundBalance',
                'unrestrictedFundBalance',
                'totalExpenses',
                'activeFunds',
                'fundOverview',
                'activeProjects',
                'projectStatusCounts',
                'recentTransactions',
                'pendingExpenses',
                'pendingDonations'
            )
        );
    }
}