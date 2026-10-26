<?php

namespace App\Domain\Finance\Funds\Services;

use App\Domain\Business\Models\BusinessApplication;
use App\Domain\Finance\Funds\Models\Fund;
use App\Domain\Finance\Support\Money;
use App\Domain\Procurement\Models\Procurement;
use App\Domain\Projects\Models\Project;
use App\Domain\Qard\Models\QardLoan;
use App\Domain\Users\Models\User;
use App\Domain\Welfare\Applications\Models\WelfareApplication;
use App\Domain\Welfare\Assistance\Models\WelfareAssistance;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class FundService
{
    public function create(array $data, User $actor): Fund
    {
        abort_unless($actor->hasPermission('finance.funds.manage'), 403);

        return DB::transaction(function () use ($data, $actor) {
            unset($data['project_id']);
            $data['opening_balance'] = Money::decimal(Money::minor($data['opening_balance'] ?? '0.00'));
            $fund = Fund::create(array_merge($data, ['fund_reference' => 'FND-'.Str::ulid()]));
            $fund->update(['fund_reference' => sprintf('FUND-%05d', $fund->id)]);
            $fund->refresh();
            $this->audit($fund, 'created', [], $fund->only($fund->getFillable()), $actor);

            return $fund;
        }, 5);
    }

    public function update(Fund $fund, array $data, User $actor): void
    {
        abort_unless($actor->hasPermission('finance.funds.manage'), 403);

        DB::transaction(function () use ($fund, $data, $actor) {
            $locked = $this->lock($fund);
            if (array_key_exists('project_id', $data) && (int) $data['project_id'] !== (int) $locked->project_id) {
                throw ValidationException::withMessages(['project_id' => 'Dedicated project bindings cannot be changed.']);
            }
            unset($data['project_id']);
            $before = $locked->only($locked->getFillable());
            if (array_key_exists('opening_balance', $data)) {
                $data['opening_balance'] = Money::decimal(Money::minor($data['opening_balance'] ?? '0.00'));
            }
            $proposed = (clone $locked)->fill($data);
            if ($this->hasActivity($locked)) {
                foreach (['opening_balance', 'opening_balance_date', 'type', 'purpose', 'restrict_spending', 'protect_restricted_designation'] as $field) {
                    if ((string) $proposed->{$field} !== (string) $locked->{$field}) {
                        throw ValidationException::withMessages([
                            $field => 'The opening balance and designation are protected once this fund has financial activity or linked commitments. Use a separate correctly designated fund for new activity.',
                        ]);
                    }
                }
            }
            $locked->fill($data);
            if (! $locked->isDirty()) {
                return;
            }
            $locked->save();
            $this->audit($locked, 'updated', $before, $locked->only($locked->getFillable()), $actor);
        }, 5);
    }

    public function archive(Fund $fund, User $actor): void
    {
        abort_unless($actor->hasPermission('finance.funds.manage'), 403);

        DB::transaction(function () use ($fund, $actor) {
            $locked = $this->lock($fund);
            $summary = app(FundReportingService::class)->summary($locked);
            foreach (BusinessApplication::withTrashed()->where('fund_id', $locked->id)->where('support_type', 'repayable')->get() as $business) {
                if ($business->outstandingMinor() > 0) {
                    throw ValidationException::withMessages(['fund' => 'This fund has outstanding business principal. Keep it available for recovery.']);
                }
            }
            if (QardLoan::where('fund_id', $locked->id)->where('status', 'active')->exists()) {
                throw ValidationException::withMessages(['fund' => 'This fund has outstanding Qard principal. Keep it available for repayments.']);
            }
            if (Money::minor($summary['balance']) !== 0 || Money::minor($summary['allocated']) !== 0) {
                throw ValidationException::withMessages(['fund' => 'Only a fund with zero ledger balance and no unpaid approved allocations can be archived.']);
            }
            $this->audit($locked, 'archived', ['archived' => false], ['archived' => true], $actor);
            $locked->delete();
        }, 5);
    }

    public function hasActivity(Fund $fund): bool
    {
        return (bool) $fund->project_id
            || Procurement::withTrashed()->where('fund_id', $fund->id)->exists()
            || Project::withTrashed()->where('fund_id', $fund->id)->whereNotNull('fund_locked_at')->exists()
            || $fund->transactions()->withTrashed()->exists()
            || BusinessApplication::withTrashed()->where('fund_id', $fund->id)->exists()
            || QardLoan::where('fund_id', $fund->id)->exists()
            || $fund->donations()->withTrashed()->exists()
            || $fund->expenses()->withTrashed()->exists()
            || WelfareAssistance::withTrashed()->where('fund_id', $fund->id)->exists()
            || WelfareApplication::withTrashed()->where('fund_id', $fund->id)->exists();
    }

    private function lock(Fund $fund): Fund
    {
        if (DB::connection()->getDriverName() === 'sqlite') {
            DB::table('funds')->where('id', $fund->id)->update(['id' => DB::raw('id')]);
        }

        return Fund::query()->lockForUpdate()->findOrFail($fund->id);
    }

    private function audit(Fund $fund, string $action, array $before, array $after, User $actor): void
    {
        $fund->changes()->create([
            'action' => $action, 'before_values' => $before, 'after_values' => $after,
            'changed_by' => $actor->id, 'changed_at' => now(),
        ]);
    }
}
