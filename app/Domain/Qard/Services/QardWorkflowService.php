<?php

namespace App\Domain\Qard\Services;

use App\Domain\Documents\Services\UploadValidator;
use App\Domain\Finance\Funds\Models\Fund;
use App\Domain\Finance\Funds\Services\FundPurposeService;
use App\Domain\Finance\Support\Money;
use App\Domain\Finance\Support\PrincipalSchedule;
use App\Domain\Qard\Models\QardLoan;
use App\Domain\Users\Models\User;
use App\Domain\Welfare\Beneficiaries\Models\Beneficiary;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class QardWorkflowService
{
    public function authorize(User $actor, string $permission): void
    {
        abort_unless($actor->hasPermission('qard.view') && $actor->hasPermission('qard.'.$permission), 403);
    }

    public function fail(string $field, string $message): never
    {
        throw ValidationException::withMessages([$field => $message]);
    }

    public function lock(QardLoan $loan): QardLoan
    {
        if (DB::connection()->getDriverName() === 'sqlite') {
            DB::table('qard_loans')->where('id', $loan->id)->update(['id' => DB::raw('id')]);
        }

        return QardLoan::whereKey($loan->id)->lockForUpdate()->firstOrFail();
    }

    public function event(QardLoan $loan, User $actor, string $action, array $before = [], array $after = []): void
    {
        $loan->events()->create(['actor_id' => $actor->id, 'action' => $action, 'before' => $before, 'after' => $after]);
    }

    public function eligible(QardLoan $loan): void
    {
        $person = Beneficiary::whereKey($loan->beneficiary_id)->lockForUpdate()->first();
        if (! $person || $person->verification_status !== 'verified' || $person->approval_status !== 'approved') {
            $this->fail('beneficiary_id', 'Select a verified and approved beneficiary.');
        }
        $fund = Fund::whereKey($loan->fund_id)->lockForUpdate()->first();
        if (! $fund || $fund->status !== 'active' || ! app(FundPurposeService::class)->allowsQard($fund)) {
            $this->fail('fund_id', 'Choose an active unrestricted general fund or a fund designated for Qard.');
        }
    }

    public function save(?QardLoan $loan, array $data, User $actor): QardLoan
    {
        $this->authorize($actor, 'manage');
        $data = QardRules::validate($loan ? 'update' : 'store', $data);

        return DB::transaction(function () use ($loan, $data, $actor) {
            $before = [];
            if ($loan) {
                $loan = $this->lock($loan);
                if (! in_array($loan->status, ['draft', 'rejected'])) {
                    $this->fail('status', 'Only drafts and rejected applications may be edited. Use Request revision before changing approved terms.');
                }
                $before = $loan->attributesToArray();
                $loan->fill($data);
            } else {
                $loan = new QardLoan($data + ['created_by' => $actor->id, 'number' => 'QRD-'.Str::ulid(), 'status' => 'draft', 'revision' => 1]);
            }
            if (Money::minor($data['principal']) < (int) $data['installment_count']) {
                $this->fail('installment_count', 'Each installment must be at least one paisa.');
            }
            $this->eligible($loan);
            $loan->save();
            $loan->update(['number' => sprintf('QRD-%05d', $loan->id)]);
            $this->event($loan, $actor, $before ? 'application_updated' : 'application_created', $before, $loan->attributesToArray());

            return $loan;
        }, 5);
    }

    public function submit(QardLoan $loan, User $actor): void
    {
        $this->authorize($actor, 'manage');
        DB::transaction(function () use ($loan, $actor) {
            $loan = $this->lock($loan);
            if (! in_array($loan->status, ['draft', 'rejected'])) {
                $this->fail('status', 'Only a draft or rejected application may be submitted.');
            }
            $this->eligible($loan);
            if ($loan->first_due_date->toDateString() <= now('Asia/Karachi')->toDateString()) {
                $this->fail('first_due_date', 'Choose a future first due date before submitting.');
            }
            $before = $loan->only(['status', 'revision']);
            if ($loan->status === 'rejected') {
                $loan->revision++;
            }
            $loan->status = 'submitted';
            $loan->save();
            $this->event($loan, $actor, 'submitted', $before, $loan->only(['status', 'revision']));
        }, 5);
    }

    // File writes are outside the retried database transaction. Remove only newly
    // staged files on failure; retained evidence from earlier revisions is immutable.
    public function withFiles(QardLoan $loan, array $files, callable $callback): mixed
    {
        $stored = [];
        try {
            foreach ($files as $kind => $file) {
                app(UploadValidator::class)->inspect($file);
                $path = $file->store('qard/'.$loan->id, 'private');
                if (! $path) {
                    throw new \RuntimeException('Could not store private evidence.');
                }
                $stored[$kind] = ['path' => $path, 'original_name' => $file->getClientOriginalName()];
            }

            return $callback($stored);
        } catch (\Throwable $e) {
            foreach ($stored as $document) {
                Storage::disk('private')->delete($document['path']);
            }
            throw $e;
        }
    }

    public function verify(QardLoan $loan, array $data, User $actor): void
    {
        $this->authorize($actor, 'verify');
        $data = QardRules::validate('verify', $data);
        $this->withFiles($loan, ['verification' => $data['evidence']], function ($files) use ($loan, $data, $actor) {
            DB::transaction(function () use ($loan, $data, $actor, $files) {
                $loan = $this->lock($loan);
                if ($loan->status !== 'submitted') {
                    $this->fail('status', 'Field verification requires a submitted application.');
                }
                $loan->reviews()->create(['revision' => $loan->revision, 'stage' => 'verification', 'decision' => 'verified', 'notes' => $data['notes'], 'actor_id' => $actor->id]);
                $loan->documents()->create($files['verification'] + ['revision' => $loan->revision, 'kind' => 'verification', 'actor_id' => $actor->id]);
                $loan->update(['status' => 'implementation']);
                $this->event($loan, $actor, 'field_verified', ['status' => 'submitted'], ['status' => 'implementation', 'notes' => $data['notes']]);
            }, 5);
        });
    }

    public function decide(QardLoan $loan, string $stage, array $data, User $actor): void
    {
        $permission = ['implementation' => 'recommend', 'secretary' => 'review', 'committee' => 'approve'][$stage] ?? null;
        abort_unless($permission, 404);
        $this->authorize($actor, $permission);
        $data = QardRules::validate('decide', $data);
        DB::transaction(function () use ($loan, $stage, $data, $actor) {
            $loan = $this->lock($loan);
            if ($loan->status !== $stage) {
                $this->fail('status', 'This decision is not available at the current stage.');
            }
            if ((int) $loan->created_by === (int) $actor->id || $loan->reviews()->where('revision', $loan->revision)
                ->whereIn('stage', ['implementation', 'secretary', 'committee'])->where('actor_id', $actor->id)->exists()) {
                $this->fail('actor', 'The creator cannot decide this application. Each approval stage requires a different person.');
            }
            $next = $data['decision'] === 'reject' ? 'rejected' : ['implementation' => 'secretary', 'secretary' => 'committee', 'committee' => 'approved'][$stage];
            if ($next === 'approved') {
                $this->eligible($loan);
                if ($loan->first_due_date->toDateString() <= now('Asia/Karachi')->toDateString()) {
                    $this->fail('first_due_date', 'Reject for correction: the first due date must still be in the future.');
                }
            }
            $loan->reviews()->create(['revision' => $loan->revision, 'stage' => $stage, 'actor_id' => $actor->id] + $data);
            $loan->update(['status' => $next]);
            if ($next === 'approved') {
                foreach ($this->schedule($loan) as $row) {
                    $loan->installments()->create($row + ['revision' => $loan->revision]);
                }
            }
            $this->event($loan, $actor, $stage.'_'.$data['decision'], ['status' => $stage], ['status' => $next, 'notes' => $data['notes']]);
        }, 5);
    }

    public function schedule(QardLoan $loan): array
    {
        return app(PrincipalSchedule::class)->make($loan->principal, (int) $loan->installment_count, $loan->frequency, $loan->first_due_date->toDateString());
    }

    public function sign(QardLoan $loan, array $data, User $actor): void
    {
        $this->authorize($actor, 'agreements');
        $data = QardRules::validate('sign', $data);
        $this->withFiles($loan, ['borrower' => $data['borrower_file'], 'foundation' => $data['foundation_file']], function ($files) use ($loan, $data, $actor) {
            DB::transaction(function () use ($loan, $data, $actor, $files) {
                $loan = $this->lock($loan);
                if ($loan->status !== 'approved') {
                    $this->fail('status', 'Only an approved loan can have its agreement signed.');
                }
                $loan->agreements()->create(['revision' => $loan->revision, 'terms' => $loan->only(['principal', 'fund_id', 'purpose', 'frequency', 'installment_count', 'first_due_date']) + ['schedule' => $this->schedule($loan)],
                    'witnesses' => $data['witnesses'], 'legacy_witnesses' => false, 'borrower_name' => $data['borrower_name'], 'foundation_name' => $data['foundation_name'], 'signed_at' => now(), 'actor_id' => $actor->id]);
                foreach ($files as $kind => $file) {
                    $loan->documents()->create($file + ['revision' => $loan->revision, 'kind' => $kind, 'actor_id' => $actor->id]);
                }
                $loan->update(['status' => 'signed']);
                $this->event($loan, $actor, 'agreement_signed', ['status' => 'approved'], ['status' => 'signed']);
            }, 5);
        });
    }

    public function revise(QardLoan $loan, array $data, User $actor): void
    {
        $this->authorize($actor, 'manage');
        $data = QardRules::validate('revise', $data);
        DB::transaction(function () use ($loan, $data, $actor) {
            $loan = $this->lock($loan);
            if (! in_array($loan->status, ['approved', 'signed']) || $loan->movements()->where('status', 'posted')->exists()) {
                $this->fail('status', 'Only an undisbursed approved or signed loan can return for revision.');
            }
            $before = $loan->only(['status', 'revision']);
            $loan->update(['status' => 'draft', 'revision' => $loan->revision + 1]);
            $this->event($loan, $actor, 'revision_requested', $before, $loan->only(['status', 'revision']) + $data);
        }, 5);
    }

    public function followup(QardLoan $loan, array $data, User $actor): void
    {
        $this->authorize($actor, 'followups');
        $data = QardRules::validate('followup', $data);
        DB::transaction(function () use ($loan, $data, $actor) {
            $loan = $this->lock($loan);
            if (! in_array($loan->status, ['active', 'repaid'])) {
                $this->fail('status', 'Follow-ups are available after disbursement.');
            }
            $loan->followups()->create($data + ['actor_id' => $actor->id]);
            $this->event($loan, $actor, 'followup_recorded', [], $data);
            app(QardMonitoringService::class)->refreshLocked($loan);
        }, 5);
    }
}
