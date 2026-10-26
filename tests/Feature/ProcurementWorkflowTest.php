<?php

namespace Tests\Feature;

use App\Domain\Finance\Funds\Services\FundReportingService;
use App\Domain\Finance\Transactions\Models\Transaction;
use App\Domain\Procurement\Services\ProcurementOperationsService;
use App\Domain\Procurement\Services\ProcurementPaymentService;
use App\Domain\Procurement\Services\ProcurementWorkflowService;
use App\Domain\Users\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Tests\Support\CreatesProcurementFixtures;
use Tests\TestCase;

class ProcurementWorkflowTest extends TestCase
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

    public function test_office_approval_partial_payments_and_exact_ledger(): void
    {
        $a = $this->procurementActor();
        $p = $this->purchaseApprove($this->purchaseDraft($a), $a);
        $this->assertSame(0, Transaction::count());
        $this->assertSame('500.00', app(FundReportingService::class)->summary($p->fund)['allocated']);
        $l = $this->purchaseInvoice($p, $a);
        $payer = $this->procurementActor();
        $s = app(ProcurementPaymentService::class);
        $d = $this->purchasePayment($p, $l, '10.25');
        $m = $s->record($p, 'pay', $d, $payer);
        $this->assertSame($m->id, $s->record($p, 'pay', $d, $payer)->id);
        $this->assertSame(48975, $p->invoiceBalance($l));
        $this->denied(fn () => $s->record($p, 'pay', $this->purchasePayment($p, $l, '500.00'), $payer));
        $s->record($p, 'pay', $this->purchasePayment($p, $l, '489.75'), $payer);
        $this->assertSame(2, Transaction::count());
        $this->assertSame(0, $p->invoiceBalance($l));
        app(ProcurementWorkflowService::class)->transition($p, 'close', $this->purchaseVersion($p, ['reason' => 'All goods and bills settled']), $this->procurementActor());
        $this->assertSame('closed', $p->fresh()->status);
    }

    public function test_partial_receipt_and_independence(): void
    {
        $a = $this->procurementActor();
        $p = $this->purchaseApprove($this->purchaseDraft($a), $a);
        $l = $this->purchaseInvoice($p, $a, '1');
        $s = app(ProcurementPaymentService::class);
        $this->denied(fn () => $s->record($p, 'pay', $this->purchasePayment($p, $l, '250.01'), $this->procurementActor()));
        $this->denied(fn () => $s->record($p, 'pay', $this->purchasePayment($p, $l, '250.00'), $a));
        $receiver = User::findOrFail($p->receipts()->first()->actor_id);
        $this->denied(fn () => $s->record($p, 'pay', $this->purchasePayment($p, $l, '250.00'), $receiver));
        $s->record($p, 'pay', $this->purchasePayment($p, $l, '250.00'), $this->procurementActor());
        $this->assertSame(1, Transaction::count());
    }

    public function test_returns_need_actual_cash_and_cannot_exceed_original_payment(): void
    {
        $a = $this->procurementActor();
        $p = $this->purchaseApprove($this->purchaseDraft($a), $a);
        $l = $this->purchaseInvoice($p, $a);
        $s = app(ProcurementPaymentService::class);
        $payer = $this->procurementActor();
        $m = $s->record($p, 'pay', $this->purchasePayment($p, $l), $payer);
        app(ProcurementOperationsService::class)->returnGoods($p, $this->purchaseVersion($p, ['receipt_id' => $l->receipt_id, 'invoice_line_id' => $l->id, 'quantity' => '0.4', 'credit_reference' => 'CN-1', 'returned_on' => now()->toDateString(), 'refund_due_on' => now()->addDay()->toDateString(), 'reason' => 'Damaged goods returned', 'evidence' => $this->evidence()]), $payer);
        $this->assertSame(1, Transaction::count());
        $return = $p->returns()->first();
        $this->assertSame(10000, (int) $return->refund_due_minor);
        $d = $this->purchasePayment($p, $l, '100.00');
        unset($d['invoice_line_id']);
        $d += ['return_id' => $return->id, 'original_payment_id' => $m->id];
        $s->record($p, 'refund', $d, $payer);
        $this->assertSame(2, Transaction::count());
        $this->assertSame(10000, $p->refundedMinor());
        $d['version'] = $p->fresh()->version;
        $d['idempotency_key'] = (string) Str::uuid();
        $this->denied(fn () => $s->record($p, 'refund', $d, $payer));
    }

    public function test_stage_skips_self_approval_and_missing_quotes_fail(): void
    {
        $a = $this->procurementActor();
        $p = $this->purchaseDraft($a);
        $w = app(ProcurementWorkflowService::class);
        $this->denied(fn () => $w->transition($p, 'submit', $this->purchaseVersion($p), $a));
        $this->purchaseQuotes($p, $a);
        $w->transition($p, 'submit', $this->purchaseVersion($p), $a);
        $this->denied(fn () => $w->decide($p,'committee',$this->purchaseVersion($p,['decision' => 'accept', 'notes' => 'Skip']),$this->procurementActor()));
        $this->denied(fn () => $w->decide($p,'coordinator',$this->purchaseVersion($p,['decision' => 'accept', 'notes' => 'Self']),$a));
    }
}
