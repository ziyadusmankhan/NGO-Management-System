<?php

namespace Tests\Feature;

use App\Domain\Finance\Funds\Services\FundBalanceService;
use App\Domain\Finance\Support\Money;
use App\Domain\Finance\Transactions\Models\Transaction;
use App\Domain\Welfare\Assistance\Models\WelfareAssistancePayment;
use App\Domain\Welfare\Assistance\Services\WelfareAssistancePaymentService;
use Illuminate\Database\QueryException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\Support\CreatesWelfareFixtures;
use Tests\TestCase;

class WelfarePaymentIntegrityTest extends TestCase
{
    use CreatesWelfareFixtures, RefreshDatabase;

    private function scenario(array $fundAttributes = [], array $assistanceAttributes = []): array
    {
        Storage::fake('private');
        $actor = $this->staff();
        $application = $this->signedApplication($actor);
        $fund = $application->fund;
        $fund->update(array_merge(['opening_balance' => '100.25'], $fundAttributes));
        $assistance = $this->legacyAssistance($application, 'approved');
        $assistance->update($assistanceAttributes);
        $this->actingAs($actor);

        return [$actor, $fund, $assistance, $application];
    }

    private function payload(array $overrides = []): array
    {
        return array_merge([
            'idempotency_key' => (string) Str::uuid(), 'amount' => '10.25',
            'payment_date' => '2026-10-18', 'payment_method' => 'cash',
        ], $overrides);
    }

    public function test_payment_posts_once_and_form_shows_current_balance_and_remaining_amount(): void
    {
        [$actor, $fund, $assistance] = $this->scenario();
        $data = $this->payload();
        $url = route('welfare.assistance.payments.store', $assistance);
        $this->post($url, $data)->assertRedirect()->assertSessionHasNoErrors();
        $this->post($url, $data)->assertRedirect()->assertSessionHasNoErrors();
        $this->assertDatabaseCount('welfare_assistance_payments', 1);
        $this->assertDatabaseCount('transactions', 1);
        $payment = WelfareAssistancePayment::sole();
        $ledger = $payment->transaction;
        $this->assertSame('10.25', $ledger->amount);
        $this->assertSame('debit', $ledger->direction);
        $this->assertSame($fund->id, $ledger->fund_id);
        $this->assertSame($actor->id, $payment->recorded_by);
        $this->assertSame($actor->id, $ledger->recorded_by);
        $this->assertSame('90.00', app(FundBalanceService::class)->balance($fund));
        $this->get(route('welfare.assistance.payments.create', $assistance))->assertOk()
            ->assertSee('Available Fund Balance')->assertSee('max="90.00"', false)
            ->assertSee('value="90.00"', false);
        $this->get(route('welfare.assistance.payments.show', [$assistance, $payment]))->assertOk()->assertSee('10.25');
    }

    #[DataProvider('negativeBalanceFlags')]
    public function test_insufficient_balance_is_blocked_even_when_overdraft_checkbox_is_disabled(bool $flag): void
    {
        [, $fund, $assistance] = $this->scenario(['opening_balance' => '10.24', 'prevent_negative_balance' => $flag]);
        $this->postJson(route('welfare.assistance.payments.store', $assistance), $this->payload())
            ->assertUnprocessable()->assertJsonValidationErrors('amount');
        $this->assertDatabaseCount('welfare_assistance_payments', 0);
        $this->assertDatabaseCount('transactions', 0);
        $this->assertSame('10.24', app(FundBalanceService::class)->balance($fund));
    }

    public static function negativeBalanceFlags(): array { return [[true], [false]]; }

    #[DataProvider('designations')]
    public function test_fund_purpose_restrictions(string $type, bool $restricted, string $purpose, string $assistanceType, bool $allowed): void
    {
        [, , $assistance] = $this->scenario(['type' => $type, 'restrict_spending' => $restricted, 'purpose' => $purpose], ['assistance_type' => $assistanceType]);
        $response = $this->postJson(route('welfare.assistance.payments.store', $assistance), $this->payload());
        if ($allowed) {
            $response->assertRedirect()->assertSessionHasNoErrors();
        } else {
            $response->assertUnprocessable()->assertJsonValidationErrors('fund');
        }
        $this->assertDatabaseCount('transactions', $allowed ? 1 : 0);
        $this->assertDatabaseCount('welfare_assistance_payments', $allowed ? 1 : 0);
    }

