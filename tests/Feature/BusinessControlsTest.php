<?php

namespace Tests\Feature;

use App\Domain\Business\Models\BusinessApplication;
use App\Domain\Business\Services\BusinessMonitoringService;
use App\Domain\Business\Services\BusinessPaymentService;
use App\Domain\Business\Services\BusinessReportService;
use App\Domain\Business\Services\BusinessWorkflowService;
use App\Domain\Finance\Funds\Services\FundPurposeService;
use App\Domain\Finance\Funds\Services\FundReportingService;
use App\Domain\Finance\Transactions\Models\Transaction;
use App\Domain\Finance\Transactions\Services\TransactionService;
use App\Domain\Users\Models\Permission;
use App\Domain\Users\Models\Role;
use Database\Seeders\BusinessRoleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\ValidationException;
use PhpOffice\PhpSpreadsheet\IOFactory;
use Tests\Support\CreatesBusinessFixtures;
use Tests\TestCase;

class BusinessControlsTest extends TestCase
{
    use CreatesBusinessFixtures, RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        Storage::fake('private');
        $this->travelTo(now()->setDate(2026, 10, 23)->setTime(12, 0));
    }

    private function assertBlocked(callable $operation, string $field): void
    {
        try {
            $operation();
            $this->fail('Expected rejection: '.$field);
        } catch (ValidationException $e) {
            $this->assertArrayHasKey($field, $e->errors());
        }
    }

    private function committeeStage(BusinessApplication $a, $officer): void
    {
        $w = app(BusinessWorkflowService::class);
        $w->submit($a, $this->versioned($a), $officer);
        $w->verify($a, $this->versioned($a, ['notes' => 'Verified', 'evidence' => $this->evidence()]), $officer);
        foreach (['implementation', 'secretary'] as $stage) {
            $w->decide($a, $stage, $this->versioned($a, ['decision' => 'accept', 'notes' => 'Accept']), $this->businessActor());
        }
    }

    public function test_suppliers_need_two_distinct_price_checks_or_explicit_exception_and_active_consents(): void
    {
        $officer = $this->businessActor();
        $w = app(BusinessWorkflowService::class);
        $data = $this->businessData($officer);
        $data['items'][0]['release_method'] = 'supplier';
        $data['items'][0]['supplier'] = 'Shop A';
        $data['items'][0]['exception_reason'] = 'Only local supplier';
        $a = $w->save(null, $data, $officer);
        $this->committeeStage($a, $officer);
        $approver = $this->businessActor();
        $decision = ['decision' => 'accept', 'notes' => 'Resolution approved'] + $this->committeeResolution();
        $this->assertBlocked(fn () => $w->decide($a, 'committee', $this->versioned($a, $decision), $approver), 'price_checks');
        $decision['approve_purchase_exceptions'] = '1';
        $ids = $decision['consenting_member_ids'];
        $decision['consenting_member_ids'] = [$ids[0]];
        $this->assertBlocked(fn () => $w->decide($a, 'committee', $this->versioned($a, $decision), $approver), 'consenting_member_ids');
        $decision['consenting_member_ids'] = $ids;
        DB::table('committee_members')->where('member_id', $ids[1])->update(['status' => 'inactive']);
        $this->assertBlocked(fn () => $w->decide($a, 'committee', $this->versioned($a, $decision), $approver), 'consenting_member_ids');
        DB::table('committee_members')->where('member_id', $ids[1])->update(['status' => 'active']);
        $w->decide($a, 'committee', $this->versioned($a, $decision), $approver);
        $this->assertCount(1, $a->reviews()->where('stage', 'committee')->first()->purchase_exceptions);
        $this->assertSame('approved', $a->fresh()->status);
    }

    public function test_asset_purchases_require_handover_and_payer_independence(): void
    {
        $officer = $this->businessActor();
        $w = app(BusinessWorkflowService::class);
        $p = app(BusinessPaymentService::class);
        $m = app(BusinessMonitoringService::class);
        $data = $this->businessData($officer, 'grant');
        $data['items'][0]['release_method'] = 'asset';
        $data['items'][0]['supplier'] = 'Supplier A';
        $data['items'][0]['price_checks'] = [['supplier' => 'Supplier A', 'amount' => '600.00', 'reference' => 'Q1'], ['supplier' => 'Supplier B', 'amount' => '650.00', 'reference' => 'Q2']];
        $data['price_check_file'] = $this->evidence();
        $a = $w->save(null, $data, $officer);
        $this->approveBusiness($a, $officer);
        $w->sign($a, $this->versioned($a, $this->businessSignatures() + ['contribution_confirmed' => 1, 'terms_hash' => $w->hash($w->terms($a->fresh()))]), $officer);
        $payer = $this->businessActor();
        $payment = $this->businessPayment($a, 'disbursement');
        $this->assertBlocked(fn () => $p->record($a, 'disbursement', $payment, $payer), 'payee');
        $payment['payee'] = 'Supplier A';
        $movement = $p->record($a, 'disbursement', $payment, $payer);
        $this->assertBlocked(fn () => $p->finalize($a->fresh(), $this->finalization($a), $payer), 'handover');
        $this->assertBlocked(fn () => $m->record($a, 'start', $this->versioned($a, ['started_on' => '2026-10-23', 'evidence' => $this->evidence()]), $officer), 'handover');
        $m->record($a, 'handover', $this->versioned($a, ['movement_id' => $movement->id, 'recipient' => 'Borrower', 'items' => 'Stock received', 'handed_over_on' => '2026-10-23', 'evidence' => $this->evidence()]), $officer);
        $m->record($a, 'start', $this->versioned($a, ['started_on' => '2026-10-23', 'evidence' => $this->evidence()]), $officer);
        $p->finalize($a->fresh(), $this->finalization($a), $payer);
        $this->assertSame('running', $a->fresh()->operating_status);
        $this->assertDatabaseCount('business_handovers', 1);
    }

    public function test_month_end_final_principal_and_overdue_reminders_do_not_post_money(): void
    {
        $this->travelTo(now()->setDate(2026, 1, 31));
        $officer = $this->businessActor();
        $a = $this->fundedBusiness($officer);
        $this->assertSame(['2026-02-28', '2026-03-31', '2026-04-30'], $a->installments()->orderBy('sequence')->get()->map(fn ($i) => $i->due_date->toDateString())->all());
        $p = app(BusinessPaymentService::class);
        $payer = $this->businessActor();
        $p->record($a, 'repayment', $this->businessPayment($a, 'repayment', '10.25'), $payer);
        $this->assertSame(1025, $a->installments()->orderBy('sequence')->first()->paidMinor());
        $this->travelTo(now()->setDate(2026, 3, 1));
        $this->artisan('business:refresh-reminders')->assertSuccessful();
        $this->assertStringContainsString('156.41', $a->fresh()->reminder->message);
        $this->assertDatabaseCount('transactions', 2);
        $this->assertSame(15641, app(BusinessReportService::class)->report([])['totals']['overdue']);
    }

    public function test_stale_forms_changed_payment_keys_and_signed_statements_are_rejected(): void
    {
        $officer = $this->businessActor();
        $a = $this->signedBusiness($officer);
        $p = app(BusinessPaymentService::class);
        $payer = $this->businessActor();
        $payment = $this->businessPayment($a, 'disbursement', '100.00');
        $p->record($a, 'disbursement', $payment, $payer);
        $changed = $payment;
        $changed['amount'] = '200.00';
        $this->assertBlocked(fn () => $p->record($a, 'disbursement', $changed, $payer), 'idempotency_key');
        $stale = $this->businessPayment($a, 'disbursement', '100.00');
        $stale['version'] = $payment['version'];
        $this->assertBlocked(fn () => $p->record($a, 'disbursement', $stale, $payer), 'version');
        $statement = $this->finalization($a);
        $p->record($a, 'disbursement', $this->businessPayment($a, 'disbursement', '100.00'), $payer);
        $statement['version'] = $a->fresh()->version;
        $this->assertBlocked(fn () => $p->finalize($a->fresh(), $statement, $payer), 'terms_hash');
        $this->assertDatabaseCount('business_agreements', 1);
        $this->assertDatabaseCount('business_installments', 0);
        $p->finalize($a->fresh(), $this->finalization($a), $payer);
        $this->assertSame(['66.66', '66.66', '66.68'], $a->installments()->pluck('amount')->all());
        $this->assertSame('0.00', app(FundReportingService::class)->summary($a->fund)['allocated']);
    }

    public function test_business_failure_does_not_write_off_debt_and_closure_needs_another_actor(): void
    {
        $officer = $this->businessActor();
        $a = $this->fundedBusiness($officer);
        $w = app(BusinessWorkflowService::class);
        $m = app(BusinessMonitoringService::class);
        $m->record($a, 'monitor', $this->versioned($a, ['review_date' => '2026-10-23', 'condition' => 'stopped', 'findings' => 'Business could not start', 'actions' => 'Recover full principal', 'final_report' => 1]), $officer);
        $closer = $this->businessActor();
        $this->assertBlocked(fn () => $w->transition($a, 'close', $this->versioned($a, ['reason' => 'Close']), $closer), 'status');
        app(BusinessPaymentService::class)->record($a, 'repayment', $this->businessPayment($a, 'repayment'), $closer);
        $this->assertBlocked(fn () => $w->transition($a, 'close', $this->versioned($a, ['reason' => 'Close']), $officer), 'closure');
        $w->transition($a, 'close', $this->versioned($a, ['reason' => 'Settled and reviewed']), $closer);
        $w->transition($a, 'archive', $this->versioned($a, ['reason' => 'Retain closed history']), $officer);
        $this->assertSoftDeleted($a);
        $this->assertDatabaseCount('transactions', 2);
        $this->actingAs($closer)->get(route('business.applications.show', $a))->assertOk()->assertSee('Archived case');
    }

    public function test_revision_requires_new_approval_and_signed_copies(): void
    {
        $officer = $this->businessActor();
        $a = $this->signedBusiness($officer);
        $w = app(BusinessWorkflowService::class);
        $w->transition($a, 'revise', $this->versioned($a, ['reason' => 'Correct business terms']), $officer);
        $this->assertSame(2, $a->fresh()->revision);
        $this->assertDatabaseCount('business_agreements', 1);
        $this->assertBlocked(fn () => app(BusinessPaymentService::class)->record($a, 'disbursement', $this->businessPayment($a->fresh(), 'disbursement'), $this->businessActor()), 'status');
        $this->approveBusiness($a->fresh(), $officer);
        $this->assertSame('approved', $a->fresh()->status);
        $this->assertBlocked(fn () => app(BusinessPaymentService::class)->record($a, 'disbursement', $this->businessPayment($a->fresh(), 'disbursement'), $this->businessActor()), 'status');
    }

    public function test_missing_or_mismatched_ledger_blocks_repayment_and_void(): void
    {
        $officer = $this->businessActor();
        $a = $this->fundedBusiness($officer);
        $p = app(BusinessPaymentService::class);
        $movement = $a->movements()->first();
        DB::table('transactions')->where('source_type', $movement->getMorphClass())->where('source_id', $movement->id)->update(['amount' => '499.00']);
        $this->assertBlocked(fn () => $p->record($a, 'repayment', $this->businessPayment($a, 'repayment'), $this->businessActor()), 'transaction');
        $this->assertBlocked(fn () => $p->void($a, $movement, $this->versioned($a, ['reason' => 'Correct']), $officer), 'transaction');
        $this->assertSame('posted', $movement->fresh()->status);
    }

    public function test_void_overdraft_and_direct_ledger_reversal_are_blocked(): void
    {
        $officer = $this->businessActor();
        $a = $this->fundedBusiness($officer);
        $p = app(BusinessPaymentService::class);
        $repayment = $p->record($a, 'repayment', $this->businessPayment($a, 'repayment'), $this->businessActor());
        $this->assertBlocked(fn () => app(TransactionService::class)->voidForSource($repayment), 'transaction');
        $this->assertBlocked(fn () => $p->void($a, $a->movements()->where('kind', 'disbursement')->first(), $this->versioned($a, ['reason' => 'Reverse']), $officer), 'movement');
        // Simulate other legitimate spending after recovered capital has been reused.
        Transaction::create(['transaction_number' => 'TXN-OTHER', 'fund_id' => $a->fund_id, 'type' => 'expense', 'direction' => 'debit', 'amount' => '600.00', 'status' => 'posted', 'transaction_date' => '2026-10-23', 'description' => 'Other spending']);
        $this->assertBlocked(fn () => $p->void($a, $repayment, $this->versioned($a, ['reason' => 'Reverse']), $officer), 'amount');
        $this->assertSame('posted', $repayment->fresh()->status);
    }

    public function test_qard_funds_cannot_pay_grants_and_generic_expenses_cannot_use_business_funds(): void
    {
        $officer = $this->businessActor();
        $a = $this->draftBusiness($officer);
        $fund = $a->fund;
        $s = app(FundPurposeService::class);
        $this->assertFalse($s->allowsExpense($fund, 'project'));
        $this->assertTrue($s->allowsBusiness($fund, 'grant'));
        $fund->update(['purpose' => 'qard']);
        $this->assertFalse($s->allowsBusiness($fund, 'grant'));
        $this->assertTrue($s->allowsBusiness($fund, 'repayable'));
        $fund->update(['purpose' => 'education']);
        $this->assertFalse($s->allowsBusiness($fund, 'repayable'));
        $this->assertBlocked(fn () => app(BusinessWorkflowService::class)->submit($a, $this->versioned($a), $officer), 'fund_id');
    }

    public function test_permission_seeding_is_additive_and_portfolios_are_permission_gated(): void
    {
        $role = Role::create(['slug' => 'treasurer', 'name' => 'Treasurer', 'status' => 'active']);
        $permission = Permission::create(['slug' => 'custom.keep', 'name' => 'Keep', 'module' => 'custom']);
        $role->permissions()->attach($permission);
        $this->seed(BusinessRoleSeeder::class);
        $this->seed(BusinessRoleSeeder::class);
        $this->assertTrue($role->permissions()->where('slug', 'custom.keep')->exists());
        $this->assertFalse($role->permissions()->where('slug', 'business.approve')->exists());
        $officer = $this->businessActor();
        $a = $this->fundedBusiness($officer);
        $this->actingAs($officer)->get(route('qard.index'))->assertOk()->assertSee($a->number);
        $this->actingAs($this->staff(['qard.view']))->get(route('qard.index'))->assertOk()->assertDontSee($a->number);
        $this->assertDatabaseCount('qard_loans', 0);
        $this->actingAs($this->staff(['business.view']))->get(route('business.reports', ['format' => 'xlsx']))->assertForbidden();
    }

    public function test_document_scope_and_malformed_payloads_are_rejected(): void
    {
        $officer = $this->businessActor();
        $a = $this->signedBusiness($officer);
        $other = $this->draftBusiness($officer);
        $doc = $a->documents()->first();
        $this->actingAs($officer)->get(route('business.applications.documents.show', [$other, $doc]))->assertNotFound();
        $data = $this->businessData($officer);
        $data['items'] = 'not an array';
        $this->postJson(route('business.applications.store'), $data)->assertUnprocessable()->assertJsonValidationErrors('items');
        $data['items'] = [['description' => ['invalid']]];
        $this->postJson(route('business.applications.store'), $data)->assertUnprocessable();
    }

    public function test_reports_include_end_date_money_and_excel_escapes_formula_names(): void
    {
        $officer = $this->businessActor();
        $a = $this->fundedBusiness($officer);
        $a->update(['name' => '=HYPERLINK("https://example.invalid")']);
        app(BusinessPaymentService::class)->record($a, 'repayment', $this->businessPayment($a, 'repayment', '10.25'), $this->businessActor());
        $report = app(BusinessReportService::class)->report(['from' => '2026-10-23', 'to' => '2026-10-23']);
        $this->assertSame(50000, $report['totals']['period_released']);
        $this->assertSame(1025, $report['totals']['period_recovered']);
        $this->assertSame(48975, $report['totals']['outstanding']);
        $this->assertSame('510.25', $report['capital']->first()['summary']['balance']);
        $this->assertSame(0, app(BusinessReportService::class)->report(['from' => '2026-10-01', 'to' => '2026-10-22'])['totals']['released']);
        $response = $this->actingAs($officer)->get(route('business.reports', ['format' => 'xlsx', 'from' => '2026-10-23', 'to' => '2026-10-23']))->assertOk();
        $path = tempnam(sys_get_temp_dir(), 'business-xlsx-');
        try {
            file_put_contents($path, $response->streamedContent());
            $book = IOFactory::load($path);
            $sheet = $book->getSheetByName('Small Business');
            $this->assertSame('s', $sheet->getCell('B2')->getDataType());
            $this->assertSame($a->name, $sheet->getCell('B2')->getValue());
            $this->assertSame('489.75', $sheet->getCell('I2')->getValue());
            $this->assertNotNull($book->getSheetByName('Current pooled capital'));
            $book->disconnectWorksheets();
        } finally {
            unlink($path);
        }
    }

    public function test_signed_evidence_and_consent_members_block_ineligible_payments(): void
    {
        $officer = $this->businessActor();
        $a = $this->signedBusiness($officer);
        $p = app(BusinessPaymentService::class);
        $payer = $this->businessActor();
        $consent = $a->reviews()->where('stage', 'committee')->first()->consenting_members[0];
        DB::table('members')->where('id', $consent['id'])->update(['user_id' => $payer->id]);
        $this->assertBlocked(fn () => $p->record($a, 'disbursement', $this->businessPayment($a, 'disbursement'), $payer), 'actor');
        $doc = $a->agreements()->where('kind', 'initial')->first()->documents()->first();
        Storage::disk('private')->delete($doc->path);
        $this->assertBlocked(fn () => $p->record($a,'disbursement',$this->businessPayment($a,'disbursement'),$this->businessActor()),'agreement');
        $this->assertDatabaseCount('transactions',0);
    }

    public function test_view_only_account_has_no_action_forms_and_stage_skips_fail(): void
    {
        $officer = $this->businessActor();
        $a = $this->draftBusiness($officer);
        $this->actingAs($this->staff(['business.view']));
        $this->get(route('business.applications.show',$a))->assertOk()->assertDontSee('Submit application')->assertDontSee('Edit draft');
        $this->postJson(route('business.applications.submit',$a),$this->versioned($a))->assertForbidden();
        $this->actingAs($officer)->postJson(route('business.applications.decide',[$a, 'committee']),$this->versioned($a,['decision' => 'accept', 'notes' => 'Skip']))->assertUnprocessable()->assertJsonValidationErrors('status');
        $this->get(route('business.applications.show',$a))->assertOk()->assertSee('Submit application');
    }
}
