<?php

namespace App\Domain\Welfare\Assistance\Services;

use App\Domain\Users\Models\User;
use App\Domain\Welfare\Applications\Models\WelfareApplication;
use App\Domain\Welfare\Assistance\Models\WelfareAssistance;
use App\Domain\Welfare\Beneficiaries\Models\Beneficiary;
use Brick\Math\BigDecimal;
use Illuminate\Database\UniqueConstraintViolationException;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class WelfareAssistanceService
{
    public function eligibilityError(WelfareApplication $application): ?string
    {
        $application->loadMissing(['agreement', 'beneficiary', 'fund']);

        if ($application->isTestApplication()) {
            return 'This application is reserved for workflow testing and cannot create assistance.';
        }
        if ($application->status !== 'approved' || ! $application->approved_by || ! $application->approved_at) {
            return 'The application must have recorded final approval.';
        }
        if (! $this->beneficiaryEligible($application->beneficiary)) {
            return 'The beneficiary must remain verified and approved.';
        }
        if (! $application->fund || $application->fund->status !== 'active') {
            return 'The application must have an active authorized fund before assistance can be created.';
        }

        $agreement = $application->agreement;
        if (! $agreement || $agreement->status !== 'signed') {
            return 'Only a signed agreement can create assistance.';
        }
        if (! $agreement->fully_signed_at) {
            return 'The agreement is missing its completed signature record.';
        }
        foreach (['beneficiary', 'organization'] as $party) {
            $path = $agreement->{$party.'_document_path'};
            $prefix = "welfare/agreements/{$agreement->id}/{$party}/";
            if (! $agreement->{$party.'_signed_at'} || ! $agreement->{$party.'_signature_verified_by'}
                || blank($agreement->{$party.'_signatory_name'}) || ! $path
                || ! str_starts_with($path, $prefix) || str_contains($path, '..')
                || ! Storage::disk('private')->exists($path)) {
                return 'Both verified signatures and their private evidence files are required.';
            }
        }
        if (mb_strtolower(trim($agreement->beneficiary_signatory_name))
            === mb_strtolower(trim($agreement->organization_signatory_name))) {
            return 'The beneficiary and organization signatories must be different people.';
        }
        if (! BigDecimal::of($agreement->approved_amount)->isPositive()) {
            return 'The signed agreement must contain a positive approved amount.';
        }

        return null;
    }

    public function createFromApplication(int $applicationId, array $details, User $actor): WelfareAssistance
    {
        abort_unless($actor->hasPermission('welfare.assistance.manage'), 403);

        try {
            return DB::transaction(function () use ($applicationId, $details, $actor) {
                $this->reserveSqliteWrite('welfare_applications', $applicationId);
                $application = $this->lockedApplication($applicationId);

                if ($application->assistanceIncludingArchived()->exists()) {
                    $this->duplicateError();
                }
                $this->assertEligible($application);

                $assistance = $application->assistance()->create(array_merge(
                    Arr::only($details, WelfareAssistance::ADMINISTRATIVE_FIELDS),
                    $this->sourceAttributes($application),
                    [
                        'assistance_number' => 'TEMP-'.Str::uuid(),
                        'status' => 'pending',
                        'requested_at' => now(),
                    ]
                ));
                $assistance->update(['assistance_number' => sprintf('WFA-%05d', $assistance->id)]);
                $this->recordStatus($assistance, null, $actor);

                return $assistance;
            }, 3);
        } catch (UniqueConstraintViolationException $exception) {
            // The database constraint remains the final guard for competing writers.
            if (WelfareAssistance::withTrashed()->where('application_id', $applicationId)->exists()) {
                $this->duplicateError();
            }
            throw $exception;
        }
    }

    public function update(WelfareAssistance $assistance, array $details, User $actor): void
    {
        abort_unless($actor->hasPermission('welfare.assistance.manage'), 403);

        DB::transaction(function () use ($assistance, $details) {
            $this->reserveSqliteWrite('welfare_assistances', $assistance->id);
            $locked = WelfareAssistance::query()->lockForUpdate()->findOrFail($assistance->id);
            if (! in_array($locked->status, ['pending', 'rejected'], true)) {
                throw ValidationException::withMessages(['status' => 'Only pending or rejected assistance can be edited.']);
            }

            $fields = WelfareAssistance::ADMINISTRATIVE_FIELDS;
            if ($locked->application_id === null) {
                $beneficiary = Beneficiary::query()->lockForUpdate()->find($details['beneficiary_id']);
                if (! $this->beneficiaryEligible($beneficiary)) {
                    throw ValidationException::withMessages(['beneficiary_id' => 'The beneficiary must remain verified and approved.']);
                }
                $fields = array_merge($fields, WelfareAssistance::INHERITED_FIELDS);
                $details['has_supporting_document'] = (bool) ($details['has_supporting_document'] ?? false);
            }
            // Signed terms and application identity can never be rewritten by an edit.
            $locked->update(Arr::only($details, $fields));
        }, 3);
    }

    public function transition(WelfareAssistance $assistance, string $to, User $actor, ?string $reason = null): void
    {
        $permission = $to === 'pending' ? 'welfare.assistance.manage' : 'welfare.assistance.approve';
        abort_unless($actor->hasPermission($permission), 403);

        DB::transaction(function () use ($assistance, $to, $actor, $reason) {
            $this->reserveSqliteWrite('welfare_assistances', $assistance->id);
            // Use the same application-before-assistance lock order as creation.
            $application = $assistance->application_id && in_array($to, ['approved', 'pending'], true)
                ? $this->lockedApplication($assistance->application_id, false) : null;
            $locked = WelfareAssistance::query()->lockForUpdate()->findOrFail($assistance->id);
            $allowed = ['pending' => ['approved', 'rejected'], 'rejected' => ['pending']];
            if (! in_array($to, $allowed[$locked->status] ?? [], true)) {
                throw ValidationException::withMessages(['status' => "Cannot change assistance from {$locked->status} to {$to}."]);
            }
            if (in_array($to, ['approved', 'pending'], true)) {
                if ($application) {
                    $this->lockSourceRelations($application);
                    $this->assertEligible($application);
                    $expected = new WelfareAssistance($this->sourceAttributes($application));
                    foreach (WelfareAssistance::INHERITED_FIELDS as $field) {
                        if ((string) $locked->{$field} !== (string) $expected->{$field}) {
                            throw ValidationException::withMessages(['application_id' => 'Assistance terms no longer match the signed application. Review the source records before proceeding.']);
                        }
                    }
                } elseif (! $this->beneficiaryEligible(Beneficiary::query()->lockForUpdate()->find($locked->beneficiary_id))) {
                    throw ValidationException::withMessages(['beneficiary_id' => 'The beneficiary must remain verified and approved.']);
                }
            }

            $from = $locked->status;
            if ($to === 'approved' && ! $locked->next_review_date) {
                $locked->next_review_date = app(WelfareMonitoringService::class)->suggestedReviewDate($locked);
            }
            $locked->update([
                'status' => $to,
                'approved_by' => $to === 'approved' ? $actor->id : null,
                'approved_at' => $to === 'approved' ? now() : null,
            ]);
            $this->recordStatus($locked, $from, $actor, $reason);
            app(WelfareMonitoringService::class)->syncRemindersLocked($locked);
        }, 3);
    }

    private function lockedApplication(int $id, bool $withRelations = true): WelfareApplication
    {
        $application = WelfareApplication::query()->lockForUpdate()->find($id);
        if (! $application) {
            throw ValidationException::withMessages(['application_id' => 'Choose an existing application that has not been archived.']);
        }
        if ($withRelations) {
            $this->lockSourceRelations($application);
        }

        return $application;
    }

    private function lockSourceRelations(WelfareApplication $application): void
    {
        foreach (['agreement', 'beneficiary', 'fund'] as $relation) {
            $application->setRelation($relation, $application->{$relation}()->lockForUpdate()->first());
        }
    }

    private function sourceAttributes(WelfareApplication $application): array
    {
        return [
            'beneficiary_id' => $application->beneficiary_id,
            'fund_id' => $application->fund_id,
            'amount' => $application->agreement->approved_amount,
            'purpose_notes' => $application->purpose_details,
            'assistance_type' => $application->request_type,
            'frequency' => $application->frequency,
            'start_date' => $application->start_date,
            'end_date' => $application->end_date,
            'has_supporting_document' => $application->has_supporting_document,
        ];
    }

    private function assertEligible(WelfareApplication $application): void
    {
        if ($error = $this->eligibilityError($application)) {
            throw ValidationException::withMessages(['application_id' => $error]);
        }
    }

    private function beneficiaryEligible(?Beneficiary $beneficiary): bool
    {
        return $beneficiary && $beneficiary->verification_status === 'verified' && $beneficiary->approval_status === 'approved';
    }

    private function duplicateError(): never
    {
        throw ValidationException::withMessages([
            'application_id' => 'Assistance already exists for this application, including archived records. Open the existing record instead.',
        ]);
    }

    private function recordStatus(WelfareAssistance $assistance, ?string $from, User $actor, ?string $reason = null): void
    {
        $assistance->statusChanges()->create([
            'from_status' => $from, 'to_status' => $assistance->status,
            'changed_by' => $actor->id, 'changed_at' => now(), 'reason' => $reason,
        ]);
    }

    private function reserveSqliteWrite(string $table, int $id): void
    {
        // SQLite ignores FOR UPDATE. Reserve the writer before taking a read snapshot.
        if (DB::connection()->getDriverName() === 'sqlite') {
            DB::table($table)->where('id', $id)->update(['id' => DB::raw('id')]);
        }
    }
}
