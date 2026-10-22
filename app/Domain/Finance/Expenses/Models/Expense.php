<?php

namespace App\Domain\Finance\Expenses\Models;

use App\Domain\Finance\Funds\Models\Fund;
use App\Domain\Users\Models\User;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use App\Domain\Finance\Transactions\Models\Transaction;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Expense extends Model
{
    use \App\Domain\Finance\Support\ReadsDecimalMoney;

    protected array $moneyColumns = ['amount'];

    use HasFactory, SoftDeletes;

    protected $fillable = [
        'expense_number',
        'fund_id',
        'category',
        'description',
        'amount',
        'expense_date',
        'payee_name',
        'payment_method',
        'transaction_reference',
        'receipt_number',
        'status',
        'recorded_by',
        'approved_by',
        'approved_at',
        'purpose_notes',
        'internal_notes',
        'has_supporting_document',
    ];

    protected function casts(): array
    {
        return [
            'amount' => 'decimal:2',
            'expense_date' => 'date',
            'approved_at' => 'datetime',
            'has_supporting_document' => 'boolean',
        ];
    }
    public function transaction()
    {
        return $this->morphOne(
            Transaction::class,
            'source'
        );
    }

    public function fund(): BelongsTo
    {
        return $this->belongsTo(Fund::class, 'fund_id');
    }

    public function recordedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'recorded_by');
    }

    public function approvedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'approved_by');
    }
}