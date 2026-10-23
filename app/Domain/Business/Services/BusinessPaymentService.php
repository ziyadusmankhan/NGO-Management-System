<?php

namespace App\Domain\Business\Services;

use App\Domain\Business\Models\BusinessApplication;
use App\Domain\Business\Models\BusinessMovement;
use App\Domain\Finance\Funds\Models\Fund;
use App\Domain\Finance\Funds\Services\FundBalanceService;
use App\Domain\Finance\Support\Money;
use App\Domain\Finance\Support\PrincipalSchedule;
use App\Domain\Finance\Transactions\Services\TransactionService;
use App\Domain\Members\Models\Member;
use App\Domain\Users\Models\User;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;

class BusinessPaymentService
{
    public function __construct(private BusinessWorkflowService $workflow, private TransactionService $ledger) {}

    public function assertPayer(BusinessApplication $application, User $actor): void
    {
        $reviews = $application->reviews()->where('revision', $application->revision)->whereIn('stage', ['implementation', 'secretary', 'committee'])->get();
        $consents = $reviews->flatMap(fn ($r) => $r->consenting_members ?? []);
        $memberIds = Member::withTrashed()->where('user_id', $actor->id)->pluck('id');
        if ((int) $application->created_by === (int) $actor->id || $reviews->contains(fn ($r) => (int) $r->actor_id === (int) $actor->id)
            || $consents->contains(fn ($m) => (int) ($m['user_id'] ?? 0) === (int) $actor->id || $memberIds->contains($m['id']))) {
            $this->workflow->fail('actor', 'The payer must be different from the requester, approval actors, and consenting committee members.');
        }
    }

    public function assertSigned(BusinessApplication $application): void
    {
        $agreement = $application->agreements()->where('revision', $application->revision)->where('kind', 'initial')->first();
        if (! $agreement || $this->workflow->hash($agreement->terms) !== $this->workflow->hash($this->workflow->terms($application))) {
            $this->workflow->fail('agreement', 'The signed agreement must match the current approved terms.');
        }
        foreach (['borrower', 'foundation'] as $kind) {
            $doc = $agreement->documents()->where('kind', $kind)->first();
            if (! $doc || ! Storage::disk('private')->exists($doc->path)) {
                $this->workflow->fail('agreement', 'Both signed evidence files must be available before payment.');
            }
        }
    }

