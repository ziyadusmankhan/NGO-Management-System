<?php

namespace Tests\Feature;

use App\Domain\Finance\Donations\Models\Donation;
use App\Domain\Finance\Expenses\Models\Expense;
use App\Domain\Finance\Funds\Models\Fund;
use App\Domain\Finance\Funds\Services\FundBalanceService;
use App\Domain\Finance\Funds\Services\FundPurposeService;
use App\Domain\Finance\Funds\Services\FundReportingService;
use App\Domain\Finance\Funds\Services\FundService;
use App\Domain\Finance\Transactions\Models\Transaction;
use App\Domain\Finance\Transactions\Services\TransactionService;
use App\Domain\Welfare\Assistance\Services\WelfareAssistancePaymentService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Tests\Support\CreatesWelfareFixtures;
use Tests\TestCase;

class FundControlsTest extends TestCase
{
    use CreatesWelfareFixtures, RefreshDatabase;

    private function fund(array $overrides = []): Fund
    {
        return Fund::create(array_merge([
            'fund_reference' => 'FND-'.Str::random(10), 'name' => 'Education reserve',
            'type' => 'restricted', 'purpose' => 'education', 'opening_balance' => '1000.00',
            'status' => 'active', 'restrict_spending' => true, 'protect_restricted_designation' => true,
        ], $overrides))->fresh();
    }

    private function movement(Fund $fund, string $amount, string $direction = 'credit', string $type = 'donation', array $extra = []): Transaction
    {
        // Historical ledger fixtures also cover future recovery/refund source workflows.
        return Transaction::create(array_merge([
            'transaction_number' => 'TXN-'.Str::random(12), 'fund_id' => $fund->id,
            'type' => $type, 'description' => 'Reporting fixture', 'amount' => $amount,
            'direction' => $direction, 'transaction_date' => '2026-10-18', 'status' => 'posted',
        ], $extra));
    }

    private function expense(Fund $fund, string $category = 'education'): Expense
    {
        return Expense::create([
            'expense_number' => 'EXP-'.Str::random(10), 'fund_id' => $fund->id,
            'category' => $category, 'description' => 'Purpose check', 'amount' => '50.00',
            'expense_date' => '2026-10-18', 'payee_name' => 'School', 'status' => 'approved',
        ]);
    }

    private function debit(Fund $fund): array
    {
        return ['fund_id' => $fund->id, 'amount' => '50.00', 'direction' => 'debit',
            'type' => 'expense', 'description' => 'Purpose check', 'transaction_date' => '2026-10-18'];
    }

    private function assertInvalid(callable $action, string $field): void
    {
        try {
            $action();
            $this->fail('Expected the operation to be rejected.');
        } catch (ValidationException $exception) {
            $this->assertArrayHasKey($field, $exception->errors());
        }
    }

    private function form(Fund $fund, array $overrides = []): array
    {
        return array_merge($fund->only(array_merge(['name', 'type', 'purpose', 'opening_balance', 'status'], Fund::CONTROL_FLAGS)), $overrides);
    }

    public function test_report_separates_receipts_recoveries_and_both_refund_directions(): void
    {
        $fund = $this->fund();
        $this->movement($fund, '200.25');
        $this->movement($fund, '100.10', 'debit', 'expense');
        $this->movement($fund, '25.35', 'credit', 'recovery');
        $this->movement($fund, '5.15', 'credit', 'refund');
        $this->movement($fund, '10.05', 'debit', 'refund');
        $this->movement($fund, '999.00', 'credit', 'donation', ['status' => 'void']);
        $this->movement($fund, '888.00')->delete();
        $summary = app(FundReportingService::class)->summary($fund);
        foreach (['original_received' => '1200.25', 'received' => '200.25', 'spent' => '100.10',
            'recovered' => '25.35', 'refunded' => '5.15', 'refunds_paid' => '10.05',
            'balance' => '1120.60', 'available' => '1120.60', 'allocated' => '0.00'] as $key => $value) {
            $this->assertSame($value, $summary[$key], $key);
        }
        $this->assertSame(app(FundBalanceService::class)->balance($fund), $summary['balance']);
        $ledger = app(FundReportingService::class)->ledger($fund);
        $this->assertCount(7, $ledger);
        $this->assertSame(['1200.25', '1100.15', '1125.50', '1130.65', '1120.60', '1120.60', '1120.60'], $ledger->pluck('running_balance')->all());
        $this->assertFalse($ledger->last()->affects_balance);
    }

