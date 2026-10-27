<?php

namespace App\Domain\Welfare\Assistance\Models;

use App\Domain\Audit\Concerns\RecordsAudit;
use App\Domain\Finance\Funds\Models\Fund;
use App\Domain\Users\Models\User;
use App\Domain\Welfare\Applications\Models\WelfareApplication;
use App\Domain\Welfare\Beneficiaries\Models\Beneficiary;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\SoftDeletes;

class WelfareAssistance extends Model
{
    use HasFactory, SoftDeletes;
    use RecordsAudit;

    public function newModelQuery()
    {
        $query = parent::newModelQuery();
        if ($this->getConnection()->getDriverName() === 'sqlite') {
            // Read fixed-scale text before PDO/PHP can shorten SQLite REAL values.
            $query->select('welfare_assistances.*')->selectRaw(
                "CASE WHEN welfare_assistances.amount IS NULL THEN NULL ELSE printf('%.2f', welfare_assistances.amount) END AS amount"
            );
        }

        return $query;
    }

    public const INHERITED_FIELDS = [
        'beneficiary_id', 'fund_id', 'assistance_type', 'amount', 'frequency',
        'start_date', 'end_date', 'purpose_notes', 'has_supporting_document',
    ];

    public const ADMINISTRATIVE_FIELDS = ['next_review_date', 'internal_notes', 'notes'];

    public function statusChanges(): HasMany
    {
        return $this->hasMany(WelfareAssistanceStatusChange::class, 'assistance_id')->orderBy('id');
    }

    public function latestReview(): HasOne
    {
        return $this->hasOne(WelfareAssistanceReview::class, 'assistance_id')->latestOfMany();
    }

    public function reminders(): HasMany
    {
        return $this->hasMany(WelfareAssistanceReminder::class, 'assistance_id');
    }

    public function closedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'closed_by')->withTrashed();
    }

    protected $fillable = [
        'assistance_number',
        'beneficiary_id',
        'fund_id',
        'assistance_type',
        'amount',
        'frequency',
        'start_date',
        'end_date',
        'status',
        'requested_at',
        'approved_by',
        'approved_at',
        'next_review_date',
        'purpose_notes',
        'internal_notes',
        'has_supporting_document',
        'notes',
    ];

    public function payments(): HasMany
    {
        return $this->hasMany(
            WelfareAssistancePayment::class,
            'assistance_id'
        );
    }

    public function reviews(): HasMany
    {
        return $this->hasMany(
            WelfareAssistanceReview::class,
            'assistance_id'
        );
    }

    public function application(): BelongsTo
    {
        return $this->belongsTo(
            WelfareApplication::class,
            'application_id'
        );
    }

    protected function casts(): array
    {
        return [
            'amount' => 'decimal:2',
            'start_date' => 'date',
            'end_date' => 'date',
            'requested_at' => 'datetime',
            'approved_at' => 'datetime',
            'next_review_date' => 'date',
            'has_supporting_document' => 'boolean',
            'closed_at' => 'datetime',
        ];
    }

    public function beneficiary(): BelongsTo
    {
        return $this->belongsTo(Beneficiary::class);
    }

    public function fund(): BelongsTo
    {
        return $this->belongsTo(Fund::class);
    }

    public function approvedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'approved_by');
    }
}
