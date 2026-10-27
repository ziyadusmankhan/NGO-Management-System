<?php

namespace App\Domain\Audit\Services;

use App\Domain\Audit\Models\AuditEvent;
use App\Domain\Business\Models\BusinessApplication;
use App\Domain\Business\Models\BusinessCategory;
use App\Domain\Business\Models\BusinessCategoryEvent;
use App\Domain\Business\Models\BusinessMovement;
use App\Domain\Finance\Donations\Models\Donation;
use App\Domain\Finance\Expenses\Models\Expense;
use App\Domain\Finance\Funds\Models\Fund;
use App\Domain\Finance\Transactions\Models\Transaction;
use App\Domain\Procurement\Models\Procurement;
use App\Domain\Procurement\Models\ProcurementMovement;
use App\Domain\Procurement\Services\ProcurementGuard;
use App\Domain\Projects\Models\Project;
use App\Domain\Projects\Models\ProjectMovement;
use App\Domain\Qard\Models\QardLoan;
use App\Domain\Qard\Models\QardMovement;
use App\Domain\Welfare\Applications\Models\WelfareApplication;
use App\Domain\Welfare\Assistance\Models\WelfareAssistance;
use App\Domain\Welfare\Assistance\Models\WelfareAssistancePayment;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Arr;

class AuditCapture
{
    public function created(Model $m): void
    {
        $c = config('audit.models')[get_class($m)] ?? null;
        if (! $c) {
            return;
        }
        if ($c['history'] ?? false) {
            $this->history($m);

            return;
        }
        $this->change($m, 'created', [], $m->getAttributes());
    }

    public function updated(Model $m): void
    {
        if (config('audit.models')[get_class($m)]['history'] ?? false) {
            return;
        }
        $changes = Arr::except($m->getChanges(), ['updated_at', 'last_active_at', 'remember_token']);
        if (! $changes) {
            return;
        }
        $action = isset($changes['status']) ? 'status_changed' : 'updated';
        $this->change($m, $action, Arr::only($m->getRawOriginal(), array_keys($changes)), $changes);
    }

    public function deleted(Model $m): void
    {
        $this->change($m, 'archived', [], ['archived' => true]);
    }

    private function change(Model $m, string $action, array $before, array $after): void
    {
        $c = config('audit.models')[get_class($m)] ?? null;
        if (! $c) {
            return;
        }
        $after['changed_fields'] = array_values(array_diff(array_keys($after), ['password', 'remember_token']));
        if (array_key_exists('password', $after)) {
            $after['password_changed'] = true;
        }
        app(AuditRecorder::class)->record($c['module'], str(class_basename($m))->snake().'_'.$action, [
            'subject_type' => get_class($m), 'subject_id' => $m->getKey(), 'subject_reference' => $this->reference($m),
            'before' => $before, 'after' => $after,
        ]);
    }

    public function reference(Model $m): string
    {
        foreach (['number', 'fund_reference', 'application_number', 'assistance_number', 'payment_number', 'agreement_number', 'expense_number', 'donation_number', 'committee_number', 'member_number', 'verification_number'] as $key) {
            if ($m->getAttribute($key)) {
                return (string) $m->getAttribute($key);
            }
        }

        return class_basename($m).' #'.$m->getKey();
    }

    public function representedByProcurement(Model $m): bool
    {
        $module = config('audit.models')[get_class($m)]['module'] ?? null;
        if (! in_array($module, ['business', 'projects'])) {
            return false;
        }
        $after = $m->after_values ?? $m->after ?? [];
        $movementId = $after['movement_id'] ?? (($after['record_type'] ?? null) === 'movement' ? ($after['record_id'] ?? null) : null);
        if (! $movementId) {
            return false;
        }

        return (bool) ProcurementGuard::activeOrderId()
         || ProcurementMovement::where('source_type', $module === 'projects' ? 'project' : 'business')->where('source_id', $movementId)->exists();
    }

    public function history(Model $m, bool $historical = false): ?AuditEvent
    {
        if ($this->representedByProcurement($m)) {
            return null;
        }
        $c = config('audit.models')[get_class($m)];
        $before = $m->before_values ?? $m->before ?? [];
        $after = $m->after_values ?? $m->after ?? $m->getAttributes();
        $action = ($m instanceof BusinessCategoryEvent ? 'category_saved' : $m->action) ?? ($m->decision ? 'decision_'.$m->decision : ($m->to_status ? 'status_'.$m->to_status : 'review_recorded'));
        $parentClasses = [
            'category_id' => BusinessCategory::class, 'fund_id' => Fund::class, 'qard_loan_id' => QardLoan::class,
            'business_application_id' => BusinessApplication::class, 'project_id' => Project::class,
            'procurement_id' => Procurement::class, 'application_id' => WelfareApplication::class, 'assistance_id' => WelfareAssistance::class,
        ];
        $parentClass = $parentClasses[$c['parent']];
        $parent = (new $parentClass)->newQueryWithoutScopes()->find($m->{$c['parent']});
        $context = [];
        $movementId = $after['movement_id'] ?? (($after['record_type'] ?? null) === 'movement' ? ($after['record_id'] ?? null) : null);
        $movementClass = ['qard' => QardMovement::class, 'business' => BusinessMovement::class, 'projects' => ProjectMovement::class, 'procurement' => ProcurementMovement::class][$c['module']] ?? null;
        if ($movementId && $movementClass && ($movement = $movementClass::find($movementId))) {
            $transaction = $c['module'] === 'procurement' ? $movement->ledger() : $movement->transaction;
            if ($transaction) {
                $context['ledger_entries'] = [$transaction->only($historical ? ['id', 'transaction_number', 'fund_id', 'amount', 'direction', 'source_type', 'source_id'] : ['id', 'transaction_number', 'fund_id', 'amount', 'direction', 'status', 'source_type', 'source_id'])];
            }
            $after = array_merge($after, $movement->only(['amount', 'payment_date', 'payment_method', 'fund_id', 'original_payment_id']));
        }

        return app(AuditRecorder::class)->record($c['module'], $action, [
            'actor_id' => $m->{$c['actor']}, 'historical' => $historical, 'occurred_at' => $m->{$c['time']},
            'subject_type' => $parent ? get_class($parent) : get_class($m), 'subject_id' => $parent?->id ?? $m->id, 'subject_reference' => $this->reference($parent ?? $m),
            'source_key' => $m->getTable().':'.$m->id, 'before' => $before, 'after' => $after, 'context' => $context,
            'reason' => $after['reason'] ?? $m->reason ?? null,
        ]);
    }

    public function ledger(Model $source, Transaction $t, string $action): void
    {
        // Qard/Business/Project/Procurement writers already capture their authoritative movement.
        if (! in_array(get_class($source), [Donation::class, Expense::class, WelfareAssistancePayment::class])) {
            return;
        }
        app(AuditRecorder::class)->record($source instanceof WelfareAssistancePayment ? 'welfare' : 'finance', $action, [
            'actor_id' => auth()->id() ?? ($action === 'payment_voided' ? $source->voided_by : $t->recorded_by),
            'subject_type' => get_class($source), 'subject_id' => $source->id, 'subject_reference' => $this->reference($source),
            'after' => $t->only(['amount', 'fund_id', 'direction', 'status', 'transaction_date']),
            'context' => ['ledger_entries' => [$t->only(['id', 'transaction_number', 'fund_id', 'amount', 'direction', 'status', 'source_type', 'source_id'])]], 'reason' => $source->void_reason,
            'source_key' => $source instanceof WelfareAssistancePayment ? 'welfare_payments:'.$source->id.':'.$t->status : null,
        ]);
    }
}
