<?php

namespace App\Domain\Procurement\Services;

use App\Domain\Business\Services\BusinessPaymentService;
use App\Domain\Finance\Funds\Models\Fund;
use App\Domain\Finance\Funds\Services\FundBalanceService;
use App\Domain\Finance\Support\Money;
use App\Domain\Finance\Transactions\Services\TransactionService;
use App\Domain\Procurement\Models\Procurement;
use App\Domain\Procurement\Models\ProcurementMovement;
use App\Domain\Projects\Services\ProjectPaymentService;
use App\Domain\Users\Models\User;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;

class ProcurementPaymentService
{
    public function __construct(private ProcurementWorkflowService $w, private TransactionService $ledger) {}

    public function reconcile(Procurement $p): void
    {
        foreach ($p->movements()->get() as $m) {
            $t = $m->ledger();
            if (! $t || $t->trashed() || (int) $t->fund_id !== (int) $p->fund_id || $t->status !== $m->status || Money::minor($t->amount) !== Money::minor($m->amount) || $t->direction !== ($m->kind === 'payment' ? 'debit' : 'credit') || $t->transaction_date->toDateString() !== $m->payment_date->toDateString()) {
                $this->w->fail('ledger', 'Procurement ledger mismatch. Investigate retained history; do not create a replacement payment.');
            }
            if ($m->source_type === 'project') {
                $this->ledger->assertMatchesProject($t, $m->sourceMovement());
            } elseif ($m->source_type === 'business') {
                $this->ledger->assertMatchesBusiness($t, $m->sourceMovement());
            } elseif ($t->type !== ($m->kind === 'payment' ? 'expense' : 'refund') || $t->source_type !== $m->getMorphClass() || (int) $t->source_id !== $m->id) {
                $this->w->fail('ledger', 'The office purchase ledger source does not match.');
            }
        }
    }

    public function record(Procurement $p, string $kind, array $input, User $a): ProcurementMovement
    {
        abort_unless(in_array($kind, ['pay', 'refund']), 404);
        $this->w->authorize($a, $kind, $p);
        $d = ProcurementRules::validate($kind, $input);
        $payload = Arr::except($d, ['version', 'evidence', 'idempotency_key']);
        $payload['amount'] = Money::decimal(Money::minor($d['amount']));
        $payload['actor_id'] = $a->id;
        $payload['action'] = $kind;
        $payload['file'] = hash_file('sha256', $d['evidence']->getRealPath());
        ksort($payload);
        $hash = hash('sha256', json_encode($payload, JSON_THROW_ON_ERROR));

        return $this->w->files($d, fn ($f) => DB::transaction(function () use ($p, $kind, $d, $a, $hash, $f) {
            $p = $this->w->lock($p);
            $existing = ProcurementMovement::where('idempotency_key', $d['idempotency_key'])->first();
            if ($existing) {
                if ($existing->procurement_id !== $p->id || $existing->payload_hash !== $hash || $existing->status !== 'posted') {
                    $this->w->fail('request', 'This request key belongs to another or voided payment.');
                }
                $this->reconcile($p);
                Storage::disk('private')->delete($f['path']);

                return $existing;
            }
            $this->w->current($p, $d);
            $this->reconcile($p);
            $amount = Money::minor($d['amount']);
            if ($amount < 1) {
                $this->w->fail('amount', 'Enter a positive amount.');
            }
            $original = null;
            $return = null;
            if ($kind === 'pay') {
                app(ProcurementOperationsService::class)->open($p);
                $this->w->independent($p, $a, 'pay');
                $line = $p->lines()->whereHas('invoice', fn ($q) => $q->where('status', 'posted'))->findOrFail($d['invoice_line_id']);
                if ($amount > $p->invoiceBalance($line) || $d['payment_date'] < $line->invoice->invoiced_on->toDateString()) {
                    $this->w->fail('amount', 'Pay no more than the accepted, invoiced balance, on or after the invoice date.');
                }
            } else {
                if ($p->parent_type === 'business') {
                    $this->w->fail('refund', 'Business supplier refunds are outside this phase.');
                }
                $return = $p->returns()->where('status', 'posted')->findOrFail($d['return_id']);
                $original = $p->movements()->where('kind', 'payment')->where('status', 'posted')->where('invoice_line_id', $return->invoice_line_id)->findOrFail($d['original_payment_id']);
                $line = $original->line;
                $refunded = Money::sumMinor($p->movements()->where('original_payment_id', $original->id)->where('status', 'posted')->getQuery());
                if ($amount > Money::minor($original->amount) - $refunded || $amount > app(ProcurementOperationsService::class)->refundRemaining($p, $return) || $d['payment_date'] < max($return->returned_on->toDateString(), $original->payment_date->toDateString())) {
                    $this->w->fail('amount', 'Refund only money actually due from this return and original payment, on or after both dates.');
                }
            }
            $fund = Fund::whereKey($p->fund_id)->lockForUpdate()->firstOrFail();
            if ($kind === 'pay' && $amount > app(FundBalanceService::class)->balanceMinor($fund)) {
                $this->w->fail('amount', 'The original fund has insufficient ledger cash.');
            }
            $m = $p->movements()->create(Arr::only($d, ['amount', 'payment_date', 'payment_method', 'receipt', 'reference', 'notes', 'idempotency_key']) + ['fund_id' => $p->fund_id, 'invoice_line_id' => $line->id, 'return_id' => $return?->id, 'original_payment_id' => $original?->id, 'kind' => $kind === 'pay' ? 'payment' : 'refund', 'recorded_by' => $a->id, 'status' => 'posted', 'payload_hash' => $hash]);
            ProcurementGuard::within($p, function () use ($p, $d, $m, $line, $original, $kind, $a) {
                $parent = $p->parentRecord();
                if ($parent) {
                    $item = $p->item($line->item_key);
                    $data = Arr::only($d, ['amount', 'payment_date', 'payment_method', 'receipt', 'reference', 'notes', 'idempotency_key', 'evidence']);
                    $data['version'] = $parent->version;
                    $data['payee'] = $p->approved()->supplier_snapshot['name'];
                    if ($kind === 'pay') {
                        $data['budget_item_id'] = $item['budget_item_id'];
                    } else {
                        $data['original_payment_id'] = $original->source_id;
                    }
                    $source = $p->parent_type === 'project' ? app(ProjectPaymentService::class)->record($parent, $kind === 'pay' ? 'payment' : 'refund', $data, $a) : app(BusinessPaymentService::class)->record($parent, 'disbursement', $data, $a);
                    $m->update(['source_type' => $p->parent_type, 'source_id' => $source->id]);
                } else {
                    $this->ledger->createForSource($m, ['fund_id' => $p->fund_id, 'type' => $kind === 'pay' ? 'expense' : 'refund', 'direction' => $kind === 'pay' ? 'debit' : 'credit', 'amount' => $m->amount, 'transaction_date' => $d['payment_date'], 'description' => $p->number.' '.$m->kind, 'reference' => $d['reference'] ?? $d['receipt'], 'recorded_by' => $a->id]);
                }
            });
            $this->w->document($p, $a, $kind, $f, $m->id);
            $this->w->event($p, $a, $kind.'_posted', [], ['movement_id' => $m->id, 'amount' => $m->amount]);

            return $m->fresh();
        }, 5));
    }

