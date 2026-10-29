<?php

namespace App\Domain\Projects\Services;

use App\Domain\Committees\Models\Committee;
use App\Domain\Documents\Services\UploadValidator;
use App\Domain\Finance\Funds\Models\Fund;
use App\Domain\Finance\Funds\Services\FundPurposeService;
use App\Domain\Finance\Funds\Services\FundService;
use App\Domain\Finance\Support\Money;
use App\Domain\Procurement\Services\ProcurementGuard;
use App\Domain\Projects\Models\Project;
use App\Domain\Projects\Models\ProjectCategory;
use App\Domain\Users\Models\User;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class ProjectWorkflowService
{
    public function authorize(User $actor, string $action): void
    {
        abort_unless($actor->isActive() && ! $actor->trashed() && $actor->hasPermission('projects.view') && $actor->hasPermission('projects.'.$action), 403);
    }

    public function fail(string $field, string $message): never
    {
        throw ValidationException::withMessages([$field => $message]);
    }

    public function lock(Project $project, bool $archived = false): Project
    {
        if (DB::connection()->getDriverName() === 'sqlite') {
            DB::table('projects')->where('id', $project->id)->update(['id' => DB::raw('id')]);
        }

        return Project::when($archived, fn ($q) => $q->withTrashed())->whereKey($project->id)->lockForUpdate()->firstOrFail();
    }

    public function current(Project $p, array $d): void
    {
        if ((int) $d['version'] !== (int) $p->version) {
            $this->fail('version', 'This project has changed. Reload and review its current details.');
        }
    }

    public function event(Project $p, User $a, string $action, array $before = [], array $after = []): void
    {
        $p->increment('version');
        $p->events()->create(['actor_id' => $a->id, 'action' => $action, 'before_values' => $before, 'after_values' => $after]);
    }

    public function withFiles(array $files, callable $callback): mixed
    {
        $stored = [];
        try {
            foreach ($files as $kind => $file) {
                if ($file) {
                    app(UploadValidator::class)->inspect($file);
                    $path = $file->storeAs('projects', Str::uuid().'.'.strtolower($file->getClientOriginalExtension()), 'private');
                    if (! $path) {
                        throw new \RuntimeException('Evidence could not be saved.');
                    } $stored[$kind] = ['path' => $path, 'original_name' => $file->getClientOriginalName(), 'sha256' => hash_file('sha256', $file->getRealPath())];
                }
            }

            return $callback($stored);
        } catch (\Throwable $e) {
            foreach ($stored as $f) {
                Storage::disk('private')->delete($f['path']);
            } throw $e;
        }
    }

    public function documents(Project $p, User $a, array $files, array $extra = []): void
    {
        foreach ($files as $kind => $f) {
            $p->documents()->create($f + ['kind' => $kind, 'revision' => $p->revision, 'actor_id' => $a->id] + $extra);
        }
    }

    public function eligible(Project $p): void
    {
        $r = $p->proposal();
        $f = Fund::whereKey($p->fund_id)->lockForUpdate()->first();
        if (! $f || $f->status !== 'active' || ! app(FundPurposeService::class)->allowsProject($f, $p, $r->purpose)) {
            $this->fail('fund_id', 'Choose an active unrestricted fund or a fund matching this project purpose. Dedicated funds belong only to their named project.');
        }
        if (! Committee::whereKey($r->committee_id)->where('type', 'implementation')->where('status', 'active')->exists() || ! User::whereKey($r->responsible_id)->where('status', 'active')->exists()) {
            $this->fail('responsible_id', 'Assign an active implementation committee and active responsible user.');
        }
        if (! ProjectCategory::whereKey($r->category_id)->where('active', true)->exists()) {
            $this->fail('category_id', 'Choose an active category.');
        }
    }

    public function save(?Project $p, array $input, User $actor): Project
    {
        $this->authorize($actor, $p?->approved_revision ? 'amend' : 'manage');
        $d = ProjectRules::validate($p ? 'update' : 'store', $input);
        $files = ['estimates' => $d['evidence'] ?? null];
        foreach ($d['attachments'] ?? [] as $i => $f) {
            $files['proposal_'.$i] = $f;
        }

        return $this->withFiles($files, function ($stored) use ($p, $d, $actor) {
            return DB::transaction(function () use ($p, $d, $actor, $stored) {
                $before = [];
                if ($p) {
                    $p = $this->lock($p);
                    $this->authorize($actor, $p->approved_revision ? 'amend' : 'manage');
                    $this->current($p, $d);
                    $old = $p->proposal();
                    if ($old->stage !== 'draft' || in_array($p->status, ['closed', 'cancelled']) && $old->kind !== 'reopen') {
                        $this->fail('status', 'Create a correction or amendment revision before editing.');
                    }
                    if ($p->fund_locked_at && (int) $p->fund_id !== (int) ($d['fund_id'] ?? 0)) {
                        $this->fail('fund_id', 'The original fund is locked.');
                    }
                    if ($p->fund?->project_id && (int) $p->fund_id !== (int) ($d['fund_id'] ?? 0)) {
                        $this->fail('fund_id', 'Keep the dedicated fund bound to this project as its original fund.');
                    }
                    $before = $old->toArray();
                } else {
                    $p = Project::create(['number' => 'PRJ-'.Str::ulid(), 'name' => $d['name'], 'created_by' => $actor->id]);
                    $p->update(['number' => sprintf('PRJ-%05d', $p->id)]);
                }
                $category = ProjectCategory::findOrFail($d['category_id']);
                if (! $category->active) {
                    $this->fail('category_id', 'Choose an active category.');
                }
                $p->update(['fund_id' => $d['fund_id'] ?? null]);
                $total = 0;
                $items = [];
                $seen = [];
                foreach ($d['items'] as $i) {
                    $amount = Money::minor($i['amount']);
                    if ($amount < 1) {
                        $this->fail('items', 'Each budget limit must be positive.');
                    }
                    $total = Money::add($total, $amount);
                    $id = $i['budget_item_id'] ?? null;
                    $item = $id ? $p->items()->find($id) : $p->items()->create(['reference' => 'PBI-'.Str::ulid()]);
                    if (! $item) {
                        $this->fail('items', 'Budget items must belong to this project.');
                    }
                    if ($amount < $p->netMinor($item->id)) {
                        $this->fail('items', 'A revised item limit cannot be below its net expenditure.');
                    }
                    $seen[] = $item->id;
                    $items[] = Arr::only($i, ['description', 'kind', 'payee', 'price_checks', 'estimate', 'exception_reason']) + ['budget_item_id' => $item->id, 'amount' => Money::decimal($amount)];
                }
                if ($total > Money::minor('9999999999999.99')) {
                    $this->fail('items', 'The total budget is too large.');
                }
                foreach ($p->items()->whereNotIn('id', $seen)->get() as $missing) {
                    if ($p->netMinor($missing->id) > 0) {
                        $this->fail('items', 'Retain every item with existing net expenditure in the amended budget.');
                    }
                }
                $fields = Arr::only($d, ['name', 'description', 'location', 'outcomes', 'approach', 'beneficiaries', 'category_id', 'committee_id', 'responsible_id', 'planned_start', 'planned_completion']);
                $p->revisions()->updateOrCreate(['revision' => $p->revision], $fields + ['category_name' => $category->name, 'purpose' => $category->purpose, 'budget' => Money::decimal($total), 'prepared_by' => $actor->id]);
                $p->budgets()->delete();
                foreach ($items as $i) {
                    $p->budgets()->create($i + ['revision' => $p->revision]);
                }
                if (! $p->approved_revision) {
                    $p->update(['name' => $d['name']]);
                }
                $this->documents($p, $actor, $stored);
                $this->event($p, $actor, 'draft_saved', $before, $fields + ['budget' => $items]);

                return $p;
            }, 5);
        });
    }

    public function submit(Project $p, array $input, User $actor): void
    {
        $d = ProjectRules::validate('submit', $input);
        DB::transaction(function () use ($p, $d, $actor) {
            $p = $this->lock($p);
            $this->authorize($actor, $p->approved_revision ? 'amend' : 'manage');
            $this->current($p, $d);
            if ($p->proposal()->stage !== 'draft') {
                $this->fail('status', 'Only a draft revision can be submitted.');
            }
            $this->eligible($p);
            $this->limits($p, $p->revision);
            $p->proposal()->update(['stage' => 'submitted', 'submitted_at' => now()]);
            $p->update(['fund_locked_at' => $p->fund_locked_at ?? now()]);
            $this->event($p, $actor, 'submitted', [], ['revision' => $p->revision]);
        }, 5);
    }

    public function limits(Project $p, int $revision): void
    {
        app(ProcurementGuard::class)->projectBudget($p, $revision);
        $r = $p->revisions()->where('revision', $revision)->firstOrFail();
        if (Money::minor($r->budget) < $p->netMinor()) {
            $this->fail('budget', 'The budget cannot be below existing net expenditure.');
        }
        $items = $p->budgets($revision)->get()->keyBy('budget_item_id');
        foreach ($p->items()->get() as $i) {
            if ($p->netMinor($i->id) > Money::minor($items->get($i->id)?->amount ?? '0')) {
                $this->fail('budget', 'An item limit cannot be below its existing net expenditure.');
            }
        }
    }

    public function verify(Project $p, array $input, User $actor): void
    {
        $this->authorize($actor, 'verify');
        $d = ProjectRules::validate('verify', $input);
        $this->withFiles(['field_review' => $d['evidence']], function ($files) use ($p, $d, $actor) {
            DB::transaction(function () use ($p, $d, $actor, $files) {
                $p = $this->lock($p);
                $this->current($p, $d);
                if ($p->proposal()->stage !== 'submitted') {
                    $this->fail('status', 'Submit this revision before field review.');
                }
                $p->reviews()->create(['revision' => $p->revision, 'stage' => 'field', 'decision' => 'verified', 'notes' => $d['notes'], 'actor_id' => $actor->id]);
                $this->documents($p, $actor, $files);
                $p->proposal()->update(['stage' => 'coordinator']);
                $this->event($p, $actor, 'field_reviewed', [], ['notes' => $d['notes']]);
            }, 5);
        });
    }

    public function decide(Project $p, string $stage, array $input, User $actor): void
    {
        $permission = ['coordinator' => 'recommend', 'secretary' => 'review', 'committee' => 'approve'][$stage] ?? null;
        abort_unless($permission, 404);
        $this->authorize($actor, $permission);
        $d = ProjectRules::validate('decide', $input);
        $this->withFiles(['resolution' => $d['evidence'] ?? null], function ($files) use ($p, $stage, $d, $actor) {
            DB::transaction(function () use ($p, $stage, $d, $actor, $files) {
                $p = $this->lock($p);
                $this->current($p, $d);
                $r = $p->proposal();
                if ($r->stage !== $stage) {
                    $this->fail('status', 'This is not the current decision stage.');
                }
                if (in_array($actor->id, [$p->created_by, $r->prepared_by]) || $p->reviews()->where('revision', $p->revision)->whereIn('stage', ['coordinator', 'secretary', 'committee'])->where('actor_id', $actor->id)->exists()) {
                    $this->fail('actor', 'The creator and revision preparer cannot approve. Each decision stage needs a different person.');
                }
                $resolution = [];
                if ($stage === 'committee' && $d['decision'] === 'accept') {
                    $this->eligible($p);
                    $this->limits($p, $p->revision);
                    if (! $files || empty($d['resolution_reference']) || empty($d['resolution_date'])) {
                        $this->fail('resolution', 'Record the resolution reference, date and attachment.');
                    }
                    $committee = Committee::whereKey($d['committee_id'] ?? 0)->where('type', 'decision')->where('status', 'active')->first();
                    $ids = $d['consenting_member_ids'] ?? [];
                    $members = $committee?->members()->wherePivot('status', 'active')->whereNull('committee_members.ended_at')->whereDate('committee_members.joined_at', '<=', $d['resolution_date'])->where('members.status', 'active')->whereIn('members.id', $ids)->get();
                    if (! $members || $members->count() < 2 || $members->count() !== count($ids)) {
                        $this->fail('consenting_member_ids', 'Select at least two distinct active members of the decision committee.');
                    }
                    if ($members->contains(fn ($m) => in_array($m->user_id, [$p->created_by, $r->prepared_by]))) {
                        $this->fail('consenting_member_ids', 'The creator or revision preparer cannot provide approval consent.');
                    }
                    $exceptions = [];
                    foreach ($p->budgets()->get() as $i) {
                        if ($i->kind === 'purchase') {
                            $quotes = collect($i->price_checks ?? [])->filter(fn ($q) => Money::minor($q['amount']) > 0)->unique(fn ($q) => mb_strtolower(trim($q['supplier'])));
                            if ($quotes->count() < 2) {
                                if (! $i->exception_reason || empty($d['approve_purchase_exceptions'])) {
                                    $this->fail('price_checks', 'Purchases need two documented price checks or an explicit committee exception.');
                                } $exceptions[$i->budget_item_id] = $i->exception_reason;
                            } elseif (! $p->documents()->where('revision', $p->revision)->where('kind', 'estimates')->exists()) {
                                $this->fail('evidence', 'Upload price-check evidence with this revision.');
                            }
                        } elseif (! $i->estimate || ! $p->documents()->where('revision', $p->revision)->where('kind', 'estimates')->exists()) {
                            $this->fail('estimate', 'Labor and services need a documented estimate or quotation and evidence.');
                        }
                    }
                    $resolution = Arr::only($d, ['committee_id', 'resolution_reference', 'resolution_date']) + ['consents' => $members->map(fn ($m) => ['member_id' => $m->id, 'user_id' => $m->user_id, 'name' => $m->full_name])->all(), 'exceptions' => $exceptions];
                }
                $p->reviews()->create(['revision' => $p->revision, 'stage' => $stage, 'decision' => $d['decision'], 'notes' => $d['notes'], 'actor_id' => $actor->id, 'resolution' => $resolution]);
                $this->documents($p, $actor, $files);
                $next = $d['decision'] === 'reject' ? 'rejected' : (['coordinator' => 'secretary', 'secretary' => 'committee', 'committee' => 'approved'][$stage]);
                $r->update(['stage' => $next, 'approved_at' => $next === 'approved' ? now() : null]);
                if ($next === 'approved') {
                    $status = $r->kind === 'reopen' ? ($p->started_on ? 'in_progress' : 'approved') : ($p->approved_revision ? $p->status : 'approved');
                    $p->update(['approved_revision' => $p->revision, 'payments_frozen' => false, 'name' => $r->name, 'status' => $status, 'closed_at' => null, 'completed_on' => $r->kind === 'reopen' ? null : $p->completed_on, 'next_review_date' => now(config('projects.timezone'))->addMonthNoOverflow()->toDateString()]);
                }
                $this->event($p, $actor, 'decision_'.$stage, [], ['revision' => $p->revision, 'decision' => $d['decision'], 'resolution' => $resolution]);
            }, 5);
        });
    }

    public function transition(Project $p, string $action, array $input, User $actor): void
    {
        $permission = match ($action) {
            'amend','withdraw','resume' => 'amend','reopen','close','cancel' => 'close',default => 'manage'
        };
        $this->authorize($actor, $permission);
        $d = ProjectRules::validate($action, $input);
        $this->withFiles(['final_accounting' => $d['evidence'] ?? null], function ($files) use ($p, $action, $d, $actor) {
            DB::transaction(function () use ($p, $action, $d, $actor, $files) {
                $p = $this->lock($p);
                $this->current($p, $d);
                $r = $p->proposal();
                $before = $p->toArray();
                if (in_array($action, ['close', 'cancel'])) {
                    app(ProcurementGuard::class)->assertNoOpen('project', $p->id);
                }
                if (in_array($action, ['amend', 'reopen'])) {
                    if ($action === 'reopen' && ! in_array($p->status, ['closed', 'cancelled']) || $action === 'amend' && in_array($p->status, ['closed', 'cancelled'])) {
                        $this->fail('status', 'Use reopening for a closed or cancelled project.');
                    }
                    if (! in_array($r->stage, ['approved', 'rejected', 'withdrawn'])) {
                        $this->fail('status', 'Finish or withdraw the current revision first.');
                    }
                    $next = $p->revision + 1;
                    $copy = $r->replicate();
                    $copy->fill(['revision' => $next, 'stage' => 'draft', 'kind' => $action === 'reopen' ? 'reopen' : ($p->approved_revision ? 'amendment' : 'proposal'), 'reason' => $d['reason'], 'prepared_by' => $actor->id, 'submitted_at' => null, 'approved_at' => null]);
                    $copy->save();
                    foreach ($p->budgets()->get() as $item) {
                        $copy = $item->replicate();
                        $copy->revision = $next;
                        $copy->legacy_direct_purchase = false;
                        $copy->save();
                    }
                    // Evidence remains explicitly associated with the prior revision; attach fresh estimates for the new revision.
                    $p->update(['revision' => $next, 'payments_frozen' => (bool) $p->approved_revision]);
                } elseif ($action === 'withdraw') {
                    if (! $p->approved_revision || in_array($r->stage, ['approved', 'withdrawn'])) {
                        $this->fail('status', 'Only a pending amendment can be withdrawn.');
                    } $r->update(['stage' => 'withdrawn']);
                } elseif ($action === 'resume') {
                    if (! $p->approved_revision || ! $p->payments_frozen || ! in_array($r->stage, ['withdrawn', 'rejected']) || in_array($p->status, ['closed', 'cancelled'])) {
                        $this->fail('status', 'Resume only after rejecting or withdrawing a pending amendment on an open project.');
                    } $p->update(['payments_frozen' => false]);
                } elseif ($action === 'close') {
                    $final = $p->progress()->where('kind', 'complete')->latest('id')->first();
                    if ($p->status !== 'completed' || $p->payments_frozen || ! $final) {
                        $this->fail('status', 'Complete the final report and resolve amendments before closure.');
                    }
                    $this->independentClosure($p, $actor, [$final->actor_id]);
                    $p->update(['status' => 'closed', 'closed_at' => now(), 'next_review_date' => null]);
                } elseif ($action === 'cancel') {
                    if (in_array($p->status, ['closed', 'cancelled'])) {
                        $this->fail('status', 'This project has already ended.');
                    }
                    if ($p->grossMinor() > 0) {
                        $this->independentClosure($p, $actor);
                        if (! $files) {
                            $this->fail('evidence', 'Attach the final accounting report before cancelling a project with spending.');
                        }
                    }
                    if (! in_array($r->stage, ['approved', 'rejected', 'withdrawn'])) {
                        $r->update(['stage' => 'withdrawn']);
                    }
                    $p->update(['status' => 'cancelled', 'closed_at' => now(), 'next_review_date' => null, 'payments_frozen' => false]);
                } elseif ($action === 'archive') {
                    if (! in_array($p->status, ['closed', 'cancelled'])) {
                        $this->fail('status', 'Close or cancel this project before archiving.');
                    } $p->delete();
                }
                $this->documents($p, $actor, $files);
                $this->event($p, $actor, $action, $before, ['reason' => $d['reason'], 'status' => $p->status, 'revision' => $p->revision]);
            }, 5);
        });
    }

    private function independentClosure(Project $p, User $actor, array $extra = []): void
    {
        if (in_array($actor->id, [$p->created_by, $p->proposal()->responsible_id, ...$extra]) || $p->movements()->where('recorded_by', $actor->id)->exists() || $p->progress()->where('actor_id', $actor->id)->exists()) {
            $this->fail('actor', 'Closure or spent-project cancellation needs an approver independent of creation, implementation and payments.');
        }
    }

    public function dedicatedFund(Project $p, array $input, User $actor): void
    {
        $d = ProjectRules::validate('dedicated-fund', $input);
        abort_unless($actor->hasPermission('finance.funds.manage') && $actor->hasPermission('projects.view'), 403);
        DB::transaction(function () use ($p, $d, $actor) {
            $p = $this->lock($p);
            $this->current($p, $d);
            if ($p->fund_id || $p->fund_locked_at || $p->proposal()->stage !== 'draft') {
                $this->fail('fund_id', 'Create a dedicated fund only for a draft with no assigned fund.');
            }
            $fund = app(FundService::class)->create(['name' => $d['name'], 'type' => 'restricted', 'purpose' => $p->proposal()->purpose, 'opening_balance' => '0.00', 'status' => 'active', 'restrict_spending' => true, 'protect_restricted_designation' => true, 'prevent_negative_balance' => true], $actor);
            $fund->forceFill(['project_id' => $p->id])->save();
            $p->update(['fund_id' => $fund->id]);
            $this->event($p, $actor, 'dedicated_fund_created', [], ['fund_id' => $fund->id]);
        }, 5);
    }
}
