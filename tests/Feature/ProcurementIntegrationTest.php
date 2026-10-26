<?php

namespace Tests\Feature;

use App\Domain\Business\Services\BusinessPaymentService;
use App\Domain\Business\Services\BusinessWorkflowService;
use App\Domain\Finance\Funds\Services\FundReportingService;
use App\Domain\Finance\Transactions\Models\Transaction;
use App\Domain\Procurement\Services\ProcurementGuard;
use App\Domain\Procurement\Services\ProcurementMath;
use App\Domain\Procurement\Services\ProcurementOperationsService;
use App\Domain\Procurement\Services\ProcurementPaymentService;
use App\Domain\Procurement\Services\ProcurementReportService;
use App\Domain\Procurement\Services\ProcurementWorkflowService;
use App\Domain\Projects\Services\ProjectPaymentService;
use App\Domain\Projects\Services\ProjectWorkflowService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use PhpOffice\PhpSpreadsheet\IOFactory;
use Tests\Support\CreatesProcurementFixtures;
use Tests\TestCase;

class ProcurementIntegrationTest extends TestCase
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

    private function goodsProject($a)
    {
        $data = $this->projectData($a);
        $data['items'][0]['kind'] = 'purchase';
        $data['items'][0]['price_checks'] = [['supplier' => 'Worker One', 'amount' => '500.00', 'reference' => 'Q1'], ['supplier' => 'Other', 'amount' => '550.00', 'reference' => 'Q2']];

        return $this->approveProject(app(ProjectWorkflowService::class)->save(null, $data, $a), $a);
    }

    public function test_project_purchase_uses_one_parent_movement_and_no_duplicate_commitment(): void
    {
        $a = $this->procurementActor();
        $parent = $this->goodsProject($a);
        $item = $parent->budgets($parent->approved_revision)->first();
        $this->denied(fn () => app(ProjectPaymentService::class)->record($parent, 'payment', $this->projectPayment($parent), $this->procurementActor()));
        $before = app(FundReportingService::class)->summary($parent->fund)['allocated'];
        $p = $this->purchaseApprove($this->linkedPurchase($a, 'project', $parent, $item->budget_item_id, 'Worker One'), $a);
        $this->assertSame($before, app(FundReportingService::class)->summary($parent->fund)['allocated']);
        $this->denied(fn () => $this->linkedPurchase($a, 'project', $parent, $item->budget_item_id, 'Worker One'));
        $l = $this->purchaseInvoice($p, $a);
        $payer = $this->procurementActor();
        $m = app(ProcurementPaymentService::class)->record($p, 'pay', $this->purchasePayment($p, $l, '10.25'), $payer);
        $this->assertSame('project', $m->source_type);
        $this->assertSame(1, $parent->movements()->count());
        $this->assertSame(1, Transaction::count());
        $this->assertSame(1025, $parent->netMinor());
        $this->denied(fn () => app(ProjectPaymentService::class)->void($parent, $m->sourceMovement(), $this->projectVersion($parent, ['reason' => 'Attempt bypass']), $payer));
        app(ProcurementPaymentService::class)->reconcile($p);
        $doc = $parent->documents()->where('movement_id', $m->source_id)->firstOrFail();
        $this->actingAs($this->staff(['projects.view']))->get(route('projects.documents.show', [$parent, $doc]))->assertForbidden();
        $this->actingAs($a)->get(route('projects.documents.show', [$parent, $doc]))->assertOk();
    }

    public function test_business_purchase_requires_signed_terms_and_keeps_handover_separate(): void
    {
        $a = $this->procurementActor();
        $d = $this->businessData($a);
        $d['items'][0] = ['description' => 'Machine', 'cost' => '500.00', 'applicant_amount' => '0.00', 'release_method' => 'asset', 'supplier' => 'Machine supplier', 'price_checks' => [['supplier' => 'Machine supplier', 'amount' => '500.00', 'reference' => 'Q1'], ['supplier' => 'Other', 'amount' => '550.00', 'reference' => 'Q2']]];
        $d['price_check_file'] = $this->evidence();
        $parent = $this->approveBusiness(app(BusinessWorkflowService::class)->save(null, $d, $a), $a);
        $p = $this->purchaseApprove($this->linkedPurchase($a, 'business', $parent, $parent->currentBudget()->first()->id, 'Machine supplier'), $a);
        $l = $this->purchaseInvoice($p, $a);
        $payer = $this->procurementActor();
        $this->denied(fn () => app(ProcurementPaymentService::class)->record($p, 'pay', $this->purchasePayment($p, $l), $payer));
        $w = app(BusinessWorkflowService::class);
        $w->sign($parent, $this->versioned($parent, $this->businessSignatures() + ['contribution_confirmed' => '1', 'terms_hash' => $w->hash($w->terms($parent))]), $a);
        $m = app(ProcurementPaymentService::class)->record($p, 'pay', $this->purchasePayment($p, $l), $payer);
        $this->assertSame('business', $m->source_type);
        $this->assertNull($m->sourceMovement()->handover);
        $this->assertSame(1, Transaction::where('type', 'business_disbursement')->count());
        $this->denied(fn () => app(BusinessPaymentService::class)->finalize($parent, $this->finalization($parent), $payer));
        $doc = $parent->documents()->where('movement_id', $m->source_id)->firstOrFail();
        $this->actingAs($this->staff(['business.view']))->get(route('business.applications.documents.show', [$parent, $doc]))->assertForbidden();
        $this->actingAs($a)->get(route('business.applications.documents.show', [$parent, $doc]))->assertOk();
    }

    public function test_project_return_posts_one_refund_to_original_fund_and_retains_closed_spending_block(): void
    {
        $a = $this->procurementActor();
        $parent = $this->goodsProject($a);
        $item = $parent->budgets($parent->approved_revision)->first();
        $p = $this->purchaseApprove($this->linkedPurchase($a, 'project', $parent, $item->budget_item_id, 'Worker One'), $a);
        $line = $this->purchaseInvoice($p, $a);
        $payer = $this->procurementActor();
        $payments = app(ProcurementPaymentService::class);
        $payment = $payments->record($p, 'pay', $this->purchasePayment($p, $line), $payer);
        app(ProcurementWorkflowService::class)->transition($p, 'close', $this->purchaseVersion($p, ['reason' => 'Original order settled']), $this->procurementActor());
        app(ProcurementOperationsService::class)->returnGoods($p, $this->purchaseVersion($p, ['receipt_id' => $line->receipt_id, 'invoice_line_id' => $line->id, 'quantity' => '0.4', 'credit_reference' => 'PROJECT-CN', 'returned_on' => now()->toDateString(), 'refund_due_on' => now()->addDay()->toDateString(), 'reason' => 'Returned damaged goods', 'evidence' => $this->evidence()]), $payer);
        $return = $p->returns()->firstOrFail();
        $input = $this->purchasePayment($p, $line, '100.00');
        unset($input['invoice_line_id']);
        $this->denied(fn () => app(ProcurementGuard::class)->assertNoOpen('project', $parent->id));
        $report = app(ProcurementReportService::class)->rows($a, [])->first();
        $this->assertSame('100.00', $report['refund_due']);
        $this->assertSame('Accepted in full', $report['delivery_status']);
        $input += ['return_id' => $return->id, 'original_payment_id' => $payment->id];
        $refund = $payments->record($p, 'refund', $input, $payer);
        $this->assertSame($refund->id, $payments->record($p, 'refund', $input, $payer)->id);
        $this->assertSame(2, Transaction::count());
        $this->assertSame($parent->fund_id, $refund->ledger()->fund_id);
        $this->assertSame('refund', $refund->ledger()->type);
        $this->assertSame(40000, $parent->netMinor($item->budget_item_id));
        $this->assertSame(0, $p->fresh()->reservation());
        $this->assertSame('closed', $p->fresh()->status);
        $this->denied(fn () => $payments->record($p, 'pay', $this->purchasePayment($p, $line, '100.00'), $payer));
        $this->denied(fn () => app(ProjectPaymentService::class)->record($parent, 'refund', $this->projectVersion($parent, ['original_payment_id' => $payment->source_id, 'amount' => '1.00', 'payment_date' => now()->toDateString(), 'payment_method' => 'cash', 'receipt' => 'BYPASS', 'idempotency_key' => (string) Str::uuid(), 'evidence' => $this->evidence()]), $payer));
        $payments->reconcile($p);
    }

    public function test_pages_documents_reports_and_actual_xlsx_totals(): void
    {
        $a = $this->procurementActor();
        $p = $this->purchaseApprove($this->purchaseDraft($a), $a);
        $l = $this->purchaseInvoice($p, $a);
        app(ProcurementPaymentService::class)->record($p, 'pay', $this->purchasePayment($p, $l, '10.25'), $this->procurementActor());
        $this->actingAs($a);
        foreach (['index', 'create', 'settings', 'reports'] as $name) {
            $this->get(route('operations.procurement.'.$name))->assertOk();
        }
        $this->get(route('operations.procurement.show', $p))->assertOk()->assertSee('489.75');
        $this->get(route('operations.procurement.order', $p))->assertOk()->assertSee('Purchase order');
        $doc = $p->documents()->first();
        $this->get(route('operations.procurement.documents', [$p, $doc]))->assertOk();
        $other = $this->purchaseDraft($a);
        $this->get(route('operations.procurement.documents', [$other, $doc]))->assertNotFound();
        $this->get(route('operations.procurement.reports', ['to' => now()->toDateString()]))->assertOk()->assertSee('10.25');
        $res = $this->get(route('operations.procurement.reports', ['format' => 'xlsx']))->assertOk();
        $path = tempnam(sys_get_temp_dir(), 'procurement-xlsx-');
        try {
            file_put_contents($path, $res->streamedContent());
            $book = IOFactory::load($path);
            $this->assertSame('10.25', (string) $book->getActiveSheet()->getCell('J2')->getValue());
            $this->assertSame('489.75', (string) $book->getActiveSheet()->getCell('I2')->getValue());
            $book->disconnectWorksheets();
        } finally {
            unlink($path);
        }
        $this->actingAs($this->staff([]))->get(route('operations.procurement.documents', [$p, $doc]))->assertForbidden();
    }

    public function test_amendment_freeze_resume_and_invoice_receipt_dependencies(): void
    {
        $a = $this->procurementActor();
        $p = $this->purchaseApprove($this->purchaseDraft($a), $a);
        $l = $this->purchaseInvoice($p, $a);
        $w = app(ProcurementWorkflowService::class);
        $s = app(ProcurementPaymentService::class);
        $w->transition($p, 'amend', $this->purchaseVersion($p, ['reason' => 'Consider a quantity change']), $a);
        $this->assertSame(50000, $p->fresh()->reservation());
        $this->denied(fn () => $s->record($p, 'pay', $this->purchasePayment($p, $l), $this->procurementActor()));
        $w->transition($p, 'withdraw', $this->purchaseVersion($p, ['reason' => 'Keep original order']), $a);
        $w->transition($p, 'resume', $this->purchaseVersion($p, ['reason' => 'Resume previous approved order']), $a);
        $m = $s->record($p, 'pay', $this->purchasePayment($p, $l), $this->procurementActor());
        $this->denied(fn () => app(ProcurementOperationsService::class)->correct($p, $this->purchaseVersion($p, ['record_type' => 'invoice', 'record_id' => $l->invoice_id, 'reason' => 'Wrong invoice']), $a));
        $this->denied(fn () => app(ProcurementOperationsService::class)->correct($p, $this->purchaseVersion($p, ['record_type' => 'receipt', 'record_id' => $l->receipt_id, 'reason' => 'Wrong receipt']), $a));
        app(ProcurementOperationsService::class)->correct($p, $this->purchaseVersion($p, ['record_type' => 'movement', 'record_id' => $m->id, 'reason' => 'Recording error']), $a);
        $this->assertSame('void', $m->fresh()->status);
        app(ProcurementOperationsService::class)->correct($p, $this->purchaseVersion($p, ['record_type' => 'movement', 'record_id' => $m->id, 'reason' => 'Repeated void']), $a);
        $this->assertSame(1, Transaction::count());
    }

    public function test_reminders_and_fractional_quantity_rounding(): void
    {
        $this->assertSame(1,ProcurementMath::cost(5,100));
        $a = $this->procurementActor();
        $p = $this->purchaseApprove($this->purchaseDraft($a),$a);
        $l = $this->purchaseInvoice($p,$a,'1');
        $this->travel(10)->days();
        app(ProcurementReportService::class)->refresh($p);
        $this->assertTrue($p->fresh()->reminder->delivery_due);
        $this->assertTrue($p->fresh()->reminder->invoice_due);
        $this->assertSame(0,Transaction::count());
    }
}