    public function record(BusinessApplication $application, string $kind, array $input, User $actor): BusinessMovement
    {
        abort_unless(in_array($kind, ['disbursement', 'repayment']), 404);
        $this->workflow->authorize($actor, $kind === 'disbursement' ? 'disburse' : 'repay');
        $data = BusinessRules::validate($kind === 'disbursement' ? 'disburse' : 'repay', $input);
        $payload = Arr::only($data, ['amount', 'payment_date', 'payment_method', 'payee', 'reference', 'receipt', 'notes', 'budget_item_id']);
        $payload['amount'] = Money::decimal(Money::minor($payload['amount']));
        foreach (['reference', 'notes'] as $key) {
            $payload[$key] = $payload[$key] ?? null;
        }
        $payload['budget_item_id'] = isset($payload['budget_item_id']) ? (int) $payload['budget_item_id'] : null;
        $payload['evidence_hash'] = isset($data['evidence']) ? hash_file('sha256', $data['evidence']->getRealPath()) : null;
        $hash = $this->workflow->hash($payload + ['kind' => $kind, 'application_id' => $application->id]);

        return $this->workflow->withFiles(['payment' => $data['evidence'] ?? null], function ($files) use ($application, $kind, $data, $actor, $hash, $payload) {
            return DB::transaction(function () use ($application, $kind, $data, $actor, $hash, $files, $payload) {
                $application = $this->workflow->lock($application);
                $existing = BusinessMovement::where('idempotency_key', $data['idempotency_key'])->lockForUpdate()->first();
                if ($existing) {
                    if (! hash_equals($existing->request_hash, $hash) || $existing->status !== 'posted') {
                        $this->workflow->fail('idempotency_key', 'This request key belongs to a different or voided payment. Open a fresh payment form.');
                    }
                    $this->ledger->assertMatchesBusiness($existing->transaction, $existing);
                    foreach ($files as $file) {
                        Storage::disk('private')->delete($file['path']);
                    }

                    return $existing;
                }
                $this->workflow->current($application, $data);
                if ($application->status !== 'signed') {
                    $this->workflow->fail('status', 'Payments require a signed, open business case.');
                }
                $fund = Fund::whereKey($application->fund_id)->lockForUpdate()->first();
                if (! $fund || $fund->status !== 'active') {
                    $this->workflow->fail('fund_id', 'The original fund must be active.');
                }
                $amount = Money::minor($data['amount']);
                if ($amount < 1) {
                    $this->workflow->fail('amount', 'Enter an amount greater than zero.');
                }
                foreach ($application->movements()->where('status', 'posted')->with('transaction')->get() as $previous) {
                    $this->ledger->assertMatchesBusiness($previous->transaction, $previous);
                }
                if ($kind === 'disbursement') {
                    $this->assertPayer($application, $actor);
                    $this->workflow->eligible($application);
                    $this->assertSigned($application);
                    if ($application->funding_status !== 'open') {
                        $this->workflow->fail('funding_status', 'Funding has been finalized. No further release is authorized.');
                    }
                    $item = $application->currentBudget()->whereKey($data['budget_item_id'])->lockForUpdate()->firstOrFail();
                    if ($item->release_method !== 'cash' && $data['payee'] !== $item->supplier) {
                        $this->workflow->fail('payee', 'Pay the supplier recorded in the approved budget.');
                    }
                    $remaining = min($application->remainingMinor(), Money::minor($item->organization_amount) - $item->paidMinor());
                    if ($amount > $remaining) {
                        $this->workflow->fail('amount', 'The remaining approved release for this item is PKR '.Money::decimal($remaining).'.');
                    }
                    if ($amount > app(FundBalanceService::class)->balanceMinor($fund)) {
                        $this->workflow->fail('amount', 'The fund has insufficient ledger balance.');
                    }
                    $signed = $application->agreements()->where('revision', $application->revision)->where('kind', 'initial')->firstOrFail();
                    if ($data['payment_date'] < $signed->signed_on->toDateString()) {
                        $this->workflow->fail('payment_date', 'Payment cannot precede signing.');
                    }
                } else {
                    if ($application->support_type !== 'repayable' || $application->funding_status !== 'finalized') {
                        $this->workflow->fail('status', 'Repayments require finalized repayable funding and its signed final statement.');
                    }
                    $finalAgreement = $application->agreements()->where('kind', 'final')->where('funding_version', $application->funding_version)->first();
                    foreach (['borrower_final', 'foundation_final'] as $kindName) {
                        $document = $finalAgreement?->documents()->where('kind', $kindName)->first();
                        if (! $document || ! Storage::disk('private')->exists($document->path)) {
                            $this->workflow->fail('agreement', 'Restore the retained signed final statement before recording repayments.');
                        }
                    }
                    if ($data['payment_date'] < $application->funding_closed_at->timezone(config('business.timezone'))->toDateString()) {
                        $this->workflow->fail('payment_date', 'A repayment cannot precede funding finalization.');
                    }
                    if ($amount > $application->outstandingMinor()) {
                        $this->workflow->fail('amount', 'The remaining principal is PKR '.Money::decimal($application->outstandingMinor()).'.');
                    }
                }
                $movement = $application->movements()->create(Arr::except($payload, ['evidence_hash']) + ['fund_id' => $application->fund_id, 'kind' => $kind, 'status' => 'posted', 'recorded_by' => $actor->id, 'idempotency_key' => $data['idempotency_key'], 'request_hash' => $hash]);
                $this->ledger->createForSource($movement, ['fund_id' => $application->fund_id, 'type' => $kind === 'disbursement' ? 'business_disbursement' : 'recovery',
                    'direction' => $kind === 'disbursement' ? 'debit' : 'credit', 'amount' => $movement->amount, 'transaction_date' => $data['payment_date'],
                    'description' => $application->number.' '.$kind, 'reference' => $data['reference'] ?? $application->number, 'recorded_by' => $actor->id, 'notes' => $data['notes'] ?? null]);
                $this->workflow->documents($application, $actor, $files, ['movement_id' => $movement->id]);
                if ($kind === 'repayment') {
                    $this->allocate($application, $movement, $amount);
                }
                if ($kind === 'disbursement' && ! $application->next_review_date) {
                    $application->update(['next_review_date' => now(config('business.timezone'))->addMonthsNoOverflow(config('business.monitoring_months'))->toDateString()]);
                }
                $this->workflow->event($application, $actor, $kind.'_posted', [], ['movement_id' => $movement->id, 'amount' => $movement->amount]);
                app(BusinessMonitoringService::class)->refreshLocked($application);

                return $movement;
            }, 5);
        });
    }

