<?php

namespace App\Domain\Finance\Transactions\Services;

use App\Domain\Finance\Transactions\Models\Transaction;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;

class TransactionService
{
    /**
     * Create one transaction for a source record.
     *
     * If a transaction already exists for the source, return it
     * instead of creating a duplicate.
     */
    public function createForSource(
        Model $source,
        array $data
    ): Transaction {
        return DB::transaction(function () use ($source, $data) {

            $existing = Transaction::withTrashed()
                ->where('source_type', $source->getMorphClass())
                ->where('source_id', $source->getKey())
                ->lockForUpdate()
                ->first();

            if ($existing) {
                if ($existing->trashed()) {
                    $existing->restore();
                }

                return $existing->fresh();
            }

            $lastTransaction = Transaction::withTrashed()
                ->orderByDesc('id')
                ->lockForUpdate()
                ->first();

            $nextNumber = ($lastTransaction?->id ?? 0) + 1;

            $transactionNumber =
                'TXN-' . str_pad(
                    $nextNumber,
                    5,
                    '0',
                    STR_PAD_LEFT
                );

            return Transaction::create([
                'transaction_number' => $transactionNumber,
                'source_type' => $source->getMorphClass(),
                'source_id' => $source->getKey(),
                'fund_id' => $data['fund_id'],
                'type' => $data['type'],
                'reference' => $data['reference'] ?? null,
                'description' => $data['description'],
                'amount' => $data['amount'],
                'direction' => $data['direction'],
                'transaction_date' => $data['transaction_date'],
                'status' => $data['status'] ?? 'posted',
                'recorded_by' =>
                    $data['recorded_by']
                    ?? ($source->recorded_by ?? auth()->id()),
                'notes' => $data['notes'] ?? null,
            ]);
        });
    }

    /**
     * Synchronize an existing source with exactly one transaction.
     */
    public function syncForSource(
        Model $source,
        array $data
    ): Transaction {
        return DB::transaction(function () use ($source, $data) {

            $transaction = Transaction::withTrashed()
                ->where('source_type', $source->getMorphClass())
                ->where('source_id', $source->getKey())
                ->lockForUpdate()
                ->first();

            if (! $transaction) {
                return $this->createForSource($source, $data);
            }

            if ($transaction->trashed()) {
                $transaction->restore();
            }

            $transaction->update([
                'fund_id' =>
                    $data['fund_id'] ?? $transaction->fund_id,

                'type' =>
                    $data['type'] ?? $transaction->type,

                'reference' =>
                    $data['reference'] ?? $transaction->reference,

                'description' =>
                    $data['description'] ?? $transaction->description,

                'amount' =>
                    $data['amount'] ?? $transaction->amount,

                'direction' =>
                    $data['direction'] ?? $transaction->direction,

                'transaction_date' =>
                    $data['transaction_date']
                    ?? $transaction->transaction_date,

                'status' =>
                    $data['status'] ?? $transaction->status,

                'recorded_by' =>
                    $data['recorded_by']
                    ?? $transaction->recorded_by,

                'notes' =>
                    $data['notes'] ?? $transaction->notes,
            ]);

            return $transaction->fresh();
        });
    }

    /**
     * Void the transaction belonging to a source record.
     */
    public function voidForSource(Model $source): void
    {
        $transaction = Transaction::withTrashed()
            ->where('source_type', $source->getMorphClass())
            ->where('source_id', $source->getKey())
            ->first();

        if (! $transaction) {
            return;
        }

        $transaction->update([
            'status' => 'void',
        ]);
    }
}