<?php

namespace Tests\Feature;

use App\Domain\Qard\Models\QardLoan;
use App\Domain\Qard\Models\QardMovement;
use App\Domain\Qard\Services\QardWorkflowService;
use App\Domain\Qard\Services\QardPaymentService;
use App\Domain\Qard\Services\QardMonitoringService;
use App\Domain\Finance\Funds\Services\FundReportingService;
use App\Domain\Finance\Funds\Services\FundBalanceService;
use App\Domain\Finance\Transactions\Services\TransactionService;
use App\Domain\Finance\Transactions\Models\Transaction;
use App\Domain\Users\Models\Role;
use App\Domain\Finance\Support\Money;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\Support\CreatesQardFixtures;
use Tests\TestCase;

class QardWorkflowTest extends TestCase
{
    use RefreshDatabase, CreatesQardFixtures;

    protected function setUp(): void { parent::setUp(); Storage::fake('private'); $this->travelTo(now()->setDate(2026,10,22)->setTime(12,0)); }

    public function test_full_workflow_payments_rounding_and_real_screens(): void
    {
        $actor=$this->qardActor(); $loan=$this->signedLoan($actor); $this->actingAs($actor);
        $this->assertDatabaseCount('transactions',0);
        $this->assertSame('500.00',app(FundReportingService::class)->summary($loan->fund)['allocated']);
        foreach (['qard.index','qard.create','qard.show','qard.agreement','qard.recycling'] as $route) {
            $this->get(route($route,in_array($route,['qard.index','qard.create'])?[]:$loan))->assertOk();
        }
        $rows=$loan->installments()->orderBy('sequence')->get();
        $this->assertSame(['166.66','166.66','166.68'],$rows->pluck('amount')->all());
        $data=$this->qardPayment();
        $this->post(route('qard.disburse',$loan),$data)->assertRedirect()->assertSessionHasNoErrors();
        $this->post(route('qard.disburse',$loan),$data)->assertRedirect()->assertSessionHasNoErrors();
        $this->assertDatabaseCount('transactions',1);
        $this->assertSame('0.00',app(FundReportingService::class)->summary($loan->fund)['allocated']);
        $repay=$this->qardPayment('10.25');
        $this->post(route('qard.repay',$loan),$repay)->assertRedirect()->assertSessionHasNoErrors();
        $this->post(route('qard.repay',$loan),$repay)->assertRedirect()->assertSessionHasNoErrors();
        $this->get(route('qard.repayment',$loan))->assertOk()->assertSee('489.75');
        $this->postJson(route('qard.repay',$loan),$this->qardPayment())->assertUnprocessable()->assertJsonValidationErrors('amount');
        $this->post(route('qard.repay',$loan),$this->qardPayment('489.75'))->assertRedirect()->assertSessionHasNoErrors();
        $this->assertSame('repaid',$loan->fresh()->status);
        $this->assertDatabaseCount('transactions',3);
        $summary=app(FundReportingService::class)->summary($loan->fund);
        $this->assertSame('500.00',$summary['recovered']);
        $this->assertSame('1000.00',$summary['balance']);
        $this->assertSame('0.00',$summary['received']);
        foreach($rows as $row) $this->assertSame(Money::minor($row->amount),$row->paidMinor());
        $this->get(route('qard.show',$loan))->assertOk()->assertSee('Repaid');
    }

    public function test_verification_decisions_and_revision_are_enforced(): void
    {
        $actor=$this->qardActor();$loan=$this->draftLoan($actor);$this->actingAs($actor);
        $this->postJson(route('qard.decide',[$loan,'committee']),['decision'=>'accept','notes'=>'skip'])->assertUnprocessable();
        $this->post(route('qard.submit',$loan))->assertRedirect()->assertSessionHasNoErrors();
        $this->postJson(route('qard.verify',$loan),['notes'=>'No evidence'])->assertUnprocessable();
        $this->post(route('qard.verify',$loan),['notes'=>'Verified','evidence'=>UploadedFile::fake()->create('a.pdf',200,'application/pdf')])->assertRedirect()->assertSessionHasNoErrors();
        $this->postJson(route('qard.decide',[$loan,'implementation']),['decision'=>'accept','notes'=>'self'])->assertUnprocessable()->assertJsonValidationErrors('actor');
        $decider=$this->qardActor();$this->actingAs($decider);
        $this->post(route('qard.decide',[$loan,'implementation']),['decision'=>'accept','notes'=>'yes'])->assertRedirect()->assertSessionHasNoErrors();
        $this->postJson(route('qard.decide',[$loan,'secretary']),['decision'=>'accept','notes'=>'again'])->assertUnprocessable()->assertJsonValidationErrors('actor');
        $this->actingAs($this->qardActor())->post(route('qard.decide',[$loan,'secretary']),['decision'=>'reject','notes'=>'Correct terms'])->assertRedirect()->assertSessionHasNoErrors();
        $this->actingAs($actor)->post(route('qard.submit',$loan))->assertRedirect()->assertSessionHasNoErrors();
        $this->assertSame(2,$loan->fresh()->revision);
        $this->assertSame('submitted',$loan->fresh()->status);
    }

