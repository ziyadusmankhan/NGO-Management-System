<?php

namespace Tests\Feature;

use App\Domain\Finance\Transactions\Models\Transaction;
use App\Domain\Procurement\Models\Procurement;
use App\Domain\Procurement\Models\ProcurementPolicy;
use App\Domain\Procurement\Services\ProcurementOperationsService;
use App\Domain\Procurement\Services\ProcurementPaymentService;
use App\Domain\Procurement\Services\ProcurementWorkflowService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\ValidationException;
use Tests\Support\CreatesProcurementFixtures;
use Tests\TestCase;

class ProcurementIntegrityTest extends TestCase
{
    use CreatesProcurementFixtures,RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        Storage::fake('private');
        $this->travelTo(now()->setTime(12, 0));
    }

    private function denied(callable $f): void
    {
        try {
            $f();
            $this->fail('Expected rejection');
        } catch (ValidationException $e) {
            $this->assertNotEmpty($e->errors());
        }
    }

    public function test_browser_office_draft_accepts_empty_hidden_parent_and_item_key(): void
    {
        $actor = $this->procurementActor();
        $existing = $this->purchaseDraft($actor);
        $this->actingAs($actor)->post(route('operations.procurement.store'), [
            'title' => 'Browser purchase', 'description' => 'Office supplies', 'parent_type' => 'office', 'parent_id' => '',
            'fund_id' => $existing->fund_id, 'supplier_id' => $existing->proposal()->supplier_id, 'delivery_location' => 'Office',
            'required_on' => now()->addDay()->toDateString(), 'exception_reason' => '',
            'items' => [['key' => '', 'description' => 'Chair', 'unit' => 'each', 'quantity' => '2', 'unit_price' => '250.00']],
        ])->assertSessionHasNoErrors()->assertRedirect();
        $this->assertDatabaseHas('procurement_revisions', ['title' => 'Browser purchase']);
    }

    public function test_cancelled_request_cannot_be_revived_by_a_pending_approval(): void
    {
        $actor = $this->procurementActor();
        $p = $this->purchaseDraft($actor);
        $this->purchaseQuotes($p, $actor);
        $w = app(ProcurementWorkflowService::class);
        $w->transition($p, 'submit', $this->purchaseVersion($p), $actor);
        $w->transition($p, 'cancel', $this->purchaseVersion($p, ['reason' => 'No longer required']), $this->procurementActor());
        $this->denied(fn () => $w->decide($p, 'coordinator', $this->purchaseVersion($p, ['decision' => 'accept', 'notes' => 'Old pending approval']), $this->procurementActor()));
        $this->assertSame('cancelled', $p->fresh()->status);
    }

    public function test_approved_unissued_order_can_be_issued_after_explicit_amendment_resume(): void
    {
        $actor = $this->procurementActor();
        $p = $this->purchaseApprove($this->purchaseDraft($actor), $actor);
        $p->approved()->update(['issued_at' => null]);
        $p->update(['status' => 'approved']);
        $w = app(ProcurementWorkflowService::class);
        $w->transition($p, 'amend', $this->purchaseVersion($p, ['reason' => 'Consider delivery change']), $actor);
        $w->transition($p, 'withdraw', $this->purchaseVersion($p, ['reason' => 'Keep original terms']), $actor);
        $this->denied(fn () => $w->transition($p, 'issue', $this->purchaseVersion($p), $actor));
        $w->transition($p, 'resume', $this->purchaseVersion($p, ['reason' => 'Explicitly resume approved order']), $actor);
        $w->transition($p, 'issue', $this->purchaseVersion($p), $actor);
        $this->assertSame(1, $p->fresh()->approved_revision);
        $this->assertSame('ordered', $p->fresh()->status);
    }

    public function test_duplicate_supplier_invoice_and_excess_quantity_are_rejected_atomically(): void
    {
        $a = $this->procurementActor();
        $p = $this->purchaseApprove($this->purchaseDraft($a), $a);
        $l = $this->purchaseInvoice($p, $a, '1');
        $ops = app(ProcurementOperationsService::class);
        $data = ['reference' => ' inv-1 ', 'invoiced_on' => now()->toDateString(), 'due_on' => now()->addDay()->toDateString(), 'lines' => [['receipt_id' => $l->receipt_id, 'quantity' => '1']], 'evidence' => $this->evidence()];
        $files = count(Storage::disk('private')->allFiles());
        $this->denied(fn () => $ops->invoice($p, $this->purchaseVersion($p, $data), $a));
        $this->assertSame($files, count(Storage::disk('private')->allFiles()));
        $data['reference'] = 'INV-2';
        $this->denied(fn () => $ops->invoice($p, $this->purchaseVersion($p, $data), $a));
        $this->assertSame(1, $p->invoices()->count());
    }

    public function test_policy_snapshot_and_explicit_committee_exception(): void
    {
        $a = $this->procurementActor();
        $p = $this->purchaseDraft($a);
        $w = app(ProcurementWorkflowService::class);
        $this->purchaseQuotes($p, $a);
        ProcurementPolicy::create(['quote_count' => 3]);
        $this->denied(fn () => $w->transition($p, 'submit', $this->purchaseVersion($p), $a));
        $p->proposal()->update(['exception_reason' => 'Only two local suppliers can deliver']);
        $w->transition($p, 'submit', $this->purchaseVersion($p), $a);
        ProcurementPolicy::create(['quote_count' => 1]);
        $this->assertSame(3, $p->fresh()->proposal()->quote_count);
        foreach (['coordinator', 'secretary'] as $stage) {
            $w->decide($p, $stage, $this->purchaseVersion($p, ['decision' => 'accept', 'notes' => 'Checked']), $this->procurementActor());
        }
        $d = $this->committeeResolution() + ['decision' => 'accept', 'notes' => 'Accept exception'];
        $d['evidence'] = $d['resolution_file'];
        unset($d['resolution_file']);
        $approver = $this->procurementActor();
        $this->denied(fn () => $w->decide($p, 'committee', $this->purchaseVersion($p, $d), $approver));
        $d['accept_exception'] = '1';
        $w->decide($p, 'committee', $this->purchaseVersion($p, $d), $approver);
        $this->assertSame('approved', $p->fresh()->status);
    }

    public function test_office_restricted_fund_and_payment_without_permission_are_blocked(): void
    {
        $a = $this->procurementActor();
        $p = $this->purchaseApprove($this->purchaseDraft($a), $a);
        $l = $this->purchaseInvoice($p, $a);
        $this->actingAs($this->staff(['procurement.view', 'procurement.manage']))->post(route('operations.procurement.pay', $p), $this->purchasePayment($p, $l))->assertForbidden();
        $p->fund->update(['type' => 'restricted', 'purpose' => 'education']);
        $this->denied(fn () => app(ProcurementPaymentService::class)->record($p, 'pay', $this->purchasePayment($p, $l), $this->procurementActor()));
        $this->assertSame(0, Transaction::count());
    }

    public function test_late_return_refund_void_cash_control_and_no_automatic_reopening(): void
    {
        $a = $this->procurementActor();
        $p = $this->purchaseApprove($this->purchaseDraft($a), $a);
        $l = $this->purchaseInvoice($p, $a);
        $payer = $this->procurementActor();
        $s = app(ProcurementPaymentService::class);
        $ops = app(ProcurementOperationsService::class);
        $m = $s->record($p, 'pay', $this->purchasePayment($p, $l), $payer);
        app(ProcurementWorkflowService::class)->transition($p, 'close', $this->purchaseVersion($p, ['reason' => 'Settled']), $this->procurementActor());
        app(ProcurementWorkflowService::class)->transition($p, 'archive', $this->purchaseVersion($p, ['reason' => 'Keep history']), $a);
        $ops->returnGoods($p, $this->purchaseVersion($p, ['receipt_id' => $l->receipt_id, 'invoice_line_id' => $l->id, 'quantity' => '0.4', 'credit_reference' => 'CN-1', 'returned_on' => now()->toDateString(), 'refund_due_on' => now()->addDay()->toDateString(), 'reason' => 'Defect discovered', 'evidence' => $this->evidence()]), $payer);
        $return = $p->returns()->first();
        $d = $this->purchasePayment($p, $l, '100.00');
        unset($d['invoice_line_id']);
        $d += ['original_payment_id' => $m->id, 'return_id' => $return->id];
        $refund = $s->record($p, 'refund', $d, $payer);
        $p->fund->update(['opening_balance' => '450.00']); // Simulate other authorized spending exhausting available cash.
        $this->denied(fn () => $ops->correct($p, $this->purchaseVersion($p, ['record_type' => 'movement', 'record_id' => $refund->id, 'reason' => 'Correction']), $a));
        $this->assertSame('posted', $refund->fresh()->status);
        $this->assertSame('closed', Procurement::withTrashed()->find($p->id)->status);
    }

    public function test_stale_forms_and_missing_ledger_are_detected_without_recreating_payments(): void
    {
        $a = $this->procurementActor();
        $p = $this->purchaseApprove($this->purchaseDraft($a), $a);
        $l = $this->purchaseInvoice($p, $a);
        $payer = $this->procurementActor();
        $s = app(ProcurementPaymentService::class);
        $stale = $this->purchasePayment($p, $l, '10.25');
        $m = $s->record($p, 'pay', $this->purchasePayment($p, $l, '10.25'), $payer);
        $this->denied(fn () => $s->record($p, 'pay', $stale, $payer));
        DB::table('transactions')->where('id', $m->transaction->id)->delete();
        $this->denied(fn () => $s->reconcile($p));
        $this->assertSame(1, $p->movements()->count());
        $this->assertSame(0, Transaction::count());
    }

    public function test_unpaid_return_reduces_invoice_without_creating_credit_cash(): void
    {
        $a = $this->procurementActor();
        $p = $this->purchaseApprove($this->purchaseDraft($a), $a);
        $l = $this->purchaseInvoice($p, $a);
        app(ProcurementOperationsService::class)->returnGoods($p, $this->purchaseVersion($p, ['receipt_id' => $l->receipt_id, 'invoice_line_id' => $l->id, 'quantity' => '0.4', 'credit_reference' => 'CN-U', 'returned_on' => now()->toDateString(), 'reason' => 'Unpaid goods returned', 'evidence' => $this->evidence()]), $this->procurementActor());
        $this->assertSame(40000, $p->invoiceBalance($l));
        $this->assertSame(0, (int) $p->returns()->first()->refund_due_minor);
        $this->assertSame(0, Transaction::count());
    }

    public function test_additive_migration_preserves_users_and_marks_only_previously_approved_items(): void
    {
        $a = $this->procurementActor();
        $project = $this->approveProject($this->draftProject($a), $a);
        $draft = $this->draftProject($a);
        $business = $this->signedBusiness($a);
        $password = $a->getRawOriginal('password');
        $migration = require base_path('database/migrations/2026_10_26_000001_create_procurement_domain.php');
        $migration->down();
        $fund = $project->fund;
        $id = DB::table('expenses')->insertGetId(['expense_number' => 'EXP-PRE-P9', 'fund_id' => $fund->id, 'category' => 'admin', 'description' => 'Historical goods', 'amount' => '10.00', 'expense_date' => now()->toDateString(), 'payee_name' => 'Supplier', 'status' => 'approved', 'created_at' => now(), 'updated_at' => now()]);
        $migration->up();
        $this->assertSame($password, $a->fresh()->getRawOriginal('password'));
        $this->assertTrue((bool) $project->budgets($project->approved_revision)->first()->legacy_direct_purchase);
        $this->assertFalse((bool) $draft->budgets()->first()->legacy_direct_purchase);
        $this->assertTrue((bool) $business->currentBudget()->first()->legacy_direct_purchase);
        $this->assertTrue((bool) DB::table('expenses')->where('id',$id)->value('legacy_posted'));
        $this->assertSame(0,Procurement::count());
        $this->assertSame(2,ProcurementPolicy::first()->quote_count);
    }
}
