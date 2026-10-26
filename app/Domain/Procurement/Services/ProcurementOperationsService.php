<?php

namespace App\Domain\Procurement\Services;

use App\Domain\Finance\Support\Money;
use App\Domain\Procurement\Models\Procurement;
use App\Domain\Procurement\Models\ProcurementInvoice;
use App\Domain\Users\Models\User;

class ProcurementOperationsService
{
    public function __construct(private ProcurementWorkflowService $w) {}

    public function open(Procurement $p): void
    {
        if ($p->trashed() || $p->frozen || $p->status !== 'ordered' || ! $p->approved()?->issued_at) {
            $this->w->fail('status', 'Use an issued, open order with no pending amendment.');
        }
        $this->w->eligible($p, $p->approved()->items, $p->id, $p->approved()->supplier_snapshot['name']);
    }

    public function receive(Procurement $p, array $input, User $a): void
    {
        $this->w->authorize($a, 'receive', $p);
        $d = ProcurementRules::validate('receive', $input);
        $this->w->mutate($p, $d, $a, 'receipt_recorded', function ($p, $f) use ($d, $a) {
            $this->open($p);
            $this->w->independent($p, $a, 'receive');
            $i = $p->item($d['item_key']);
            $accepted = ProcurementMath::quantity($d['accepted']);
            $rejected = ProcurementMath::quantity($d['rejected']);
            $previous = (int) $p->receipts()->where('item_key', $d['item_key'])->where('status', 'posted')->sum('accepted_milli');
            if (! $i || $accepted + $rejected < 1 || $accepted + $previous > $i['quantity_milli']) {
                $this->w->fail('quantity', 'Accepted deliveries cannot exceed the approved quantity. Returned goods require a separate replacement order.');
            }
            if ($d['received_on'] < $p->approved()->issued_at->timezone('Asia/Karachi')->toDateString()) {
                $this->w->fail('received_on', 'Receipt cannot precede order issue.');
            }
            $receipt = $p->receipts()->create(['revision' => $p->approved_revision, 'item_key' => $i['key'], 'received_on' => $d['received_on'], 'reference' => $d['reference'], 'accepted_milli' => $accepted, 'rejected_milli' => $rejected, 'notes' => $d['notes'], 'actor_id' => $a->id, 'status' => 'posted']);
            $this->w->document($p, $a, 'receipt', $f, $receipt->id);
        });
    }

    public function invoice(Procurement $p, array $input, User $a): void
    {
        $this->w->authorize($a, 'invoices', $p);
        $d = ProcurementRules::validate('invoice', $input);
        $this->w->mutate($p, $d, $a, 'invoice_recorded', function ($p, $f) use ($d, $a) {
            $this->open($p);
            $reference = mb_strtolower(trim($d['reference']));
            $supplier = $p->approved()->supplier_id;
            if (ProcurementInvoice::where('supplier_id', $supplier)->where('reference_key', $reference)->exists()) {
                $this->w->fail('reference', 'This supplier invoice reference has already been recorded, including retained voids.');
            }
            $invoice = $p->invoices()->create(['supplier_id' => $supplier, 'reference' => $d['reference'], 'reference_key' => $reference, 'invoiced_on' => $d['invoiced_on'], 'due_on' => $d['due_on'], 'actor_id' => $a->id, 'status' => 'posted']);
            foreach ($d['lines'] as $line) {
                $r = $p->receipts()->where('status', 'posted')->findOrFail($line['receipt_id']);
                $i = $p->item($r->item_key);
                $q = ProcurementMath::quantity($line['quantity']);
                $billed = (int) $p->lines()->where('receipt_id', $r->id)->whereHas('invoice', fn ($x) => $x->where('status', 'posted'))->sum('quantity_milli');
                $unbilledReturns = (int) $p->returns()->where('receipt_id', $r->id)->whereNull('invoice_line_id')->where('status', 'posted')->sum('quantity_milli');
                if ($q < 1 || $q + $billed + $unbilledReturns > $r->accepted_milli || $d['invoiced_on'] < $r->received_on->toDateString()) {
                    $this->w->fail('lines', 'Invoice only accepted, unbilled quantities on or after their receipt date.');
                }
                $allBilled = (int) $p->lines()->where('item_key', $i['key'])->whereHas('invoice', fn ($x) => $x->where('status', 'posted'))->sum('quantity_milli');
                $amount = ProcurementMath::cost($allBilled + $q, $i['unit_minor']) - ProcurementMath::cost($allBilled, $i['unit_minor']);
                if ($amount < 1) {
                    $this->w->fail('lines', 'Combine quantities whose rounded invoice total would be zero.');
                }
                $invoice->lines()->create(['procurement_id' => $p->id, 'receipt_id' => $r->id, 'item_key' => $i['key'], 'quantity_milli' => $q, 'amount_minor' => $amount]);
            }
            $this->w->document($p, $a, 'invoice', $f, $invoice->id);
        });
    }