    public static function designations(): array
    {
        return [
            ['general', false, 'education', 'food_ration', true],
            ['general', true, 'education', 'food_ration', false],
            ['restricted', false, 'education', 'food_ration', false],
            ['designated', false, 'education', 'education_support', true],
            ['restricted', false, 'education', 'education_support', true],
            ['restricted', false, 'general_welfare', 'food_ration', true],
            ['restricted', false, 'general_welfare', 'education_support', true],
            ['restricted', false, 'emergency', 'emergency_assistance', true],
            ['restricted', false, 'emergency', 'monthly_assistance', false],
            ['restricted', false, 'small_business', 'livelihood_assistance', true],
            ['restricted', false, 'qard', 'education_support', false],
            ['restricted', false, 'community_development', 'food_ration', false],
            ['restricted', false, 'custom_purpose', 'education_support', false],
            ['restricted', false, 'education', 'unknown', false],
        ];
    }

    public function test_linked_application_category_controls_designation_and_rejects_test_applications(): void
    {
        [, , $assistance, $application] = $this->scenario(['type' => 'restricted', 'purpose' => 'education']);
        $assistance->forceFill(['application_id' => $application->id, 'assistance_type' => 'custom school fees'])->save();
        $this->postJson(route('welfare.assistance.payments.store', $assistance), $this->payload())->assertRedirect();
        $application->forceFill(['is_test' => true])->save();
        $this->postJson(route('welfare.assistance.payments.store', $assistance), $this->payload())
            ->assertUnprocessable()->assertJsonValidationErrors('application');
        $this->assertDatabaseCount('transactions', 1);
    }

    public function test_fractional_payments_complete_exactly_and_cannot_overpay(): void
    {
        [, $fund, $assistance] = $this->scenario(['opening_balance' => '0.30'], ['amount' => '0.30']);
        $url = route('welfare.assistance.payments.store', $assistance);
        $this->postJson($url, $this->payload(['amount' => '0.10']))->assertRedirect();
        $this->postJson($url, $this->payload(['amount' => '0.21']))->assertUnprocessable();
        $last = $this->payload(['amount' => '0.20']);
        $this->postJson($url, $last)->assertRedirect();
        $this->postJson($url, $last)->assertRedirect();
        $this->postJson($url, $this->payload(['amount' => '0.01']))->assertUnprocessable();
        $this->assertSame('completed', $assistance->fresh()->status);
        $this->assertSame('0.00', app(FundBalanceService::class)->balance($fund));
        $this->assertDatabaseCount('transactions', 2);
        $this->assertDatabaseCount('welfare_assistance_status_changes', 1);
    }

    public function test_largest_supported_payment_preserves_every_paisa(): void
    {
        $amount = '999999999999.99';
        [, $fund, $assistance] = $this->scenario(['opening_balance' => $amount], ['amount' => $amount]);
        $this->postJson(route('welfare.assistance.payments.store', $assistance), $this->payload(['amount' => $amount]))->assertRedirect();
        $this->assertSame($amount, WelfareAssistancePayment::sole()->amount);
        $this->assertSame($amount, Transaction::sole()->amount);
        $this->assertSame('0.00', app(FundBalanceService::class)->balance($fund->fresh()));
        $this->assertSame('999,999,999,999.99', Money::format($amount));
    }

