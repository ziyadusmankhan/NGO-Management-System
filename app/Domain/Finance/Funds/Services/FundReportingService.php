<?php

namespace App\Domain\Finance\Funds\Services;

use App\Domain\Finance\Funds\Models\Fund;
use App\Domain\Finance\Support\Money;
use App\Domain\Finance\Transactions\Models\Transaction;
use App\Domain\Welfare\Assistance\Models\WelfareAssistance;
use Brick\Math\BigInteger;
use Illuminate\Support\Collection;

class FundReportingService
{
    /** Current unpaid commitments, including legacy and recurring assistance. */
    public function allocations(Fund $fund): Collection
    {
        $welfare = WelfareAssistance::query()->where('fund_id', $fund->id)->where('status', 'approved')
            ->with(['beneficiary', 'payments' => fn ($query) => $query->where('status', 'posted')])
            ->orderBy('id')->get()->map(function (WelfareAssistance $assistance) {
                $paid = 0;
                foreach ($assistance->payments as $payment) {
                    $paid = Money::add($paid, Money::minor($payment->amount));
                }
                $assistance->paid_amount = Money::decimal($paid);
                $assistance->outstanding_amount = Money::decimal(max(0, Money::add(Money::minor($assistance->amount), -$paid)));

                return $assistance;
            })->filter(fn ($assistance) => Money::minor($assistance->outstanding_amount) > 0)->values();
        $qard = \App\Domain\Qard\Models\QardLoan::where('fund_id', $fund->id)->whereIn('status', ['approved', 'signed'])
            ->with('beneficiary')->get()->map(function ($loan) {
                $loan->assistance_number = $loan->number;
                $loan->amount = $loan->principal;
                $loan->paid_amount = '0.00';
                $loan->outstanding_amount = $loan->principal;
                return $loan;
            });
        $business = \App\Domain\Business\Models\BusinessApplication::where('fund_id', $fund->id)
            ->whereIn('status', ['approved', 'signed'])->where('funding_status', 'open')->with('beneficiary')->get()->map(function ($application) {
                $application->assistance_number = $application->number;
                $application->amount = $application->approved_amount;
                $application->paid_amount = Money::decimal($application->releasedMinor());
                $application->outstanding_amount = Money::decimal($application->remainingMinor());
                return $application;
            })->filter(fn ($application) => Money::minor($application->outstanding_amount) > 0);
        $projects = \App\Domain\Projects\Models\Project::where('fund_id',$fund->id)->whereIn('status',['approved','in_progress','completed'])->get()->map(function ($project) {
            $project->assistance_number=$project->number; $project->amount=$project->approved()->budget;
            $project->paid_amount=Money::decimal($project->netMinor()); $project->outstanding_amount=Money::decimal($project->commitmentMinor()); return $project;
        })->filter(fn($p)=>Money::minor($p->outstanding_amount)>0);
        return $welfare->concat($qard)->concat($business)->concat($projects);
    }

    public function summary(Fund $fund, ?Collection $allocations = null): array
    {
        $allocations ??= $this->allocations($fund);
        $amounts = array_fill_keys(['received', 'spent', 'recovered', 'refunded', 'refunds_paid', 'allocated'], 0);
        foreach (Transaction::query()->where('fund_id', $fund->id)->where('status', 'posted')->cursor() as $transaction) {
            $bucket = match (true) {
                $transaction->direction === 'credit' && $transaction->type === 'recovery' => 'recovered',
                $transaction->direction === 'credit' && $transaction->type === 'refund' => 'refunded',
                $transaction->direction === 'debit' && $transaction->type === 'refund' => 'refunds_paid',
                $transaction->direction === 'credit' => 'received',
                $transaction->direction === 'debit' => 'spent',
                default => null,
            };
            if ($bucket !== null) {
                $amounts[$bucket] = Money::add($amounts[$bucket], Money::minor($transaction->amount));
            }
        }
        foreach ($allocations as $assistance) {
            $amounts['allocated'] = Money::add($amounts['allocated'], Money::minor($assistance->outstanding_amount));
        }
        $amounts['opening_balance'] = Money::minor($fund->opening_balance);
        $amounts['original_received'] = Money::add($amounts['opening_balance'], $amounts['received']);
        $inflows = Money::add($amounts['original_received'], Money::add($amounts['recovered'], $amounts['refunded']));
        $outflows = Money::add($amounts['spent'], $amounts['refunds_paid']);
        $amounts['balance'] = Money::add($inflows, -$outflows);
        // Preserve negative values so overcommitment is visible, not hidden by zero.
        $amounts['available'] = Money::add($amounts['balance'], -$amounts['allocated']);

        $summary = array_map(fn (int $amount) => Money::decimal($amount), $amounts);
        $summary['utilization_percent'] = $inflows > 0
            ? min(100, max(0, BigInteger::of(Money::add($outflows, $amounts['allocated']))->multipliedBy(100)->quotient($inflows)->toInt())) : 0;

        return $summary;
    }

    /** All retained entries, including voids/archives, in stable date/ID order. */
    public function ledger(Fund $fund): Collection
    {
        $balance = Money::minor($fund->opening_balance);

        return $fund->transactions()->withTrashed()->with('recordedBy')->orderBy('transaction_date')->orderBy('id')->get()
            ->each(function (Transaction $transaction) use (&$balance) {
                $transaction->affects_balance = ! $transaction->trashed() && $transaction->status === 'posted'
                    && in_array($transaction->direction, ['credit', 'debit'], true);
                if ($transaction->affects_balance) {
                    $change = Money::minor($transaction->amount);
                    $balance = Money::add($balance, $transaction->direction === 'credit' ? $change : -$change);
                }
                $transaction->running_balance = Money::decimal($balance);
            });
    }

    public function totals(Collection $funds): array
    {
        $totals = array_fill_keys(['balance', 'allocated', 'available', 'received', 'spent', 'recovered', 'refunded', 'refunds_paid'], 0);
        foreach ($funds as $fund) {
            $summary = $fund->financial_summary ?? $this->summary($fund);
            foreach ($totals as $key => $value) {
                $totals[$key] = Money::add($value, Money::minor($summary[$key]));
            }
        }

        return array_map(fn (int $amount) => Money::decimal($amount), $totals);
    }
}
