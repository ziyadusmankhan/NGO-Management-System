<?php

namespace App\Domain\Operations\Models;

use App\Domain\Audit\Concerns\RecordsAudit;
use App\Domain\Committees\Models\Committee;
use App\Domain\Projects\Models\Project;
use App\Domain\Security\Concerns\EncryptsSensitive;
use App\Domain\Users\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Task extends Model
{
    use EncryptsSensitive, RecordsAudit, SoftDeletes;

    protected $table = 'operation_tasks';

    protected $guarded = ['id'];

    protected $attributes = ['status' => 'pending', 'priority' => 'medium', 'category' => 'other', 'progress_percent' => 0, 'revision' => 1];

    protected function casts(): array
    {
        return ['starts_on' => 'date', 'due_on' => 'date', 'completed_at' => 'datetime', 'progress_percent' => 'integer', 'revision' => 'integer'];
    }

    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by')->withTrashed();
    }

    public function assignee()
    {
        return $this->belongsTo(User::class, 'assigned_user_id')->withTrashed();
    }

    public function committee()
    {
        return $this->belongsTo(Committee::class)->withTrashed();
    }

    public function project()
    {
        return $this->belongsTo(Project::class)->withTrashed();
    }

    public function sourceMeeting()
    {
        return $this->belongsTo(Meeting::class, 'source_meeting_id')->withTrashed();
    }

    public function comments()
    {
        return $this->hasMany(TaskComment::class, 'task_id');
    }

    public function getOverdueAttribute(): bool
    {
        return $this->due_on && $this->due_on->isBefore(today('Asia/Karachi')) && ! in_array($this->status, ['completed', 'cancelled'], true);
    }
}
