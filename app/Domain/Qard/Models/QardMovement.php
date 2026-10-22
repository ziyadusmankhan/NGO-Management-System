<?php

namespace App\Domain\Qard\Models;

use Illuminate\Database\Eloquent\Model;

class QardMovement extends Model
{
    protected $guarded = ['id'];
    use \App\Domain\Finance\Support\ReadsDecimalMoney;
    protected function casts(): array { return ['amount' => 'decimal:2', 'payment_date' => 'date', 'voided_at' => 'datetime']; }
    public function loan() { return $this->belongsTo(QardLoan::class, 'qard_loan_id'); }
    public function transaction() { return $this->morphOne(\App\Domain\Finance\Transactions\Models\Transaction::class, 'source')->withTrashed(); }
    public function allocations() { return $this->hasMany(QardAllocation::class); }
}
