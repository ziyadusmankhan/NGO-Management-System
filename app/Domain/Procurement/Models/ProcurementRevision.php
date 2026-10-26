<?php

namespace App\Domain\Procurement\Models;

use App\Domain\Users\Models\User;
use Illuminate\Database\Eloquent\Model;

class ProcurementRevision extends Model
{
    protected $table = 'procurement_revisions';

    protected $guarded = ['id'];

    protected function casts(): array
    {
        return ['items' => 'array', 'supplier_snapshot' => 'array', 'required_on' => 'date', 'approved_at' => 'datetime', 'issued_at' => 'datetime'];
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