    public function returnGoods(Procurement $p, array $input, User $a): void
    {
        $this->w->authorize($a, 'returns', $p);
        $d = ProcurementRules::validate('return', $input);
        $this->w->mutate($p, $d, $a, 'goods_returned', function ($p, $f) use ($d, $a) {
            if ($p->parent_type === 'business') {
                $this->w->fail('parent', 'Business supplier returns are deferred. Reject unacceptable goods at receipt before payment.');
            }
            $r = $p->receipts()->where('status', 'posted')->findOrFail($d['receipt_id']);
            $q = ProcurementMath::quantity($d['quantity']);
            $line = null;
            $returned = (int) $p->returns()->where('receipt_id', $r->id)->where('status', 'posted')->sum('quantity_milli');
            if ($q < 1 || $returned + $q > $r->accepted_milli || $d['returned_on'] < $r->received_on->toDateString()) {
                $this->w->fail('quantity', 'Return only remaining accepted quantities, on or after receipt.');
            }
            $due = 0;
            if (! empty($d['invoice_line_id'])) {
                $line = $p->lines()->where('receipt_id', $r->id)->whereHas('invoice', fn ($x) => $x->where('status', 'posted'))->findOrFail($d['invoice_line_id']);
                if ($d['returned_on'] < $line->invoice->invoiced_on->toDateString()) {
                    $this->w->fail('returned_on', 'An invoiced return cannot precede its invoice.');
                }
                $prior = (int) $p->returns()->where('invoice_line_id', $line->id)->where('status', 'posted')->sum('quantity_milli');
                if ($prior + $q > $line->quantity_milli) {
                    $this->w->fail('quantity', 'This return exceeds the remaining invoiced quantity.');
                }
                $credit = ProcurementMath::portion($line->amount_minor, $prior + $q, $line->quantity_milli) - ProcurementMath::portion($line->amount_minor, $prior, $line->quantity_milli);
                $due = max(0, $credit - $p->invoiceBalance($line));
            } else {
                $billed = (int) $p->lines()->where('receipt_id', $r->id)->whereHas('invoice', fn ($x) => $x->where('status', 'posted'))->sum('quantity_milli');
                $unbilledReturned = (int) $p->returns()->where('receipt_id', $r->id)->whereNull('invoice_line_id')->where('status', 'posted')->sum('quantity_milli');
                if ($q + $billed + $unbilledReturned > $r->accepted_milli) {
                    $this->w->fail('invoice_line_id', 'Select the invoice line for previously billed goods.');
                }
                $i = $p->item($r->item_key);
                $credit = ProcurementMath::cost($unbilledReturned + $q, $i['unit_minor']) - ProcurementMath::cost($unbilledReturned, $i['unit_minor']);
            }
            if ($credit < 1 || ($due > 0 && empty($d['refund_due_on']))) {
                $this->w->fail('refund_due_on', 'Use a positive credit value and specify when any supplier refund is due.');
            }
            $return = $p->returns()->create(['receipt_id' => $r->id, 'invoice_line_id' => $line?->id, 'item_key' => $r->item_key, 'quantity_milli' => $q, 'credit_minor' => $credit, 'refund_due_minor' => $due, 'credit_reference' => $d['credit_reference'], 'returned_on' => $d['returned_on'], 'refund_due_on' => $d['refund_due_on'] ?? null, 'reason' => $d['reason'], 'actor_id' => $a->id, 'status' => 'posted']);
            $this->w->document($p, $a, 'return_credit', $f, $return->id);
        });
    }

