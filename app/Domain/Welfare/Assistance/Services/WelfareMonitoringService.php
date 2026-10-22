<?php

namespace App\Domain\Welfare\Assistance\Services;

use App\Domain\Users\Models\User;
use App\Domain\Welfare\Assistance\Models\WelfareAssistance;
use App\Domain\Welfare\Assistance\Models\WelfareAssistanceReview;
use Carbon\CarbonImmutable;
use Illuminate\Database\UniqueConstraintViolationException;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

class WelfareMonitoringService
{
    public function today(): CarbonImmutable
    {
        return CarbonImmutable::today(config('welfare.timezone'));
    }

    public function suggestedReviewDate(WelfareAssistance $assistance): string
    {
        $base = $this->today();
        if ($assistance->start_date && $assistance->start_date->toDateString() > $base->toDateString()) {
            $base = CarbonImmutable::parse($assistance->start_date->toDateString(), config('welfare.timezone'));
        }
        $next = match ($assistance->frequency) {
            'weekly' => $base->addDays(7),
            'monthly' => $base->addMonthNoOverflow(),
            default => $base->addDays(30),
        };
        if ($assistance->end_date && $assistance->end_date->toDateString() < $next->toDateString()) {
            return max($this->today()->toDateString(), $assistance->end_date->toDateString());
        }

        return $next->toDateString();
    }

    public function reviewRules(): array
    {
        return [
            'idempotency_key' => ['required', 'uuid'],
            'expected_review_id' => ['required', 'integer', 'min:0'],
            'review_date' => ['required', 'date_format:Y-m-d', 'before_or_equal:'.$this->today()->toDateString()],
            'outcome' => ['required', Rule::in(['continued', 'follow_up_required', 'no_longer_needed'])],
            'next_review_date' => ['nullable', 'date_format:Y-m-d', 'after_or_equal:review_date', 'required_unless:outcome,no_longer_needed'],
            'notes' => ['required', 'string', 'max:5000'],
            'follow_up_action' => ['nullable', 'string', 'max:5000', 'required_if:outcome,follow_up_required'],
        ];
    }

    public function recordReview(WelfareAssistance $assistance, array $data, User $actor): WelfareAssistanceReview
    {
        abort_unless($actor->hasPermission('welfare.reviews.manage'), 403);
        $data = Validator::make($data, $this->reviewRules())->validate();
        if ($data['outcome'] === 'no_longer_needed') {
            $data['next_review_date'] = null;
        }

        try {
            return DB::transaction(function () use ($assistance, $data, $actor) {
                $locked = $this->lock($assistance);
                $existing = WelfareAssistanceReview::where('idempotency_key', $data['idempotency_key'])->first();
                if ($existing) {
                    $matches = (int) $existing->assistance_id === (int) $locked->id && (int) $existing->reviewed_by === (int) $actor->id;
                    foreach (['outcome', 'notes', 'follow_up_action'] as $field) {
                        $matches = $matches && (string) $existing->{$field} === (string) ($data[$field] ?? '');
                    }
                    foreach (['review_date', 'next_review_date'] as $field) {
                        $matches = $matches && (string) $existing->{$field}?->toDateString() === (string) ($data[$field] ?? '');
                    }
                    abort_unless($matches, 409, 'This review request was already used with different details. Open a new review form.');

                    return $existing;
                }
                if ($locked->status !== 'approved') {
                    throw ValidationException::withMessages(['status' => 'Only approved assistance can be reviewed.']);
                }
                $latest = $locked->latestReview()->first();
                if ((int) ($latest?->id ?? 0) !== (int) $data['expected_review_id']) {
                    throw ValidationException::withMessages(['review' => 'Another review was recorded while this form was open. Reload the form and review the latest findings.']);
                }
                if ($latest && $data['review_date'] < $latest->review_date->toDateString()) {
                    throw ValidationException::withMessages(['review_date' => 'A new review cannot be dated before the latest recorded review.']);
                }
                $review = $locked->reviews()->create([
                    'reviewed_by' => $actor->id, 'idempotency_key' => $data['idempotency_key'],
                    'review_date' => $data['review_date'], 'outcome' => $data['outcome'],
                    'next_review_date' => $data['next_review_date'] ?? null,
                    'notes' => $data['notes'], 'follow_up_action' => $data['follow_up_action'] ?? null,
                ]);
                $locked->update(['next_review_date' => $review->next_review_date]);
                $this->syncRemindersLocked($locked);

                return $review;
            }, 5);
        } catch (UniqueConstraintViolationException $exception) {
            // Concurrent use on different assistance locks still hits the global key constraint.
            if (WelfareAssistanceReview::where('idempotency_key', $data['idempotency_key'])->exists()) {
                abort(409, 'This review request has already been recorded. Reload the assistance before continuing.');
            }
            throw $exception;
        }
    }

