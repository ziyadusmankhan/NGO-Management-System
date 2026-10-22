<?php

namespace App\Domain\Qard\Services;

use App\Domain\Finance\Funds\Services\FundBalanceService;
use App\Domain\Finance\Funds\Models\Fund;
use App\Domain\Finance\Support\Money;
use App\Domain\Finance\Transactions\Services\TransactionService;
use App\Domain\Qard\Models\QardLoan;
use App\Domain\Qard\Models\QardMovement;
use App\Domain\Users\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;

class QardPaymentService
{
    public function __construct(private QardWorkflowService $workflow, private TransactionService $ledger) {}

    public function recoveredMinor(QardLoan $loan): int
    {
        return Money::sumMinor($loan->movements()->where('kind', 'repayment')->where('status', 'posted')->getQuery());
    }

    public function outstandingMinor(QardLoan $loan): int
    {
        $disbursed = Money::sumMinor($loan->movements()->where('kind', 'disbursement')->where('status', 'posted')->getQuery());
        return $disbursed - $this->recoveredMinor($loan);
    }

    public function record(QardLoan $loan, string $kind, array $data, User $actor): QardMovement
    {
        abort_unless(in_array($kind, ['disbursement', 'repayment']), 404);
        $this->workflow->authorize($actor, $kind === 'disbursement' ? 'disburse' : 'repay');
        $data = QardRules::validate($kind === 'disbursement' ? 'disburse' : 'repay', $data);
        return DB::transaction(function () use ($loan, $kind, $data, $actor) {
            $loan = $this->workflow->lock($loan);
            $existing = QardMovement::where('idempotency_key', $data['idempotency_key'])->lockForUpdate()->first();
            if ($existing) {
                foreach (['payment_date', 'payment_method', 'reference', 'receipt', 'notes'] as $field) {
                    $saved = $field === 'payment_date' ? $existing->payment_date->toDateString() : $existing->$field;
                    if (($saved ?? '') !== ($data[$field] ?? '')) { $this->workflow->fail('idempotency_key', 'This request key was already used with different details.'); }
                }
                if ($existing->qard_loan_id !== $loan->id || $existing->kind !== $kind || $existing->status !== 'posted'
                    || Money::minor($existing->amount) !== Money::minor($data['amount'])) {
                    $this->workflow->fail('idempotency_key', 'This request was already used for a different or voided payment.');
                }
                $this->ledger->assertMatchesQard($existing->transaction, $existing);
                return $existing;
            }
            $fund = Fund::withTrashed()->whereKey($loan->fund_id)->lockForUpdate()->firstOrFail();
            if ($fund->trashed() || $fund->status !== 'active') { $this->workflow->fail('fund_id', 'The original fund must be active. Ask an administrator to reactivate it.'); }
            $amount = Money::minor($data['amount']);
            if ($kind === 'disbursement') {
                if ($loan->status !== 'signed' || $loan->movements()->where('kind', $kind)->where('status', 'posted')->exists()) {
                    $this->workflow->fail('status', 'A signed, undisbursed loan is required.');
                }
                $this->workflow->eligible($loan);
                $agreement = $loan->agreements()->where('revision', $loan->revision)->first();
                if (! $agreement || $amount !== Money::minor($loan->principal)) {
                    $this->workflow->fail('amount', 'Disburse exactly the full signed principal.');
                }
                foreach (['principal', 'fund_id', 'purpose', 'frequency', 'installment_count'] as $field) {
                    if ((string) ($agreement->terms[$field] ?? '') !== (string) $loan->$field) {
                        $this->workflow->fail('agreement', 'Approved terms differ from the signed agreement. Request a revision before payment.');
                    }
                }
                if (($agreement->terms['schedule'] ?? []) !== $this->workflow->schedule($loan)) {
                    $this->workflow->fail('agreement', 'The repayment schedule differs from the signed agreement.');
                }
                foreach (['borrower', 'foundation'] as $party) {
                    $document = $loan->documents()->where('revision', $loan->revision)->where('kind', $party)->first();
                    if (! $document || ! Storage::disk('private')->exists($document->path)) {
                        $this->workflow->fail('agreement', 'Both private signed agreement copies must be available.');
                    }
                }
                if ($data['payment_date'] < $agreement->signed_at->timezone('Asia/Karachi')->toDateString()
                    || $data['payment_date'] >= $loan->first_due_date->toDateString()) {
                    $this->workflow->fail('payment_date', 'Payment must be on or after signing and before the first installment date. Revise and reapprove expired terms.');
                }
                if ($amount > app(FundBalanceService::class)->balanceMinor($fund)) {
                    $this->workflow->fail('amount', 'The original fund has insufficient balance.');
                }
            } else {
                if ($loan->status !== 'active') { $this->workflow->fail('status', 'Repayments require an active loan with outstanding principal.'); }
                $disbursement = $loan->movements()->where('kind', 'disbursement')->where('status', 'posted')->firstOrFail();
                $this->ledger->assertMatchesQard($disbursement->transaction, $disbursement);
                if ($data['payment_date'] < $disbursement->payment_date->toDateString()) { $this->workflow->fail('payment_date', 'A repayment cannot precede disbursement.'); }
                if ($amount > $this->outstandingMinor($loan)) { $this->workflow->fail('amount', 'This exceeds the remaining principal of PKR '.Money::decimal($this->outstandingMinor($loan)).'.'); }
            }
            $movement = $loan->movements()->create($data + ['fund_id' => $loan->fund_id, 'kind' => $kind, 'recorded_by' => $actor->id,
                'active_disbursement_for' => $kind === 'disbursement' ? $loan->id : null, 'status' => 'posted']);
            $this->ledger->createForSource($movement, [
                'fund_id' => $loan->fund_id, 'type' => $kind === 'disbursement' ? 'qard_disbursement' : 'recovery',
                'direction' => $kind === 'disbursement' ? 'debit' : 'credit', 'amount' => $data['amount'],
                'transaction_date' => $data['payment_date'], 'description' => $loan->number.' '.str_replace('_', ' ', $kind),
                'reference' => $data['reference'] ?? $loan->number, 'recorded_by' => $actor->id, 'notes' => $data['notes'] ?? null,
            ]);
            if ($kind === 'repayment') { $this->allocate($loan, $movement, $amount); }
            $before = $loan->status;
            $loan->update(['status' => $this->outstandingMinor($loan) === 0 ? 'repaid' : 'active']);
            $this->workflow->event($loan, $actor, $kind.'_posted', ['status' => $before], ['status' => $loan->status, 'movement_id' => $movement->id, 'amount' => $movement->amount]);
            app(QardMonitoringService::class)->refreshLocked($loan);
            return $movement;
        }, 5);
    }

