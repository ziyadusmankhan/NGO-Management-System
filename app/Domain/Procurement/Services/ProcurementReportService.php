<?php

namespace App\Domain\Procurement\Services;

use App\Domain\Finance\Support\Money;
use App\Domain\Procurement\Models\Procurement;
use Illuminate\Support\Facades\DB;

class ProcurementReportService
{
    public function visible($actor)
    {
        return Procurement::withTrashed()->where(function ($q) use ($actor) {
            $q->where('parent_type', 'office');
            if ($actor->hasPermission('projects.view')) {
                $q->orWhere('parent_type', 'project');
            }
            if ($actor->hasPermission('business.view')) {
                $q->orWhere('parent_type', 'business');
            }
        });
    }

    public function rows($actor, array $filters)
    {
        return $this->visible($actor)->with('fund')->when($filters['status'] ?? null, fn ($q, $v) => $q->where('status', $v))->when($filters['fund_id'] ?? null, fn ($q, $v) => $q->where('fund_id', $v))->when($filters['parent_type'] ?? null, fn ($q, $v) => $q->where('parent_type', $v))->when($filters['parent_id'] ?? null, fn ($q, $v) => $q->where('parent_id', $v))->orderBy('id')->get()
            ->filter(fn ($p) => empty($filters['supplier_id']) || ($p->approved() ?? $p->proposal())->supplier_id == $filters['supplier_id'])->map(function ($p) use ($filters) {
                $r = $p->approved() ?? $p->proposal();
                $q = $p->movements()->where('status', 'posted')->when($filters['from'] ?? null, fn ($q, $v) => $q->whereDate('payment_date', '>=', $v))->when($filters['to'] ?? null, fn ($q, $v) => $q->whereDate('payment_date', '<=', $v));
                $paid = Money::sumMinor((clone $q)->where('kind', 'payment')->getQuery());
                $refunds = Money::sumMinor((clone $q)->where('kind', 'refund')->getQuery());
                $lines = $p->lines()->whereHas('invoice', fn ($q) => $q->where('status', 'posted'))->get();
                $unpaid = $lines->sum(fn ($l) => $p->invoiceBalance($l));
                $approved = $p->approved();
                $delivery = 'Not ordered';
                if ($approved?->issued_at) {
                    $received = 0;
                    $complete = true;
                    foreach ($approved->items as $item) {
                        $quantity = (int) $p->receipts()->where('item_key', $item['key'])->where('status', 'posted')->sum('accepted_milli');
                        $received += $quantity;
                        $complete = $complete && $quantity >= $item['quantity_milli'];
                    }
                    $delivery = $complete ? 'Accepted in full' : ($received ? 'Partially accepted' : 'Awaiting delivery');
                    if ($p->status === 'cancelled' && ! $complete) {
                        $delivery = 'Remainder cancelled';
                    }
                }
                $refundDue = $p->returns()->where('status', 'posted')->get()->sum(fn ($return) => app(ProcurementOperationsService::class)->refundRemaining($p, $return));

                return ['number' => $p->number, 'title' => $r->title, 'supplier' => $r->supplier_snapshot['name'], 'status' => $p->status, 'parent' => $p->parent_type.($p->parent_id ? ' #'.$p->parent_id : ''), 'fund' => $p->fund->name, 'approved' => Money::decimal($p->approved()?->total_minor ?? 0), 'invoiced' => Money::decimal($lines->sum('amount_minor')), 'unpaid' => Money::decimal($unpaid), 'paid' => Money::decimal($paid), 'refunds' => Money::decimal($refunds), 'net' => Money::decimal($paid - $refunds), 'reserved' => Money::decimal($p->reservation()), 'refund_due' => Money::decimal($refundDue), 'delivery_status' => $delivery, 'accepted_quantity' => ProcurementMath::display((int) $p->receipts()->where('status', 'posted')->sum('accepted_milli')), 'returned_quantity' => ProcurementMath::display((int) $p->returns()->where('status', 'posted')->sum('quantity_milli'))];
            })->values();
    }

    public function refresh(Procurement $p): void
    {
        DB::transaction(function () use ($p) {
            $p = app(ProcurementWorkflowService::class)->lock($p);
            $today = now('Asia/Karachi')->toDateString();
            $delivery = false;
            $invoice = false;
            $refund = false;
            if ($r = $p->approved()) {
                if (! in_array($p->status, ['closed', 'cancelled']) && $r->required_on->toDateString() < $today) {
                    foreach ($r->items as $i) {
                        if ($p->receipts()->where('item_key', $i['key'])->where('status', 'posted')->sum('accepted_milli') < $i['quantity_milli']) {
                            $delivery = true;
                        }
                    }
                }
                foreach ($p->lines()->whereHas('invoice', fn ($q) => $q->where('status', 'posted')->whereDate('due_on', '<', $today))->get() as $l) {
                    $invoice = $invoice || $p->invoiceBalance($l) > 0;
                }
                foreach ($p->returns()->where('status', 'posted')->whereDate('refund_due_on', '<', $today)->get() as $r) {
                    $refund = $refund || app(ProcurementOperationsService::class)->refundRemaining($p, $r) > 0;
                }
            }
            $p->reminder()->updateOrCreate([],['delivery_due' => $delivery, 'invoice_due' => $invoice, 'refund_due' => $refund]);
        }, 5);
    }
}