    public function test_report_orders_backdated_entries_by_date_then_id(): void
    {
        $fund = $this->fund();
        $later = $this->movement($fund, '10.00');
        $earlier = $this->movement($fund, '20.00', extra: ['transaction_date' => '2026-10-17']);
        $sameDay = $this->movement($fund, '5.00', 'debit', 'expense');
        $ledger = app(FundReportingService::class)->ledger($fund);
        $this->assertSame([$earlier->id, $later->id, $sameDay->id], $ledger->modelKeys());
        $this->assertSame(['1020.00', '1030.00', '1025.00'], $ledger->pluck('running_balance')->all());
    }

    public function test_allocations_reduce_with_payment_and_restore_once_after_void_for_every_frequency(): void
    {
        Storage::fake('private');
        $actor = $this->staff();
        foreach (['one_time', 'weekly', 'monthly'] as $frequency) {
            $application = $this->signedApplication($actor);
            $fund = $application->fund;
            $fund->update(['opening_balance' => '1200.00']);
            $assistance = $this->legacyAssistance($application, 'approved');
            $assistance->update(['amount' => '500.00', 'frequency' => $frequency]);
            $service = app(WelfareAssistancePaymentService::class);
            $payment = $service->record($assistance, ['amount' => '10.25', 'payment_date' => '2026-10-18',
                'payment_method' => 'cash', 'idempotency_key' => 'report-'.$frequency], $actor)['payment'];
            $report = app(FundReportingService::class);
            $summary = $report->summary($fund);
            $this->assertSame('1189.75', $summary['balance']);
            $this->assertSame('489.75', $summary['allocated']);
            $this->assertSame('700.00', $summary['available']);
            $service->void($assistance, $payment, 'Reporting void check', $actor);
            $service->void($assistance, $payment->fresh(), 'Repeated request', $actor);
            $summary = $report->summary($fund);
            $this->assertSame('1200.00', $summary['balance']);
            $this->assertSame('500.00', $summary['allocated']);
            $this->assertSame('700.00', $summary['available']);
            $this->assertSame('0.00', $summary['recovered']);
            $this->assertSame('0.00', $summary['refunded']);
            $this->assertCount(1, $report->ledger($fund));
        }
    }

    public function test_allocations_exclude_nonapproved_archived_and_fully_paid_assistance_and_show_shortfall(): void
    {
        Storage::fake('private');
        $actor = $this->staff();
        $application = $this->signedApplication($actor);
        $fund = $application->fund;
        $fund->update(['opening_balance' => '50.00']);
        foreach (['pending', 'rejected', 'completed'] as $status) {
            $this->legacyAssistance($application, $status);
        }
        $this->legacyAssistance($application, 'approved')->delete();
        $approved = $this->legacyAssistance($application, 'approved');
        $this->legacyAssistance($application, 'approved')->update(['amount' => '0.00']);
        $report = app(FundReportingService::class);
        $this->assertSame([$approved->id], $report->allocations($fund)->modelKeys());
        $this->assertSame('100.25', $report->summary($fund)['allocated']);
        $this->assertSame('-50.25', $report->summary($fund)['available']);
        $this->actingAs($this->staff(['finance.view']))->get(route('finance.funds.show', $fund))
            ->assertOk()->assertSee('funding shortfall')->assertSee('PKR -50.25');
    }

    public function test_final_payment_clears_allocation_and_void_reopens_only_the_unpaid_portion(): void
    {
        Storage::fake('private');
        $actor = $this->staff(['finance.view', 'finance.funds.manage', 'welfare.assistance.manage']);
        $application = $this->signedApplication($actor);
        $fund = $application->fund;
        $fund->update(['opening_balance' => '1200.00']);
        $assistance = $this->legacyAssistance($application, 'approved');
        $assistance->update(['amount' => '500.00', 'frequency' => 'monthly']);
        $service = app(WelfareAssistancePaymentService::class);
        foreach (['10.25', '489.75'] as $amount) {
            $payment = $service->record($assistance->fresh(), ['amount' => $amount,
                'payment_date' => '2026-10-18', 'payment_method' => 'cash', 'idempotency_key' => 'complete-'.$amount], $actor)['payment'];
        }
        $report = app(FundReportingService::class);
        $this->assertSame('completed', $assistance->fresh()->status);
        $this->assertSame('0.00', $report->summary($fund)['allocated']);
        $this->assertSame('700.00', $report->summary($fund)['available']);
        $service->void($assistance->fresh(), $payment, 'Replace final payment', $actor);
        $summary = $report->summary($fund);
        $this->assertSame('approved', $assistance->fresh()->status);
        $this->assertSame('1189.75', $summary['balance']);
        $this->assertSame('489.75', $summary['allocated']);
        $this->assertSame('700.00', $summary['available']);
        $this->actingAs($actor)->get(route('finance.funds.edit', $fund))->assertOk()
            ->assertSee('PKR 1,189.75')->assertSee('PKR 489.75')->assertSee('PKR 700.00');
    }