    private function allocate(QardLoan $loan, QardMovement $movement, int $remaining): void
    {
        foreach ($loan->installments()->where('revision', $loan->revision)->orderBy('sequence')->lockForUpdate()->get() as $installment) {
            $amount = min($remaining, Money::minor($installment->amount) - $installment->paidMinor());
            if ($amount > 0) {
                $movement->allocations()->create(['qard_installment_id' => $installment->id, 'amount' => Money::decimal($amount)]);
                $remaining -= $amount;
            }
        }
        if ($remaining !== 0) { $this->workflow->fail('schedule', 'The schedule does not reconcile with the principal. No payment was posted.'); }
    }

    public function void(QardLoan $loan, QardMovement $movement, array $data, User $actor): void
    {
        $this->workflow->authorize($actor, 'void');
        $data = QardRules::validate('void', $data);
        DB::transaction(function () use ($loan, $movement, $data, $actor) {
            $loan = $this->workflow->lock($loan);
            $movement = $loan->movements()->whereKey($movement->id)->lockForUpdate()->firstOrFail();
            $this->ledger->assertMatchesQard($movement->transaction, $movement);
            if ($movement->status === 'void') { return; }
            $fund = Fund::withTrashed()->whereKey($movement->fund_id)->lockForUpdate()->firstOrFail();
            if ($movement->kind === 'disbursement' && $loan->movements()->where('kind', 'repayment')->where('status', 'posted')->exists()) {
                $this->workflow->fail('movement', 'Void all posted repayments before reversing disbursement.');
            }
            if ($movement->kind === 'repayment' && app(FundBalanceService::class)->balanceMinor($fund) < Money::minor($movement->amount)) {
                $this->workflow->fail('amount', 'Reversing this repayment would overdraw its original fund.');
            }
            $movement->update(['status' => 'void', 'active_disbursement_for' => null, 'voided_by' => $actor->id, 'voided_at' => now(), 'void_reason' => $data['reason']]);
            $this->ledger->voidForSource($movement);
            $before = $loan->status;
            $loan->update(['status' => $movement->kind === 'disbursement' ? 'signed' : 'active']);
            $this->workflow->event($loan, $actor, $movement->kind.'_voided', ['status' => $before], ['status' => $loan->status, 'movement_id' => $movement->id, 'reason' => $data['reason']]);
            app(QardMonitoringService::class)->refreshLocked($loan);
        }, 5);
    }
}