    private function allocate(BusinessApplication $application, BusinessMovement $movement, int $remaining): void
    {
        foreach ($application->installments()->where('funding_version', $application->funding_version)->orderBy('sequence')->lockForUpdate()->get() as $installment) {
            $amount = min($remaining, Money::minor($installment->amount) - $installment->paidMinor());
            if ($amount > 0) {
                $movement->allocations()->create(['business_installment_id' => $installment->id, 'amount' => Money::decimal($amount)]);
                $remaining -= $amount;
            }
        }
        if ($remaining !== 0) {
            $this->workflow->fail('schedule', 'The schedule and outstanding principal do not reconcile. Nothing was posted.');
        }
    }

    public function finalTerms(BusinessApplication $application): array
    {
        $today = now(config('business.timezone'))->toImmutable()->startOfDay();
        $first = $application->frequency === 'weekly' ? $today->addWeek() : $today->addMonthNoOverflow();
        $actual = Money::decimal($application->releasedMinor());

        return ['number' => $application->number, 'revision' => $application->revision, 'version' => $application->version, 'fund_id' => $application->fund_id,
            'support_type' => $application->support_type, 'actual_principal' => $actual, 'finalized_on' => $today->toDateString(),
            'released_movement_ids' => $application->movements()->where('kind', 'disbursement')->where('status', 'posted')->orderBy('id')->pluck('id')->all(),
            'schedule' => $application->support_type === 'repayable' && $application->releasedMinor() >= $application->installment_count
                ? app(PrincipalSchedule::class)->make($actual, $application->installment_count, $application->frequency, $first->toDateString(), $today->day) : []];
    }

