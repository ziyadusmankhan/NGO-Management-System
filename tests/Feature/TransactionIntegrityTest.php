<?php

namespace Tests\Feature;

use App\Domain\Finance\Donations\Models\Donation;
use App\Domain\Finance\Expenses\Models\Expense;
use App\Domain\Finance\Funds\Models\Fund;
use App\Domain\Finance\Funds\Services\FundBalanceService;
use App\Domain\Finance\Transactions\Models\Transaction;
use App\Domain\Finance\Transactions\Services\TransactionService;
use App\Domain\Welfare\Assistance\Models\WelfareAssistancePayment;
use App\Domain\Welfare\Assistance\Services\WelfareAssistancePaymentService;
use Illuminate\Database\UniqueConstraintViolationException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\ValidationException;
use Tests\Support\CreatesWelfareFixtures;
use Tests\TestCase;

class TransactionIntegrityTest extends TestCase
{
    use CreatesWelfareFixtures, RefreshDatabase;

    private function fund(): Fund
    {
        return Fund::create(['fund_reference' => 'FND-TEST', 'name' => 'Test fund', 'purpose' => 'education', 'opening_balance' => '100.00']);
    }

    private function expense(Fund $fund, string $number = 'EXP-TEST'): Expense
    {
        return Expense::create([
            'expense_number' => $number, 'fund_id' => $fund->id, 'category' => 'education',
            'description' => 'School tuition service', 'purchase_kind' => 'service', 'amount' => '60.00', 'expense_date' => '2026-10-18',
            'payee_name' => 'School', 'status' => 'approved',
        ]);
    }

    private function movement(Fund $fund, array $overrides = []): array
    {
        return array_merge([
            'fund_id' => $fund->id, 'type' => 'expense', 'description' => 'School supplies',
            'amount' => '60.00', 'direction' => 'debit', 'transaction_date' => '2026-10-18',
        ], $overrides);
    }

    public function test_shared_ledger_serializes_spending_checks_and_allows_exactly_one_entry_per_source(): void
    {
        $fund = $this->fund();
        $expense = $this->expense($fund);
        $service = app(TransactionService::class);
        $ledger = $service->createForSource($expense, $this->movement($fund));
        $this->assertSame($ledger->id, $service->createForSource($expense, $this->movement($fund))->id);
        try {
            $service->createForSource($this->expense($fund, 'EXP-SECOND'), $this->movement($fund));
            $this->fail('The shared service must prevent an overdraft.');
        } catch (ValidationException $exception) {
            $this->assertArrayHasKey('amount', $exception->errors());
        }
        $this->assertDatabaseCount('transactions', 1);
        $balances = app(FundBalanceService::class);
        $this->assertSame('40.00', $balances->balance($fund));
        $this->assertSame('100.00', $balances->balanceBeforeTransaction($ledger));
        $this->assertSame('40.00', $balances->balanceAfterTransaction($ledger));
        $service->voidForSource($expense);
        $service->voidForSource($expense);
        $this->assertSame('100.00', $balances->balance($fund));
        $this->assertSame('100.00', $balances->balanceAfterTransaction($ledger->fresh()));
        $this->assertDatabaseCount('transactions', 1);
    }

    public function test_sync_uses_only_the_net_change_and_rolls_back_unaffordable_update(): void
    {
        $fund = $this->fund();
        $expense = $this->expense($fund);
        $service = app(TransactionService::class);
        $service->createForSource($expense, $this->movement($fund));
        $service->syncForSource($expense, $this->movement($fund, ['amount' => '80.00']));
        $this->assertSame('20.00', app(FundBalanceService::class)->balance($fund));
        try {
            $service->syncForSource($expense, $this->movement($fund, ['amount' => '100.01']));
            $this->fail('An unaffordable edit must fail.');
        } catch (ValidationException $exception) {
            $this->assertArrayHasKey('amount', $exception->errors());
        }
        $this->assertSame('80.00', Transaction::sole()->amount);
        $this->assertSame('20.00', app(FundBalanceService::class)->balance($fund));
        $this->assertDatabaseCount('transactions', 1);
    }

    public function test_spent_donation_cannot_be_removed_or_moved_to_another_fund(): void
    {
        $fund = $this->fund();
        $fund->update(['opening_balance' => '0.00']);
        $donation = Donation::create([
            'donation_number' => 'DON-TEST', 'fund_id' => $fund->id, 'donor_name' => 'Donor',
            'amount' => '100.00', 'donation_date' => '2026-10-18', 'purpose' => 'education',
        ]);
        $service = app(TransactionService::class);
        $data = $this->movement($fund, ['type' => 'donation', 'amount' => '100.00', 'direction' => 'credit']);
        $service->createForSource($donation, $data);
        $service->createForSource($this->expense($fund), $this->movement($fund));
        $other = Fund::create(['fund_reference' => 'FND-OTHER', 'name' => 'Other', 'purpose' => 'education']);
        foreach (['void', 'move'] as $action) {
            try {
                if ($action === 'void') {
                    $service->voidForSource($donation);
                } else {
                    $service->syncForSource($donation, array_merge($data, ['fund_id' => $other->id]));
                }
                $this->fail('Spent credits cannot be removed from their fund.');
            } catch (ValidationException $exception) {
                $this->assertArrayHasKey('amount', $exception->errors());
            }
        }
        $this->assertSame('40.00', app(FundBalanceService::class)->balance($fund));
        $this->assertSame('0.00', app(FundBalanceService::class)->balance($other));
        $this->assertSame('posted', $donation->transaction->status);
    }

