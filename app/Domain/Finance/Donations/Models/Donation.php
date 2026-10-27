<?php

namespace App\Domain\Finance\Donations\Models;

use App\Domain\Audit\Concerns\RecordsAudit;
use App\Domain\Finance\Funds\Models\Fund;
use App\Domain\Finance\Support\ReadsDecimalMoney;
use App\Domain\Finance\Transactions\Models\Transaction;
use App\Domain\Users\Models\User;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class Donation extends Model
{
    use ReadsDecimalMoney;
    use RecordsAudit;

    protected array $moneyColumns = ['amount'];

    use HasFactory, SoftDeletes;

    protected $table = 'donations';

    protected $fillable = [
        'donation_number',
        'donor_type',
        'donor_name',
        'phone',
        'email',
        'amount',
        'donation_date',
        'payment_method',
        'transaction_reference',
        'receipt_number',
        'designation',
        'purpose',
        'fund_reference',
        'purpose_notes',
        'internal_notes',
        'status',
        'recorded_by',
        'fund_id',
    ];

    protected $casts = [
        'amount' => 'decimal:2',
        'donation_date' => 'date',
    ];

    public function fund(): BelongsTo
    {
        return $this->belongsTo(
            Fund::class,
            'fund_id'
        );
    }

    public function transaction()
    {
        return $this->morphOne(
            Transaction::class,
            'source'
        );
    }

    public function recordedBy(): BelongsTo
    {
        return $this->belongsTo(
            User::class,
            'recorded_by'
        );
    }
}
