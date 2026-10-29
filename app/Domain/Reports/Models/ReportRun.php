<?php

namespace App\Domain\Reports\Models;

use App\Domain\Audit\Concerns\RecordsAudit;
use App\Domain\Users\Models\User;
use Illuminate\Database\Eloquent\Model;

class ReportRun extends Model
{
    use RecordsAudit;

    protected $guarded = ['id'];

    protected function casts(): array
    {
        return ['filters' => 'array', 'snapshot' => 'array', 'period_from' => 'date', 'period_to' => 'date', 'generated_at' => 'datetime'];
    }

    public function generator()
    {
        return $this->belongsTo(User::class, 'generated_by')->withTrashed();
    }
}