    public function test_report_and_totals_preserve_large_decimal_values(): void
    {
        $fund = $this->fund(['opening_balance' => '999999999999.99']);
        $this->movement($fund, '0.01', 'debit', 'expense');
        $other = $this->fund(['opening_balance' => '0.03']);
        $report = app(FundReportingService::class);
        $this->assertSame('999999999999.98', $report->summary($fund)['balance']);
        $this->assertSame('1000000000000.01', $report->totals(collect([$fund, $other]))['balance']);
    }

    public function test_fund_creation_and_updates_are_audited_without_creating_movements(): void
    {
        $actor = $this->staff(['finance.view', 'finance.funds.manage']);
        $service = app(FundService::class);
        $fund = $service->create(['name' => 'New fund', 'purpose' => 'education', 'type' => 'restricted', 'opening_balance' => '10.25'], $actor);
        $this->assertSame(sprintf('FUND-%05d', $fund->id), $fund->fund_reference);
        $created = $fund->changes()->sole();
        $this->assertSame('created', $created->action);
        $this->assertSame($actor->id, $created->changed_by);
        $this->assertSame('10.25', $created->after_values['opening_balance']);
        $this->assertNotNull($created->changed_at);
        $this->assertSame([], $created->before_values);
        $service->update($fund, ['name' => 'Updated fund'], $actor);
        $updated = $fund->changes()->first();
        $this->assertSame('New fund', $updated->before_values['name']);
        $this->assertSame('Updated fund', $updated->after_values['name']);
        $service->update($fund, ['name' => 'Updated fund'], $actor);
        $this->assertDatabaseCount('fund_changes', 2);
        $this->assertDatabaseCount('transactions', 0);
    }

    public function test_used_fund_designation_and_opening_balance_cannot_be_rewritten_even_after_void_and_archive(): void
    {
        $actor = $this->staff(['finance.funds.manage']);
        $fund = $this->fund();
        $this->movement($fund, '10.00', extra: ['status' => 'void'])->delete();
        $service = app(FundService::class);
        foreach (['opening_balance' => '2000.00', 'opening_balance_date' => '2026-10-18', 'type' => 'general',
            'purpose' => 'emergency', 'restrict_spending' => false, 'protect_restricted_designation' => false] as $key => $value) {
            $this->assertInvalid(fn () => $service->update($fund, [$key => $value], $actor), $key);
        }
        $this->assertDatabaseCount('fund_changes', 0);
        $service->update($fund, ['description' => 'Allowed metadata change'], $actor);
        $this->assertSame('Allowed metadata change', $fund->fresh()->description);
        $this->assertDatabaseCount('fund_changes', 1);
    }

    public function test_linked_commitment_protects_designation_before_any_payment(): void
    {
        Storage::fake('private');
        $actor = $this->staff(['finance.funds.manage']);
        $application = $this->signedApplication($actor);
        $this->assertInvalid(fn () => app(FundService::class)->update($application->fund, ['purpose' => 'emergency'], $actor), 'purpose');
        $this->assertDatabaseCount('transactions', 0);
    }

