<?php

namespace App\Domain\Business\Services;

use App\Domain\Business\Models\BusinessApplication;
use App\Domain\Finance\Funds\Models\Fund;
use App\Domain\Finance\Funds\Services\FundPurposeService;
use App\Domain\Finance\Funds\Services\FundReportingService;
use App\Domain\Finance\Support\Money;

class BusinessReportService
{
    public function report(array $filters): array
    {
        $to = $filters['to'] ?? now(config('business.timezone'))->toDateString();
        $from = $filters['from'] ?? now(config('business.timezone'))->startOfMonth()->toDateString();
        $rows = BusinessApplication::withTrashed()->with(['beneficiary', 'fund'])->when($filters['fund_id'] ?? null, fn ($q, $id) => $q->where('fund_id', $id))
            ->when($filters['support_type'] ?? null, fn ($q, $type) => $q->where('support_type', $type))->orderBy('id')->get()->map(function ($application) use ($from, $to) {
                $base = $application->movements()->where('status', 'posted')->whereDate('payment_date', '<=', $to);
                $released = Money::sumMinor((clone $base)->where('kind', 'disbursement')->getQuery());
                $recovered = Money::sumMinor((clone $base)->where('kind', 'repayment')->getQuery());
                $periodReleased = Money::sumMinor((clone $base)->where('kind', 'disbursement')->whereDate('payment_date', '>=', $from)->getQuery());
                $periodRecovered = Money::sumMinor((clone $base)->where('kind', 'repayment')->whereDate('payment_date', '>=', $from)->getQuery());

                return ['application' => $application, 'released' => $released, 'recovered' => $recovered,
                    'outstanding' => $application->support_type === 'repayable' ? $released - $recovered : 0,
                    'period_released' => $periodReleased, 'period_recovered' => $periodRecovered,
                    'started' => $application->started_on && $application->started_on->toDateString() <= $to ? 1 : 0,
                    'unused_approval' => max(0, Money::minor($application->approved_amount) - $application->releasedMinor()),
                    'awaiting_funding' => in_array($application->status, ['approved', 'signed']) && $application->funding_status === 'open' && $application->remainingMinor() > 0,
                    'overdue' => $application->funding_status !== 'finalized' || $application->support_type !== 'repayable' ? 0 : $application->installments()->where('funding_version', $application->funding_version)->where('due_date', '<', $to)->get()->reduce(function ($total, $i) use ($to) {
                        $paid = Money::sumMinor($i->allocations()->whereHas('movement', fn ($q) => $q->where('status', 'posted')->whereDate('payment_date', '<=', $to))->getQuery());

                        return Money::add($total, Money::minor($i->amount) - $paid);
                    }, 0),
                ];
            });
        $totals = [];
        foreach (['released', 'recovered', 'outstanding', 'period_released', 'period_recovered', 'started', 'overdue'] as $key) {
            $totals[$key] = $rows->reduce(fn ($sum, $row) => Money::add($sum, $row[$key]), 0);
        }
        $capital = Fund::where('status', 'active')
            ->when($filters['fund_id'] ?? null, fn ($q, $id) => $q->whereKey($id))->orderBy('name')->get()
            ->filter(fn ($fund) => app(FundPurposeService::class)->allowsBusiness($fund, $filters['support_type'] ?? 'repayable'))
            ->map(fn ($fund) => ['fund' => $fund, 'summary' => app(FundReportingService::class)->summary($fund)]);

        return compact('rows', 'totals', 'from', 'to', 'capital');
    }
}