    public function void(Procurement $p, array $d, User $a): void
    {
        $this->w->authorize($a, 'void', $p);
        $this->w->mutate($p, $d, $a, 'payment_void', function ($p) use ($d, $a) {
            $m = $p->movements()->findOrFail($d['record_id']);
            if ($m->status === 'void') {
                return;
            } $this->reconcile($p);
            if ($m->kind === 'payment' && $p->movements()->where('original_payment_id', $m->id)->where('status', 'posted')->exists()) {
                $this->w->fail('refund', 'Void dependent refunds before voiding their payment.');
            }
            if ($m->kind === 'payment' && $p->returns()->where('invoice_line_id', $m->invoice_line_id)->where('status', 'posted')->exists()) {
                $this->w->fail('return', 'Void dependent credit notes before reversing this payment.');
            }
            $fund = Fund::whereKey($p->fund_id)->lockForUpdate()->firstOrFail();
            if ($m->kind === 'refund' && Money::minor($m->amount) > app(FundBalanceService::class)->balanceMinor($fund)) {
                $this->w->fail('fund', 'This refund reversal would overdraw the fund.');
            }
            ProcurementGuard::within($p, function () use ($p, $m, $d, $a) {
                $parent = $p->parentRecord();
                $source = $m->sourceMovement();
                if ($parent) {
                    $data = ['version' => $parent->version, 'reason' => $d['reason']];
                    if ($p->parent_type === 'project') {
                        app(ProjectPaymentService::class)->void($parent, $source, $data, $a);
                    } else {
                        app(BusinessPaymentService::class)->void($parent, $source, $data, $a);
                    }
                }
                $m->update(['status' => 'void', 'voided_by' => $a->id, 'voided_at' => now(), 'void_reason' => $d['reason']]);
                if ($p->parent_type === 'project') {
                    app(ProcurementGuard::class)->projectBudget($parent,$parent->approved_revision);
                }
                if (! $parent) {
                    $this->ledger->voidForSource($m);
                }
            });
            // Closed orders remain closed; restored obligations are visible and can only be corrected, not repaid automatically.
        });
    }
}
