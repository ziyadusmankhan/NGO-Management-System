<?php

namespace App\Domain\Procurement\Models;

use App\Domain\Security\Concerns\EncryptsSensitive;
use App\Domain\Users\Models\User;
use Illuminate\Database\Eloquent\Model;

class ProcurementQuote extends Model
{
    use EncryptsSensitive;

    protected $table = 'procurement_quotes';

    protected $guarded = ['id'];

    protected function casts(): array
    {
        return ['items' => 'array', 'supplier_snapshot' => 'array', 'quoted_on' => 'date', 'valid_until' => 'date'];
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
