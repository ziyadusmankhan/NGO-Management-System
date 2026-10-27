<?php

namespace App\Domain\Finance\Funds\Models;

use App\Domain\Audit\Concerns\RecordsAudit;
use App\Domain\Users\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class FundChange extends Model
{
    use RecordsAudit;

    public $timestamps = false;

    protected $fillable = ['action', 'before_values', 'after_values', 'changed_by', 'changed_at'];

    protected function casts(): array
    {
        return ['before_values' => 'array', 'after_values' => 'array', 'changed_at' => 'datetime'];
    }

    public function changedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'changed_by')->withTrashed();
    }
}
