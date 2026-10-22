<?php

namespace App\Domain\Welfare\Assistance\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class WelfareAssistanceReminder extends Model
{
    protected $fillable = ['kind', 'due_date', 'resolved_at'];

    public function setDueDateAttribute($value): void
    {
        // Keep the unique calendar-date key identical on SQLite and SQL DATE columns.
        $this->attributes['due_date'] = \Carbon\CarbonImmutable::parse($value)->toDateString();
    }

    protected function casts(): array
    {
        return ['due_date' => 'date', 'resolved_at' => 'datetime'];
    }

    public function assistance(): BelongsTo
    {
        return $this->belongsTo(WelfareAssistance::class, 'assistance_id')->withTrashed();
    }
}
