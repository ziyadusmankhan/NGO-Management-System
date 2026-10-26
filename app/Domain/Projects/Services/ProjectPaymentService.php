<?php

namespace App\Domain\Projects\Services;

use App\Domain\Finance\Funds\Models\Fund;
use App\Domain\Finance\Support\Money;
use App\Domain\Finance\Transactions\Services\TransactionService;
use App\Domain\Members\Models\Member;
use App\Domain\Projects\Models\Project;
use App\Domain\Projects\Models\ProjectMovement;
use App\Domain\Users\Models\User;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;

class ProjectPaymentService
{
    public function __construct(private ProjectWorkflowService $workflow, private TransactionService $ledger) {}

    public function reconcile(Project $p): void
    {
        foreach ($p->movements()->with('transaction')->get() as $m) {
            $this->ledger->assertMatchesProject($m->transaction, $m);
        }
    }

    public function independentPayer(Project $p, User $actor): void
    {
        $ids = [$p->created_by, ...$p->revisions()->pluck('prepared_by')->all()];
        foreach ($p->reviews()->whereIn('stage', ['coordinator', 'secretary', 'committee'])->get() as $review) {
            $ids[] = $review->actor_id;
            foreach ($review->resolution['consents'] ?? [] as $consent) {
                $ids[] = $consent['user_id'];
                $ids[] = Member::withTrashed()->find($consent['member_id'])?->user_id;
            }
        }
        if (in_array($actor->id, array_filter($ids))) {
            $this->workflow->fail('actor', 'The payer cannot be the creator, revision preparer, decision actor or consenting member.');
        }
    }

    public function record(Project $p, string $kind, array $input, User $actor): ProjectMovement
    {
        abort_unless(in_array($kind, ['payment', 'refund'], true), 404);
        $action = $kind === 'payment' ? 'pay' : 'refund';
        $this->workflow->authorize($actor, $action);
        $d = ProjectRules::validate($action, $input);
        $payload = Arr::except($d, ['version', 'evidence', 'idempotency_key']);
        $payload['amount'] = Money::decimal(Money::minor($d['amount']));
        $payload['kind'] = $kind;
        $payload['actor_id'] = $actor->id;
        $payload['evidence_sha256'] = hash_file('sha256', $d['evidence']->getRealPath());
        ksort($payload);
        $hash = hash('sha256', json_encode($payload, JSON_THROW_ON_ERROR));

        return $this->workflow->withFiles(['payment_evidence' => $d['evidence']], function ($files) use ($p, $kind, $d, $actor, $hash) {
            return DB::transaction(function () use ($p, $kind, $d, $actor, $hash, $files) {
                $p = $this->workflow->lock($p, $kind === 'refund');
                $existing = ProjectMovement::where('idempotency_key', $d['idempotency_key'])->first();
                if ($existing) {
                    if ($existing->project_id !== $p->id || $existing->payload_hash !== $hash || $existing->status !== 'posted') {
                        $this->workflow->fail('idempotency_key', 'This request key belongs to a different or voided movement. Reload the form.');
                    }
                    $this->ledger->assertMatchesProject($existing->transaction, $existing);
                    foreach ($files as $f) {
                        Storage::disk('private')->delete($f['path']);
                    }

                    return $existing;
                }
                $this->workflow->current($p, $d);
                $this->reconcile($p);
                $amount = Money::minor($d['amount']);
                if ($amount < 1) {
                    $this->workflow->fail('amount', 'Enter a positive amount.');
                }
                Fund::whereKey($p->fund_id)->lockForUpdate()->firstOrFail();
                $original = null;
                if ($kind === 'payment') {
                    if (! $p->approved_revision || $p->payments_frozen || ! in_array($p->status, ['approved', 'in_progress', 'completed'])) {
                        $this->workflow->fail('status', 'Payment needs an approved open budget, with no pending amendment. Completed projects may pay final bills until closure.');
                    }
                    $this->independentPayer($p, $actor);
                    $budget = $p->budgets($p->approved_revision)->where('budget_item_id', $d['budget_item_id'])->first();
                    if (! $budget || $budget->payee !== $d['payee']) {
                        $this->workflow->fail('budget_item_id', 'Use an approved budget item and its approved payee. Supplier changes require an amendment.');
                    }
                    if ($amount > Money::minor($budget->amount) - $p->netMinor($budget->budget_item_id) || $amount > $p->remainingMinor()) {
                        $this->workflow->fail('amount', 'This payment exceeds the remaining item or total budget.');
                    }
                    if ($d['payment_date'] < $p->approved()->approved_at->timezone(config('projects.timezone'))->toDateString()) {
                        $this->workflow->fail('payment_date', 'Payment cannot predate budget approval.');
                    }
                    $itemId = $budget->budget_item_id;
                } else {
                    $original = $p->movements()->where('kind', 'payment')->where('status', 'posted')->find($d['original_payment_id']);
                    if (! $original) {
                        $this->workflow->fail('original_payment_id', 'Choose a posted payment belonging to this project.');
                    }
                    $refunded = Money::sumMinor($p->movements()->where('original_payment_id', $original->id)->where('status', 'posted')->getQuery());
                    if ($amount > Money::minor($original->amount) - $refunded) {
                        $this->workflow->fail('amount', 'The refund exceeds the unrefunded amount of the original payment.');
                    }
                    if ($d['payment_date'] < $original->payment_date->toDateString() || $d['payee'] !== $original->payee) {
                        $this->workflow->fail('payee', 'Use the original payee and a refund date on or after the payment.');
                    }
                    $itemId = $original->budget_item_id;
                }
                $m = $p->movements()->create(Arr::only($d, ['payment_date', 'payment_method', 'payee', 'reference', 'receipt', 'notes', 'idempotency_key']) + ['revision' => $p->approved_revision, 'budget_item_id' => $itemId, 'original_payment_id' => $original?->id, 'fund_id' => $p->fund_id, 'kind' => $kind, 'status' => 'posted', 'amount' => Money::decimal($amount), 'payload_hash' => $hash, 'recorded_by' => $actor->id]);
                $this->ledger->createForSource($m, ['fund_id' => $p->fund_id, 'type' => $kind === 'payment' ? 'expense' : 'refund', 'direction' => $kind === 'payment' ? 'debit' : 'credit', 'amount' => $m->amount, 'transaction_date' => $d['payment_date'], 'description' => $p->number.' — project '.$kind.' — '.$d['payee'], 'reference' => $d['reference'] ?? $d['receipt'], 'recorded_by' => $actor->id]);
                $this->workflow->documents($p, $actor, $files, ['movement_id' => $m->id]);
                $this->workflow->event($p, $actor, $kind.'_posted', [], ['movement_id' => $m->id, 'amount' => $m->amount, 'item_id' => $itemId]);

                return $m;
            }, 5);
        });
    }