    public function test_revision_retains_old_signatures_and_releases_commitments(): void
    {
        $actor=$this->qardActor();$loan=$this->signedLoan($actor);$this->actingAs($actor);
        $this->putJson(route('qard.update',$loan),['principal'=>'900.00'])->assertUnprocessable();
        $this->post(route('qard.revise',$loan),['reason'=>'New terms requested'])->assertRedirect()->assertSessionHasNoErrors();
        $this->assertSame('draft',$loan->fresh()->status);
        $this->assertSame(2,$loan->fresh()->revision);
        $this->assertSame(1,$loan->agreements()->count());
        $this->assertSame(3,$loan->documents()->count());
        $this->assertSame('0.00',app(FundReportingService::class)->summary($loan->fund)['allocated']);
        $this->postJson(route('qard.disburse',$loan),$this->qardPayment())->assertUnprocessable();
    }

    #[DataProvider('blockedPayments')]
    public function test_disbursement_controls(string $case): void
    {
        $actor=$this->qardActor();$loan=$this->signedLoan($actor);$this->actingAs($actor);
        $data=$this->qardPayment();
        if($case==='unsigned') $loan->update(['status'=>'approved']);
        if($case==='wrong_amount') $data['amount']='499.99';
        if($case==='insufficient') $loan->fund->update(['opening_balance'=>'499.99','prevent_negative_balance'=>false]);
        if($case==='restricted') $loan->fund->update(['purpose'=>'education']);
        if($case==='inactive') $loan->fund->update(['status'=>'inactive']);
        if($case==='missing_file') Storage::disk('private')->delete($loan->documents()->where('kind','borrower')->first()->path);
        if($case==='future_date') $data['payment_date']='2027-01-01';
        if($case==='old_date') $data['payment_date']='2026-10-21';
        $this->postJson(route('qard.disburse',$loan),$data)->assertUnprocessable();
        $this->assertDatabaseCount('transactions',0);
    }
    public static function blockedPayments():array {return array_map(fn($s)=>[$s],['unsigned','wrong_amount','insufficient','restricted','inactive','missing_file','future_date','old_date']);}

    public function test_permissions_and_private_document_ownership(): void
    {
        $actor=$this->qardActor();$loan=$this->signedLoan($actor);$doc=$loan->documents()->first();
        $this->get(route('qard.documents.show',[$loan,$doc]))->assertRedirect(route('login'));
        $this->actingAs($this->staff([]))->get(route('qard.index'))->assertForbidden();
        $manager=$this->staff(['qard.view','qard.manage']);
        $this->actingAs($manager)->postJson(route('qard.disburse',$loan),$this->qardPayment())->assertForbidden();
        $this->postJson(route('qard.decide',[$loan,'committee']),['decision'=>'accept','notes'=>'no'])->assertForbidden();
        $this->actingAs($actor)->get(route('qard.documents.show',[$loan,$doc]))->assertOk();
        $other=$this->draftLoan($actor);
        $this->get(route('qard.documents.show',[$other,$doc]))->assertNotFound();
        $this->postJson(route('qard.sign',$other),['borrower_name'=>'B','foundation_name'=>'F','borrower_file'=>UploadedFile::fake()->create('bad.exe',1),'foundation_file'=>UploadedFile::fake()->create('b.pdf',1,'application/pdf')])->assertUnprocessable();
    }