    public function test_only_empty_funds_can_be_archived_and_archived_history_remains_readable(): void
    {
        $actor = $this->staff(['finance.view', 'finance.funds.manage']);
        $fund = $this->fund();
        $service = app(FundService::class);
        $this->assertInvalid(fn () => $service->archive($fund, $actor), 'fund');
        $fund->update(['opening_balance' => '0.00']);
        $transaction = $this->movement($fund, '10.00', extra: ['status' => 'void']);
        $transaction->delete();
        $service->archive($fund, $actor);
        $this->assertTrue(Fund::withTrashed()->findOrFail($fund->id)->trashed());
        $this->assertDatabaseHas('fund_changes', ['fund_id' => $fund->id, 'action' => 'archived', 'changed_by' => $actor->id]);
        $this->actingAs($actor)->get(route('finance.funds.show', $fund))->assertOk()->assertSee('Archived')->assertSee($transaction->transaction_number);
        $this->get(route('finance.funds.index', ['status' => 'archived']))->assertOk()->assertSee($fund->name)->assertSee('Archived');
        $this->get(route('finance.transactions.show', $transaction))->assertOk()->assertSee($fund->name);
    }

    public function test_zero_balance_fund_with_unpaid_approval_cannot_be_archived(): void
    {
        Storage::fake('private');
        $actor = $this->staff(['finance.funds.manage']);
        $application = $this->signedApplication($actor);
        $application->fund->update(['opening_balance' => '0.00']);
        $this->legacyAssistance($application, 'approved');
        $this->assertInvalid(fn () => app(FundService::class)->archive($application->fund, $actor), 'fund');
        $this->assertFalse($application->fund->fresh()->trashed());
    }

    public function test_view_only_account_cannot_create_update_or_archive_funds(): void
    {
        $fund = $this->fund();
        $this->actingAs($this->staff(['finance.view']));
        $this->get(route('finance.funds.show', $fund))->assertOk()->assertDontSee('Edit Fund');
        $this->postJson(route('finance.funds.store'), $this->form($fund))->assertForbidden();
        $this->putJson(route('finance.funds.update', $fund), $this->form($fund))->assertForbidden();
        $this->deleteJson(route('finance.funds.destroy', $fund))->assertForbidden();
        $this->assertDatabaseCount('fund_changes', 0);
    }

    public function test_checkbox_values_and_legacy_purpose_survive_metadata_edit_correctly(): void
    {
        $fund = $this->fund(['purpose' => 'legacy_custom_purpose', 'monitor_balance' => true]);
        $this->movement($fund, '1.00');
        $this->actingAs($this->staff(['finance.view', 'finance.funds.manage']));
        $this->get(route('finance.funds.edit', $fund))->assertOk()->assertSee('existing purpose')->assertSee('are locked');
        $data = $this->form($fund, ['name' => 'Updated legacy name']);
        unset($data['monitor_balance']);
        $this->put(route('finance.funds.update', $fund), $data)->assertRedirect(route('finance.funds.show', $fund))->assertSessionHasNoErrors();
        $this->assertFalse($fund->fresh()->monitor_balance);
        $this->assertSame('legacy_custom_purpose', $fund->fresh()->purpose);
        $this->assertDatabaseCount('fund_changes', 1);
    }

    public function test_fund_request_rejects_unknown_new_purpose_and_invalid_money(): void
    {
        $fund = $this->fund();
        $this->actingAs($this->staff(['finance.view', 'finance.funds.manage']));
        $this->postJson(route('finance.funds.store'), $this->form($fund, ['purpose' => 'unknown']))->assertUnprocessable()->assertJsonValidationErrors('purpose');
        foreach (['0.001', '-1.00', '1e3'] as $amount) {
            $this->postJson(route('finance.funds.store'), $this->form($fund, ['opening_balance' => $amount]))->assertUnprocessable()->assertJsonValidationErrors('opening_balance');
        }
        $this->assertDatabaseCount('fund_changes', 0);
    }

    public function test_failed_audit_write_rolls_back_fund_mutations(): void
    {
        $actor = $this->staff(['finance.funds.manage']);
        $fund = $this->fund(['opening_balance' => '0.00']);
        DB::unprepared("CREATE TRIGGER reject_fund_audit BEFORE INSERT ON fund_changes BEGIN SELECT RAISE(ABORT, 'audit unavailable'); END");
        try {
            foreach (['create', 'update', 'archive'] as $action) {
                try {
                    $service = app(FundService::class);
                    match ($action) {
                        'create' => $service->create(['name' => 'Must roll back', 'purpose' => 'education'], $actor),
                        'update' => $service->update($fund, ['name' => 'Must roll back'], $actor),
                        'archive' => $service->archive($fund, $actor),
                    };
                    $this->fail('Audit failure must roll back the operation.');
                } catch (\Illuminate\Database\QueryException $exception) {
                    $this->assertStringContainsString('audit unavailable', $exception->getMessage());
                }
                $this->assertSame('Education reserve', $fund->fresh()->name);
                $this->assertFalse($fund->fresh()->trashed());
                $this->assertDatabaseCount('funds', 1);
                $this->assertDatabaseCount('fund_changes', 0);
            }
        } finally {
            DB::unprepared('DROP TRIGGER reject_fund_audit');
        }
    }

