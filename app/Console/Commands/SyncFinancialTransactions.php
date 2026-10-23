<?php

namespace App\Console\Commands;

use App\Domain\Finance\Donations\Models\Donation;
use App\Domain\Finance\Expenses\Models\Expense;
use App\Domain\Finance\Funds\Models\Fund;
use App\Domain\Finance\Transactions\Services\TransactionService;
use Illuminate\Console\Command;
use App\Domain\Finance\Support\Money;
use App\Domain\Finance\Funds\Services\FundBalanceService;
use App\Domain\Welfare\Assistance\Models\WelfareAssistancePayment;
use App\Domain\Qard\Models\QardMovement;
use Illuminate\Validation\ValidationException;

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
                        $service->voidForSource($donation);

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
                        $service->voidForSource($expense);

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

        \App\Domain\Business\Models\BusinessMovement::with('transaction')->each(function ($movement) use ($service, &$mismatches) {
            try { $service->assertMatchesBusiness($movement->transaction, $movement); }
            catch (ValidationException $exception) { $mismatches++; $this->warn('Business movement #'.$movement->id.': missing or inconsistent ledger entry.'); }
        });

        QardMovement::with('transaction')->each(function (QardMovement $movement) use ($service, &$mismatches) {
            try {
                $service->assertMatchesQard($movement->transaction, $movement);
            } catch (ValidationException $exception) {
                $mismatches++;
                $this->warn('Qard movement #'.$movement->id.': missing or inconsistent payment ledger entry.');
            }
        });

        // Audit welfare entries without silently recreating or reversing payments.
        WelfareAssistancePayment::query()->with(['transaction' => fn ($query) => $query->withTrashed()])
            ->each(function (WelfareAssistancePayment $payment) use ($service, &$mismatches) {
                try {
                    $service->assertMatchesPayment($payment->transaction, $payment);
                } catch (ValidationException $exception) {
                    $mismatches++;
                    $this->warn($payment->payment_number.': missing or inconsistent payment ledger entry.');
                }
            });


        Fund::query()
            ->withTrashed()
            ->orderBy('id')
            ->get()
            ->each(function (Fund $fund) use (&$mismatches) {

                $sourceCredits = Money::sumMinor($fund->donations()->getQuery()
                    ->whereIn('status', ['received', 'restricted']));
                $sourceDebits = Money::sumMinor($fund->expenses()->getQuery()
                    ->whereIn('status', ['approved', 'paid']));
                $welfareDebits = Money::sumMinor(WelfareAssistancePayment::query()
                    ->where('fund_id', $fund->id)->where('status', 'posted'));
                $sourceBalance = Money::add(Money::minor($fund->opening_balance),
                    Money::add($sourceCredits, -Money::add($sourceDebits, $welfareDebits)));
                $qardCredits = Money::sumMinor(QardMovement::where('fund_id', $fund->id)->where('status', 'posted')->where('kind', 'repayment'));
                $qardDebits = Money::sumMinor(QardMovement::where('fund_id', $fund->id)->where('status', 'posted')->where('kind', 'disbursement'));
                $sourceBalance = Money::add($sourceBalance, Money::add($qardCredits, -$qardDebits));
                $businessCredits = Money::sumMinor(\App\Domain\Business\Models\BusinessMovement::where('fund_id', $fund->id)->where('status', 'posted')->where('kind', 'repayment'));
                $businessDebits = Money::sumMinor(\App\Domain\Business\Models\BusinessMovement::where('fund_id', $fund->id)->where('status', 'posted')->where('kind', 'disbursement'));
                $sourceBalance = Money::add($sourceBalance, Money::add($businessCredits, -$businessDebits));
                $ledgerBalance = app(FundBalanceService::class)->balanceMinor($fund);
                $difference = Money::add($sourceBalance, -$ledgerBalance);

                if ($difference !== 0) {

                    $mismatches++;

                    $this->warn(
                        sprintf(
                            '%s (%s): mismatch PKR %s | source=%s | ledger=%s',
                            $fund->name,
                            $fund->fund_reference,
                            Money::format(Money::decimal($difference)),
                            Money::format(Money::decimal($sourceBalance)),
                            Money::format(Money::decimal($ledgerBalance))
                        )
                    );

                    return;
                }

                $this->line(
                    sprintf(
                        '%s (%s): reconciled at PKR %s',
                        $fund->name,
                        $fund->fund_reference,
                        Money::format(Money::decimal($sourceBalance))
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