    public function test_voids_reopen_balances_and_keep_ledger_and_allocation_history():void
    {
        $actor=$this->qardActor();$loan=$this->activeLoan($actor);$service=app(QardPaymentService::class);
        $repayment=$service->record($loan,'repayment',$this->qardPayment(),$actor);
        $disbursement=$loan->movements()->where('kind','disbursement')->first();
        $this->actingAs($actor)->postJson(route('qard.void',[$loan,$disbursement]),['reason'=>'bad order'])->assertUnprocessable();
        foreach([1,2] as $i)$this->post(route('qard.void',[$loan,$repayment]),['reason'=>'Correction'])->assertRedirect()->assertSessionHasNoErrors();
        $this->assertSame('active',$loan->fresh()->status);
        $this->assertSame(50000,$service->outstandingMinor($loan));
        $this->assertSame(3,$repayment->allocations()->count());
        foreach($loan->installments as $row)$this->assertSame(0,$row->paidMinor());
        $this->post(route('qard.void',[$loan,$disbursement]),['reason'=>'Correction'])->assertRedirect()->assertSessionHasNoErrors();
        $this->assertSame('signed',$loan->fresh()->status);
        $this->assertSame('1000.00',app(FundBalanceService::class)->balance($loan->fund));
        $this->assertSame(0,Transaction::where('status','posted')->count());
        $this->assertDatabaseCount('transactions',2);
    }

    public function test_repayment_void_cannot_overdraw_even_if_overdraft_flag_disabled():void
    {
        $actor=$this->qardActor();$loan=$this->activeLoan($actor);
        $repayment=app(QardPaymentService::class)->record($loan,'repayment',$this->qardPayment('10.25'),$actor);
        $loan->fund->update(['opening_balance'=>'500.00','prevent_negative_balance'=>false]);
        Transaction::create(['transaction_number'=>'OTHER','fund_id'=>$loan->fund_id,'type'=>'expense','amount'=>'10.25','direction'=>'debit','transaction_date'=>now(),'status'=>'posted','description'=>'Spent repayment']);
        $this->actingAs($actor)->postJson(route('qard.void',[$loan,$repayment]),['reason'=>'correct'])->assertUnprocessable();
        $this->assertSame('posted',$repayment->fresh()->status);
    }

    public function test_month_end_schedule_preserves_anchor_and_weekly_dates():void
    {
        $actor=$this->qardActor();$loan=$this->draftLoan($actor);
        $loan->update(['first_due_date'=>'2027-01-31']);
        $service=app(QardWorkflowService::class);
        $this->assertSame(['2027-01-31','2027-02-28','2027-03-31'],array_column($service->schedule($loan->fresh()),'due_date'));
        $loan->update(['frequency'=>'weekly']);
        $this->assertSame(['2027-01-31','2027-02-07','2027-02-14'],array_column($service->schedule($loan->fresh()),'due_date'));
    }

    public function test_reminders_deduplicate_resolve_and_do_not_pay():void
    {
        $actor=$this->qardActor();$loan=$this->activeLoan($actor);
        $this->travelTo($loan->first_due_date->copy()->addDay()->setTime(12,0));
        $this->artisan('qard:refresh-reminders')->assertSuccessful();
        $this->artisan('qard:refresh-reminders')->assertSuccessful();
        $this->assertDatabaseCount('qard_reminders',1);
        $this->assertStringContainsString('166.66',$loan->reminder->message);
        $this->assertDatabaseCount('transactions',1);
        app(QardPaymentService::class)->record($loan,'repayment',$this->qardPayment(),$actor);
        $this->assertNotNull($loan->reminder()->first()->resolved_at);
    }

    public function test_mismatched_retry_and_ledger_mutation_are_blocked():void
    {
        $actor=$this->qardActor();$loan=$this->signedLoan($actor);$data=$this->qardPayment();
        $movement=app(QardPaymentService::class)->record($loan,'disbursement',$data,$actor);
        $this->actingAs($actor)->postJson(route('qard.disburse',$loan),array_merge($data,['amount'=>'1.00']))->assertUnprocessable();
        $this->expectException(ValidationException::class);
        app(TransactionService::class)->voidForSource($movement);
    }

    public function test_role_seeder_is_additive_and_separates_money_from_management():void
    {
        $this->seed(\Database\Seeders\DatabaseSeeder::class);
        $this->seed(\Database\Seeders\QardRoleSeeder::class);
        $officer=Role::where('slug','qard-officer')->sole()->permissions()->pluck('slug')->all();
        $this->assertContains('qard.manage',$officer);
        $this->assertNotContains('qard.disburse',$officer);
        $this->assertContains('qard.repay',Role::where('slug','treasurer')->sole()->permissions()->pluck('slug')->all());
    }

