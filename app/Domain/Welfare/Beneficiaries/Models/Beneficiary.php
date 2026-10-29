<?php

namespace App\Domain\Welfare\Beneficiaries\Models;

use App\Domain\Audit\Concerns\RecordsAudit;
use App\Domain\Finance\Support\ReadsDecimalMoney;
use App\Domain\Security\Concerns\EncryptsSensitive;
use App\Domain\Users\Models\User;
use App\Domain\Welfare\Assistance\Models\WelfareAssistance;
use App\Domain\Welfare\Verification\Models\BeneficiaryVerification;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Beneficiary extends Model
{
    use EncryptsSensitive;
    use ReadsDecimalMoney;
    use RecordsAudit;

    protected array $moneyColumns = ['monthly_income'];

    use HasFactory;
    use SoftDeletes;

    protected $fillable = [
        'beneficiary_number',
        'full_name',
        'father_husband_name',
        'cnic',
        'phone',
        'alternate_phone',
        'address',
        'location',
        'category',
        'marital_status',
        'dependents_count',
        'occupation',
        'monthly_income',
        'assets_description',
        'debts_description',
        'household_condition',
        'verification_status',
        'verified_by',
        'verified_at',
        'approval_status',
        'approved_by',
        'approved_at',
        'next_review_date',
        'notes',
    ];

    protected function casts(): array
    {
        return [
            'dependents_count' => 'integer',
            'monthly_income' => 'decimal:2',
            'verified_at' => 'datetime',
            'approved_at' => 'datetime',
            'next_review_date' => 'date',
        ];
    }

    /*
     * User who verified the beneficiary.
     */
    public function verifiedBy(): BelongsTo
    {
        return $this->belongsTo(
            User::class,
            'verified_by'
        );
    }

    /*
     * User who approved the beneficiary.
     */
    public function approvedBy(): BelongsTo
    {
        return $this->belongsTo(
            User::class,
            'approved_by'
        );
    }

    /*
     * Welfare assistance history.
     */
    public function assistance(): HasMany
    {
        return $this->hasMany(
            WelfareAssistance::class
        );
    }

    /*
     * Verification records/history.
     */
    public function verifications(): HasMany
    {
        return $this->hasMany(
            BeneficiaryVerification::class
        );
    }
}
