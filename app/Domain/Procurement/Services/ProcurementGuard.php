<?php

namespace App\Domain\Procurement\Services;

use App\Domain\Business\Models\BusinessMovement;
use App\Domain\Finance\Expenses\Models\Expense;
use App\Domain\Finance\Support\Money;
use App\Domain\Procurement\Models\Procurement;
use App\Domain\Procurement\Models\ProcurementMovement;
use App\Domain\Projects\Models\ProjectMovement;
use App\Domain\Users\Models\User;
use Illuminate\Database\Eloquent\Model;

class ProcurementGuard
{
    public function authorizeEvidence(string $type, ?int $movementId, User $actor): void
    {
        if (! $movementId) {
            return;
        }
        $link = ProcurementMovement::where('source_type', $type)->where('source_id', $movementId)->first();
        if ($link) {
            app(ProcurementWorkflowService::class)->authorize($actor, 'view', $link->procurement);
        }
    }

    private static ?int $activeOrder = null;

    public static function within(Procurement $p, callable $callback): mixed
    {
        $old = self::$activeOrder;
        self::$activeOrder = $p->id;
        try {
            return $callback();
        } finally {
            self::$activeOrder = $old;
        }
    }

    public function reserved(string $type, int $parent, int $budget, ?int $except = null): int
    {
        $total = 0;
        foreach (Procurement::where('parent_type', $type)->where('parent_id', $parent)->whereNotNull('approved_revision')->when($except, fn ($q) => $q->where('id', '!=', $except))->get() as $p) {
            foreach ($p->approved()->items as $i) {
                if ((int) $i['budget_item_id'] === $budget) {
                    $total = Money::add($total, $p->reservation($i['key']));
                }
            }
        }

        return $total;
    }

    public function payment(string $type, $parent, $budget, int $amount): void
    {
        $id = $type === 'project' ? $budget->budget_item_id : $budget->id;
        if (self::$activeOrder) {
            $p = Procurement::findOrFail(self::$activeOrder);
            if ($p->parent_type === $type && (int) $p->parent_id === (int) $parent->id && collect($p->approved()->items)->contains(fn ($i) => (int) $i['budget_item_id'] === (int) $id)) {
                return;
            }
        }
        $goods = $type === 'project' ? $budget->kind === 'purchase' : $budget->release_method !== 'cash';
        $w = app(ProcurementWorkflowService::class);
        if ($goods && ! $budget->legacy_direct_purchase) {
            $w->fail('procurement', 'This goods/asset item requires an accepted procurement invoice before payment.');
        }
        $limit = Money::minor($type === 'project' ? $budget->amount : $budget->organization_amount);
        $paid = $type === 'project' ? $parent->netMinor($id) : $budget->paidMinor();
        if ($amount > $limit - $paid - $this->reserved($type, $parent->id, $id)) {
            $w->fail('amount', 'This budget is reserved by an approved purchase order.');
        }
    }

    public function linked(Model $source): void
    {
        $type = match (true) {
            $source instanceof ProjectMovement => 'project',$source instanceof BusinessMovement => 'business',default => null
        };
        if (! $type) {
            return;
        }
        $link = ProcurementMovement::where('source_type', $type)->where('source_id', $source->id)->first();
        if ($link && self::$activeOrder !== $link->procurement_id) {
            app(ProcurementWorkflowService::class)->fail('procurement', 'Use the linked procurement record for refunds and corrections.');
        }
    }

    public function ledger(Model $source): void
    {
        $source = $source->fresh() ?? $source;
        $w = app(ProcurementWorkflowService::class);
        if ($source instanceof ProcurementMovement) {
            if (self::$activeOrder !== $source->procurement_id || $source->procurement->parent_type !== 'office') {
                $w->fail('procurement', 'Use the authorized procurement payment workflow.');
            }
        } elseif ($source instanceof ProjectMovement && $source->kind === 'payment') {
            $parent = $source->project;
            $budget = $parent->budgets($source->revision)->where('budget_item_id', $source->budget_item_id)->firstOrFail();
            // The source is already inserted: exclude its amount from the limit check here.
            if ($budget->kind === 'purchase' && ! $budget->legacy_direct_purchase && ! self::$activeOrder) {
                $w->fail('procurement', 'Procurement is required for this project purchase.');
            }
            if (! self::$activeOrder && $parent->netMinor($budget->budget_item_id) + $this->reserved('project', $parent->id, $budget->budget_item_id) > Money::minor($budget->amount)) {
                $w->fail('procurement', 'This posting would spend a reserved project budget.');
            }
        } elseif ($source instanceof ProjectMovement && $source->kind === 'refund') {
            $original = ProjectMovement::findOrFail($source->original_payment_id);
            $this->linked($original);
        } elseif ($source instanceof BusinessMovement && $source->kind === 'disbursement') {
            $budget = $source->budgetItem;
            if ($budget->release_method !== 'cash' && ! $budget->legacy_direct_purchase && ! self::$activeOrder) {
                $w->fail('procurement', 'Procurement is required for this business purchase.');
            }
            if (! self::$activeOrder && $budget->paidMinor() + $this->reserved('business', $source->business_application_id, $budget->id) > Money::minor($budget->organization_amount)) {
                $w->fail('procurement', 'This posting would spend a reserved business budget.');
            }
        } elseif ($source instanceof Expense && ! $source->legacy_posted && ! in_array($source->purchase_kind, ['service', 'labor', 'other_non_goods'])) {
            $w->fail('procurement', 'Classify this expense. Goods and assets must use Procurement.');
        }
    }

    public function assertNoOpen(string $type, int $id): void
    {
        if (Procurement::where('parent_type', $type)->where('parent_id', $id)->whereNotIn('status', ['closed', 'cancelled'])->exists()) {
            app(ProcurementWorkflowService::class)->fail('procurement', 'Close or cancel all linked purchase requests before ending parent funding.');
        }
        foreach (Procurement::withTrashed()->where('parent_type', $type)->where('parent_id', $id)->get() as $purchase) {
            app(ProcurementOperationsService::class)->assertSettled($purchase, false);
        }
    }

    public function projectBudget($project, int $revision): void
    {
        $items = $project->budgets($revision)->get()->keyBy('budget_item_id');
        foreach (Procurement::where('parent_type', 'project')->where('parent_id', $project->id)->whereNotIn('status', ['closed', 'cancelled'])->whereNotNull('approved_revision')->get() as $p) {
            foreach ($p->approved()->items as $i) {
                $budget = $items->get($i['budget_item_id']);
                if (! $budget || $budget->kind !== 'purchase' || $budget->payee !== $p->approved()->supplier_snapshot['name'] || Money::minor($budget->amount) < $project->netMinor($i['budget_item_id']) + $this->reserved('project', $project->id, $i['budget_item_id'])) {
                    app(ProcurementWorkflowService::class)->fail('procurement', 'Retain budget items, suppliers and sufficient limits for open purchase orders.');
                }
            }
        }
    }

    public function afterProjectCorrection($project): void
    {
        // Linked corrections validate after both authoritative and procurement movements change.
        if (! self::$activeOrder) {
            $this->projectBudget($project,$project->approved_revision);
        }
    }
}
