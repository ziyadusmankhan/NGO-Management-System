<?php

namespace App\Domain\Welfare\Applications\Models;

use App\Domain\Audit\Concerns\RecordsAudit;
use App\Domain\Finance\Funds\Models\Fund;
use App\Domain\Finance\Support\ReadsDecimalMoney;
use App\Domain\Security\Concerns\EncryptsSensitive;
use App\Domain\Users\Models\User;
use App\Domain\Welfare\Assistance\Models\WelfareAssistance;
use App\Domain\Welfare\Beneficiaries\Models\Beneficiary;
use App\Domain\Welfare\Verification\Models\BeneficiaryVerification;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\SoftDeletes;

class WelfareApplication extends Model
{
    use EncryptsSensitive;
    use ReadsDecimalMoney;
    use RecordsAudit;

    protected array $moneyColumns = ['requested_amount', 'applicant_contribution', 'organization_contribution', 'recommended_amount'];

    use SoftDeletes;

    protected $fillable = [
        'application_number',
        'beneficiary_id',
        'fund_id',
        'category',
        'request_type',
        'requested_amount',
        'applicant_contribution',
        'organization_contribution',
        'purpose_details',
        'priority',
        'frequency',
        'start_date',
        'end_date',
        'status',
        'submitted_at',
        'submitted_by',
        'recommended_amount',
        'recommendation_notes',
        'recommended_by',
        'recommended_at',
        'approved_by',
        'approved_at',
        'rejected_by',
        'rejected_at',
        'rejection_reason',
        'has_supporting_document',
        'internal_notes',
    ];

    protected function casts(): array
    {
        return [
            'is_test' => 'boolean',
            'requested_amount' => 'decimal:2',
            'applicant_contribution' => 'decimal:2',
            'organization_contribution' => 'decimal:2',
            'recommended_amount' => 'decimal:2',

            'start_date' => 'date',
            'end_date' => 'date',

            'submitted_at' => 'datetime',
            'recommended_at' => 'datetime',
            'approved_at' => 'datetime',
            'rejected_at' => 'datetime',

            'has_supporting_document' => 'boolean',
        ];
    }

    /**
     * Field verification records associated with this application.
     */
    public function verifications(): HasMany
    {
        return $this->hasMany(
            BeneficiaryVerification::class,
            'application_id'
        );
    }

    /**
     * Committee and organizational review history.
     */
    public function reviews(): HasMany
    {
        return $this->hasMany(
            WelfareApplicationReview::class,
            'application_id'
        );
    }

    public function agreement(): HasOne
    {
        return $this->hasOne(
            WelfareApplicationAgreement::class,
            'application_id'
        );
    }

    public function assistance(): HasOne
    {
        return $this->hasOne(
            WelfareAssistance::class,
            'application_id'
        );
    }

    public function assistanceIncludingArchived(): HasOne
    {
        return $this->hasOne(WelfareAssistance::class, 'application_id')->withTrashed();
    }

    public function isTestApplication(): bool
    {
        return $this->is_test;
    }

    public function beneficiary(): BelongsTo
    {
        return $this->belongsTo(
            Beneficiary::class,
            'beneficiary_id'
        );
    }

    public function fund(): BelongsTo
    {
        return $this->belongsTo(
            Fund::class,
            'fund_id'
        );
    }

    public function submittedBy(): BelongsTo
    {
        return $this->belongsTo(
            User::class,
            'submitted_by'
        );
    }

    public function recommendedBy(): BelongsTo
    {
        return $this->belongsTo(
            User::class,
            'recommended_by'
        );
    }

    public function approvedBy(): BelongsTo
    {
        return $this->belongsTo(
            User::class,
            'approved_by'
        );
    }

    public function rejectedBy(): BelongsTo
    {
        return $this->belongsTo(
            User::class,
            'rejected_by'
        );
    }
}
