<?php

namespace App\Domain\Business\Services;

use App\Domain\Business\Models\BusinessApplication;
use App\Domain\Business\Models\BusinessCategory;
use App\Domain\Committees\Models\Committee;
use App\Domain\Documents\Services\UploadValidator;
use App\Domain\Finance\Funds\Models\Fund;
use App\Domain\Finance\Funds\Services\FundPurposeService;
use App\Domain\Finance\Support\Money;
use App\Domain\Procurement\Services\ProcurementGuard;
use App\Domain\Users\Models\User;
use App\Domain\Welfare\Beneficiaries\Models\Beneficiary;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class BusinessWorkflowService
{
    public function authorize(User $actor, string $action): void
    {
        abort_unless($actor->hasPermission('business.view') && $actor->hasPermission('business.'.$action), 403);
    }

    public function fail(string $field, string $message): never
    {
        throw ValidationException::withMessages([$field => $message]);
    }

    public function lock(BusinessApplication $application): BusinessApplication
    {
        if (DB::connection()->getDriverName() === 'sqlite') {
            DB::table('business_applications')->where('id', $application->id)->update(['id' => DB::raw('id')]);
        }

        return BusinessApplication::whereKey($application->id)->lockForUpdate()->firstOrFail();
    }

    public function current(BusinessApplication $application, array $data): void
    {
        if ((int) $data['version'] !== (int) $application->version) {
            $this->fail('version', 'This case has changed. Reload it and review the latest details before saving.');
        }
    }

    public function event(BusinessApplication $application, User $actor, string $action, array $before = [], array $after = []): void
    {
        $application->increment('version');
        $application->events()->create(['actor_id' => $actor->id, 'action' => $action, 'before_values' => $before, 'after_values' => $after]);
    }

    public function eligible(BusinessApplication $application): void
    {
        $beneficiary = Beneficiary::whereKey($application->beneficiary_id)->lockForUpdate()->first();
        $fund = Fund::whereKey($application->fund_id)->lockForUpdate()->first();
        if (! $beneficiary || $beneficiary->verification_status !== 'verified' || $beneficiary->approval_status !== 'approved') {
            $this->fail('beneficiary_id', 'Choose a verified and approved beneficiary.');
        }
        if (! $fund || $fund->status !== 'active' || ! app(FundPurposeService::class)->allowsBusiness($fund, $application->support_type)) {
            $this->fail('fund_id', 'The active original fund must permit this type of business assistance. Qard-designated funds cannot pay grants.');
        }
    }

    /** Files are cleaned up if the atomic database operation fails. */
    public function withFiles(array $files, callable $callback): mixed
    {
        $stored = [];
        try {
            foreach ($files as $kind => $file) {
                if ($file) {
                    app(UploadValidator::class)->inspect($file);
                    $path = $file->storeAs('business', Str::uuid().'.'.strtolower($file->getClientOriginalExtension()), 'private');
                    if (! $path) {
                        throw new \RuntimeException('The private evidence file could not be stored.');
                    }
                    $stored[$kind] = ['path' => $path, 'original_name' => $file->getClientOriginalName(), 'sha256' => hash_file('sha256', $file->getRealPath())];
                }
            }

            return $callback($stored);
        } catch (\Throwable $e) {
            foreach ($stored as $file) {
                Storage::disk('private')->delete($file['path']);
            }
            throw $e;
        }
    }

    public function documents(BusinessApplication $application, User $actor, array $files, array $extra = []): void
    {
        foreach ($files as $kind => $file) {
            $application->documents()->create($file + ['kind' => $kind, 'revision' => $application->revision, 'actor_id' => $actor->id] + $extra);
        }
    }

    public function save(?BusinessApplication $application, array $input, User $actor): BusinessApplication
    {
        $this->authorize($actor, 'manage');
        $data = BusinessRules::validate($application ? 'update' : 'store', $input);
        $files = [];
        foreach ($data['attachments'] ?? [] as $i => $file) {
            $files['application_'.$i] = $file;
        }
        if ($data['price_check_file'] ?? null) {
            $files['price_checks'] = $data['price_check_file'];
        }

        return $this->withFiles($files, function ($stored) use ($application, $data, $actor) {
            return DB::transaction(function () use ($application, $data, $actor, $stored) {
                $fields = Arr::only($data, ['beneficiary_id', 'fund_id', 'category_id', 'name', 'location', 'description', 'skills', 'market_assessment', 'risks', 'expected_start_date', 'expected_sales', 'expected_expenses', 'support_type', 'frequency', 'installment_count']);
                $before = [];
                if ($application) {
                    $application = $this->lock($application);
                    $this->current($application, $data);
                    if (! in_array($application->status, ['draft', 'rejected'])) {
                        $this->fail('status', 'Only draft or rejected applications can be edited.');
                    }
                    $before = $application->toArray();
                    // Keep every submitted revision and its budgets intact.
                    if ($application->status === 'rejected') {
                        $application->revision++;
                    }
                    $application->fill($fields + ['status' => 'draft']);
                } else {
                    $application = new BusinessApplication($fields + ['number' => 'BUS-'.Str::ulid(), 'created_by' => $actor->id, 'revision' => 1, 'version' => 1, 'status' => 'draft', 'funding_status' => 'open', 'operating_status' => 'not_started']);
                }
                $this->eligible($application);
                if (! BusinessCategory::whereKey($application->category_id)->where('active', true)->exists()) {
                    $this->fail('category_id', 'Choose an active business category.');
                }
                $total = 0;
                $contribution = 0;
                $items = [];
                foreach ($data['items'] as $item) {
                    $cost = Money::minor($item['cost']);
                    $own = Money::minor($item['applicant_amount']);
                    if ($cost <= 0 || $own < 0 || $own > $cost) {
                        $this->fail('items', 'Each item needs a positive cost and an applicant share no greater than its cost.');
                    }
                    if ($item['release_method'] !== 'cash' && empty($item['supplier'])) {
                        $this->fail('items', 'Enter the selected supplier for each supplier payment or asset purchase.');
                    }
                    $total = Money::add($total, $cost);
                    $contribution = Money::add($contribution, $own);
                    $items[] = $item + ['organization_amount' => Money::decimal($cost - $own)];
                }
                if ($total - $contribution < 1) {
                    $this->fail('items', 'The organization contribution must be greater than zero.');
                }
                if ($total > Money::minor('9999999999999.99')) {
                    $this->fail('items', 'The investment exceeds the supported amount.');
                }
                if ($application->support_type === 'repayable' && $total - $contribution < $application->installment_count) {
                    $this->fail('installment_count', 'Each installment must be at least one paisa.');
                }
                $application->fill(['total_investment' => Money::decimal($total), 'applicant_contribution' => Money::decimal($contribution), 'approved_amount' => Money::decimal($total - $contribution)]);
                $application->save();
                if (! $before) {
                    $application->update(['number' => sprintf('BUS-%05d', $application->id)]);
                }
                $application->currentBudget()->delete();
                foreach ($items as $item) {
                    $application->budgetItems()->create($item + ['revision' => $application->revision]);
                }
                $this->documents($application, $actor, $stored);
                $this->event($application, $actor, 'draft_saved', $before, $application->toArray() + ['budget' => $items]);

                return $application;
            }, 5);
        });
    }

    public function submit(BusinessApplication $application, array $input, User $actor): void
    {
        $this->authorize($actor, 'manage');
        $data = BusinessRules::validate('submit', $input);
        DB::transaction(function () use ($application, $data, $actor) {
            $application = $this->lock($application);
            $this->current($application, $data);
            if ($application->status !== 'draft') {
                $this->fail('status', 'Save a draft before submitting.');
            }
            $this->eligible($application);
            if (! $application->category->active) {
                $this->fail('category_id', 'Choose an active category before submitting.');
            }
            $application->update(['status' => 'submitted', 'beneficiary_snapshot' => $application->beneficiary->only(['beneficiary_number', 'full_name', 'cnic', 'phone', 'address', 'dependents_count', 'occupation', 'monthly_income', 'assets_description', 'debts_description', 'household_condition']), 'category_snapshot' => $application->category->name]);
            $this->event($application, $actor, 'submitted', ['status' => 'draft'], ['status' => 'submitted', 'terms' => $this->terms($application)]);
        }, 5);
    }

    public function verify(BusinessApplication $application, array $input, User $actor): void
    {
        $this->authorize($actor, 'verify');
        $data = BusinessRules::validate('verify', $input);
        $this->withFiles(['verification' => $data['evidence']], function ($files) use ($application, $data, $actor) {
            DB::transaction(function () use ($application, $data, $actor, $files) {
                $application = $this->lock($application);
                $this->current($application, $data);
                if ($application->status !== 'submitted') {
                    $this->fail('status', 'Verification requires a submitted application.');
                }
                $application->reviews()->create(['revision' => $application->revision, 'stage' => 'verification', 'decision' => 'verified', 'notes' => $data['notes'], 'actor_id' => $actor->id]);
                $this->documents($application, $actor, $files);
                $application->update(['status' => 'implementation']);
                $this->event($application, $actor, 'verified', ['status' => 'submitted'], ['status' => 'implementation', 'notes' => $data['notes']]);
            }, 5);
        });
    }

    public function decide(BusinessApplication $application, string $stage, array $input, User $actor): void
    {
        $permission = ['implementation' => 'recommend', 'secretary' => 'review', 'committee' => 'approve'][$stage] ?? null;
        abort_unless($permission, 404);
        $this->authorize($actor, $permission);
        $data = BusinessRules::validate('decide', $input);
        $this->withFiles(['resolution' => $data['resolution_file'] ?? null], function ($files) use ($application, $stage, $data, $actor) {
            DB::transaction(function () use ($application, $stage, $data, $actor, $files) {
                $application = $this->lock($application);
                $this->current($application, $data);
                if ($application->status !== $stage) {
                    $this->fail('status', 'This is not the current approval stage.');
                }
                if ((int) $application->created_by === (int) $actor->id || $application->reviews()->where('revision', $application->revision)->whereIn('stage', ['implementation', 'secretary', 'committee'])->where('actor_id', $actor->id)->exists()) {
                    $this->fail('actor', 'The creator cannot approve. Each decision stage requires a different account.');
                }
                $extra = [];
                if ($stage === 'committee' && $data['decision'] === 'accept') {
                    $this->eligible($application);
                    if (! ($files['resolution'] ?? null) || empty($data['resolution_reference']) || empty($data['resolution_date'])) {
                        $this->fail('resolution', 'Attach the committee resolution and enter its reference and date.');
                    }
                    $committee = Committee::whereKey($data['committee_id'] ?? 0)->where('status', 'active')->where('type', 'decision')->first();
                    if (! $committee) {
                        $this->fail('committee_id', 'Select an active decision-making committee.');
                    }
                    $ids = $data['consenting_member_ids'] ?? [];
                    $members = $committee->members()->wherePivot('status', 'active')->whereNull('committee_members.ended_at')->where('committee_members.joined_at', '<=', $data['resolution_date'])->where('members.status', 'active')->whereIn('members.id', $ids)->get();
                    if ($members->count() !== count($ids) || $members->count() < max(2, (int) config('business.minimum_consents'))) {
                        $this->fail('consenting_member_ids', 'Select at least the required number of distinct active committee members.');
                    }
                    if ($members->contains(fn ($m) => (int) $m->user_id === (int) $application->created_by)) {
                        $this->fail('consenting_member_ids', 'The requester cannot count as a consenting member.');
                    }
                    $exceptions = [];
                    foreach ($application->currentBudget()->get() as $item) {
                        if ($item->release_method === 'cash' || Money::minor($item->organization_amount) === 0) {
                            continue;
                        }
                        $quotes = collect($item->price_checks ?? []);
                        $valid = $quotes->filter(fn ($q) => Money::minor($q['amount']) > 0)->unique(fn ($q) => mb_strtolower(trim($q['supplier'])))->count();
                        if ($valid < max(2, (int) config('business.minimum_price_checks'))) {
                            if (! $item->exception_reason || empty($data['approve_purchase_exceptions'])) {
                                $this->fail('price_checks', 'Each purchase needs two price checks or an explicitly approved committee exception.');
                            }
                            $exceptions[] = ['item_id' => $item->id, 'reason' => $item->exception_reason];
                        } elseif (! $application->documents()->where('revision', $application->revision)->where('kind', 'price_checks')->exists()) {
                            $this->fail('price_checks', 'Upload the price-check evidence with the application.');
                        }
                    }
                    $extra = ['committee_id' => $committee->id, 'resolution_reference' => $data['resolution_reference'], 'resolution_date' => $data['resolution_date'],
                        'consenting_members' => $members->map(fn ($m) => ['id' => $m->id, 'name' => $m->full_name, 'user_id' => $m->user_id, 'position' => $m->pivot->position])->all(), 'purchase_exceptions' => $exceptions];
                }
                $next = $data['decision'] === 'reject' ? 'rejected' : ['implementation' => 'secretary', 'secretary' => 'committee', 'committee' => 'approved'][$stage];
                $application->reviews()->create(['revision' => $application->revision, 'stage' => $stage, 'decision' => $data['decision'], 'notes' => $data['notes'], 'actor_id' => $actor->id] + $extra);
                $this->documents($application, $actor, $files);
                $application->update(['status' => $next]);
                $this->event($application, $actor, $stage.'_'.$data['decision'], ['status' => $stage], ['status' => $next, 'notes' => $data['notes']] + $extra);
            }, 5);
        });
    }

    public function terms(BusinessApplication $application): array
    {
        return $application->only(['number', 'revision', 'beneficiary_id', 'beneficiary_snapshot', 'category_snapshot', 'name', 'location', 'description', 'fund_id', 'support_type', 'total_investment', 'applicant_contribution', 'approved_amount', 'frequency', 'installment_count']) +
            ['budget' => $application->currentBudget()->orderBy('id')->get()->map(fn ($i) => $i->only(['id', 'description', 'cost', 'applicant_amount', 'organization_amount', 'release_method', 'supplier', 'price_checks', 'exception_reason']))->all()];
    }

    public function hash(array $terms): string
    {
        return hash('sha256', json_encode($terms, JSON_THROW_ON_ERROR));
    }

    public function sign(BusinessApplication $application, array $input, User $actor): void
    {
        $this->authorize($actor, 'agreements');
        $data = BusinessRules::validate('sign', $input);
        $this->withFiles(['borrower' => $data['borrower_file'], 'foundation' => $data['foundation_file']], function ($files) use ($application, $data, $actor) {
            DB::transaction(function () use ($application, $data, $actor, $files) {
                $application = $this->lock($application);
                $this->current($application, $data);
                $this->eligible($application);
                if ($application->status !== 'approved') {
                    $this->fail('status', 'Only an approved application can be signed.');
                }
                $terms = $this->terms($application);
                if (! hash_equals($this->hash($terms), $data['terms_hash'])) {
                    $this->fail('terms_hash', 'The agreement changed. Print and sign the current version.');
                }
                $approval = $application->reviews()->where('revision', $application->revision)->where('stage', 'committee')->firstOrFail();
                if ($data['signed_on'] < $approval->created_at->timezone(config('business.timezone'))->toDateString()) {
                    $this->fail('signed_on', 'Signing cannot precede final approval.');
                }
                $agreement = $application->agreements()->create(Arr::only($data, ['borrower_name', 'foundation_name', 'witnesses', 'signed_on']) + ['revision' => $application->revision, 'kind' => 'initial', 'funding_version' => 0, 'terms' => $terms, 'actor_id' => $actor->id]);
                $this->documents($application, $actor, $files, ['agreement_id' => $agreement->id]);
                $application->update(['status' => 'signed']);
                $this->event($application, $actor, 'agreement_signed', ['status' => 'approved'], ['status' => 'signed', 'agreement_id' => $agreement->id, 'applicant_contribution_confirmed' => true]);
            }, 5);
        });
    }

    public function transition(BusinessApplication $application, string $action, array $input, User $actor): void
    {
        $this->authorize($actor, in_array($action, ['cancel', 'close']) ? 'close' : 'manage');
        $data = BusinessRules::validate($action, $input);
        DB::transaction(function () use ($application, $action, $data, $actor) {
            $application = $this->lock($application);
            $this->current($application, $data);
            if (in_array($action, ['revise', 'cancel', 'close', 'archive'])) {
                app(ProcurementGuard::class)->assertNoOpen('business', $application->id);
            }
            $before = $application->only(['status', 'revision']);
            if ($action === 'revise') {
                if (! in_array($application->status, ['approved', 'signed']) || $application->movements()->exists()) {
                    $this->fail('status', 'Only an approved, never-funded case can restart approval.');
                }
                $oldItems = $application->currentBudget()->get();
                $application->update(['status' => 'draft', 'revision' => $application->revision + 1]);
                foreach ($oldItems as $item) {
                    $application->budgetItems()->create(Arr::except($item->getAttributes(), ['id', 'business_application_id', 'created_at', 'updated_at', 'revision', 'legacy_direct_purchase']) + ['revision' => $application->revision]);
                }
            } elseif ($action === 'cancel') {
                if ($application->releasedMinor() !== 0 || in_array($application->status, ['closed', 'cancelled'])) {
                    $this->fail('status', 'Cancellation is only available before funding.');
                }
                if ((int) $application->created_by === (int) $actor->id) {
                    $this->fail('actor', 'A different authorized approver must cancel this case.');
                }
                $application->update(['status' => 'cancelled', 'next_review_date' => null]);
            } elseif ($action === 'close') {
                if ($application->status !== 'signed' || $application->funding_status !== 'finalized' || $application->outstandingMinor() !== 0) {
                    $this->fail('status', 'Finalize funding and settle principal before closing the case.');
                }
                $last = $application->monitorings()->latest('id')->first();
                if (! $last?->final_report || (int) $last->actor_id === (int) $actor->id || (int) $application->created_by === (int) $actor->id) {
                    $this->fail('closure', 'A different authorized approver must accept the latest final monitoring report.');
                }
                $application->update(['status' => 'closed', 'closed_at' => now(), 'next_review_date' => null]);
            } elseif ($action === 'archive') {
                if (! in_array($application->status, ['draft', 'closed', 'cancelled']) || $application->outstandingMinor() !== 0) {
                    $this->fail('status', 'Only a draft, cancelled, or financially settled closed case can be archived.');
                }
            } else {
                abort(404);
            }
            $this->event($application, $actor, $action, $before, $application->only(['status', 'revision']) + ['reason' => $data['reason']]);
            app(BusinessMonitoringService::class)->refreshLocked($application);
            if ($action === 'archive') {
                $application->delete();
            }
        }, 5);
    }
}
