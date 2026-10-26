<?php

namespace Tests\Feature;

use App\Domain\Business\Models\BusinessMovement;
use App\Domain\Finance\Expenses\Models\Expense;
use App\Domain\Finance\Funds\Services\FundPurposeService;
use App\Domain\Finance\Funds\Services\FundService;
use App\Domain\Finance\Transactions\Models\Transaction;
use App\Domain\Finance\Transactions\Services\TransactionService;
use App\Domain\Projects\Models\ProjectMovement;
use App\Domain\Projects\Services\ProjectDonationPolicy;
use App\Domain\Projects\Services\ProjectPaymentService;
use App\Domain\Projects\Services\ProjectWorkflowService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Tests\Support\CreatesProjectFixtures;
use Tests\TestCase;

class ProjectIntegrityTest extends TestCase
{
    use CreatesProjectFixtures,RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        Storage::fake('private');
    }

    private function blocked(callable $fn): void
    {
        try {
            $fn();
            $this->fail('Expected rejection');
        } catch (ValidationException $e) {
            $this->assertNotEmpty($e->errors());
        }
    }

    public function test_insufficient_cash_rolls_back_payment_documents_and_ledger(): void
    {
        $a = $this->projectActor();
        $p = $this->approveProject($this->draftProject($a), $a);
        $p->fund->update(['opening_balance' => '10.00', 'prevent_negative_balance' => false]);
        $count = $p->documents()->count();
        $files = count(Storage::disk('private')->allFiles());
        $this->blocked(fn () => app(ProjectPaymentService::class)->record($p, 'payment', $this->projectPayment($p, '10.25'), $this->projectActor()));
        $this->assertSame(0, Transaction::count());
        $this->assertSame(0, ProjectMovement::count());
        $this->assertSame($count, $p->documents()->count());
        $this->assertCount($files, Storage::disk('private')->allFiles());
    }

    public function test_refund_void_rejects_budget_excess_after_amendment(): void
    {
        $a = $this->projectActor();
        $payer = $this->projectActor();
        $p = $this->approveProject($this->draftProject($a), $a);
        $s = app(ProjectPaymentService::class);
        $w = app(ProjectWorkflowService::class);
        $m = $s->record($p, 'payment', $this->projectPayment($p), $payer);
        $r = $s->record($p, 'refund', $this->refundData($p, $m), $payer);
        $w->transition($p, 'amend', $this->projectVersion($p, ['reason' => 'Reduce budget']), $a);
        $p = $p->fresh();
        $w->save($p, $this->projectRevisionData($p, '400'), $a);
        $p = $this->approveProject($p, $a);
        $this->blocked(fn () => $s->void($p, $r, $this->projectVersion($p, ['reason' => 'Not received']), $payer));
        $this->assertSame('posted', $r->fresh()->status);
    }

    public function test_refund_void_rejects_overdrawing_shared_fund(): void
    {
        $a = $this->projectActor();
        $payer = $this->projectActor();
        $p = $this->approveProject($this->draftProject($a), $a);
        $p->fund->update(['opening_balance' => '500.00']);
        $s = app(ProjectPaymentService::class);
        $m = $s->record($p, 'payment', $this->projectPayment($p), $payer);
        $r = $s->record($p, 'refund', $this->refundData($p, $m), $payer);
        $other = $this->approveProject($this->draftProject($a), $a);
        $other->update(['fund_id' => $p->fund_id]);
        $s->record($other, 'payment', $this->projectPayment($other, '100'), $payer);
        $this->blocked(fn () => $s->void($p, $r, $this->projectVersion($p, ['reason' => 'Wrong refund']), $payer));
        $this->assertSame('posted', $r->fresh()->transaction->status);
    }

    public function test_price_checks_exception_and_estimate_evidence_are_required(): void
    {
        $a = $this->projectActor();
        $d = $this->projectData($a);
        $d['items'][0]['kind'] = 'purchase';
        $d['items'][0]['estimate'] = null;
        $w = app(ProjectWorkflowService::class);
        $p = $w->save(null, $d, $a);
        $w->submit($p, $this->projectVersion($p), $a);
        $w->verify($p, $this->projectVersion($p, ['notes' => 'Good', 'evidence' => $this->evidence()]), $a);
        foreach (['coordinator', 'secretary'] as $stage) {
            $w->decide($p, $stage, $this->projectVersion($p, ['decision' => 'accept', 'notes' => 'Good']), $this->projectActor());
        }
        $resolution = $this->committeeResolution();
        $resolution['evidence'] = $resolution['resolution_file'];
        $this->blocked(fn () => $w->decide($p, 'committee', $this->projectVersion($p, ['decision' => 'accept', 'notes' => 'Approved'] + $resolution), $this->projectActor()));
        $w->decide($p, 'committee', $this->projectVersion($p, ['decision' => 'reject', 'notes' => 'Needs purchase exception']), $this->projectActor());
        $w->transition($p, 'amend', $this->projectVersion($p, ['reason' => 'Emergency single supplier']), $a);
        $p = $p->fresh();
        $d['version'] = $p->version;
        $d['items'][0]['exception_reason'] = 'Only supplier in emergency';
        $d['items'][0]['budget_item_id'] = $p->budgets()->first()->budget_item_id;
        $w->save($p, $d, $a);
        $w->submit($p, $this->projectVersion($p), $a);
        $w->verify($p, $this->projectVersion($p, ['notes' => 'Confirmed emergency', 'evidence' => $this->evidence()]), $a);
        foreach (['coordinator', 'secretary'] as $stage) {
            $w->decide($p, $stage, $this->projectVersion($p, ['decision' => 'accept', 'notes' => 'Good']), $this->projectActor());
        }
        $w->decide($p, 'committee', $this->projectVersion($p, ['decision' => 'accept', 'notes' => 'Exception accepted', 'approve_purchase_exceptions' => 1] + $resolution), $this->projectActor());
        $this->assertSame('approved', $p->fresh()->status);
    }

    public function test_named_project_policy_binding_and_central_debit_guard(): void
    {
        $a = $this->projectActor();
        $w = app(ProjectWorkflowService::class);
        $data = $this->projectData($a);
        $data['fund_id'] = null;
        $p = $w->save(null, $data, $a);
        $w->dedicatedFund($p, $this->projectVersion($p, ['name' => 'Exclusive']), $a);
        $fund = $p->fresh()->fund;
        $this->blocked(fn () => app(FundService::class)->update($fund, ['project_id' => null], $a));
        $this->blocked(fn () => app(FundService::class)->update($fund, ['type' => 'general'], $a));
        $policy = app(ProjectDonationPolicy::class);
        $this->blocked(fn () => $policy->validate(['fund_id' => $fund->id, 'designation' => 'unrestricted', 'purpose' => 'project']));
        $this->blocked(fn () => $policy->validate(['fund_id' => null, 'designation' => 'restricted', 'purpose' => 'project']));
        $expense = Expense::create(['expense_number' => 'EXP-TEST', 'fund_id' => $fund->id, 'category' => 'admin', 'amount' => '1.00', 'expense_date' => now()->toDateString(), 'description' => 'Attempt', 'payee_name' => 'Payee', 'status' => 'approved', 'recorded_by' => $a->id]);
        $this->blocked(fn () => app(TransactionService::class)->createForSource($expense, ['fund_id' => $fund->id, 'type' => 'expense', 'direction' => 'debit', 'amount' => '1.00', 'transaction_date' => now()->toDateString(), 'description' => 'Attempt']));
        $welfare = $this->legacyAssistance($this->signedApplication($a), 'approved');
        $this->assertFalse(app(FundPurposeService::class)->allowsWelfare($fund, $welfare));
        $business = $this->signedBusiness($this->businessActor());
        $movement = new BusinessMovement(['kind' => 'disbursement', 'fund_id' => $fund->id, 'amount' => '1.00', 'status' => 'posted', 'payment_date' => now()->toDateString(), 'payment_method' => 'cash', 'payee' => 'Payee', 'receipt' => 'R1', 'idempotency_key' => (string) Str::uuid(), 'payload_hash' => str_repeat('a', 64), 'recorded_by' => $a->id]);
        $this->blocked(fn () => app(FundPurposeService::class)->assertAllowsDebit($fund, $movement));
        $this->assertSame(0, Transaction::count());
    }

    public function test_legacy_project_expenses_cannot_create_new_spending_but_posted_history_can_mark_paid(): void
    {
        $a = $this->projectActor();
        $p = $this->draftProject($a);
        $f = $p->fund;
        $expense = Expense::create(['expense_number' => 'EXP-LEGACY', 'fund_id' => $f->id, 'category' => 'project', 'amount' => '10.00', 'expense_date' => now()->toDateString(), 'description' => 'Historical', 'payee_name' => 'Payee', 'status' => 'approved', 'recorded_by' => $a->id]);
        $data = ['fund_id' => $f->id, 'type' => 'expense', 'direction' => 'debit', 'amount' => '10.00', 'transaction_date' => now()->toDateString(), 'description' => 'Historical'];
        $this->blocked(fn () => app(TransactionService::class)->createForSource($expense, $data));
        $txn = Transaction::create($data + ['transaction_number' => 'TXN-OLD', 'source_type' => $expense->getMorphClass(), 'source_id' => $expense->id, 'status' => 'posted', 'recorded_by' => $a->id]);
        $this->actingAs($a)->post(route('finance.expenses.paid', $expense))->assertRedirect();
        $this->assertSame('paid', $expense->fresh()->status);
        $this->assertSame(1, Transaction::count());
        $this->blocked(fn () => app(TransactionService::class)->syncForSource($expense, array_replace($data, ['amount' => '20.00'])));
    }

    public function test_reconciliation_detects_missing_movement_without_recreating_it(): void
    {
        $a = $this->projectActor();
        $p = $this->approveProject($this->draftProject($a), $a);
        $s = app(ProjectPaymentService::class);
        $m = $s->record($p, 'payment', $this->projectPayment($p, '10.25'), $this->projectActor());
        $this->artisan('finance:sync-transactions')->assertSuccessful();
        DB::table('transactions')->where('id', $m->transaction->id)->delete();
        $this->artisan('finance:sync-transactions')->assertFailed();
        $this->assertSame(0, Transaction::count());
        $this->blocked(fn () => $s->record($p, 'payment', $this->projectPayment($p, '1'), $this->projectActor()));
    }

    public function test_archived_history_retains_late_refunds_and_private_evidence(): void
    {
        $a = $this->projectActor();
        $payer = $this->projectActor();
        $p = $this->approveProject($this->draftProject($a), $a);
        $w = app(ProjectWorkflowService::class);
        $s = app(ProjectPaymentService::class);
        $m = $s->record($p, 'payment', $this->projectPayment($p), $payer);
        $w->transition($p, 'cancel', $this->projectVersion($p, ['reason' => 'Work stopped', 'evidence' => $this->evidence()]), $this->projectActor());
        $w->transition($p, 'archive', $this->projectVersion($p, ['reason' => 'Keep history']), $a);
        $p = $p->fresh();
        $r = $s->record($p, 'refund', $this->refundData($p, $m), $payer);
        $this->assertSame('cancelled', $p->fresh()->status);
        $this->assertSame('100.00', $r->amount);
        $this->actingAs($a)->get(route('projects.show', $p))->assertOk()->assertSee('Archived');
        $this->get(route('projects.documents.show', [$p, $p->documents()->first()]))->assertOk();
    }

    public function test_multi_item_limits_and_refund_cannot_increase_another_item(): void
    {
        $a = $this->projectActor();
        $d = $this->projectData($a);
        $d['items'][0]['amount'] = '300';
        $d['items'][] = array_replace($d['items'][0], ['description' => 'Second item', 'amount' => '200']);
        $p = $this->approveProject(app(ProjectWorkflowService::class)->save(null, $d, $a), $a);
        $payer = $this->projectActor();
        $s = app(ProjectPaymentService::class);
        $m = $s->record($p, 'payment', $this->projectPayment($p,'300'), $payer);
        $s->record($p,'refund',$this->refundData($p,$m),$payer);
        $payment = $this->projectPayment($p,'300');
        $payment['budget_item_id'] = $p->budgets()->latest('id')->first()->budget_item_id;
        $this->blocked(fn () => $s->record($p,'payment',$payment,$payer));
        $s->record($p,'payment',$this->projectPayment($p,'100'),$payer);
        $this->assertSame(30000,$p->fresh()->netMinor($m->budget_item_id));
    }
}
