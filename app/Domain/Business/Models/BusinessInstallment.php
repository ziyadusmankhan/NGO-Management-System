<?php

namespace App\Domain\Business\Models;

use App\Domain\Finance\Support\Money;
use App\Domain\Finance\Support\ReadsDecimalMoney;
use Illuminate\Database\Eloquent\Model;

class BusinessInstallment extends Model
{
    use ReadsDecimalMoney;

    protected $guarded = ['id'];

    protected function casts(): array
    {
        return ['due_date' => 'date'];
    }

    public function allocations()
    {
        return $this->hasMany(BusinessAllocation::class);
    }

    public function paidMinor(): int
    {
        return Money::sumMinor($this->allocations()->whereHas('movement', fn ($q) => $q->where('status', 'posted'))->getQuery());
    }
}