    #[DataProvider('paymentFrequencies')]
    public function test_all_frequencies_deduct_previous_payments_and_restore_remaining_after_void(?string $frequency): void
    {
        [, $fund, $assistance] = $this->scenario(['opening_balance' => '1000.00'], ['frequency' => $frequency, 'amount' => '500.00']);
        $url = route('welfare.assistance.payments.store', $assistance);
        $this->postJson($url, $this->payload(['amount' => '10.25']))->assertRedirect();
        foreach (['500.00', '489.76'] as $tooMuch) {
            $this->postJson($url, $this->payload(['amount' => $tooMuch]))->assertUnprocessable()
                ->assertJsonValidationErrors('amount')->assertSee('489.75');
        }
        $this->assertDatabaseCount('transactions', 1);
        $this->get(route('welfare.assistance.payments.create', $assistance))->assertOk()
            ->assertSee('Remaining Approved Amount')->assertSee('max="489.75"', false)
            ->assertSee('value="489.75"', false)->assertDontSee('Maximum Per Payment');
        $this->postJson($url, $this->payload(['amount' => '489.75']))->assertRedirect();
        $this->assertSame('completed', $assistance->fresh()->status);
        $this->postJson($url, $this->payload(['amount' => '0.01']))->assertUnprocessable();
        $this->assertSame('500.00', app(FundBalanceService::class)->balance($fund));
        $last = WelfareAssistancePayment::latest('id')->firstOrFail();
        $this->postJson(route('welfare.assistance.payments.void', [$assistance, $last]), ['void_reason' => 'Correct the payment details'])->assertRedirect();
        $this->assertSame('approved', $assistance->fresh()->status);
        $this->assertSame(48975, app(WelfareAssistancePaymentService::class)->remainingMinor($assistance));
        $this->get(route('welfare.assistance.payments.create', $assistance))->assertOk()->assertSee('max="489.75"', false);
        $this->postJson($url, $this->payload(['amount' => '489.75']))->assertRedirect();
        $this->assertSame('completed', $assistance->fresh()->status);
        $this->assertSame('500.00', app(FundBalanceService::class)->balance($fund));
        $this->assertDatabaseCount('transactions', 3);
        $this->assertDatabaseCount('welfare_assistance_status_changes', 3);
    }

    public static function paymentFrequencies(): array
    {
        return [[null], [''], ['one_time'], ['weekly'], ['monthly'], ['quarterly'], ['yearly']];
    }

    public function test_existing_overpaid_records_show_zero_remaining_and_cannot_receive_more(): void
    {
        [, , $assistance] = $this->scenario(['opening_balance' => '1000.00'], ['frequency' => 'weekly', 'amount' => '500.00']);
        $url = route('welfare.assistance.payments.store', $assistance);
        $this->postJson($url, $this->payload(['amount' => '10.25']))->assertRedirect();
        $this->postJson($url, $this->payload(['amount' => '489.75']))->assertRedirect();
        // Reproduce history allowed by the former per-payment rule, only in the test database.
        $last = WelfareAssistancePayment::latest('id')->firstOrFail();
        $last->update(['amount' => '500.00']);
        $last->transaction->update(['amount' => '500.00']);
        $assistance->refresh()->update(['status' => 'approved']);
        $this->get(route('welfare.assistance.payments.create', $assistance))->assertOk()
            ->assertSee('max="0.00"', false)->assertSee('already been fully paid or exceeded');
        $this->postJson($url, $this->payload(['amount' => '0.01']))->assertUnprocessable()->assertJsonValidationErrors('amount');
        $this->assertSame('500.00', $last->fresh()->amount);
        $this->assertDatabaseCount('transactions', 2);
    }

    #[DataProvider('changedPayloads')]
    public function test_reusing_key_with_changed_details_conflicts(string $field, string $value): void
    {
        [, , $assistance] = $this->scenario();
        $data = $this->payload();
        $url = route('welfare.assistance.payments.store', $assistance);
        $this->postJson($url, $data)->assertRedirect();
        $this->postJson($url, array_merge($data, [$field => $value]))->assertConflict();
        $this->assertDatabaseCount('transactions', 1);
    }

    public static function changedPayloads(): array
    {
        return [['amount', '11.25'], ['payment_date', '2026-10-19'], ['payment_method', 'cheque'], ['paid_to', 'Different person'], ['notes', 'Changed note'], ['receipt_number', 'Changed receipt'], ['transaction_reference', 'Changed reference']];
    }

    public function test_request_key_cannot_be_reused_for_another_assistance(): void
    {
        [, , $assistance, $application] = $this->scenario();
        $other = $this->legacyAssistance($application, 'approved');
        $data = $this->payload();
        $this->postJson(route('welfare.assistance.payments.store', $assistance), $data)->assertRedirect();
        $this->postJson(route('welfare.assistance.payments.store', $other), $data)->assertConflict();
        $this->assertDatabaseCount('transactions', 1);
    }

