<?php

namespace App\Domain\Business\Models;

use App\Domain\Finance\Support\Money;
use App\Domain\Finance\Support\ReadsDecimalMoney;
use Illuminate\Database\Eloquent\Model;

class BusinessBudgetItem extends Model
{
    use ReadsDecimalMoney;

    protected $guarded = ['id'];

    protected array $moneyColumns = ['cost', 'applicant_amount', 'organization_amount'];

    protected function casts(): array
    {
        return ['price_checks' => 'array'];
    }

    public function movements()
    {
        return $this->hasMany(BusinessMovement::class, 'budget_item_id');
    }

    public function paidMinor(): int
    {
        return Money::sumMinor($this->movements()->where('status', 'posted')->getQuery());
    }
}
