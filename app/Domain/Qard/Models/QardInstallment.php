<?php

namespace App\Domain\Qard\Models;

use Illuminate\Database\Eloquent\Model;

class QardInstallment extends Model
{
    protected $guarded = ['id'];
    use \App\Domain\Finance\Support\ReadsDecimalMoney;
    protected function casts(): array { return ['amount' => 'decimal:2', 'due_date' => 'date']; }
    public function allocations() { return $this->hasMany(QardAllocation::class); }
    public function paidMinor(): int { return \App\Domain\Finance\Support\Money::sumMinor($this->allocations()->whereHas('movement', fn ($q) => $q->where('status', 'posted'))->getQuery()); }
}
