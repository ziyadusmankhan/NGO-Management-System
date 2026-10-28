<?php

namespace App\Domain\Business\Models;

use App\Domain\Finance\Support\ReadsDecimalMoney;
use App\Domain\Security\Concerns\EncryptsSensitive;
use App\Domain\Users\Models\User;
use Illuminate\Database\Eloquent\Model;

class BusinessMonitoring extends Model
{
    use EncryptsSensitive;
    use ReadsDecimalMoney;

    protected $table = 'business_monitorings';

    protected $guarded = ['id'];

    protected array $moneyColumns = ['sales', 'expenses'];

    protected function casts(): array
    {
        return ['review_date' => 'date', 'next_review_date' => 'date', 'final_report' => 'boolean'];
    }

    public function actor()
    {
        return $this->belongsTo(User::class, 'actor_id')->withTrashed();
    }
}
