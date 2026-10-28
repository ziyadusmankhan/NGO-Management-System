<?php

namespace App\Domain\Procurement\Models;

use App\Domain\Audit\Concerns\RecordsAudit;
use App\Domain\Security\Concerns\EncryptsSensitive;
use App\Domain\Users\Models\User;
use Illuminate\Database\Eloquent\Model;

class ProcurementEvent extends Model
{
    use EncryptsSensitive;
    use RecordsAudit;

    protected $table = 'procurement_events';

    protected $guarded = ['id'];

    protected function casts(): array
    {
        return ['before_values' => 'array', 'after_values' => 'array'];
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
