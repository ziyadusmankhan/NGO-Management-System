<?php

namespace App\Domain\Finance\Transactions\Models;

use App\Domain\Finance\Funds\Models\Fund;
use App\Domain\Users\Models\User;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Transaction extends Model
{
    use HasFactory, SoftDeletes, \App\Domain\Finance\Support\ReadsDecimalMoney;

    protected $fillable = [
        'transaction_number',
        'fund_id',
        'type',
        'source_type',
        'source_id',
        'reference',
        'description',
        'amount',
        'direction',
        'transaction_date',
        'status',
        'recorded_by',
        'notes',
    ];
    public function source()
    {
        return $this->morphTo();
    }

    protected function casts(): array
    {
        return [
            'amount' => 'decimal:2',
            'transaction_date' => 'date',
        ];
    }

    public function fund(): BelongsTo
    {
        return $this->belongsTo(Fund::class, 'fund_id')->withTrashed();
    }

    public function recordedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'recorded_by');
    }
}
