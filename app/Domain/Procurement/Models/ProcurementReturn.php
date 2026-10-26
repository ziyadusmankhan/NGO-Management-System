<?php

namespace App\Domain\Procurement\Models;

use App\Domain\Users\Models\User;
use Illuminate\Database\Eloquent\Model;

class ProcurementReturn extends Model
{
    protected $table = 'procurement_returns';

    protected $guarded = ['id'];

    protected function casts(): array
    {
        return ['returned_on' => 'date', 'refund_due_on' => 'date'];
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