    public function assertSettled(Procurement $p, bool $complete): void
    {
        if ($complete && ! $p->approved()?->issued_at) {
            $this->w->fail('status', 'Issue and fulfill an order before closing it.');
        }
        foreach ($p->approved()?->items ?? [] as $i) {
            $accepted = (int) $p->receipts()->where('item_key', $i['key'])->where('status', 'posted')->sum('accepted_milli');
            if ($complete && $accepted < $i['quantity_milli']) {
                $this->w->fail('delivery', 'Receive the remaining goods or cancel the unfulfilled order with a reason.');
            }
        }
        foreach ($p->receipts()->where('status', 'posted')->get() as $r) {
            $billed = (int) $p->lines()->where('receipt_id', $r->id)->whereHas('invoice', fn ($q) => $q->where('status', 'posted'))->sum('quantity_milli');
            $unbilledReturns = (int) $p->returns()->where('receipt_id', $r->id)->whereNull('invoice_line_id')->where('status', 'posted')->sum('quantity_milli');
            if ($billed + $unbilledReturns < $r->accepted_milli) {
                $this->w->fail('invoice', 'Invoice or return accepted goods before closing/cancelling.');
            }
        }
        foreach ($p->lines()->whereHas('invoice', fn ($q) => $q->where('status', 'posted'))->get() as $l) {
            if ($p->invoiceBalance($l) > 0) {
                $this->w->fail('invoice', 'Settle all accepted invoice balances first.');
            }
        }
        foreach ($p->returns()->where('status', 'posted')->get() as $r) {
            if ($this->refundRemaining($p, $r) > 0) {
                $this->w->fail('refund', 'Receive all required supplier refunds first.');
            }
        }
    }

    public function refundRemaining(Procurement $p, $r): int
    {
        return $r->refund_due_minor - Money::sumMinor($p->movements()->where('return_id', $r->id)->where('kind', 'refund')->where('status', 'posted')->getQuery());
    }

    public function correct(Procurement $p, array $input, User $a): void
    {
        $this->w->authorize($a, 'void', $p);
        $d = ProcurementRules::validate('void', $input);
        if ($d['record_type'] === 'movement') {
            app(ProcurementPaymentService::class)->void($p, $d, $a);

            return;
        }
        $this->w->mutate($p, $d, $a, 'void_'.$d['record_type'], function ($p) use ($d) {
            $relation = match ($d['record_type']) {
                'receipt' => 'receipts','invoice' => 'invoices','return' => 'returns'
            };
            $r = $p->$relation()->findOrFail($d['record_id']);
            if ($r->status === 'void') {
                return;
            }
            if ($d['record_type'] === 'receipt' && ($p->lines()->where('receipt_id', $r->id)->whereHas('invoice', fn ($q) => $q->where('status', 'posted'))->exists() || $p->returns()->where('receipt_id', $r->id)->where('status', 'posted')->exists())) {
                $this->w->fail('record', 'Void dependent invoices/returns before correcting a receipt.');
            }
            if ($d['record_type'] === 'invoice') {
                $ids = $r->lines()->pluck('id');
                if ($p->movements()->whereIn('invoice_line_id', $ids)->where('status', 'posted')->exists() || $p->returns()->whereIn('invoice_line_id', $ids)->where('status', 'posted')->exists()) {
                    $this->w->fail('record', 'Void dependent payments/returns before correcting an invoice.');
                }
            }
            if ($d['record_type'] === 'return') {
                if ($p->movements()->where('return_id',$r->id)->where('status','posted')->exists()) {
                    $this->w->fail('record','Void actual refunds before reversing their credit note.');
                }
                // Restoring an obligation also needs available approved authority.
                $r->update(['status' => 'void', 'void_reason' => $d['reason']]);
                $this->w->eligible($p,$p->approved()->items,$p->id);

                return;
            }
            $r->update(['status' => 'void', 'void_reason' => $d['reason']]);
        });
    }
}
