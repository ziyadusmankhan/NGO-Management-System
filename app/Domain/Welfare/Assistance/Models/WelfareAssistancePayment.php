<?php

namespace App\Domain\Welfare\Assistance\Models;

use App\Domain\Finance\Funds\Models\Fund;
use App\Domain\Finance\Transactions\Models\Transaction;
use App\Domain\Users\Models\User;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphOne;

class WelfareAssistancePayment extends Model
{
    use HasFactory, \App\Domain\Finance\Support\ReadsDecimalMoney;

    protected $fillable = [
        'payment_number',
        'idempotency_key',
        'assistance_id',
        'fund_id',
        'amount',
        'payment_date',
        'payment_method',
        'transaction_reference',
        'receipt_number',
        'paid_to',
        'status',
        'recorded_by',
        'voided_by',
        'voided_at',
        'void_reason',
        'notes',
    ];

    protected function casts(): array
    {
        return [
            'amount' => 'decimal:2',
            'payment_date' => 'date',
            'voided_at' => 'datetime',
        ];
    }

    public function assistance(): BelongsTo
    {
        return $this->belongsTo(WelfareAssistance::class);
    }

    public function fund(): BelongsTo
    {
        return $this->belongsTo(Fund::class);
    }

    public function recordedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'recorded_by');
    }
    public function voidedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'voided_by');
    }

    public function transaction(): MorphOne
    {
        return $this->morphOne(
            Transaction::class,
            'source'
        );
    }
}
