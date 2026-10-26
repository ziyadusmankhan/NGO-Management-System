<?php

namespace App\Domain\Procurement\Models;

use App\Domain\Users\Models\User;
use Illuminate\Database\Eloquent\Model;

class ProcurementReceipt extends Model
{
    protected $table = 'procurement_receipts';

    protected $guarded = ['id'];

    protected function casts(): array
    {
        return ['received_on' => 'date'];
    }

    public function procurement()
    {
        return $this->belongsTo(Procurement::class)->withTrashed();
    }

    public function actor()
    {
        return $this->belongsTo(User::class, 'actor_id')->withTrashed();
    }
}
