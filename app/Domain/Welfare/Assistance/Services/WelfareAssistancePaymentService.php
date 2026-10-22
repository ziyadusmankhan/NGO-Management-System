<?php

namespace App\Domain\Welfare\Assistance\Services;

use App\Domain\Finance\Funds\Models\Fund;
use App\Domain\Finance\Funds\Services\FundBalanceService;
use App\Domain\Finance\Funds\Services\FundPurposeService;
use App\Domain\Finance\Support\Money;
use App\Domain\Finance\Transactions\Services\TransactionService;
use App\Domain\Users\Models\User;
use App\Domain\Welfare\Applications\Models\WelfareApplication;
use App\Domain\Welfare\Assistance\Models\WelfareAssistance;
use App\Domain\Welfare\Assistance\Models\WelfareAssistancePayment;
use Illuminate\Database\UniqueConstraintViolationException;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class WelfareAssistancePaymentService
{
    public function remainingMinor(WelfareAssistance $assistance, ?int $paid = null): int
    {
        return max(0, Money::add(Money::minor($assistance->amount), -($paid ?? $this->paidMinor($assistance))));
    }

    public function paidMinor(WelfareAssistance $assistance): int
    {
        return Money::sumMinor($assistance->payments()->getQuery()->where('status', 'posted')->lockForUpdate());
    }

    /** @return array{payment: WelfareAssistancePayment, duplicate: bool} */
    public function record(WelfareAssistance $assistance, array $data, User $actor): array
    {
        abort_unless($actor->hasPermission('welfare.assistance.manage'), 403);
        try {
            return DB::transaction(function () use ($assistance, $data, $actor) {
                $this->reserveSqliteWrite($assistance);
                // Match the application-before-assistance order used by approval.
                $application = $assistance->application_id
                    ? WelfareApplication::query()->lockForUpdate()->find($assistance->application_id) : null;
                $locked = WelfareAssistance::query()->lockForUpdate()->findOrFail($assistance->id);
                $fund = Fund::query()->lockForUpdate()->find($locked->fund_id);
                $duplicate = WelfareAssistancePayment::where('idempotency_key', $data['idempotency_key'])->lockForUpdate()->first();
                if ($duplicate) {
                    return $this->replay($duplicate, $locked, $data);
                }
                if ($locked->status !== 'approved') {
                    throw ValidationException::withMessages(['status' => 'Only approved assistance can receive a payment.']);
                }
                if (! $fund || $fund->status !== 'active') {
                    throw ValidationException::withMessages(['fund' => 'The assistance fund is unavailable or inactive.']);
                }
                if ($locked->application_id) {
                    if (! $application || $application->isTestApplication() || $application->status !== 'approved'
                        || (int) $application->fund_id !== (int) $locked->fund_id
                        || $application->agreement?->status !== 'signed') {
                        throw ValidationException::withMessages(['application' => 'Payment requires the approved application, signed agreement, and authorized fund.']);
                    }
                    $locked->setRelation('application', $application);
                }
                if (! app(FundPurposeService::class)->allowsWelfare($fund, $locked)) {
                    throw ValidationException::withMessages(['fund' => 'This assistance purpose is not permitted by the selected fund designation.']);
                }
                if (! in_array($locked->frequency, [null, '', 'one_time', 'weekly', 'monthly', 'quarterly', 'yearly'], true)) {
                    throw ValidationException::withMessages(['frequency' => 'The assistance frequency must be corrected before payment.']);
                }
                $amount = Money::minor($data['amount']);
                $approved = Money::minor($locked->amount);
                $paid = $this->paidMinor($locked);
                // The approved amount is the total authorization for every frequency.
                $limit = $this->remainingMinor($locked, $paid);
                if ($amount <= 0 || $amount > $limit) {
                    throw ValidationException::withMessages(['amount' => 'Payment must be positive and cannot exceed the remaining approved amount of PKR '.Money::format(Money::decimal($limit)).'.']);
                }
                if ($amount > app(FundBalanceService::class)->balanceMinor($fund)) {
                    throw ValidationException::withMessages(['amount' => 'This payment exceeds the available fund balance.']);
                }
                $payment = WelfareAssistancePayment::create([
                    'payment_number' => 'WAP-'.Str::ulid(),
                    'idempotency_key' => $data['idempotency_key'],
                    'assistance_id' => $locked->id, 'fund_id' => $fund->id,
                    'amount' => Money::decimal($amount), 'payment_date' => $data['payment_date'],
                    'payment_method' => $data['payment_method'],
                    'transaction_reference' => $data['transaction_reference'] ?? null,
                    'receipt_number' => $data['receipt_number'] ?? null,
                    'paid_to' => $data['paid_to'] ?? $locked->beneficiary?->full_name,
                    'notes' => $data['notes'] ?? null, 'status' => 'posted', 'recorded_by' => $actor->id,
                ]);
                $payment->update(['payment_number' => sprintf('WAP-%05d', $payment->id)]);
                app(TransactionService::class)->createForSource($payment, [
                    'fund_id' => $fund->id, 'type' => 'welfare_assistance',
                    'reference' => $payment->payment_number,
                    'description' => 'Welfare assistance payment '.$payment->payment_number.' for '.$locked->assistance_number,
                    'amount' => $payment->amount, 'direction' => 'debit',
                    'transaction_date' => $payment->payment_date, 'status' => 'posted', 'recorded_by' => $actor->id,
                ]);
                if (Money::add($paid, $amount) === $approved) {
                    $this->transition($locked, 'completed', $actor, 'Fully paid by '.$payment->payment_number);
                }

                return ['payment' => $payment, 'duplicate' => false];
            }, 5);
        } catch (UniqueConstraintViolationException $exception) {
            $existing = WelfareAssistancePayment::where('idempotency_key', $data['idempotency_key'])->first();
            if (! $existing) {
                throw $exception;
            }

            return $this->replay($existing, $assistance, $data);
        }
    }

    public function void(WelfareAssistance $assistance, WelfareAssistancePayment $payment, string $reason, User $actor): bool
    {
        abort_unless($actor->hasPermission('welfare.assistance.manage'), 403);
        return DB::transaction(function () use ($assistance, $payment, $reason, $actor) {
            $this->reserveSqliteWrite($assistance);
            $locked = WelfareAssistance::query()->lockForUpdate()->findOrFail($assistance->id);
            $payment = WelfareAssistancePayment::query()->lockForUpdate()->findOrFail($payment->id);
            abort_unless((int) $payment->assistance_id === (int) $locked->id, 404);
            Fund::withTrashed()->lockForUpdate()->findOrFail($payment->fund_id);
            $ledger = $payment->transaction()->withTrashed()->lockForUpdate()->first();
            $transactions = app(TransactionService::class);
            $transactions->assertMatchesPayment($ledger, $payment);
            if ($payment->status === 'void') {
                return false;
            }
            if ($payment->status !== 'posted') {
                throw ValidationException::withMessages(['status' => 'Only posted payments can be voided.']);
            }
            $paidBefore = $this->paidMinor($locked);
            $payment->update(['status' => 'void', 'voided_by' => $actor->id, 'voided_at' => now(), 'void_reason' => $reason]);
            $transactions->voidForSource($payment);
            if ($locked->status === 'completed'
                && $paidBefore >= Money::minor($locked->amount)
                && $this->paidMinor($locked) < Money::minor($locked->amount)) {
                $this->transition($locked, 'approved', $actor, 'Reopened after voiding '.$payment->payment_number.': '.$reason);
            }

            return true;
        }, 5);
    }

    private function replay(WelfareAssistancePayment $payment, WelfareAssistance $assistance, array $data): array
    {
        $matches = (int) $payment->assistance_id === (int) $assistance->id
            && Money::minor($payment->amount) === Money::minor($data['amount'])
            && $payment->payment_date->toDateString() === date('Y-m-d', strtotime($data['payment_date']))
            && $payment->payment_method === $data['payment_method'];
        foreach (['transaction_reference', 'receipt_number', 'notes'] as $field) {
            $matches = $matches && (string) $payment->{$field} === (string) ($data[$field] ?? '');
        }
        if (isset($data['paid_to'])) {
            $matches = $matches && $payment->paid_to === $data['paid_to'];
        }
        abort_unless($matches, 409, 'This payment request key was already used with different details. Open a new payment form for a new payment.');
        app(TransactionService::class)->assertMatchesPayment($payment->transaction()->withTrashed()->first(), $payment);

        return ['payment' => $payment, 'duplicate' => true];
    }

    private function transition(WelfareAssistance $assistance, string $to, User $actor, string $reason): void
    {
        $from = $assistance->status;
        $assistance->update(['status' => $to]);
        $assistance->statusChanges()->create([
            'from_status' => $from, 'to_status' => $to, 'changed_by' => $actor->id,
            'changed_at' => now(), 'reason' => $reason,
        ]);
        app(WelfareMonitoringService::class)->syncRemindersLocked($assistance);
    }

    private function reserveSqliteWrite(WelfareAssistance $assistance): void
    {
        if (DB::connection()->getDriverName() === 'sqlite') {
            DB::table('welfare_assistances')->where('id', $assistance->id)->update(['id' => DB::raw('id')]);
        }
    }
}
