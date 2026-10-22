<?php

namespace App\Http\Controllers\Welfare;

use App\Domain\Finance\Funds\Services\FundBalanceService;
use App\Domain\Finance\Support\Money;
use App\Domain\Welfare\Assistance\Models\WelfareAssistance;
use App\Domain\Welfare\Assistance\Models\WelfareAssistancePayment;
use App\Domain\Welfare\Assistance\Services\WelfareAssistancePaymentService;
use App\Http\Controllers\Controller;
use App\Http\Requests\Welfare\StoreWelfareAssistancePaymentRequest;
use App\Http\Requests\Welfare\VoidWelfareAssistancePaymentRequest;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;

class WelfareAssistancePaymentController extends Controller
{
    public function show(WelfareAssistance $assistance, WelfareAssistancePayment $payment): View
    {
        abort_unless((int) $payment->assistance_id === (int) $assistance->id, 404);
        $payment->load(['assistance.beneficiary', 'fund', 'recordedBy', 'voidedBy', 'transaction']);

        return view('welfare.assistance.payments.show', compact('assistance', 'payment'));
    }

    public function create(WelfareAssistance $assistance, WelfareAssistancePaymentService $payments): View
    {
        abort_unless($assistance->status === 'approved', 422, 'Only approved assistance can receive a payment.');
        $assistance->load(['beneficiary', 'fund']);
        $fund = $assistance->fund;
        abort_unless($fund && $fund->status === 'active', 422, 'The assistance fund is unavailable or inactive.');
        $paidSoFar = Money::decimal($payments->paidMinor($assistance));
        $remaining = Money::decimal($payments->remainingMinor($assistance, Money::minor($paidSoFar)));
        $availableBalance = app(FundBalanceService::class)->balance($fund);
        $maximumPayment = Money::decimal(max(0, min(Money::minor($remaining), Money::minor($availableBalance))));

        return view('welfare.assistance.payments.create', compact(
            'assistance', 'fund', 'paidSoFar', 'remaining', 'availableBalance', 'maximumPayment'
        ));
    }

    public function store(StoreWelfareAssistancePaymentRequest $request, WelfareAssistance $assistance, WelfareAssistancePaymentService $payments): RedirectResponse
    {
        $result = $payments->record($assistance, $request->validated(), $request->user());
        if ($result['duplicate']) {
            return redirect()->route('welfare.assistance.payments.show', [$assistance, $result['payment']])
                ->with('success', $result['payment']->payment_number.' was already recorded ('.$result['payment']->status.'). No duplicate debit was created.');
        }

        return redirect()->route('welfare.assistance.show', $assistance)
            ->with('success', 'Welfare assistance payment recorded and posted to the finance ledger.');
    }

    public function void(VoidWelfareAssistancePaymentRequest $request, WelfareAssistance $assistance, WelfareAssistancePayment $payment, WelfareAssistancePaymentService $payments): RedirectResponse
    {
        $changed = $payments->void($assistance, $payment, $request->validated('void_reason'), $request->user());

        return redirect()->route('welfare.assistance.payments.show', [$assistance, $payment])
            ->with('success', $payment->payment_number.($changed
                ? ' was voided and its fund balance restored.'
                : ' was already voided. No additional balance adjustment was made.'));
    }
}
