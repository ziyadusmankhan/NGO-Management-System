<?php

namespace App\Domain\Qard\Models;

use Illuminate\Database\Eloquent\Model;

class QardAllocation extends Model
{
    protected $guarded = ['id'];
    use \App\Domain\Finance\Support\ReadsDecimalMoney;
    protected function casts(): array { return ['amount' => 'decimal:2']; }
    public function movement() { return $this->belongsTo(QardMovement::class, 'qard_movement_id'); }
}
