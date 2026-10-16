<?php

namespace App\Domain\Welfare\Assistance\Models;
use App\Domain\Welfare\Applications\Models\WelfareApplication;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use App\Domain\Finance\Funds\Models\Fund;
use App\Domain\Welfare\Assistance\Models\WelfareAssistancePayment;
use App\Domain\Users\Models\User;
use App\Domain\Welfare\Beneficiaries\Models\Beneficiary;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
// use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class WelfareAssistance extends Model
{
    use HasFactory, SoftDeletes;

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