<?php

namespace App\Domain\Qard\Models;

use App\Domain\Finance\Support\ReadsDecimalMoney;
use App\Domain\Finance\Transactions\Models\Transaction;
use App\Domain\Security\Concerns\EncryptsSensitive;
use Illuminate\Database\Eloquent\Model;

class QardMovement extends Model
{
    use EncryptsSensitive;

    protected $guarded = ['id'];

    use ReadsDecimalMoney;

    protected function casts(): array
    {
        return ['amount' => 'decimal:2', 'payment_date' => 'date', 'voided_at' => 'datetime'];
    }

    public function loan()
    {
        return $this->belongsTo(QardLoan::class, 'qard_loan_id');
    }

    public function transaction()
    {
        return $this->morphOne(Transaction::class, 'source')->withTrashed();
    }

    public function allocations()
    {
        return $this->hasMany(QardAllocation::class);
    }
}