    public function finalize(BusinessApplication $application, array $input, User $actor): void
    {
        $this->workflow->authorize($actor, 'finalize');
        $data = BusinessRules::validate('finalize', array_replace($input, ['support_type' => $application->support_type]));
        $this->workflow->withFiles(['borrower_final' => $data['borrower_file'] ?? null, 'foundation_final' => $data['foundation_file'] ?? null], function ($files) use ($application, $data, $actor) {
            DB::transaction(function () use ($application, $data, $actor, $files) {
                $application = $this->workflow->lock($application);
                $this->workflow->current($application, $data);
                $this->assertPayer($application, $actor);
                if ($application->status !== 'signed' || $application->funding_status !== 'open' || $application->releasedMinor() < 1) {
                    $this->workflow->fail('status', 'Finalize an open signed case after at least one payment.');
                }
                $this->assertSigned($application);
                foreach ($application->movements()->where('kind', 'disbursement')->where('status', 'posted')->with(['transaction', 'handover', 'budgetItem'])->get() as $movement) {
                    $this->ledger->assertMatchesBusiness($movement->transaction, $movement);
                    if ($movement->budgetItem->release_method === 'asset' && ! $movement->handover) {
                        $this->workflow->fail('handover', 'Record handover acknowledgement for every asset payment before finalizing.');
                    }
                }
                $terms = $this->finalTerms($application);
                if (! hash_equals($this->workflow->hash($terms), $data['terms_hash'])) {
                    $this->workflow->fail('terms_hash', 'The final statement changed. Print and acknowledge the current principal and schedule.');
                }
                $next = $application->funding_version + 1;
                if ($application->support_type === 'repayable') {
                    if (empty($terms['schedule'])) {
                        $this->workflow->fail('amount', 'The actual principal must allow at least one paisa per approved installment.');
                    }
                    if ($data['signed_on'] !== $terms['finalized_on']) {
                        $this->workflow->fail('signed_on', 'Sign the final statement on its displayed finalization date.');
                    }
                    $agreement = $application->agreements()->create(Arr::only($data, ['borrower_name', 'foundation_name', 'witnesses', 'signed_on']) + ['revision' => $application->revision, 'funding_version' => $next, 'kind' => 'final', 'terms' => $terms, 'actor_id' => $actor->id]);
                    $this->workflow->documents($application, $actor, $files, ['agreement_id' => $agreement->id]);
                    foreach ($terms['schedule'] as $row) {
                        $application->installments()->create($row + ['funding_version' => $next]);
                    }
                }
                $unused = $application->remainingMinor();
                $application->update(['funding_status' => 'finalized', 'funding_version' => $next, 'funding_closed_at' => now()]);
                $this->workflow->event($application, $actor, 'funding_finalized', ['funding_status' => 'open'], ['funding_status' => 'finalized', 'terms' => $terms, 'unused_released' => Money::decimal($unused), 'reason' => $data['reason']]);
                app(BusinessMonitoringService::class)->refreshLocked($application);
            }, 5);
        });
    }

    public function void(BusinessApplication $application, BusinessMovement $movement, array $input, User $actor): void
    {
        $this->workflow->authorize($actor, 'void');
        $data = BusinessRules::validate('void', $input);
        DB::transaction(function () use ($application, $movement, $data, $actor) {
            $application = $this->workflow->lock($application);
            $movement = $application->movements()->whereKey($movement->id)->lockForUpdate()->firstOrFail();
            $this->ledger->assertMatchesBusiness($movement->transaction, $movement);
            if ($movement->status === 'void') {
                return;
            }
            $this->workflow->current($application, $data);
            $fund = Fund::withTrashed()->whereKey($application->fund_id)->lockForUpdate()->firstOrFail();
            if ($movement->kind === 'disbursement' && $application->movements()->where('kind', 'repayment')->where('status', 'posted')->exists()) {
                $this->workflow->fail('movement', 'Void all repayments before reversing a disbursement.');
            }
            if ($movement->kind === 'repayment' && app(FundBalanceService::class)->balanceMinor($fund) < Money::minor($movement->amount)) {
                $this->workflow->fail('amount', 'This reversal would overdraw the original fund.');
            }
            $movement->update(['status' => 'void', 'voided_by' => $actor->id, 'voided_at' => now(), 'void_reason' => $data['reason']]);
            $this->ledger->voidForSource($movement);
            if ($movement->kind === 'disbursement') {
                $application->update(['funding_status' => 'open', 'funding_closed_at' => null]);
            }
            if ($application->status === 'closed') {
                $application->update(['status' => 'signed', 'closed_at' => null]);
            }
            $application->update(['next_review_date' => now(config('business.timezone'))->toDateString()]);
            $this->workflow->event($application, $actor, $movement->kind.'_voided', ['movement_status' => 'posted'], ['movement_id' => $movement->id, 'movement_status' => 'void', 'reason' => $data['reason'], 'funding_status' => $application->funding_status]);
            app(BusinessMonitoringService::class)->refreshLocked($application);
        }, 5);
    }
}