    public function void(Project $p, ProjectMovement $m, array $input, User $actor): void
    {
        $this->workflow->authorize($actor, 'void');
        $d = ProjectRules::validate('void', $input);
        DB::transaction(function () use ($p, $m, $d, $actor) {
            // Archived cases retain their correction endpoint but cannot resume spending.
            if (DB::connection()->getDriverName() === 'sqlite') {
                DB::table('projects')->where('id', $p->id)->update(['id' => DB::raw('id')]);
            }
            $p = Project::withTrashed()->lockForUpdate()->findOrFail($p->id);
            $m = $p->movements()->findOrFail($m->id);
            $this->reconcile($p);
            if ($m->status === 'void') {
                return;
            }
            $this->workflow->current($p, $d);
            if ($m->kind === 'payment' && $p->movements()->where('original_payment_id', $m->id)->where('status', 'posted')->exists()) {
                $this->workflow->fail('refunds', 'Void all linked refunds before voiding this payment.');
            }
            if ($m->kind === 'refund') {
                $budget = $p->budgets($p->approved_revision)->where('budget_item_id', $m->budget_item_id)->first();
                $amount = Money::minor($m->amount);
                if (! $budget || $p->netMinor($m->budget_item_id) + $amount > Money::minor($budget->amount) || $p->netMinor() + $amount > Money::minor($p->approved()->budget)) {
                    $this->workflow->fail('budget', 'This correction exceeds the approved limits. Approve a budget amendment first.');
                }
            }
            $m->update(['status' => 'void', 'voided_by' => $actor->id, 'voided_at' => now(), 'void_reason' => $d['reason']]);
            $this->ledger->voidForSource($m);
            $this->workflow->event($p,$actor,$m->kind.'_voided',[],['movement_id' => $m->id, 'reason' => $d['reason']]);
        }, 5);
    }
}