    public function test_void_restores_balance_once_reopens_assistance_and_preserves_original_audit(): void
    {
        [$actor, $fund, $assistance] = $this->scenario();
        $data = $this->payload(['amount' => '100.25']);
        $store = route('welfare.assistance.payments.store', $assistance);
        $this->postJson($store, $data)->assertRedirect();
        $payment = WelfareAssistancePayment::sole();
        $url = route('welfare.assistance.payments.void', [$assistance, $payment]);
        $this->postJson($url, ['void_reason' => 'Incorrect recipient'])->assertRedirect();
        $this->assertSame('100.25', app(FundBalanceService::class)->balance($fund));
        $this->assertSame('approved', $assistance->fresh()->status);
        $this->assertSame('void', $payment->fresh()->transaction->status);
        $this->assertSame($actor->id, $payment->fresh()->voided_by);
        $voidedAt = $payment->fresh()->voided_at->toISOString();
        $otherActor = $this->staff();
        $this->actingAs($otherActor)->postJson($url, ['void_reason' => 'A repeated void request'])->assertRedirect();
        $this->assertSame($voidedAt, $payment->fresh()->voided_at->toISOString());
        $this->assertSame('Incorrect recipient', $payment->fresh()->void_reason);
        $this->assertSame($actor->id, $payment->fresh()->voided_by);
        $this->assertDatabaseCount('welfare_assistance_status_changes', 2);
        $this->postJson($store, $data)->assertRedirect();
        $this->assertSame('100.25', app(FundBalanceService::class)->balance($fund));
        $this->assertDatabaseCount('transactions', 1);
        $this->postJson($store, $this->payload(['amount' => '100.25']))->assertRedirect();
        $this->assertSame('0.00', app(FundBalanceService::class)->balance($fund));
        $this->assertDatabaseCount('transactions', 2);
        $this->assertSame('completed', $assistance->fresh()->status);
    }

    #[DataProvider('badLedgers')]
    public function test_inconsistent_ledger_blocks_void_without_mutating_payment(string $case): void
    {
        [, , $assistance] = $this->scenario();
        $this->postJson(route('welfare.assistance.payments.store', $assistance), $this->payload())->assertRedirect();
        $payment = WelfareAssistancePayment::sole();
        $ledger = $payment->transaction;
        match ($case) {
            'missing' => $ledger->forceDelete(),
            'archived' => $ledger->delete(),
            'void' => $ledger->update(['status' => 'void']),
            'amount' => $ledger->update(['amount' => '1.00']),
            'direction' => $ledger->update(['direction' => 'credit']),
            'type' => $ledger->update(['type' => 'expense']),
        };
        $this->postJson(route('welfare.assistance.payments.void', [$assistance, $payment]), ['void_reason' => 'Please reverse this payment'])
            ->assertUnprocessable()->assertJsonValidationErrors('transaction');
        $this->assertSame('posted', $payment->fresh()->status);
        $this->assertNull($payment->fresh()->voided_at);
    }

    public static function badLedgers(): array { return [['missing'], ['archived'], ['void'], ['amount'], ['direction'], ['type']]; }

    public function test_ledger_insert_failure_rolls_back_payment_and_completion(): void
    {
        [$actor, , $assistance] = $this->scenario();
        DB::statement("CREATE TRIGGER fail_ledger BEFORE INSERT ON transactions BEGIN SELECT RAISE(ABORT, 'Ledger unavailable'); END");
        try {
            app(WelfareAssistancePaymentService::class)->record($assistance, $this->payload(['amount' => '100.25']), $actor);
            $this->fail('Posting failure must roll back the payment.');
        } catch (QueryException $exception) {
            $this->assertStringContainsString('Ledger unavailable', $exception->getMessage());
        } finally {
            DB::statement('DROP TRIGGER fail_ledger');
        }
        $this->assertDatabaseCount('welfare_assistance_payments', 0);
        $this->assertDatabaseCount('transactions', 0);
        $this->assertDatabaseCount('welfare_assistance_status_changes', 0);
        $this->assertSame('approved', $assistance->fresh()->status);
    }

    public function test_failed_ledger_void_rolls_back_payment_audit_and_reopening(): void
    {
        [$actor, $fund, $assistance] = $this->scenario();
        $this->postJson(route('welfare.assistance.payments.store', $assistance), $this->payload(['amount' => '100.25']))->assertRedirect();
        $payment = WelfareAssistancePayment::sole();
        DB::statement("CREATE TRIGGER fail_void BEFORE UPDATE ON transactions BEGIN SELECT RAISE(ABORT, 'Void unavailable'); END");
        try {
            app(WelfareAssistancePaymentService::class)->void($assistance, $payment, 'Incorrect payment', $actor);
            $this->fail('Void failure must roll back both records.');
        } catch (QueryException $exception) {
            $this->assertStringContainsString('Void unavailable', $exception->getMessage());
        } finally {
            DB::statement('DROP TRIGGER fail_void');
        }
        $this->assertSame('posted', $payment->fresh()->status);
        $this->assertNull($payment->fresh()->voided_at);
        $this->assertSame('completed', $assistance->fresh()->status);
        $this->assertSame('0.00', app(FundBalanceService::class)->balance($fund));
        $this->assertDatabaseCount('welfare_assistance_status_changes', 1);
    }