    public function test_application_http_validation_eligibility_and_editing():void
    {
        $actor=$this->qardActor();$draft=$this->draftLoan($actor);$this->actingAs($actor);
        $data=$draft->only(['beneficiary_id','fund_id','purpose','principal','frequency','installment_count']);
        $data['first_due_date']=$draft->first_due_date->toDateString();
        $this->postJson(route('qard.store'),array_merge($data,['principal'=>'10.255']))->assertUnprocessable();
        $this->post(route('qard.store'),$data)->assertRedirect()->assertSessionHasNoErrors();
        $this->assertDatabaseCount('qard_loans',2);
        $this->get(route('qard.edit',$draft))->assertOk();
        $this->put(route('qard.update',$draft),array_merge($data,['purpose'=>'Updated need']))->assertRedirect()->assertSessionHasNoErrors();
        $this->assertSame('Updated need',$draft->fresh()->purpose);
        $draft->beneficiary->update(['verification_status'=>'pending']);
        $this->postJson(route('qard.store'),$data)->assertUnprocessable()->assertJsonValidationErrors('beneficiary_id');
    }

    public function test_reconciliation_includes_qard_and_reports_corruption_without_reposting():void
    {
        $actor=$this->qardActor();$loan=$this->activeLoan($actor);
        app(QardPaymentService::class)->record($loan,'repayment',$this->qardPayment('10.25'),$actor);
        $this->artisan('finance:sync-transactions')->assertSuccessful();
        $this->assertDatabaseCount('transactions',2);
        Transaction::where('type','recovery')->update(['amount'=>'10.24']);
        $this->artisan('finance:sync-transactions')->assertFailed();
        $this->assertDatabaseCount('transactions',2);
    }

    public function test_fund_views_link_qard_and_outstanding_loans_prevent_archiving():void
    {
        $actor=$this->qardActor();
        $role=$actor->roles()->first();
        foreach(['finance.view','finance.funds.manage','welfare.assistance.view'] as $slug){
            $p=\App\Domain\Users\Models\Permission::firstOrCreate(['slug'=>$slug],['name'=>$slug,'module'=>'finance']);
            $role->permissions()->syncWithoutDetaching([$p->id]);
        }
        $loan=$this->signedLoan($actor);$this->actingAs($actor);
        $this->get(route('finance.funds.show',$loan->fund))->assertOk()->assertSee($loan->number);
        $loan->fund->update(['opening_balance'=>'500.00']);
        app(QardPaymentService::class)->record($loan,'disbursement',$this->qardPayment(),$actor);
        $this->get(route('finance.funds.show',$loan->fund))->assertOk()->assertSee($loan->number);
        $this->get(route('finance.transactions.show',Transaction::sole()))->assertOk()->assertSee(route('qard.show',$loan));
        $this->expectException(ValidationException::class);
        app(\App\Domain\Finance\Funds\Services\FundService::class)->archive($loan->fund,$actor);
    }

    public function test_current_signed_terms_cannot_be_silently_changed():void
    {
        $actor=$this->qardActor();$loan=$this->signedLoan($actor);
        $loan->update(['principal'=>'600.00']);
        $this->actingAs($actor)->postJson(route('qard.disburse',$loan),$this->qardPayment('600.00'))->assertUnprocessable()->assertJsonValidationErrors('agreement');
        $this->assertDatabaseCount('transactions',0);
    }

    public function test_followups_create_and_clear_due_reminder_without_financial_changes():void
    {
        $actor=$this->qardActor();$loan=$this->activeLoan($actor);$this->actingAs($actor);
        $this->post(route('qard.followup',$loan),['notes'=>'Call borrower','next_followup_date'=>now('Asia/Karachi')->toDateString()])->assertRedirect()->assertSessionHasNoErrors();
        $this->assertNull($loan->reminder()->first()->resolved_at);
        $this->post(route('qard.followup',$loan),['notes'=>'Contact completed','next_followup_date'=>now()->addWeek()->toDateString()])->assertRedirect()->assertSessionHasNoErrors();
        $this->assertNotNull($loan->reminder()->first()->resolved_at);
        $this->assertDatabaseCount('transactions',1);
        $this->get(route('qard.show',$loan))->assertOk()->assertSee('Contact completed');
    }
}
