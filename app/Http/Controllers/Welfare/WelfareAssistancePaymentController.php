<?php

namespace App\Http\Controllers\Welfare;

use App\Domain\Finance\Funds\Models\Fund;
use App\Domain\Finance\Funds\Services\FundBalanceService;
use App\Domain\Finance\Transactions\Services\TransactionService;
use App\Http\Requests\Welfare\VoidWelfareAssistancePaymentRequest;
use App\Domain\Welfare\Assistance\Models\WelfareAssistance;
use App\Domain\Welfare\Assistance\Models\WelfareAssistancePayment;
use App\Http\Controllers\Controller;
use Illuminate\Database\QueryException;
use App\Http\Requests\Welfare\StoreWelfareAssistancePaymentRequest;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\View\View;

class WelfareAssistancePaymentController extends Controller

{
    public function show(
    WelfareAssistance $assistance,
    WelfareAssistancePayment $payment
    ): View {

        abort_unless(
            $payment->assistance_id === $assistance->id,
            404
        );

        $payment->load([
        'assistance.beneficiary',
        'fund',
        'recordedBy',
        'voidedBy',
        'transaction',
    ]);

        return view(
            'welfare.assistance.payments.show',
            compact(
                'assistance',
                'payment'
            )
        );
    }

    public function void(
        VoidWelfareAssistancePaymentRequest $request,
        WelfareAssistance $assistance,
        WelfareAssistancePayment $payment
    ): RedirectResponse {

        abort_unless(
            (int) $payment->assistance_id === (int) $assistance->id,
            404
        );

        if ($payment->status !== 'posted') {
            return back()
                ->withErrors([
                    'status' =>
                        'Only posted payments can be voided.',
                ]);
        }

        $transaction = $payment->transaction()->first();

        if (!$transaction) {
            return back()
                ->withErrors([
                    'transaction' =>
                        'This payment cannot be voided because its finance transaction is missing.',
                ]);
        }

        if ($transaction->status !== 'posted') {
            return back()
                ->withErrors([
                    'transaction' =>
                        'This payment cannot be voided because its finance transaction is no longer posted.',
                ]);
        }

        DB::transaction(function () use (
            $request,
            $payment
        ) {

            $payment->update([
                'status' => 'void',
                'voided_by' => auth()->id(),
                'voided_at' => now(),
                'void_reason' => $request->validated('void_reason'),
            ]);

            app(TransactionService::class)
                ->voidForSource($payment);
        });

        return redirect()
            ->route(
                'welfare.assistance.payments.show',
                [
                    'assistance' => $assistance,
                    'payment' => $payment,
                ]
            )
            ->with(
                'success',
                "{$payment->payment_number} was voided successfully."
            );
    }

    public function create(
        WelfareAssistance $assistance
    ): View {
        abort_unless(
            $assistance->status === 'approved',
            422,
            'Only approved assistance can receive a payment.'
        );

        $assistance->load([
            'beneficiary',
            'fund',
            'payments',
        ]);

        abort_unless(
            $assistance->fund_id !== null,
            422,
            'This assistance is not linked to a fund.'
        );

        $fund = $assistance->fund;

        abort_unless(
            $fund && $fund->status === 'active',
            422,
            'The assistance fund is unavailable or inactive.'
        );

        $paidSoFar = $assistance->payments()
            ->where('status', 'posted')
            ->sum('amount');

        $remaining = max(
            0,
            (float) $assistance->amount - (float) $paidSoFar
        );

        return view(
            'welfare.assistance.payments.create',
            compact(
                'assistance',
                'fund',
                'paidSoFar',
                'remaining'
            )
        );
    }

