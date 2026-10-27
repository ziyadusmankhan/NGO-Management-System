<?php

namespace App\Domain\Welfare\Verification\Models;

use App\Domain\Audit\Concerns\RecordsAudit;
use App\Domain\Users\Models\User;
use App\Domain\Welfare\Applications\Models\WelfareApplication;
use App\Domain\Welfare\Beneficiaries\Models\Beneficiary;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class BeneficiaryVerification extends Model
{
    use HasFactory;
    use RecordsAudit;

    /**
     * Single source of truth for the field checklist
     * (used by the controller validation and the views).
     */
    public const CHECKLIST_ITEMS = [
        'identity_verified' => 'Identity Verified',
        'residence_verified' => 'Residence Verified',
        'family_condition_verified' => 'Family Condition Verified',
        'financial_condition_verified' => 'Financial Condition Verified',
        'documents_reviewed' => 'Supporting Documents Reviewed',
        'photos_attached' => 'Photos / Evidence Attached',
    ];

    protected $fillable = [
        'beneficiary_id',
        'application_id',
        'verification_number',
        'verified_by',
        'assigned_to',
        'assigned_at',
        'verification_date',
        'visit_date',
        'started_at',
        'completed_at',
        'status',
        'workflow_status',
        'priority',
        'method',
        'findings',
        'checklist',
        'notes',
        'recommendation',
        'recommendation_notes',
    ];

    protected function casts(): array
    {
        return [
            'verification_date' => 'date',
            'visit_date' => 'date',
            'assigned_at' => 'datetime',
            'started_at' => 'datetime',
            'completed_at' => 'datetime',
            'checklist' => 'array',
        ];
    }

    /**
     * Beneficiary being verified.
     */
    public function beneficiary(): BelongsTo
    {
        return $this->belongsTo(
            Beneficiary::class,
            'beneficiary_id'
        );
    }

    /**
     * Welfare application associated with this field verification.
     */
    public function application(): BelongsTo
    {
        return $this->belongsTo(
            WelfareApplication::class,
            'application_id'
        );
    }

    /**
     * User who completed/verified the record.
     */
    public function verifiedBy(): BelongsTo
    {
        return $this->belongsTo(
            User::class,
            'verified_by'
        );
    }

    /**
     * Officer assigned to conduct the field verification.
     */
    public function assignedTo(): BelongsTo
    {
        return $this->belongsTo(
            User::class,
            'assigned_to'
        );
    }

    /**
     * Evidence files (photos / documents) uploaded
     * when the verification was completed.
     */
    public function evidence(): HasMany
    {
        return $this->hasMany(
            BeneficiaryVerificationEvidence::class,
            'verification_id'
        )->oldest('id');
    }
}
