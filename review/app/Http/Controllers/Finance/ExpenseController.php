<?php

namespace App\Http\Controllers\Finance;

use App\Domain\Finance\Expenses\Models\Expense;
use App\Domain\Finance\Funds\Models\Fund;
use App\Http\Controllers\Controller;
use App\Http\Requests\Finance\StoreExpenseRequest;
use App\Http\Requests\Finance\UpdateExpenseRequest;
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
            ->latest('expense_date')
            ->latest('id')
            ->paginate(15)
            ->withQueryString();

        $thisMonth = Expense::query()
            ->whereBetween('expense_date', [
                now()->startOfMonth()->toDateString(),
                now()->endOfMonth()->toDateString(),
            ])
            ->whereIn('status', [
                'approved',
                'paid',
            ])
            ->sum('amount');

        $thisYear = Expense::query()
            ->whereYear('expense_date', now()->year)
            ->whereIn('status', [
                'approved',
                'paid',
            ])
            ->sum('amount');

        $welfare = Expense::query()
            ->where('category', 'welfare')
            ->whereIn('status', [
                'approved',
                'paid',
            ])
            ->sum('amount');

        $projects = Expense::query()
            ->where('category', 'project')
            ->whereIn('status', [
                'approved',
                'paid',
            ])
            ->sum('amount');

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

    public function create(): View
    {
        $funds = Fund::query()
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
            ->orderBy('name')
            ->get();

        $fundBalances = $funds->mapWithKeys(function ($fund) {

            $balance =
                (float) $fund->opening_balance
                + (float) ($fund->received_donations ?? 0)
                - (float) ($fund->spent_expenses ?? 0);

            return [
                $fund->id => max(0, $balance),
            ];
        });

        return view(
            'finance.expenses.create',
            compact('funds', 'fundBalances')
        );
    }

    public function store(
        StoreExpenseRequest $request
    ): RedirectResponse {

        $data = $request->validated();

        $fund = Fund::findOrFail($data['fund_id']);

        if ($fund->status !== 'active') {
            return back()
                ->withInput()
                ->withErrors([
                    'fund_id' => 'The selected fund is not active.',
                ]);
        }

        $received = $fund->donations()
            ->whereIn('status', [
                'received',
                'restricted',
            ])
            ->sum('amount');

        $expenses = $fund->expenses()
            ->whereIn('status', [
                'approved',
                'paid',
            ])
            ->sum('amount');

        $availableBalance =
            (float) $fund->opening_balance
            + (float) $received
            - (float) $expenses;

        if (
            $fund->prevent_negative_balance &&
            (float) $data['amount'] > $availableBalance
        ) {
            return back()
                ->withInput()
                ->withErrors([
                    'amount' => sprintf(
                        'This expense exceeds the available balance of PKR %s.',
                        number_format($availableBalance, 0)
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

    public function show(Expense $expense): View
    {
        $expense->load([
            'fund',
            'recordedBy',
            'approvedBy',
        ]);

        $fund = $expense->fund;

        $balanceBefore = 0;
        $balanceAfter = 0;

        if ($fund) {

            $receivedBefore = $fund->donations()
                ->whereIn('status', [
                    'received',
                    'restricted',
                ])
                ->where(function ($query) use ($expense) {
                    $query
                        ->where(
                            'donation_date',
                            '<',
                            $expense->expense_date
                        )
                        ->orWhere(function ($query) use ($expense) {
                            $query
                                ->where(
                                    'donation_date',
                                    '=',
                                    $expense->expense_date
                                )
                                ->where(
                                    'id',
                                    '<=',
                                    $expense->id
                                );
                        });
                })
                ->sum('amount');

            $spentBefore = $fund->expenses()
                ->whereIn('status', [
                    'approved',
                    'paid',
                ])
                ->where(function ($query) use ($expense) {
                    $query
                        ->where(
                            'expense_date',
                            '<',
                            $expense->expense_date
                        )
                        ->orWhere(function ($query) use ($expense) {
                            $query
                                ->where(
                                    'expense_date',
                                    '=',
                                    $expense->expense_date
                                )
                                ->where(
                                    'id',
                                    '<',
                                    $expense->id
                                );
                        });
                })
                ->sum('amount');

            $balanceBefore =
                (float) $fund->opening_balance
                + (float) $receivedBefore
                - (float) $spentBefore;

            $balanceAfter = $balanceBefore;

            if (in_array($expense->status, [
                'approved',
                'paid',
            ], true)) {
                $balanceAfter -= (float) $expense->amount;
            }
        }

        return view(
            'finance.expenses.show',
            compact(
                'expense',
                'fund',
                'balanceBefore',
                'balanceAfter'
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
        Expense $expense
    ): RedirectResponse {

        if ($expense->status === 'paid') {
            abort(
                403,
                'Paid expenses cannot be edited.'
            );
        }

        $data = $request->validated();

        $fund = Fund::findOrFail($data['fund_id']);

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
        $spentQuery = $fund->expenses()
            ->whereIn('status', [
                'approved',
                'paid',
            ])
            ->where('id', '!=', $expense->id);

        $spent = $spentQuery->sum('amount');

        $received = $fund->donations()
            ->whereIn('status', [
                'received',
                'restricted',
            ])
            ->sum('amount');

        $availableBalance =
            (float) $fund->opening_balance
            + (float) $received
            - (float) $spent;

        if (
            in_array($data['status'], [
                'approved',
                'paid',
            ], true)
            && $fund->prevent_negative_balance
            && (float) $data['amount'] > $availableBalance
        ) {
            return back()
                ->withInput()
                ->withErrors([
                    'amount' => sprintf(
                        'This expense exceeds the available balance of PKR %s.',
                        number_format($availableBalance, 0)
                    ),
                ]);
        }

        if ($data['status'] === 'approved') {

            $data['approved_by'] =
                auth()->id();

            $data['approved_at'] =
                now();
        }

        if ($data['status'] === 'pending') {

            $data['approved_by'] = null;
            $data['approved_at'] = null;
        }

        $expense->update($data);

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
        Expense $expense
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

        if (!$fund || $fund->status !== 'active') {
            return back()
                ->withErrors([
                    'fund_id' =>
                        'The expense fund is unavailable or inactive.',
                ]);
        }

        $received = $fund->donations()
            ->whereIn('status', [
                'received',
                'restricted',
            ])
            ->sum('amount');

        $spent = $fund->expenses()
            ->whereIn('status', [
                'approved',
                'paid',
            ])
            ->sum('amount');

        $availableBalance =
            (float) $fund->opening_balance
            + (float) $received
            - (float) $spent;

        if (
            $fund->prevent_negative_balance
            && (float) $expense->amount > $availableBalance
        ) {
            return back()
                ->withErrors([
                    'amount' => sprintf(
                        'Approval would exceed the available balance of PKR %s.',
                        number_format($availableBalance, 0)
                    ),
                ]);
        }

        $expense->update([
            'status' => 'approved',
            'approved_by' => auth()->id(),
            'approved_at' => now(),
        ]);

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

        $expense->update([
            'status' => 'rejected',
        ]);

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

        $expense->update([
            'status' => 'paid',
        ]);

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

        $expense->delete();

        return redirect()
            ->route('finance.expenses.index')
            ->with(
                'success',
                "{$expense->expense_number} was archived successfully."
            );
    }
}