    public function test_payment_ledger_cannot_be_changed_or_voided_without_its_payment(): void
    {
        Storage::fake('private');
        $actor = $this->staff();
        $application = $this->signedApplication($actor);
        $assistance = $this->legacyAssistance($application, 'approved');
        $payment = app(WelfareAssistancePaymentService::class)->record($assistance, [
            'amount' => '10.00', 'payment_date' => '2026-10-18', 'payment_method' => 'cash', 'idempotency_key' => 'immutable',
        ], $actor)['payment'];
        $service = app(TransactionService::class);
        foreach (['void', 'edit'] as $action) {
            try {
                if ($action === 'void') {
                    $service->voidForSource($payment);
                } else {
                    $service->syncForSource($payment, $this->movement($application->fund, ['type' => 'welfare_assistance', 'amount' => '1.00']));
                }
                $this->fail('Payment corrections must use the audited payment action.');
            } catch (ValidationException $exception) {
                $this->assertArrayHasKey('transaction', $exception->errors());
            }
        }
        $this->assertSame('posted', $payment->fresh()->status);
        $this->assertSame('10.00', $payment->fresh()->transaction->amount);
        $this->assertDatabaseCount('transactions', 1);
    }

    public function test_database_rejects_duplicate_payment_keys_and_duplicate_ledger_sources(): void
    {
        Storage::fake('private');
        $actor = $this->staff();
        $application = $this->signedApplication($actor);
        $assistance = $this->legacyAssistance($application, 'approved');
        $payment = app(WelfareAssistancePaymentService::class)->record($assistance, [
            'amount' => '10.00', 'payment_date' => '2026-10-18', 'payment_method' => 'cash', 'idempotency_key' => 'unique-key',
        ], $actor)['payment'];
        foreach ([$payment, $payment->transaction] as $original) {
            try {
                DB::transaction(function () use ($original) {
                    $copy = $original->replicate();
                    $copy->{$original instanceof WelfareAssistancePayment ? 'payment_number' : 'transaction_number'} = 'OTHER-NUMBER';
                    $copy->save();
                });
                $this->fail('The database constraint must prevent duplicate financial records.');
            } catch (UniqueConstraintViolationException $exception) {
                $this->assertNotEmpty($exception->getMessage());
            }
        }
        $this->assertDatabaseCount('welfare_assistance_payments', 1);
        $this->assertDatabaseCount('transactions', 1);
    }

    public function test_archived_ledger_entry_is_not_silently_restored_by_create(): void
    {
        $fund = $this->fund();
        $expense = $this->expense($fund);
        $service = app(TransactionService::class);
        $ledger = $service->createForSource($expense, $this->movement($fund));
        $ledger->delete();
        try {
            $service->createForSource($expense, $this->movement($fund));
            $this->fail('A retry must not silently restore an archived financial entry.');
        } catch (ValidationException $exception) {
            $this->assertArrayHasKey('transaction', $exception->errors());
        }
        $this->assertTrue(Transaction::withTrashed()->sole()->trashed());
    }

    public function test_donation_and_ledger_are_atomic_when_the_fund_cannot_accept_a_posting(): void
    {
        $fund = $this->fund();
        $fund->update(['status' => 'inactive']);
        $this->actingAs($this->staff(['finance.view', 'finance.donations.manage']));
        $this->postJson(route('finance.donations.store'), [
            'donor_type' => 'individual', 'donor_name' => 'Test donor', 'amount' => '10.25',
            'donation_date' => '2026-10-18', 'payment_method' => 'cash',
            'designation' => 'unrestricted', 'purpose' => 'education', 'fund_id' => $fund->id,
        ])->assertUnprocessable()->assertJsonValidationErrors('fund');
        $this->assertDatabaseCount('donations', 0);
        $this->assertDatabaseCount('transactions', 0);
        $fund->update(['status' => 'active']);
        $this->postJson(route('finance.donations.store'), [
            'donor_type' => 'individual', 'donor_name' => 'Test donor', 'amount' => '10.25',
            'donation_date' => '2026-10-18', 'payment_method' => 'cash',
            'designation' => 'unrestricted', 'purpose' => 'education', 'fund_id' => $fund->id,
        ])->assertRedirect();
        $this->assertDatabaseCount('donations', 1);
        $this->assertDatabaseCount('transactions', 1);
        $this->assertSame('110.25', app(FundBalanceService::class)->balance($fund));
    }

    public function test_reconciliation_includes_welfare_payments_and_reports_corruption_without_reposting(): void
    {
        Storage::fake('private');
        $actor = $this->staff();
        $application = $this->signedApplication($actor);
        $assistance = $this->legacyAssistance($application, 'approved');
        $service = app(WelfareAssistancePaymentService::class);
        $payment = $service->record($assistance, [
            'amount' => '10.00', 'payment_date' => '2026-10-18', 'payment_method' => 'cash', 'idempotency_key' => 'reconcile',
        ], $actor)['payment'];
        $this->assertSame(0, Artisan::call('finance:sync-transactions'), Artisan::output());
        $service->void($assistance, $payment, 'Incorrect payment', $actor);
        $this->assertSame(0, Artisan::call('finance:sync-transactions'), Artisan::output());
        $payment->transaction->update(['status' => 'posted', 'amount' => '0.01']);
        $this->assertSame(1, Artisan::call('finance:sync-transactions'));
        $output = Artisan::output();
        $this->assertStringContainsString('missing or inconsistent payment ledger entry', $output);
        $this->assertStringContainsString('mismatch PKR 0.01', $output);
        $this->assertSame('0.01', $payment->fresh()->transaction->amount);
        $this->assertDatabaseCount('transactions', 1);
    }
}
