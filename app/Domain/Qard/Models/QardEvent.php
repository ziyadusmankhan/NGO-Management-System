<?php

namespace App\Domain\Qard\Models;

use App\Domain\Audit\Concerns\RecordsAudit;
use App\Domain\Users\Models\User;
use Illuminate\Database\Eloquent\Model;

class QardEvent extends Model
{
    use RecordsAudit;

    protected $guarded = ['id'];

    protected function casts(): array
    {
        return ['before' => 'array', 'after' => 'array'];
    }

    public function actor()
    {
        return $this->belongsTo(User::class, 'actor_id')->withTrashed();
    }
}
