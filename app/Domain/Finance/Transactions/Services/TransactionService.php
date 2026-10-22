<?php

namespace App\Domain\Finance\Transactions\Services;

use App\Domain\Finance\Funds\Models\Fund;
use App\Domain\Finance\Funds\Services\FundBalanceService;
use App\Domain\Finance\Funds\Services\FundPurposeService;
use App\Domain\Finance\Support\Money;
use App\Domain\Finance\Transactions\Models\Transaction;
use App\Domain\Welfare\Assistance\Models\WelfareAssistancePayment;
use App\Domain\Qard\Models\QardMovement;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class TransactionService
{
    public function createForSource(Model $source, array $data): Transaction
    {
        return DB::transaction(function () use ($source, $data) {
            $this->lockSource($source);
            if ($source instanceof QardMovement) {
                $this->assertQardPayload($source, $data);
            }
            $existing = $this->sourceTransaction($source);
            if ($existing) {
                $this->assertSameMovement($existing, $data);
                return $existing;
            }
            $funds = $this->lockFunds([$data['fund_id']]);
            $this->assertAffordable($funds, null, $data, $source instanceof WelfareAssistancePayment || $source instanceof QardMovement);
            $this->assertPurpose($funds, $source, $data);

            // Allocate the display number from our own inserted ID, never MAX(id)+1.
            $transaction = Transaction::create([
                'transaction_number' => 'TXN-'.Str::ulid(),
                'source_type' => $source->getMorphClass(),
                'source_id' => $source->getKey(),
                'fund_id' => $data['fund_id'],
                'type' => $data['type'],
                'reference' => $data['reference'] ?? null,
                'description' => $data['description'],
                'amount' => Money::decimal(Money::minor($data['amount'])),
                'direction' => $data['direction'],
                'transaction_date' => $data['transaction_date'],
                'status' => $data['status'] ?? 'posted',
                'recorded_by' => $data['recorded_by'] ?? ($source->recorded_by ?? auth()->id()),
                'notes' => $data['notes'] ?? null,
            ]);
            $transaction->update(['transaction_number' => sprintf('TXN-%05d', $transaction->id)]);

            return $transaction;
        }, 5);
    }

    public function syncForSource(Model $source, array $data): Transaction
    {
        // Welfare payments are immutable; corrections use their audited void action.
        if ($source instanceof WelfareAssistancePayment || $source instanceof QardMovement) {
            return $this->createForSource($source, $data);
        }

        return DB::transaction(function () use ($source, $data) {
            $this->lockSource($source);
            $transaction = $this->sourceTransaction($source);
            if (! $transaction) {
                return $this->createForSource($source, $data);
            }
            $next = array_merge($transaction->only([
                'fund_id', 'type', 'reference', 'description', 'amount', 'direction',
                'transaction_date', 'status', 'recorded_by', 'notes',
            ]), $data);
            $funds = $this->lockFunds([$transaction->fund_id, $next['fund_id']]);
            $this->assertAffordable($funds, $transaction, $next);
            $this->assertPurpose($funds, $source, $next);
            if ($transaction->trashed()) {
                $transaction->restore();
            }
            $next['amount'] = Money::decimal(Money::minor($next['amount']));
            $transaction->update($next);

            return $transaction->fresh();
        }, 5);
    }

    public function voidForSource(Model $source): void
    {
        DB::transaction(function () use ($source) {
            $this->lockSource($source);
            $transaction = $this->sourceTransaction($source);
            if ($source instanceof QardMovement) {
                if ($source->fresh()?->status !== 'void') {
                    throw ValidationException::withMessages(['transaction' => 'Use the Qard void action to reverse this entry.']);
                }
                $expected = clone $source;
                $expected->status = $transaction?->status === 'void' ? 'void' : 'posted';
                $this->assertMatchesQard($transaction, $expected);
            }
            if (! $transaction) {
                if ($source instanceof WelfareAssistancePayment) {
                    $this->invalidLedger();
                }
                return;
            }
            if ($source instanceof WelfareAssistancePayment) {
                // The payment service changes both records in the same transaction.
                if ($source->fresh()?->status !== 'void') {
                    throw ValidationException::withMessages(['transaction' => 'Use the payment void action to reverse this ledger entry.']);
                }
                $expected = clone $source;
                $expected->status = $transaction->status === 'void' ? 'void' : 'posted';
                $this->assertMatchesPayment($transaction, $expected);
            }
            if ($transaction->status === 'void') {
                return;
            }
            $funds = $this->lockFunds([$transaction->fund_id]);
            $this->assertAffordable($funds, $transaction, null, $source instanceof QardMovement);
            $transaction->update(['status' => 'void']);
        }, 5);
    }

    public function assertMatchesPayment(?Transaction $transaction, WelfareAssistancePayment $payment): void
    {
        if (! $transaction || $transaction->trashed()
            || $transaction->source_type !== $payment->getMorphClass()
            || (int) $transaction->source_id !== (int) $payment->id
            || (int) $transaction->fund_id !== (int) $payment->fund_id
            || $transaction->direction !== 'debit' || $transaction->type !== 'welfare_assistance'
            || Money::minor($transaction->amount) !== Money::minor($payment->amount)
            || $transaction->status !== $payment->status
            || $transaction->transaction_date?->toDateString() !== $payment->payment_date?->toDateString()) {
            $this->invalidLedger();
        }
    }

    public function assertMatchesQard(?Transaction $transaction, QardMovement $movement): void
    {
        if (! $transaction || $transaction->trashed()
            || $transaction->source_type !== $movement->getMorphClass()
            || (int) $transaction->source_id !== (int) $movement->id
            || (int) $transaction->fund_id !== (int) $movement->fund_id
            || $transaction->direction !== ($movement->kind === 'disbursement' ? 'debit' : 'credit')
            || $transaction->type !== ($movement->kind === 'disbursement' ? 'qard_disbursement' : 'recovery')
            || Money::minor($transaction->amount) !== Money::minor($movement->amount)
            || $transaction->status !== $movement->status
            || $transaction->transaction_date?->toDateString() !== $movement->payment_date?->toDateString()) {
            $this->invalidLedger();
        }
    }

    private function assertQardPayload(QardMovement $source, array $data): void
    {
        $current = $source->fresh();
        if (! $current || $current->status !== 'posted'
            || (int) $data['fund_id'] !== (int) $current->fund_id
            || (int) $current->fund_id !== (int) $current->loan->fund_id
            || Money::minor($data['amount']) !== Money::minor($current->amount)
            || $data['direction'] !== ($current->kind === 'disbursement' ? 'debit' : 'credit')
            || $data['type'] !== ($current->kind === 'disbursement' ? 'qard_disbursement' : 'recovery')
            || ($data['status'] ?? 'posted') !== 'posted'
            || $data['transaction_date'] !== $current->payment_date->toDateString()) {
            $this->invalidLedger();
        }
    }

    private function invalidLedger(): never
    {
        throw ValidationException::withMessages([
            'transaction' => 'The payment ledger entry is missing or inconsistent. Ask a finance administrator to reconcile it before continuing.',
        ]);
    }

    private function assertSameMovement(Transaction $transaction, array $data): void
    {
        if ($transaction->trashed()
            || (int) $transaction->fund_id !== (int) $data['fund_id']
            || Money::minor($transaction->amount) !== Money::minor($data['amount'])
            || $transaction->direction !== $data['direction']
            || $transaction->type !== $data['type']
            || $transaction->status !== ($data['status'] ?? 'posted')) {
            throw ValidationException::withMessages(['transaction' => 'A different or voided ledger entry already exists for this source.']);
        }
    }

    private function lockSource(Model $source): void
    {
        if (DB::connection()->getDriverName() === 'sqlite') {
            DB::table($source->getTable())->where($source->getKeyName(), $source->getKey())
                ->update([$source->getKeyName() => DB::raw($source->getKeyName())]);
        }
        $source->newQueryWithoutScopes()->whereKey($source->getKey())->lockForUpdate()->firstOrFail();
    }

    private function sourceTransaction(Model $source): ?Transaction
    {
        return Transaction::withTrashed()->where('source_type', $source->getMorphClass())
            ->where('source_id', $source->getKey())->lockForUpdate()->first();
    }

    private function lockFunds(array $ids)
    {
        return Fund::withTrashed()->whereIn('id', array_unique($ids))->orderBy('id')->lockForUpdate()->get()->keyBy('id');
    }

    private function effect(?array $movement): int
    {
        if (! $movement || ($movement['status'] ?? 'posted') !== 'posted') {
            return 0;
        }
        $amount = Money::minor($movement['amount']);
        if ($amount <= 0 || ! in_array($movement['direction'], ['debit', 'credit'], true)) {
            throw ValidationException::withMessages(['amount' => 'Ledger movements require a positive amount and a valid direction.']);
        }

        return $movement['direction'] === 'credit' ? $amount : -$amount;
    }

    private function assertPurpose($funds, Model $source, array $movement): void
    {
        if (($movement['status'] ?? 'posted') === 'posted' && $movement['direction'] === 'debit') {
            // Re-read after the source/fund locks. A stale controller or maintenance
            // command cannot bypass designation checks by passing older source data.
            $currentSource = $source->newQueryWithoutScopes()->lockForUpdate()->findOrFail($source->getKey());
            app(FundPurposeService::class)->assertAllowsDebit($funds->get($movement['fund_id']), $currentSource);
        }
    }

    private function assertAffordable($funds, ?Transaction $old, ?array $next, bool $strict = false): void
    {
        $oldEffect = $old && ! $old->trashed() ? $this->effect($old->only(['amount', 'direction', 'status'])) : 0;
        $nextEffect = $this->effect($next);
        if ($next && $nextEffect !== 0 && (! $old || $old->trashed()
            || (int) $old->fund_id !== (int) $next['fund_id'] || $oldEffect !== $nextEffect)) {
            $fund = $funds->get($next['fund_id']);
            if (! $fund || $fund->trashed() || $fund->status !== 'active') {
                throw ValidationException::withMessages(['fund' => 'The transaction fund is unavailable or inactive.']);
            }
        }
        foreach ($funds as $fund) {
            $change = Money::add(
                $next && (int) $next['fund_id'] === (int) $fund->id ? $nextEffect : 0,
                $old && (int) $old->fund_id === (int) $fund->id ? -$oldEffect : 0
            );
            if ($change < 0 && ($strict || $fund->prevent_negative_balance)) {
                $available = app(FundBalanceService::class)->balanceMinor($fund);
                if (Money::add($available, $change) < 0) {
                    throw ValidationException::withMessages(['amount' => 'This movement exceeds the available fund balance of PKR '.Money::format(Money::decimal($available)).'.']);
                }
            }
        }
    }
}