    public function store(
        StoreWelfareAssistancePaymentRequest $request,
        WelfareAssistance $assistance
    ): RedirectResponse {
        abort_unless(
            $assistance->status === 'approved',
            422,
            'Only approved assistance can receive a payment.'
        );

        $data = $request->validated();

        $existingPayment = WelfareAssistancePayment::query()
            ->where(
                'idempotency_key',
                $data['idempotency_key']
            )
            ->first();

        if ($existingPayment) {

            abort_unless(
                (int) $existingPayment->assistance_id === (int) $assistance->id,
                409,
                'This payment request key has already been used for another assistance.'
            );

            return redirect()
                ->route(
                    'welfare.assistance.payments.show',
                    [
                        'assistance' => $assistance,
                        'payment' => $existingPayment,
                    ]
                )
                ->with(
                    'success',
                    "{$existingPayment->payment_number} was already recorded. No duplicate payment was created."
                );
        }

        $assistance->load([
            'beneficiary',
            'fund',
        ]);

        $fund = $assistance->fund;

        if (!$fund) {
            return back()
                ->withInput()
                ->withErrors([
                    'fund' => 'This assistance is not linked to a fund.',
                ]);
        }

        if ($fund->status !== 'active') {
            return back()
                ->withInput()
                ->withErrors([
                    'fund' => 'The selected assistance fund is inactive.',
                ]);
        }

        $amount = (float) $data['amount'];

        $paidSoFar = (float) $assistance->payments()
            ->where('status', 'posted')
            ->sum('amount');

        if (
            in_array(
                $assistance->frequency,
                ['monthly', 'weekly', 'quarterly', 'yearly'],
                true
            )
            && $amount > (float) $assistance->amount
        ) {
            return back()
                ->withInput()
                ->withErrors([
                    'amount' => sprintf(
                        'This payment cannot exceed the assistance amount of PKR %s.',
                        number_format(
                            (float) $assistance->amount,
                            2
                        )
                    ),
                ]);
        }

        if (
            in_array(
                $assistance->frequency,
                [null, '', 'one_time'],
                true
            )
            && ($paidSoFar + $amount) > (float) $assistance->amount
        ) {
            $remaining = max(
                0,
                (float) $assistance->amount - $paidSoFar
            );

            return back()
                ->withInput()
                ->withErrors([
                    'amount' => sprintf(
                        'This payment would exceed the remaining approved amount of PKR %s.',
                        number_format($remaining, 2)
                    ),
                ]);
        }

        $availableBalance = app(
            FundBalanceService::class
        )->balance($fund);

        if (
            $fund->prevent_negative_balance
            && $amount > $availableBalance
        ) {
            return back()
                ->withInput()
                ->withErrors([
                    'amount' => sprintf(
                        'This payment exceeds the available fund balance of PKR %s.',
                        number_format($availableBalance, 2)
                    ),
                ]);
        }

        try {

            $payment = DB::transaction(function () use (
                $assistance,
                $fund,
                $data,
                $amount
            ) {

                $lastPayment = WelfareAssistancePayment::query()
                    ->orderByDesc('id')
                    ->lockForUpdate()
                    ->first();

                $nextNumber = ($lastPayment?->id ?? 0) + 1;

                $payment = WelfareAssistancePayment::create([
                    'payment_number' => sprintf(
                        'WAP-%05d',
                        $nextNumber
                    ),

                    'idempotency_key' =>
                        $data['idempotency_key'],

                    'assistance_id' =>
                        $assistance->id,

                    'fund_id' =>
                        $fund->id,

                    'amount' =>
                        $amount,

                    'payment_date' =>
                        $data['payment_date'],

                    'payment_method' =>
                        $data['payment_method'],

                    'transaction_reference' =>
                        $data['transaction_reference'] ?? null,

                    'receipt_number' =>
                        $data['receipt_number'] ?? null,

                    'paid_to' =>
                        $data['paid_to']
                        ?? $assistance->beneficiary?->full_name,

                    'status' =>
                        'posted',

                    'recorded_by' =>
                        auth()->id(),

                    'notes' =>
                        $data['notes'] ?? null,
                ]);

                app(TransactionService::class)->createForSource(
                    $payment,
                    [
                        'fund_id' =>
                            $fund->id,

                        'type' =>
                            'welfare_assistance',

                        'reference' =>
                            $payment->payment_number,

                        'description' =>
                            sprintf(
                                'Welfare assistance payment %s for %s',
                                $payment->payment_number,
                                $assistance->beneficiary?->full_name
                                    ?? 'beneficiary'
                            ),

                        'amount' =>
                            $amount,

                        'direction' =>
                            'debit',

                        'transaction_date' =>
                            $payment->payment_date,

                        'status' =>
                            'posted',
                    ]
                );

                if (
                    in_array(
                        $assistance->frequency,
                        [null, '', 'one_time'],
                        true
                    )
                ) {
                    $newPaidTotal = (float) $assistance->payments()
                        ->where('status', 'posted')
                        ->sum('amount');

                    if (
                        $newPaidTotal >=
                        (float) $assistance->amount
                    ) {
                        $assistance->update([
                            'status' => 'completed',
                        ]);
                    }
                }

                return $payment;
            });

        } catch (QueryException $e) {

            $existingPayment = WelfareAssistancePayment::query()
                ->where(
                    'idempotency_key',
                    $data['idempotency_key']
                )
                ->first();

            if ($existingPayment) {

                abort_unless(
                    (int) $existingPayment->assistance_id === (int) $assistance->id,
                    409,
                    'This payment request key has already been used for another assistance.'
                );

                return redirect()
                    ->route(
                        'welfare.assistance.payments.show',
                        [
                            'assistance' => $assistance,
                            'payment' => $existingPayment,
                        ]
                    )
                    ->with(
                        'success',
                        "{$existingPayment->payment_number} was already recorded. No duplicate payment was created."
                    );
            }

            throw $e;
        }

        return redirect()
            ->route(
                'welfare.assistance.show',
                $assistance
            )
            ->with(
                'success',
                'Welfare assistance payment recorded and posted to the finance ledger.'
            );
    }   
}