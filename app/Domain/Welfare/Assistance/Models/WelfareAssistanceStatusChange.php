<?php

namespace App\Domain\Welfare\Assistance\Models;

use App\Domain\Audit\Concerns\RecordsAudit;
use App\Domain\Users\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class WelfareAssistanceStatusChange extends Model
{
    use RecordsAudit;

    public $timestamps = false;

    protected $fillable = ['from_status', 'to_status', 'changed_by', 'changed_at', 'reason'];

    protected function casts(): array
    {
        return ['changed_at' => 'datetime'];
    }

    public function assistance(): BelongsTo
    {
        return $this->belongsTo(WelfareAssistance::class, 'assistance_id');
    }

    public function changedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'changed_by')->withTrashed();
    }
}
