<?php

namespace App\Http\Controllers\Dashboard;

use App\Domain\Audit\Models\AuditEvent;
use App\Domain\Audit\Services\AuditQuery;
use App\Domain\Business\Services\BusinessReportService;
use App\Domain\Committees\Models\Committee;
use App\Domain\Finance\Donations\Models\Donation;
use App\Domain\Finance\Expenses\Models\Expense;
use App\Domain\Finance\Funds\Models\Fund;
use App\Domain\Finance\Funds\Services\FundBalanceService;
use App\Domain\Finance\Support\Money;
use App\Domain\Finance\Transactions\Models\Transaction;
use App\Domain\Members\Models\Member;
use App\Domain\Operations\Models\Task;
use App\Domain\Projects\Models\Project;
use App\Domain\Qard\Models\QardLoan;
use App\Domain\Qard\Models\QardMovement;
use App\Domain\Qard\Models\QardReminder;
use App\Domain\Welfare\Assistance\Models\WelfareAssistance;
use App\Domain\Welfare\Assistance\Models\WelfareAssistancePayment;
use App\Domain\Welfare\Assistance\Models\WelfareAssistanceReminder;
use App\Domain\Welfare\Beneficiaries\Models\Beneficiary;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Schema;
use Illuminate\View\View;

class DashboardController extends Controller
{
    public function index(
        FundBalanceService $balanceService,
        AuditQuery $auditQuery
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

        $projectStatusCounts = [];
        if (auth()->user()->hasPermission('projects.view')) {
            $projectStatusCounts = Project::withTrashed()->selectRaw('status, COUNT(*) as aggregate')->groupBy('status')->pluck('aggregate', 'status')->all();
        }
        $activeProjects = $projectStatusCounts['in_progress'] ?? 0;

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

        $businessSummary = auth()->user()->hasPermission('business.view')
            ? app(BusinessReportService::class)->report([])['totals'] : null;

        $upcomingTasks = collect();
        if (auth()->user()->hasPermission('tasks.view') && Schema::hasTable('operation_tasks')) {
            $upcomingTasks = Task::with('assignee')
                ->whereNotIn('status', ['completed', 'cancelled'])
                ->whereDate('due_on', '<=', today('Asia/Karachi')->addDays(14))
                ->orderBy('due_on')->orderByRaw("CASE priority WHEN 'urgent' THEN 1 WHEN 'high' THEN 2 ELSE 3 END")
                ->limit(7)->get();
        }

        $welfareSummary = null;
        if (auth()->user()->hasPermission('welfare.assistance.view')) {
            $welfareSummary = [
                'open' => WelfareAssistance::whereNotIn('status', ['closed', 'cancelled', 'rejected'])->count(),
                'review_due' => WelfareAssistanceReminder::whereNull('resolved_at')->count(),
                'paid' => Money::sum(WelfareAssistancePayment::where('status', 'posted')),
                'students' => Beneficiary::where('category', 'student')->count(),
            ];
        }

        $qardSummary = null;
        if (auth()->user()->hasPermission('qard.view')) {
            $disbursed = Money::sum(QardMovement::where('status', 'posted')->where('kind', 'disbursement'));
            $repaid = Money::sum(QardMovement::where('status', 'posted')->where('kind', 'repayment'));
            $qardSummary = [
                'active' => QardLoan::where('status', 'active')->count(),
                'outstanding' => Money::decimal(max(0, Money::minor($disbursed) - Money::minor($repaid))),
                'followups' => QardReminder::whereNull('resolved_at')->count(),
            ];
        }

        $recentActivity = $auditQuery->ordered(AuditEvent::where('historical', false)->where('outcome', 'success'))
            ->limit(80)->get()->filter(fn ($event) => $auditQuery->visibleTo($event, auth()->user()))->take(8)
            ->map(fn ($event) => [
                'event' => $event,
                'url' => $auditQuery->sourceLink($event, auth()->user()),
            ])->values();

        return view(
            'dashboard.index',
            compact(
                'businessSummary',
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
                'pendingDonations',
                'upcomingTasks', 'welfareSummary', 'qardSummary', 'recentActivity'
            )
        );
    }
}
