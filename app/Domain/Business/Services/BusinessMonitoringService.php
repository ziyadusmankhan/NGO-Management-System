<?php

namespace App\Domain\Business\Services;

use App\Domain\Business\Models\BusinessApplication;
use App\Domain\Finance\Support\Money;
use App\Domain\Users\Models\User;
use Carbon\CarbonImmutable;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\DB;

class BusinessMonitoringService
{
    public function __construct(private BusinessWorkflowService $workflow) {}

    public function record(BusinessApplication $application, string $action, array $input, User $actor): void
    {
        $this->workflow->authorize($actor, 'monitor');
        $data = BusinessRules::validate($action, $input);
        $this->workflow->withFiles([$action => $data['evidence'] ?? null], function ($files) use ($application, $action, $data, $actor) {
            DB::transaction(function () use ($application, $action, $data, $actor, $files) {
                $application = $this->workflow->lock($application);
                $this->workflow->current($application, $data);
                if ($application->status !== 'signed' || $application->releasedMinor() < 1) {
                    $this->workflow->fail('status', 'Monitoring and handovers require a funded, open case.');
                }
                $extra = [];
                if ($action === 'handover') {
                    $movement = $application->movements()->whereKey($data['movement_id'])->where('kind', 'disbursement')->where('status', 'posted')->firstOrFail();
                    if ($movement->budgetItem->release_method !== 'asset' || $movement->handover) {
                        $this->workflow->fail('movement_id', 'Select an asset payment that does not yet have a handover.');
                    }
                    if ($data['handed_over_on'] < $movement->payment_date->toDateString()) {
                        $this->workflow->fail('handed_over_on', 'Handover cannot precede purchase.');
                    }
                    $application->handovers()->create(Arr::only($data, ['movement_id', 'recipient', 'handed_over_on', 'items']) + ['actor_id' => $actor->id]);
                    $extra = ['movement_id' => $movement->id];
                } elseif ($action === 'start') {
                    if ($application->started_on) {
                        $this->workflow->fail('started_on', 'The business start is already recorded. Use a monitoring report to record its current condition.');
                    }
                    $firstPayment = CarbonImmutable::parse($application->movements()->where('kind', 'disbursement')->where('status', 'posted')->min('payment_date'))->toDateString();
                    if ($data['started_on'] < $firstPayment) {
                        $this->workflow->fail('started_on', 'The supported business start cannot precede its first release.');
                    }
                    if ($application->movements()->where('status', 'posted')->whereHas('budgetItem', fn ($q) => $q->where('release_method', 'asset'))->whereDoesntHave('handover')->exists()) {
                        $this->workflow->fail('handover', 'Record the outstanding asset handovers first.');
                    }
                    $application->update(['started_on' => $data['started_on'], 'operating_status' => 'running', 'next_review_date' => now(config('business.timezone'))->addMonthsNoOverflow(config('business.monitoring_months'))->toDateString()]);
                } elseif ($action === 'monitor') {
                    $last = $application->monitorings()->latest('id')->first();
                    $firstPayment = CarbonImmutable::parse($application->movements()->where('kind', 'disbursement')->where('status', 'posted')->min('payment_date'))->toDateString();
                    if ($data['review_date'] < $firstPayment || ($last && $data['review_date'] < $last->review_date->toDateString())) {
                        $this->workflow->fail('review_date', 'The review cannot precede funding or the latest review.');
                    }
                    if ($data['condition'] === 'running' && ! $application->started_on) {
                        $this->workflow->fail('condition', 'Record the business start and its evidence before marking it running.');
                    }
                    if ($application->started_on && $data['review_date'] < $application->started_on->toDateString()) {
                        $this->workflow->fail('review_date', 'The review cannot precede the recorded business start.');
                    }
                    $application->monitorings()->create(Arr::except($data, ['version', 'evidence']) + ['actor_id' => $actor->id]);
                    $application->update(['operating_status' => $data['condition'], 'next_review_date' => $data['next_review_date'] ?? null]);
                } else {
                    abort(404);
                }
                $this->workflow->documents($application, $actor, $files, $extra);
                $this->workflow->event($application, $actor, $action.'_recorded', [], Arr::except($data, ['version', 'evidence']));
                $this->refreshLocked($application);
            }, 5);
        });
    }

    public function refresh(BusinessApplication $application): void
    {
        DB::transaction(fn () => $this->refreshLocked($this->workflow->lock($application)), 5);
    }

    public function refreshLocked(BusinessApplication $application): void
    {
        $messages = [];
        $today = now(config('business.timezone'))->toDateString();
        if ($application->status === 'signed') {
            if ($application->next_review_date && $application->next_review_date->toDateString() <= $today) {
                $messages[] = 'Business monitoring is due.';
            }
            if ($application->monitorings()->latest('id')->first()?->final_report) {
                $messages[] = $application->outstandingMinor() > 0 ? 'Final report recorded; principal remains outstanding.' : 'Final report awaits authorized closure.';
            }
            if ($application->funding_status === 'finalized' && $application->support_type === 'repayable') {
                $overdue = 0;
                foreach ($application->installments()->where('funding_version', $application->funding_version)->where('due_date', '<', $today)->get() as $installment) {
                    $overdue = Money::add($overdue, Money::minor($installment->amount) - $installment->paidMinor());
                }
                if ($overdue > 0) {
                    $messages[] = 'Overdue principal: PKR '.Money::decimal($overdue).'.';
                }
            }
        }
        if ($messages) {
            $application->reminder()->updateOrCreate([], ['message' => implode(' ', $messages), 'resolved_at' => null]);
        } else {
            $application->reminder()->whereNull('resolved_at')->update(['resolved_at' => now()]);
        }
    }
}
