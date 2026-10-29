<?php

namespace App\Domain\Procurement\Services;

use App\Domain\Committees\Models\Committee;
use App\Domain\Documents\Services\UploadValidator;
use App\Domain\Finance\Funds\Models\Fund;
use App\Domain\Finance\Funds\Services\FundPurposeService;
use App\Domain\Finance\Support\Money;
use App\Domain\Members\Models\Member;
use App\Domain\Procurement\Models\Procurement;
use App\Domain\Procurement\Models\ProcurementEvent;
use App\Domain\Procurement\Models\ProcurementPolicy;
use App\Domain\Procurement\Models\ProcurementSupplier;
use App\Domain\Users\Models\User;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class ProcurementWorkflowService
{
    public function fail(string $field, string $message): never
    {
        throw ValidationException::withMessages([$field => $message]);
    }

    public function authorize(User $actor, string $action, ?Procurement $p = null): void
    {
        abort_unless($actor->isActive() && ! $actor->trashed() && $actor->hasPermission('procurement.view') && $actor->hasPermission('procurement.'.$action), 403);
        if ($p && $p->parent_type !== 'office') {
            abort_unless($actor->hasPermission($p->parent_type === 'project' ? 'projects.view' : 'business.view'), 403);
        }
    }

    public function lock(Procurement $p): Procurement
    {
        // Always parent -> procurement -> fund. SQLite obtains its writer lock before any snapshot read.
        if (DB::connection()->getDriverName() === 'sqlite') {
            DB::table('procurements')->where('id', $p->id)->update(['id' => DB::raw('id')]);
        }
        $parent = $p->parentRecord();
        if ($parent) {
            $parent->newQuery()->withTrashed()->whereKey($parent->id)->lockForUpdate()->firstOrFail();
        }

        return Procurement::withTrashed()->whereKey($p->id)->lockForUpdate()->firstOrFail();
    }

    public function current(Procurement $p, array $d): void
    {
        if ((int) $p->version !== (int) $d['version']) {
            $this->fail('version', 'This purchase changed. Reload its page before continuing.');
        }
    }

    public function event(?Procurement $p, User $a, string $action, array $before = [], array $after = []): void
    {
        if ($p) {
            $p->increment('version');
        }
        ProcurementEvent::create(['procurement_id' => $p?->id, 'actor_id' => $a->id, 'action' => $action, 'before_values' => $before, 'after_values' => $after]);
    }

    public function files(array $input, callable $callback): mixed
    {
        $stored = [];
        try {
            if ($f = $input['evidence'] ?? null) {
                app(UploadValidator::class)->inspect($f);
                // A retry may reuse the upload object and its cached hashName.
                // Give each staging attempt its own path so cleanup cannot erase retained evidence.
                $path = $f->storeAs('procurement', Str::uuid().'.'.strtolower($f->getClientOriginalExtension()), 'private');
                if (! $path) {
                    throw new \RuntimeException('Evidence could not be saved.');
                } $stored = ['path' => $path, 'original_name' => $f->getClientOriginalName(), 'sha256' => hash_file('sha256', $f->getRealPath())];
            }

            return $callback($stored);
        } catch (\Throwable $e) {
            if ($stored) {
                Storage::disk('private')->delete($stored['path']);
            } throw $e;
        }
    }

    public function document(Procurement $p, User $a, string $kind, array $f, ?int $id = null): void
    {
        if ($f) {
            $p->documents()->create($f + ['revision' => $p->revision, 'kind' => $kind, 'record_id' => $id, 'actor_id' => $a->id]);
        }
    }

    public function mutate(Procurement $p, array $d, User $a, string $action, callable $callback): mixed
    {
        return $this->files($d, fn ($f) => DB::transaction(function () use ($p, $d, $a, $action, $callback, $f) {
            $p = $this->lock($p);
            $this->current($p, $d);
            $before = $p->toArray();
            $result = $callback($p, $f);
            $this->event($p, $a, $action, $before, ['revision' => $p->revision, 'status' => $p->status, 'reason' => $d['reason'] ?? $d['notes'] ?? null] + Arr::only($d, ['record_type', 'record_id', 'return_id', 'invoice_line_id', 'receipt_id']));

            return $result;
        }, 5));
    }

    public function eligible(Procurement $p, array $items, ?int $except = null, ?string $supplierName = null): void
    {
        $f = Fund::whereKey($p->fund_id)->lockForUpdate()->first();
        if (! $f || $f->status !== 'active') {
            $this->fail('fund_id', 'Choose an active original fund.');
        }
        if ($p->parent_type === 'office') {
            if ($f->project_id || app(FundPurposeService::class)->isRestricted($f)) {
                $this->fail('fund_id', 'Office purchases require an unrestricted fund.');
            }

            return;
        }
        $parent = $p->parentRecord();
        if ((int) $parent->fund_id !== (int) $p->fund_id) {
            $this->fail('fund_id', 'Use the original parent fund.');
        }
        if ($p->parent_type === 'project' && (! $parent->approved_revision || $parent->payments_frozen || ! in_array($parent->status, ['approved', 'in_progress', 'completed']))) {
            $this->fail('parent', 'Use an approved open project without a pending amendment.');
        }
        if ($p->parent_type === 'business' && (! in_array($parent->status, ['approved', 'signed']) || $parent->funding_status !== 'open')) {
            $this->fail('parent', 'Use approved, open Business funding.');
        }
        foreach ($items as $i) {
            $budget = $p->parent_type === 'project' ? $parent->budgets($parent->approved_revision)->where('budget_item_id', $i['budget_item_id'])->first() : $parent->currentBudget()->find($i['budget_item_id']);
            $goods = $budget && ($p->parent_type === 'project' ? $budget->kind === 'purchase' : $budget->release_method !== 'cash');
            if (! $goods) {
                $this->fail('items', 'Select an approved goods/asset budget item from this parent.');
            }
            $payee = $p->parent_type === 'project' ? $budget->payee : $budget->supplier;
            if ($payee !== ($supplierName ?? $p->proposal()->supplier_snapshot['name'])) {
                $this->fail('supplier_id', 'The supplier must match the approved parent payee. Amend the parent approval first.');
            }
            $limit = Money::minor($p->parent_type === 'project' ? $budget->amount : $budget->organization_amount);
            $paid = $p->parent_type === 'project' ? $parent->netMinor($i['budget_item_id']) : $budget->paidMinor();
            $other = app(ProcurementGuard::class)->reserved($p->parent_type, $parent->id, $i['budget_item_id'], $except);
            // This order's paid amounts are already included in parent spending.
            $ownLines = $p->lines()->where('item_key', $i['key'])->pluck('id');
            $ownPaid = Money::sumMinor($p->movements()->whereIn('invoice_line_id', $ownLines)->where('kind', 'payment')->where('status', 'posted')->getQuery());
            $ownRefund = Money::sumMinor($p->movements()->whereIn('invoice_line_id', $ownLines)->where('kind', 'refund')->where('status', 'posted')->getQuery());
            $credit = (int) $p->returns()->where('item_key', $i['key'])->where('status', 'posted')->sum('credit_minor');
            if ($limit < $paid + $other + max(0, $i['amount_minor'] - $credit - $ownPaid + $ownRefund)) {
                $this->fail('items', 'The item budget is already spent or reserved by another order.');
            }
        }
    }

    public function save(?Procurement $p, array $input, User $a): Procurement
    {
        $this->authorize($a, 'manage', $p);
        $d = ProcurementRules::validate($p ? 'update' : 'store', $input);

        return $this->files($d, fn ($f) => DB::transaction(function () use ($p, $d, $a, $f) {
            $supplier = ProcurementSupplier::where('active', true)->findOrFail($d['supplier_id']);
            if ($p) {
                $p = $this->lock($p);
                $this->current($p, $d);
                if ($p->trashed() || $p->proposal()->stage !== 'draft' || in_array($p->status, ['closed', 'cancelled'])) {
                    $this->fail('status', 'Create a revision before editing this record.');
                }
                if ($p->parent_type !== $d['parent_type'] || (int) $p->parent_id !== (int) ($d['parent_id'] ?? 0) || (int) $p->fund_id !== (int) $d['fund_id']) {
                    $this->fail('parent', 'The parent and original fund cannot be changed.');
                }
            } else {
                $p = Procurement::create(Arr::only($d, ['parent_type', 'parent_id', 'fund_id']) + ['number' => 'PRC-'.Str::ulid(), 'created_by' => $a->id]);
                $p->update(['number' => sprintf('PRC-%05d', $p->id)]);
            }
            $this->authorize($a, 'manage', $p);
            $old = $p->revisions()->where('revision', $p->revision)->first();
            $oldItems = collect($old?->items ?? [])->keyBy('key');
            $items = [];
            $total = 0;
            foreach ($d['items'] as $i) {
                $key = $i['key'] ?? (string) Str::uuid();
                if (isset($i['key']) && ! $oldItems->has($key)) {
                    $this->fail('items', 'Use item references from this revision.');
                }
                $q = ProcurementMath::quantity($i['quantity']);
                $price = Money::minor($i['unit_price']);
                $amount = ProcurementMath::cost($q, $price);
                if ($q < 1 || $amount < 1) {
                    $this->fail('items', 'Quantities and line totals must be positive.');
                }
                $items[] = ['key' => $key, 'description' => $i['description'], 'unit' => $i['unit'], 'quantity_milli' => $q, 'unit_minor' => $price, 'amount_minor' => $amount, 'budget_item_id' => $i['budget_item_id'] ?? null];
                $total = Money::add($total, $amount);
            }
            if ($total > Money::minor('9999999999999.99')) {
                $this->fail('items', 'The total is too large.');
            }
            $this->revisionDependencies($p, $items, $supplier->id);
            $p->revisions()->updateOrCreate(['revision' => $p->revision], Arr::only($d, ['title', 'description', 'delivery_location', 'required_on', 'exception_reason']) + ['supplier_id' => $supplier->id, 'supplier_snapshot' => $supplier->only(['name', 'contact', 'address', 'registration']), 'items' => $items, 'total_minor' => $total, 'prepared_by' => $a->id]);
            $this->eligible($p, $items, $p->id);
            $this->document($p, $a, 'proposal', $f);
            $this->event($p, $a, 'draft_saved', [], ['items' => $items]);

            return $p->fresh();
        }, 5));
    }

    public function revisionDependencies(Procurement $p, array $items, int $supplier): void
    {
        $new = collect($items)->keyBy('key');
        foreach ($p->approved()?->items ?? [] as $old) {
            $has = $p->receipts()->where('item_key', $old['key'])->where('status', 'posted')->exists();
            if (! $has) {
                continue;
            }
            $i = $new->get($old['key']);
            if (! $i || $supplier !== $p->approved()->supplier_id || $i['unit_minor'] !== $old['unit_minor'] || $i['budget_item_id'] != $old['budget_item_id'] || $i['description'] !== $old['description'] || $i['unit'] !== $old['unit']) {
                $this->fail('items', 'Retain supplier, specification, unit price and budget mapping for items with receipts. Use a separate request for different goods.');
            }
            $accepted = (int) $p->receipts()->where('item_key', $old['key'])->where('status', 'posted')->sum('accepted_milli');
            if ($i['quantity_milli'] < $accepted) {
                $this->fail('items', 'Revised quantities cannot be below accepted deliveries.');
            }
        }
    }

    public function quote(Procurement $p, array $input, User $a): void
    {
        $this->authorize($a, 'quotations', $p);
        $d = ProcurementRules::validate('quote', $input);
        $this->mutate($p, $d, $a, 'quote_added', function ($p, $f) use ($d, $a) {
            $r = $p->proposal();
            if ($r->stage !== 'draft' || $p->trashed() || in_array($p->status, ['closed', 'cancelled'])) {
                $this->fail('status', 'Quotes belong to an open draft revision.');
            }
            $s = ProcurementSupplier::where('active', true)->findOrFail($d['supplier_id']);
            $items = [];
            $total = 0;
            $prices = collect($d['items'])->keyBy('key');
            if ($prices->count() !== count($r->items)) {
                $this->fail('items', 'Quote every requested item on the same quantities and specifications.');
            }
            foreach ($r->items as $i) {
                if (! $prices->has($i['key'])) {
                    $this->fail('items', 'Quote every requested item.');
                }
                $price = Money::minor($prices[$i['key']]['unit_price']);
                $cost = ProcurementMath::cost($i['quantity_milli'], $price);
                if ($cost < 1) {
                    $this->fail('items', 'Quote a positive price.');
                }
                $items[] = $i + ['quoted_unit_minor' => $price, 'quoted_amount_minor' => $cost];
                $total = Money::add($total, $cost);
            }
            if ($p->quotes()->where('revision', $p->revision)->where('supplier_id', $s->id)->exists()) {
                $this->fail('supplier', 'This supplier already quoted this revision. Correct the request through a new revision.');
            }
            $q = $p->quotes()->create(Arr::only($d, ['reference', 'quoted_on', 'valid_until', 'terms']) + ['revision' => $p->revision, 'supplier_id' => $s->id, 'supplier_snapshot' => $s->only(['name', 'contact', 'registration']), 'items' => $items, 'total_minor' => $total, 'actor_id' => $a->id]);
            $this->document($p, $a, 'quote', $f, $q->id);
        });
    }

    public function checkQuotes(Procurement $p, bool $acceptException = false, bool $requireExceptionApproval = false): void
    {
        $r = $p->proposal();
        $quotes = $p->quotes()->where('revision', $p->revision)->whereDate('valid_until', '>=', now('Asia/Karachi')->toDateString())->get()->filter(function ($quote) use ($r) {
            if (count($quote->items) !== count($r->items)) {
                return false;
            }
            foreach ($r->items as $i) {
                $q = collect($quote->items)->firstWhere('key', $i['key']);
                if (! $q || $q['quantity_milli'] !== $i['quantity_milli'] || $q['description'] !== $i['description'] || $q['unit'] !== $i['unit']) {
                    return false;
                }
            }

            return true;
        });
        $selected = $quotes->firstWhere('supplier_id', $r->supplier_id);
        if (! $selected || $selected->total_minor != $r->total_minor) {
            $this->fail('quotes', 'Attach a valid selected supplier quotation matching the proposed order totals.');
        }
        foreach ($r->items as $i) {
            $q = collect($selected->items)->firstWhere('key', $i['key']);
            if (! $q || $q['quoted_unit_minor'] !== $i['unit_minor'] || $q['quantity_milli'] !== $i['quantity_milli']) {
                $this->fail('quotes', 'The selected quote must match each proposed item and quantity.');
            }
        }
        $exception = $quotes->count() < ($r->quote_count ?? 2) || $quotes->min('total_minor') < $selected->total_minor;
        if ($exception && (! $r->exception_reason || ($requireExceptionApproval && ! $acceptException))) {
            $this->fail('exception', 'Record why fewer quotations or a higher price is necessary; the committee must explicitly accept it.');
        }
    }

    public function decisionActors(Procurement $p): array
    {
        $ids = [];
        foreach ($p->reviews()->get() as $r) {
            $ids[] = $r->actor_id;
            foreach ($r->resolution['consents'] ?? [] as $c) {
                $ids[] = $c['user_id'];
                $ids[] = Member::withTrashed()->find($c['member_id'])?->user_id;
            }
        }

        return array_filter($ids);
    }

    public function independent(Procurement $p, User $a, string $kind): void
    {
        $blocked = [$p->created_by, ...$p->revisions()->pluck('prepared_by')->all(), ...$this->decisionActors($p)];
        if ($kind === 'receive') {
            $blocked = [...$blocked, ...$p->movements()->pluck('recorded_by')->all()];
        }
        if ($kind === 'pay') {
            $blocked = [...$blocked, ...$p->receipts()->pluck('actor_id')->all()];
        }
        if (in_array($a->id, $blocked)) {
            $this->fail('actor', 'This action requires an independent person. Requesters, decision-makers, receivers and payers must remain separate.');
        }
    }

    public function decide(Procurement $p, string $stage, array $input, User $a): void
    {
        $permission = ['coordinator' => 'recommend', 'secretary' => 'review', 'committee' => 'approve'][$stage] ?? null;
        abort_unless($permission, 404);
        $this->authorize($a, $permission, $p);
        $d = ProcurementRules::validate('decide', $input);
        $this->mutate($p, $d, $a, 'decision_'.$stage, function ($p, $f) use ($stage, $d, $a) {
            $r = $p->proposal();
            if ($r->stage !== $stage || $p->trashed() || in_array($p->status, ['closed', 'cancelled'])) {
                $this->fail('stage', 'This is not an open request at the current approval stage.');
            }
            if (in_array($a->id, [$p->created_by, $r->prepared_by]) || $p->reviews()->where('revision', $p->revision)->where('actor_id', $a->id)->exists() || $p->receipts()->where('actor_id', $a->id)->exists() || $p->movements()->where('recorded_by', $a->id)->exists()) {
                $this->fail('actor', 'Use a different independent approver at each stage.');
            }
            $resolution = [];
            if ($stage === 'committee' && $d['decision'] === 'accept') {
                $this->eligible($p, $r->items, $p->id);
                $this->checkQuotes($p, ! empty($d['accept_exception']), true);
                $c = Committee::where('status', 'active')->where('type', 'decision')->find($d['committee_id'] ?? 0);
                $ids = $d['consenting_member_ids'] ?? [];
                if (! $c || ! $f || empty($d['resolution_reference']) || empty($d['resolution_date'])) {
                    $this->fail('resolution', 'Provide an active decision committee, dated resolution and evidence.');
                }
                $members = $c->members()->wherePivot('status', 'active')->whereNull('committee_members.ended_at')->whereDate('committee_members.joined_at', '<=', $d['resolution_date'])->where('members.status', 'active')->whereIn('members.id', $ids)->get();
                $excluded = [$p->created_by, $r->prepared_by, ...$p->receipts()->pluck('actor_id')->all(), ...$p->movements()->pluck('recorded_by')->all()];
                if ($members->count() < 2 || $members->count() !== count($ids) || $members->contains(fn ($m) => $m->user_id && in_array($m->user_id, $excluded))) {
                    $this->fail('consents', 'Select two or more distinct active, independent consenting committee members.');
                }
                $resolution = Arr::only($d, ['committee_id', 'resolution_reference', 'resolution_date', 'accept_exception']) + ['consents' => $members->map(fn ($m) => ['member_id' => $m->id, 'user_id' => $m->user_id, 'name' => $m->full_name])->all()];
            }
            $p->reviews()->create(['revision' => $p->revision, 'stage' => $stage, 'decision' => $d['decision'], 'notes' => $d['notes'], 'actor_id' => $a->id, 'resolution' => $resolution]);
            $this->document($p, $a, 'resolution', $f);
            $next = $d['decision'] === 'reject' ? 'rejected' : (['coordinator' => 'secretary', 'secretary' => 'committee', 'committee' => 'approved'][$stage]);
            $r->update(['stage' => $next, 'approved_at' => $next === 'approved' ? now() : null]);
            if ($next === 'approved') {
                $p->update(['approved_revision' => $p->revision, 'status' => 'approved', 'frozen' => false]);
            }
        });
    }

    public function transition(Procurement $p, string $action, array $input, User $a): void
    {
        $permission = match ($action) {
            'issue' => 'issue','close','cancel' => 'close',default => 'manage'
        };
        $this->authorize($a, $permission, $p);
        $d = ProcurementRules::validate($action, $input);
        $this->mutate($p, $d, $a, $action, function ($p) use ($action, $d, $a) {
            $r = $p->proposal();
            if ($p->trashed()) {
                $this->fail('status', 'Archived purchases cannot change their commercial workflow.');
            }
            if ($action === 'submit') {
                if ($r->stage !== 'draft' || in_array($p->status, ['closed', 'cancelled'])) {
                    $this->fail('status', 'Submit a draft revision.');
                }
                $r->update(['quote_count' => ProcurementPolicy::latest('id')->firstOrFail()->quote_count]);
                $this->eligible($p, $r->items, $p->id);
                $this->checkQuotes($p);
                $r->update(['stage' => 'coordinator']);
                $p->update(['submitted_at' => $p->submitted_at ?? now()]);
            } elseif ($action === 'issue') {
                if ($p->frozen || ! $p->approved_revision || ! in_array($r->stage, ['approved', 'rejected', 'withdrawn']) || ! in_array($p->status, ['approved', 'ordered'])) {
                    $this->fail('status', 'Issue only the current approved order, after explicitly resuming any rejected or withdrawn amendment.');
                }
                $r = $p->approved();
                $this->eligible($p, $r->items, $p->id, $r->supplier_snapshot['name']);
                if (! $r->issued_at) {
                    $r->update(['issued_at' => now()]);
                } $p->update(['status' => 'ordered']);
            } elseif ($action === 'amend') {
                if (in_array($p->status, ['closed', 'cancelled']) || ! in_array($r->stage, ['approved', 'rejected', 'withdrawn', 'draft'])) {
                    $this->fail('status', 'Finish or withdraw this revision first.');
                }
                $copy = $r->replicate();
                $copy->fill(['revision' => $p->revision + 1, 'stage' => 'draft', 'prepared_by' => $a->id, 'reason' => $d['reason'], 'approved_at' => null, 'issued_at' => null, 'quote_count' => null]);
                $copy->save();
                $p->update(['revision' => $copy->revision, 'frozen' => (bool) $p->approved_revision]);
            } elseif ($action === 'withdraw') {
                if (in_array($r->stage, ['approved', 'withdrawn']) || in_array($p->status, ['closed', 'cancelled'])) {
                    $this->fail('status', 'Withdraw only an open pending revision.');
                } $r->update(['stage' => 'withdrawn']);
            } elseif ($action === 'resume') {
                if (! $p->approved_revision || ! $p->frozen || ! in_array($r->stage, ['rejected', 'withdrawn']) || in_array($p->status, ['closed', 'cancelled'])) {
                    $this->fail('status', 'Resume only after rejection or withdrawal of an amendment.');
                } $p->update(['frozen' => false]);
            } elseif (in_array($action, ['close', 'cancel'])) {
                if (in_array($p->status, ['closed', 'cancelled']) || $p->frozen) {
                    $this->fail('status', 'Resolve amendments before ending this order.');
                }
                if ($a->id === $p->created_by || $p->revisions()->where('prepared_by', $a->id)->exists() || $p->receipts()->where('actor_id', $a->id)->exists() || $p->movements()->where('recorded_by', $a->id)->exists()) {
                    $this->fail('actor', 'An independent approver must close or cancel the order.');
                }
                app(ProcurementOperationsService::class)->assertSettled($p, $action === 'close');
                $p->update(['status' => $action === 'close' ? 'closed' : 'cancelled', 'closed_at' => now()]);
            } elseif ($action === 'archive') {
                if (! in_array($p->status, ['closed', 'cancelled'])) {
                    $this->fail('status', 'Close or cancel before archiving.');
                } $p->delete();
            }
        });
    }
}