    #[DataProvider('invalidAmounts')]
    public function test_invalid_amounts_are_rejected_without_financial_side_effects(string $amount): void
    {
        [, , $assistance] = $this->scenario();
        $this->postJson(route('welfare.assistance.payments.store', $assistance), $this->payload(['amount' => $amount]))
            ->assertUnprocessable()->assertJsonValidationErrors('amount');
        $this->assertDatabaseCount('transactions', 0);
        $this->assertDatabaseCount('welfare_assistance_payments', 0);
    }

    public static function invalidAmounts(): array { return [['0'], ['-1'], ['1.001'], ['1e1'], ['1000000000000.00'], ['not money']]; }

    public function test_json_float_amounts_are_rejected_instead_of_losing_paisa(): void
    {
        [, , $assistance] = $this->scenario();
        $this->postJson(route('welfare.assistance.payments.store', $assistance), $this->payload(['amount' => 10.25]))
            ->assertUnprocessable()->assertJsonValidationErrors('amount');
        $this->assertDatabaseCount('transactions', 0);
        $this->expectException(\InvalidArgumentException::class);
        Money::minor(10.25);
    }

    public function test_permissions_route_ownership_validation_and_errors_are_visible(): void
    {
        [$actor, , $assistance, $application] = $this->scenario();
        $this->postJson(route('welfare.assistance.payments.store', $assistance), $this->payload(['fund_id' => $assistance->fund_id]))
            ->assertUnprocessable()->assertJsonValidationErrors('fund_id');
        $this->postJson(route('welfare.assistance.payments.store', $assistance), $this->payload(['idempotency_key' => '']))
            ->assertUnprocessable()->assertJsonValidationErrors('idempotency_key');
        $this->postJson(route('welfare.assistance.payments.store', $assistance), $this->payload())->assertRedirect();
        $payment = WelfareAssistancePayment::sole();
        $show = route('welfare.assistance.payments.show', [$assistance, $payment]);
        $void = route('welfare.assistance.payments.void', [$assistance, $payment]);
        $this->from($show)->post($void, ['void_reason' => ''])->assertSessionHasErrors('void_reason');
        $this->get($show)->assertOk()->assertSee('Please provide a reason for voiding this payment.');
        $other = $this->legacyAssistance($application, 'approved');
        $this->postJson(route('welfare.assistance.payments.void', [$other, $payment]), ['void_reason' => 'Invalid assistance'])->assertNotFound();
        $this->actingAs($this->staff(['welfare.assistance.view']));
        $this->get(route('welfare.assistance.payments.create', $assistance))->assertForbidden();
        $this->postJson(route('welfare.assistance.payments.store', $assistance), $this->payload())->assertForbidden();
        $this->postJson($void, ['void_reason' => 'No permission'])->assertForbidden();
        $this->get($show)->assertOk()->assertDontSee('Void Payment');
        $this->assertDatabaseCount('transactions', 1);
        $this->assertSame('posted', $payment->fresh()->status);
    }

    #[DataProvider('unavailableStates')]
    public function test_payments_reject_unavailable_funds_and_unapproved_assistance(string $case): void
    {
        [, $fund, $assistance] = $this->scenario();
        match ($case) {
            'inactive' => $fund->update(['status' => 'inactive']),
            'archived' => $fund->delete(),
            'pending' => $assistance->update(['status' => 'pending']),
            'rejected' => $assistance->update(['status' => 'rejected']),
            'frequency' => $assistance->update(['frequency' => 'unknown']),
        };
        $this->postJson(route('welfare.assistance.payments.store', $assistance), $this->payload())->assertUnprocessable();
        $this->assertDatabaseCount('transactions', 0);
    }

    public static function unavailableStates(): array { return [['inactive'], ['archived'], ['pending'], ['rejected'], ['frequency']]; }
}
