<?php

namespace App\Http\Controllers\Finance;

use App\Domain\Finance\Support\Money;
use App\Domain\Finance\Expenses\Models\Expense;
use App\Domain\Finance\Funds\Models\Fund;
use App\Domain\Finance\Funds\Services\FundBalanceService;
use App\Domain\Finance\Transactions\Models\Transaction;
use App\Http\Controllers\Controller;
use App\Http\Requests\Finance\StoreExpenseRequest;
use App\Http\Requests\Finance\UpdateExpenseRequest;
use App\Domain\Finance\Transactions\Services\TransactionService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;
class ExpenseController extends Controller
{
    public function index(): View
    {
        $query = Expense::query()
            ->with('fund');

        if (request()->filled('search')) {

            $search = trim(request('search'));

            $query->where(function ($q) use ($search) {

                $q->where('expense_number', 'like', "%{$search}%")
                    ->orWhere('description', 'like', "%{$search}%")
                    ->orWhere('payee_name', 'like', "%{$search}%")
                    ->orWhere('transaction_reference', 'like', "%{$search}%")
                    ->orWhere('receipt_number', 'like', "%{$search}%")
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

        if (request()->filled('category')) {
            $query->where('category', request('category'));
        }

        if (request()->filled('fund_id')) {
            $query->where('fund_id', request('fund_id'));
        }

        if (request()->filled('status')) {
            $query->where('status', request('status'));
        }

        $expenses = $query
            ->with([
                'fund',
                'transaction',
            ])
            ->latest('expense_date')
            ->latest('id')
            ->paginate(15)
            ->withQueryString();
        
    
        
           

        /*
        * Financial summaries come from posted debit transactions.
        * Expenses themselves remain the operational/source records,
        * while the transaction ledger is the financial source of truth.
        */
        $postedDebits = Transaction::query()
            ->where('type', 'expense')
            ->where('direction', 'debit')
            ->where('status', 'posted');

        $thisMonth = Money::sum((clone $postedDebits)
            ->whereBetween('transaction_date', [
                now()->startOfMonth()->toDateString(),
                now()->endOfMonth()->toDateString(),
            ]));

        $thisYear = Money::sum((clone $postedDebits)
            ->whereYear('transaction_date', now()->year));

        /*
        * Category totals need the linked Expense record.
        */
        $welfare = Money::sum((clone $postedDebits)
            ->whereHasMorph(
                'source',
                [Expense::class],
                function ($query) {
                    $query->where('category', 'welfare');
                }
            ));

        $projects = Money::sum((clone $postedDebits)
            ->whereHasMorph(
                'source',
                [Expense::class],
                function ($query) {
                    $query->where('category', 'project');
                }
            ));

        $pending = Expense::query()
            ->where('status', 'pending')
            ->count();

        $funds = Fund::query()
            ->where('status', 'active')
            ->orderBy('name')
            ->get();

        return view(
            'finance.expenses.index',
            compact(
                'expenses',
                'funds',
                'thisMonth',
                'thisYear',
                'welfare',
                'projects',
                'pending'
            )
        );
    }

    private function fundPurposeAllowsExpense(
        Fund $fund,
        string $category
    ): bool {
        return app(\App\Domain\Finance\Funds\Services\FundPurposeService::class)
            ->allowsExpense($fund, $category);
    }

    public function create(
        FundBalanceService $balanceService
    ): View {
        $funds = Fund::query()
            ->where('status', 'active')
            ->orderBy('name')
            ->get();

        $fundBalances = $funds->mapWithKeys(
            function (Fund $fund) use ($balanceService) {
                return [
                    $fund->id => max(
                        0,
                        $balanceService->balance($fund)
                    ),
                ];
            }
        );

        return view(
            'finance.expenses.create',
            compact(
                'funds',
                'fundBalances'
            )
        );
    }

    public function store(
        StoreExpenseRequest $request,
        FundBalanceService $balanceService
    ): RedirectResponse {

        $data = $request->validated();

        $fund = Fund::findOrFail($data['fund_id']);
        if (
            !$this->fundPurposeAllowsExpense(
                $fund,
                $data['category']
            )
        ) {
            return back()
                ->withInput()
                ->withErrors([
                    'category' => sprintf(
                        'This expense category is not permitted for the %s.',
                        $fund->name
                    ),
                ]);
        }

        if ($fund->status !== 'active') {
            return back()
                ->withInput()
                ->withErrors([
                    'fund_id' => 'The selected fund is not active.',
                ]);
        }

        $availableBalance = $balanceService->balance($fund);

        if (
            $fund->prevent_negative_balance &&
            Money::minor($data['amount']) > Money::minor($availableBalance)
        ) {
            return back()
                ->withInput()
                ->withErrors([
                    'amount' => sprintf(
                        'This expense exceeds the available balance of PKR %s.',
                        Money::format($availableBalance)
                    ),
                ]);
        }

        $expense = DB::transaction(function () use ($data) {

            $lastExpense = Expense::withTrashed()
                ->orderByDesc('id')
                ->lockForUpdate()
                ->first();

            $nextNumber = ($lastExpense?->id ?? 0) + 1;

            $data['expense_number'] =
                'EXP-' . str_pad(
                    $nextNumber,
                    5,
                    '0',
                    STR_PAD_LEFT
                );

            $data['status'] = 'pending';
            $data['recorded_by'] = auth()->id();

            return Expense::create($data);
        });

        return redirect()
            ->route('finance.expenses.show', $expense)
            ->with(
                'success',
                'Expense recorded successfully and is awaiting approval.'
            );
    }

    public function show(
        Expense $expense,
        FundBalanceService $balanceService
    ): View {
        $expense->load([
            'fund',
            'recordedBy',
            'approvedBy',
            'transaction',
        ]);

        $fund = $expense->fund;

        $balanceBefore = 0;
        $balanceAfter = 0;
        $ledgerTransactions = collect();

        if ($fund) {
            /*
            * Build the historical fund ledger from actual posted
            * transactions. This keeps the Expense detail page aligned
            * with the same financial source of truth used elsewhere.
            */
            $ledgerTransactions = $fund->transactions()
                ->where('status', 'posted')
                ->orderBy('transaction_date')
                ->orderBy('id')
                ->get();

            $runningBalance = Money::minor($fund->opening_balance);

            foreach ($ledgerTransactions as $transaction) {
                if (
                    $transaction->source_type === Expense::class
                    && (int) $transaction->source_id === (int) $expense->id
                ) {
                    /*
                    * The balance immediately before this expense.
                    */
                    $balanceBefore = Money::decimal($runningBalance);

                    if ($transaction->direction === 'credit') {
                        $runningBalance = Money::add($runningBalance, Money::minor($transaction->amount));
                    } else {
                        $runningBalance = Money::add($runningBalance, -Money::minor($transaction->amount));
                    }

                    $balanceAfter = Money::decimal($runningBalance);

                    break;
                }

                if ($transaction->direction === 'credit') {
                    $runningBalance = Money::add($runningBalance, Money::minor($transaction->amount));
                } else {
                    $runningBalance = Money::add($runningBalance, -Money::minor($transaction->amount));
                }
            }

            /*
            * Pending/rejected expenses normally have no posted transaction.
            * In that case, show the fund's current balance as the available
            * financial context rather than pretending money was deducted.
            */
            if (!$expense->transaction || $expense->transaction->status !== 'posted') {
                $balanceBefore = $balanceService->balance($fund);
                $balanceAfter = $balanceBefore;
            }
        }

        return view(
            'finance.expenses.show',
            compact(
                'expense',
                'fund',
                'balanceBefore',
                'balanceAfter',
                'ledgerTransactions'
            )
        );
    }

    public function edit(Expense $expense): View
    {
        if ($expense->status === 'paid') {
            abort(
                403,
                'Paid expenses cannot be edited.'
            );
        }

        $funds = Fund::query()
            ->where('status', 'active')
            ->orderBy('name')
            ->get();

        return view(
            'finance.expenses.edit',
            compact(
                'expense',
                'funds'
            )
        );
    }

    public function update(
        UpdateExpenseRequest $request,
        Expense $expense,
        FundBalanceService $balanceService
    ): RedirectResponse {

        if ($expense->status === 'paid') {
            abort(
                403,
                'Paid expenses cannot be edited.'
            );
        }

        $data = $request->validated();

        $fund = Fund::findOrFail($data['fund_id']);
        if (
            !$this->fundPurposeAllowsExpense(
                $fund,
                $data['category']
            )
        ) {
            return back()
                ->withInput()
                ->withErrors([
                    'category' => sprintf(
                        'This expense category is not permitted for the %s.',
                        $fund->name
                    ),
                ]);
        }

        if ($fund->status !== 'active') {
            return back()
                ->withInput()
                ->withErrors([
                    'fund_id' => 'The selected fund is not active.',
                ]);
        }

        /*
         * Exclude the current expense from the spent calculation
         * when it was already approved.
         */
        $currentBalance = $balanceService->balance($fund);

        /*
        * When editing an already-approved/paid expense, its existing
        * posted debit is only part of the CURRENT balance calculation
        * above if that debit was posted against THIS SAME fund. If the
        * admin is re-assigning the expense to a different fund via this
        * edit, the new fund's balance() call above already correctly
        * excludes this expense (no transaction has been posted against
        * the new fund yet), so nothing should be added back in that
        * case — doing so would inflate the new fund's available balance
        * by the expense amount and could let it go negative.
        */
        if (
            in_array($expense->status, ['approved', 'paid'], true)
            && (int) $expense->fund_id === (int) $fund->id
        ) {
            $currentBalance = Money::total([$currentBalance, $expense->amount]);
        }

        $availableBalance = $currentBalance;

        if (
            in_array($data['status'], [
                'approved',
                'paid',
            ], true)
            && $fund->prevent_negative_balance
            && Money::minor($data['amount']) > Money::minor($availableBalance)
        ) {
            return back()
                ->withInput()
                ->withErrors([
                    'amount' => sprintf(
                        'This expense exceeds the available balance of PKR %s.',
                        Money::format($availableBalance)
                    ),
                ]);
        }
        if (
            $data['status'] === 'paid'
            && $expense->status !== 'approved'
        ) {
            return back()
                ->withInput()
                ->withErrors([
                    'status' =>
                        'An expense must be approved before it can be marked as paid.',
                ]);
        }
        if (
            in_array($data['status'], ['approved', 'paid'], true)
            && $fund->require_documents
            && !$data['has_supporting_document']
        ) {
            return back()
                ->withInput()
                ->withErrors([
                    'has_supporting_document' =>
                        'This fund requires supporting documentation before the expense can be approved or paid.',
                ]);
        }

        if ($data['status'] === 'approved') {

            $data['approved_by'] =
                $expense->approved_by
                ?? auth()->id();

            $data['approved_at'] =
                $expense->approved_at
                ?? now();
        }

        if ($data['status'] === 'pending') {

            $data['approved_by'] = null;
            $data['approved_at'] = null;
        }

        DB::transaction(function () use (
            $expense,
            $data
        ) {

            $expense->update($data);

            /*
            * Approved/Paid expenses must have exactly one
            * posted debit transaction.
            */
            if (in_array($data['status'], [
                'approved',
                'paid',
            ], true)) {

                app(TransactionService::class)
                    ->syncForSource(
                        $expense,
                        [
                            'fund_id' =>
                                $expense->fund_id,

                            'type' =>
                                'expense',

                            'reference' =>
                                $expense->expense_number,

                            'description' =>
                                $expense->description,

                            'amount' =>
                                $expense->amount,

                            'direction' =>
                                'debit',

                            'transaction_date' =>
                                $expense->expense_date,

                            'status' =>
                                'posted',
                        ]
                    );
            }

            /*
            * Pending/rejected expenses should not have
            * an active financial transaction.
            */
            if (in_array($data['status'], [
                'pending',
                'rejected',
            ], true)) {

                app(TransactionService::class)
                    ->voidForSource($expense);
            }
        });

        return redirect()
            ->route(
                'finance.expenses.show',
                $expense
            )
            ->with(
                'success',
                'Expense updated successfully.'
            );
    }

    public function approve(
        Expense $expense,
        FundBalanceService $balanceService
    ): RedirectResponse {

        if (!in_array($expense->status, [
            'pending',
        ], true)) {
            return back()
                ->withErrors([
                    'status' =>
                        'Only pending expenses can be approved.',
                ]);
        }

        $fund = $expense->fund;

        if (!$fund) {
            return back()
                ->withErrors([
                    'fund_id' => 'This expense is not linked to a fund.',
                ]);
        }

        if ($fund->status !== 'active') {
            return back()
                ->withErrors([
                    'fund_id' =>
                        'The expense fund is unavailable or inactive.',
                ]);
        }

        if (
            $fund->require_documents
            && !$expense->has_supporting_document
        ) {
            return back()
                ->withErrors([
                    'documents' =>
                        'Supporting documentation is required before this expense can be approved.',
                ]);
        }
        if (
            !$this->fundPurposeAllowsExpense(
                $fund,
                $expense->category
            )
        ) {
            return back()
                ->withErrors([
                    'category' => sprintf(
                        'This expense category is not permitted for the %s.',
                        $fund->name
                    ),
                ]);
        }

        $availableBalance = $balanceService->balance($fund);

        if (
            $fund->prevent_negative_balance
            && Money::minor($expense->amount) > Money::minor($availableBalance)
        ) {
            return back()
                ->withErrors([
                    'amount' => sprintf(
                        'Approval would exceed the available balance of PKR %s.',
                        Money::format($availableBalance)
                    ),
                ]);
        }

        DB::transaction(function () use ($expense) {

            $expense->update([
                'status' =>
                    'approved',

                'approved_by' =>
                    auth()->id(),

                'approved_at' =>
                    now(),
            ]);

            app(TransactionService::class)
                ->syncForSource(
                    $expense,
                    [
                        'fund_id' =>
                            $expense->fund_id,

                        'type' =>
                            'expense',

                        'reference' =>
                            $expense->expense_number,

                        'description' =>
                            $expense->description,

                        'amount' =>
                            $expense->amount,

                        'direction' =>
                            'debit',

                        'transaction_date' =>
                            $expense->expense_date,

                        'status' =>
                            'posted',
                    ]
                );
        });

        return back()->with(
            'success',
            'Expense approved successfully.'
        );
    }

    public function reject(
        Expense $expense
    ): RedirectResponse {

        if (!in_array($expense->status, [
            'pending',
            'approved',
        ], true)) {
            return back()
                ->withErrors([
                    'status' =>
                        'This expense cannot be rejected at its current stage.',
                ]);
        }

        DB::transaction(function () use ($expense) {

            $expense->update([
                'status' => 'rejected',
            ]);

            app(TransactionService::class)
                ->voidForSource($expense);
        });

        return back()->with(
            'success',
            'Expense rejected.'
        );
    }

    public function markPaid(
        Expense $expense
    ): RedirectResponse {

        if ($expense->status !== 'approved') {
            return back()
                ->withErrors([
                    'status' =>
                        'Only approved expenses can be marked as paid.',
                ]);
        }

        DB::transaction(function () use ($expense) {

            $expense->update([
                'status' => 'paid',
            ]);

            app(TransactionService::class)
                ->syncForSource(
                    $expense,
                    [
                        'fund_id' =>
                            $expense->fund_id,

                        'type' =>
                            'expense',

                        'reference' =>
                            $expense->expense_number,

                        'description' =>
                            $expense->description,

                        'amount' =>
                            $expense->amount,

                        'direction' =>
                            'debit',

                        'transaction_date' =>
                            $expense->expense_date,

                        'status' =>
                            'posted',
                    ]
                );
        });

        return back()->with(
            'success',
            'Expense marked as paid.'
        );
    }

    public function destroy(
        Expense $expense
    ): RedirectResponse {

        if ($expense->status === 'paid') {
            abort(
                403,
                'Paid expenses cannot be archived.'
            );
        }

        DB::transaction(function () use ($expense) {

            /*
            * An approved (but not yet paid) expense may still have
            * an active posted transaction against its fund. Void it
            * before removing the expense so the fund's ledger and
            * balance calculations don't retain a reference to a
            * deleted source record.
            */
            app(TransactionService::class)
                ->voidForSource($expense);

            $expense->delete();
        });

        return redirect()
            ->route('finance.expenses.index')
            ->with(
                'success',
                "{$expense->expense_number} was archived successfully."
            );
    }
}