    public function test_expense_purpose_matrix_includes_flagged_general_funds_and_denies_unknown_purposes(): void
    {
        $service = app(FundPurposeService::class);
        $allowed = ['general_welfare' => ['welfare', 'medical', 'education'], 'education' => ['education'],
            'emergency' => ['medical', 'welfare'], 'small_business' => [],
            'community_development' => ['project'], 'qard' => [], 'unknown' => []];
        foreach (['restricted', 'designated', 'general'] as $type) {
            foreach ($allowed as $purpose => $categories) {
                $fund = $this->fund(['type' => $type, 'purpose' => $purpose, 'restrict_spending' => true]);
                foreach (['welfare', 'medical', 'education', 'project', 'administration'] as $category) {
                    $this->assertSame(in_array($category, $categories, true), $service->allowsExpense($fund, $category), "$type/$purpose/$category");
                }
            }
        }
        $this->assertTrue($service->allowsExpense($this->fund(['type' => 'general', 'restrict_spending' => false]), 'administration'));
    }

    public function test_shared_ledger_rejects_mismatched_expense_even_when_calling_it_directly_with_stale_data(): void
    {
        $fund = $this->fund();
        $expense = $this->expense($fund);
        Expense::whereKey($expense->id)->update(['category' => 'medical']);
        $service = app(TransactionService::class);
        $this->assertInvalid(fn () => $service->createForSource($expense, $this->debit($fund)), 'fund_id');
        $this->assertDatabaseCount('transactions', 0);
        $this->assertSame('1000.00', app(FundBalanceService::class)->balance($fund));
        $expense->refresh()->update(['category' => 'education']);
        $transaction = $service->createForSource($expense, $this->debit($fund));
        $this->assertSame($transaction->id, $service->createForSource($expense, $this->debit($fund))->id);
        $this->assertDatabaseCount('transactions', 1);
    }

    public function test_shared_ledger_rejects_reassigning_a_debit_to_incompatible_fund_and_unknown_debit_sources(): void
    {
        $fund = $this->fund();
        $other = $this->fund(['purpose' => 'community_development']);
        $expense = $this->expense($fund);
        $service = app(TransactionService::class);
        $service->createForSource($expense, $this->debit($fund));
        $this->assertInvalid(fn () => $service->syncForSource($expense, $this->debit($other)), 'fund_id');
        $this->assertSame($fund->id, Transaction::sole()->fund_id);
        $donation = Donation::create(['donation_number' => 'DON-UNKNOWN', 'fund_id' => $fund->id,
            'donor_name' => 'Donor', 'amount' => '50.00', 'donation_date' => '2026-10-18', 'purpose' => 'education']);
        $this->assertInvalid(fn () => $service->createForSource($donation, $this->debit($fund)), 'fund_id');
        $this->assertDatabaseCount('transactions', 1);
    }

    public function test_fund_pages_render_actual_summary_and_source_links_with_no_placeholder_allocations(): void
    {
        $fund = $this->fund(['type' => 'general', 'restrict_spending' => true]);
        $expense = $this->expense($fund);
        $transaction = app(TransactionService::class)->createForSource($expense, $this->debit($fund));
        $this->actingAs($this->staff(['finance.view']));
        $this->get(route('finance.funds.show', $fund))->assertOk()->assertSee('Current Ledger Balance')
            ->assertSee('Recoveries Received')->assertSee('Refunds Paid Out')->assertSee('PKR 950.00')
            ->assertSee(route('finance.expenses.show', $expense), false)->assertSee($transaction->transaction_number);
        $this->get(route('finance.funds.index'))->assertOk()->assertSee('950.00')->assertDontSee('Awaiting Transactions module');
        $this->get(route('finance.restricted-funds.index'))->assertOk()->assertSee($fund->name)->assertSee('950.00');
    }

