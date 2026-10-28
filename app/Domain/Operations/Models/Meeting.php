<?php

namespace App\Domain\Operations\Models;

use App\Domain\Audit\Concerns\RecordsAudit;
use App\Domain\Committees\Models\Committee;
use App\Domain\Security\Concerns\EncryptsSensitive;
use App\Domain\Users\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Meeting extends Model
{
    use EncryptsSensitive, RecordsAudit, SoftDeletes;

    protected $table = 'operation_meetings';

    protected $guarded = ['id'];

    protected $attributes = ['status' => 'scheduled', 'type' => 'general', 'revision' => 1, 'quorum_required' => 1];

    protected function casts(): array
    {
        return ['meeting_date' => 'date', 'minutes_recorded_at' => 'datetime', 'revision' => 'integer'];
    }

    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by')->withTrashed();
    }

    public function chair()
    {
        return $this->belongsTo(User::class, 'chair_user_id')->withTrashed();
    }

    public function committee()
    {
        return $this->belongsTo(Committee::class)->withTrashed();
    }

    public function attendees()
    {
        return $this->belongsToMany(User::class, 'operation_meeting_attendees')->withPivot(['response', 'attended'])->withTimestamps();
    }

    public function tasks()
    {
        return $this->hasMany(Task::class, 'source_meeting_id');
    }
}
