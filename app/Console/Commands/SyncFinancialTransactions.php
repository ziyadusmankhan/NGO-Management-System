<?php

namespace App\Console\Commands;

use App\Domain\Finance\Donations\Models\Donation;
use App\Domain\Finance\Expenses\Models\Expense;
use App\Domain\Finance\Funds\Models\Fund;
use App\Domain\Finance\Transactions\Models\Transaction;
use App\Domain\Finance\Transactions\Services\TransactionService;
use Illuminate\Console\Command;

class SyncFinancialTransactions extends Command
{
    protected $signature = 'finance:sync-transactions';

    protected $description =
        'Synchronize financial source records and reconcile fund balances';

    public function handle(): int
    {
        $service = app(TransactionService::class);

        $donationsCreatedOrUpdated = 0;
        $donationsVoided = 0;

        $expensesCreatedOrUpdated = 0;
        $expensesVoided = 0;

        $this->info('Synchronizing donations...');

        Donation::withTrashed()
            ->orderBy('id')
            ->each(function (Donation $donation) use (
                $service,
                &$donationsCreatedOrUpdated,
                &$donationsVoided
            ) {
                $transaction = $donation->transaction()->withTrashed()->first();

                $eligible =
                    !$donation->trashed()
                    && $donation->fund_id
                    && in_array(
                        $donation->status,
                        ['received', 'restricted'],
                        true
                    );

                if (!$eligible) {

                    if ($transaction && $transaction->status !== 'void') {
                        $transaction->update([
                            'status' => 'void',
                        ]);

                        $donationsVoided++;
                    }

                    return;
                }

                $service->syncForSource(
                    $donation,
                    [
                        'fund_id' =>
                            $donation->fund_id,

                        'type' =>
                            'donation',

                        'reference' =>
                            $donation->donation_number,

                        'description' =>
                            'Donation from ' .
                            $donation->donor_name,

                        'amount' =>
                            $donation->amount,

                        'direction' =>
                            'credit',

                        'transaction_date' =>
                            $donation->donation_date,

                        'status' =>
                            'posted',

                        'recorded_by' =>
                            $donation->recorded_by,
                    ]
                );

                $donationsCreatedOrUpdated++;
            });

        $this->info(
            "Donations synchronized: {$donationsCreatedOrUpdated}"
        );

        $this->info(
            "Donation transactions voided: {$donationsVoided}"
        );


        $this->info('Synchronizing expenses...');

        Expense::withTrashed()
            ->orderBy('id')
            ->each(function (Expense $expense) use (
                $service,
                &$expensesCreatedOrUpdated,
                &$expensesVoided
            ) {
                $transaction = $expense->transaction()->withTrashed()->first();

                $eligible =
                    !$expense->trashed()
                    && $expense->fund_id
                    && in_array(
                        $expense->status,
                        ['approved', 'paid'],
                        true
                    );

                if (!$eligible) {

                    if ($transaction && $transaction->status !== 'void') {
                        $transaction->update([
                            'status' => 'void',
                        ]);

                        $expensesVoided++;
                    }

                    return;
                }

                $service->syncForSource(
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

                        'recorded_by' =>
                            $expense->recorded_by,
                    ]
                );

                $expensesCreatedOrUpdated++;
            });

        $this->info(
            "Expenses synchronized: {$expensesCreatedOrUpdated}"
        );

        $this->info(
            "Expense transactions voided: {$expensesVoided}"
        );


        $this->newLine();
        $this->info('Reconciling funds...');

        $mismatches = 0;

        Fund::query()
            ->withTrashed()
            ->orderBy('id')
            ->get()
            ->each(function (Fund $fund) use (&$mismatches) {

                $sourceCredits = $fund->donations()
                    ->whereIn('status', [
                        'received',
                        'restricted',
                    ])
                    ->sum('amount');

                $sourceDebits = $fund->expenses()
                    ->whereIn('status', [
                        'approved',
                        'paid',
                    ])
                    ->sum('amount');

                $sourceBalance =
                    (float) $fund->opening_balance
                    + (float) $sourceCredits
                    - (float) $sourceDebits;

                $ledgerCredits = Transaction::query()
                    ->where('fund_id', $fund->id)
                    ->where('status', 'posted')
                    ->where('direction', 'credit')
                    ->sum('amount');

                $ledgerDebits = Transaction::query()
                    ->where('fund_id', $fund->id)
                    ->where('status', 'posted')
                    ->where('direction', 'debit')
                    ->sum('amount');

                $ledgerBalance =
                    (float) $fund->opening_balance
                    + (float) $ledgerCredits
                    - (float) $ledgerDebits;

                $difference =
                    round(
                        $sourceBalance - $ledgerBalance,
                        2
                    );

                if (abs($difference) > 0.01) {

                    $mismatches++;

                    $this->warn(
                        sprintf(
                            '%s (%s): mismatch PKR %s | source=%s | ledger=%s',
                            $fund->name,
                            $fund->fund_reference,
                            number_format($difference, 2),
                            number_format($sourceBalance, 2),
                            number_format($ledgerBalance, 2)
                        )
                    );

                    return;
                }

                $this->line(
                    sprintf(
                        '%s (%s): reconciled at PKR %s',
                        $fund->name,
                        $fund->fund_reference,
                        number_format($sourceBalance, 2)
                    )
                );
            });

        $this->newLine();

        if ($mismatches > 0) {

            $this->error(
                "Reconciliation completed with {$mismatches} mismatch(es)."
            );

            return self::FAILURE;
        }

        $this->info(
            'Reconciliation completed successfully. All fund balances match the transaction ledger.'
        );

        return self::SUCCESS;
    }
}