<?php

namespace App\Domain\Qard\Services;

use App\Domain\Finance\Support\Money;
use App\Domain\Qard\Models\QardLoan;
use Illuminate\Support\Facades\DB;

class QardMonitoringService
{
    public function refresh(QardLoan $loan): void
    {
        DB::transaction(function () use ($loan) {
            $this->refreshLocked(app(QardWorkflowService::class)->lock($loan));
        }, 5);
    }

    public function refreshLocked(QardLoan $loan): void
    {
        $overdue = 0;
        $today = now('Asia/Karachi')->toDateString();
        if ($loan->status === 'active') {
            foreach ($loan->installments()->where('revision', $loan->revision)->where('due_date', '<', $today)->get() as $row) {
                $overdue = Money::add($overdue, Money::minor($row->amount) - $row->paidMinor());
            }
        }
        $followup = $loan->followups()->latest('id')->first();
        $due = $loan->status === 'active' && $followup?->next_followup_date
            && $followup->next_followup_date->toDateString() <= $today;
        if ($overdue > 0 || $due) {
            $loan->reminder()->updateOrCreate([], ['message' => ($overdue > 0 ? 'Overdue principal: PKR '.Money::decimal($overdue).'. ' : '').($due ? 'Follow-up is due.' : ''),
                'resolved_at' => null]);
        } else {
            $loan->reminder()->whereNull('resolved_at')->update(['resolved_at' => now()]);
        }
    }
}