    public function close(WelfareAssistance $assistance, string $reason, User $actor): bool
    {
        abort_unless($actor->hasPermission('welfare.reviews.close'), 403);
        $reason = trim($reason);
        Validator::make(['reason' => $reason], ['reason' => ['required', 'string', 'max:5000']])->validate();

        return DB::transaction(function () use ($assistance, $reason, $actor) {
            $locked = $this->lock($assistance);
            if ($locked->status === 'closed') {
                return false;
            }
            if (! in_array($locked->status, ['approved', 'completed'], true)) {
                throw ValidationException::withMessages(['status' => 'Only approved or completed assistance can be closed.']);
            }
            $from = $locked->status;
            $locked->forceFill(['status' => 'closed', 'closed_by' => $actor->id, 'closed_at' => now(),
                'closure_reason' => $reason, 'next_review_date' => null])->save();
            $locked->statusChanges()->create(['from_status' => $from, 'to_status' => 'closed',
                'changed_by' => $actor->id, 'changed_at' => now(), 'reason' => $reason]);
            $this->syncRemindersLocked($locked);

            return true;
        }, 5);
    }

    public function refreshReminders(WelfareAssistance $assistance): void
    {
        DB::transaction(function () use ($assistance) {
            $this->syncRemindersLocked($this->lock($assistance, true));
        }, 5);
    }

    /** Caller holds the assistance lock; reminders never change financial or approval state. */
    public function syncRemindersLocked(WelfareAssistance $assistance): void
    {
        $today = $this->today()->toDateString();
        $active = [];
        if (! $assistance->trashed() && $assistance->status === 'approved') {
            if ($assistance->next_review_date && $assistance->next_review_date->toDateString() <= $today) {
                $active['review_due'] = $assistance->next_review_date->toDateString();
            }
            // End date is inclusive: flag expiry on the following local calendar day.
            if ($assistance->end_date && $assistance->end_date->toDateString() < $today) {
                $active['period_ended'] = $assistance->end_date->toDateString();
            }
            $latest = $assistance->latestReview()->first();
            if ($latest?->outcome === 'no_longer_needed') {
                $active['closure_requested'] = $latest->review_date->toDateString();
            }
        }
        foreach ($assistance->reminders()->whereNull('resolved_at')->get() as $reminder) {
            if (($active[$reminder->kind] ?? null) !== $reminder->due_date->toDateString()) {
                $reminder->update(['resolved_at' => now()]);
            }
        }
        foreach ($active as $kind => $date) {
            $assistance->reminders()->updateOrCreate(['kind' => $kind, 'due_date' => $date], ['resolved_at' => null]);
        }
    }

    private function lock(WelfareAssistance $assistance, bool $withTrashed = false): WelfareAssistance
    {
        if (DB::connection()->getDriverName() === 'sqlite') {
            DB::table('welfare_assistances')->where('id', $assistance->id)->update(['id' => DB::raw('id')]);
        }

        return WelfareAssistance::query()->when($withTrashed, fn ($query) => $query->withTrashed())
            ->lockForUpdate()->findOrFail($assistance->id);
    }
}