    public function test_payment_void_history_includes_actor_reason_and_permission_aware_source_link(): void
    {
        Storage::fake('private');
        $actor = $this->staff(['finance.view', 'welfare.assistance.view', 'welfare.assistance.manage']);
        $application = $this->signedApplication($actor);
        $assistance = $this->legacyAssistance($application, 'approved');
        $service = app(WelfareAssistancePaymentService::class);
        $payment = $service->record($assistance, ['amount' => '10.25', 'payment_date' => '2026-10-18',
            'payment_method' => 'cash', 'idempotency_key' => 'history'], $actor)['payment'];
        $service->void($assistance, $payment, 'Incorrect recipient entered', $actor);
        $url = route('welfare.assistance.payments.show', [$assistance, $payment]);
        $this->actingAs($actor)->get(route('finance.funds.show', $application->fund))->assertOk()
            ->assertSee('Incorrect recipient entered')->assertSee($actor->name)->assertSee($url, false)->assertSee('Void (excluded)');
        $this->actingAs($this->staff(['finance.view']))->get(route('finance.funds.show', $application->fund))
            ->assertOk()->assertDontSee($url, false)->assertSee($payment->payment_number);
    }

    public function test_http_expense_create_update_and_approval_reject_wrong_purpose_without_ledger_changes(): void
    {
        $fund = $this->fund(['require_documents' => false]);
        $actor = $this->staff(['finance.view', 'finance.expenses.manage', 'finance.expenses.approve']);
        $this->actingAs($actor);
        $data = ['fund_id' => $fund->id, 'category' => 'medical', 'description' => 'Incompatible purpose',
            'amount' => '50.00', 'expense_date' => '2026-10-18', 'payee_name' => 'Clinic',
            'payment_method' => 'cash', 'status' => 'pending', 'has_supporting_document' => false];
        $this->post(route('finance.expenses.store'), $data)->assertSessionHasErrors('category');
        $this->assertDatabaseCount('expenses', 0);
        $expense = $this->expense($fund, 'medical');
        $expense->update(['status' => 'pending']);
        $this->post(route('finance.expenses.approve', $expense))->assertSessionHasErrors('category');
        $this->assertSame('pending', $expense->fresh()->status);
        $this->put(route('finance.expenses.update', $expense), array_merge($data, ['status' => 'approved']))->assertSessionHasErrors('category');
        $this->assertDatabaseCount('transactions', 0);
        $this->assertSame('1000.00', app(FundBalanceService::class)->balance($fund));
        $expense->update(['category' => 'education']);
        $this->post(route('finance.expenses.approve', $expense))->assertSessionHasNoErrors()->assertRedirect();
        $this->assertSame('approved', $expense->fresh()->status);
        $this->assertDatabaseCount('transactions', 1);
        $this->assertSame('950.00', app(FundBalanceService::class)->balance($fund));
    }

    public function test_http_fund_edit_cannot_disable_protection_or_change_used_purpose(): void
    {
        $fund = $this->fund();
        $this->movement($fund, '20.00');
        $this->actingAs($this->staff(['finance.view', 'finance.funds.manage']));
        $this->putJson(route('finance.funds.update', $fund), $this->form($fund, ['purpose' => 'emergency']))
            ->assertUnprocessable()->assertJsonValidationErrors('purpose');
        $data = $this->form($fund);
        unset($data['restrict_spending']);
        $this->putJson(route('finance.funds.update', $fund), $data)
            ->assertUnprocessable()->assertJsonValidationErrors('restrict_spending');
        $this->assertSame('education', $fund->fresh()->purpose);
        $this->assertTrue($fund->fresh()->restrict_spending);
        $this->assertDatabaseCount('fund_changes', 0);
    }

    public function test_users_without_finance_access_cannot_read_fund_history(): void
    {
        $fund = $this->fund();
        $this->actingAs($this->staff([]))->get(route('finance.funds.show', $fund))->assertForbidden();
        $this->get(route('finance.funds.index'))->assertForbidden();
        $this->get(route('finance.restricted-funds.index'))->assertForbidden();
    }

    public function test_audit_migration_is_reversible(): void
    {
        $migration = require database_path('migrations/2026_10_18_000001_create_fund_changes_table.php');
        $migration->down();
        $this->assertFalse(Schema::hasTable('fund_changes'));
        $migration->up();
        $this->assertTrue(Schema::hasColumns('fund_changes', ['fund_id', 'action', 'before_values', 'after_values', 'changed_by', 'changed_at']));
    }
}
