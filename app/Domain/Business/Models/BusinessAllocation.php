<?php

namespace App\Domain\Business\Models;

use App\Domain\Finance\Support\ReadsDecimalMoney;
use Illuminate\Database\Eloquent\Model;

class BusinessAllocation extends Model
{
    use ReadsDecimalMoney;

    protected $guarded = ['id'];

    public function movement()
    {
        return $this->belongsTo(BusinessMovement::class, 'business_movement_id');
    }
}
