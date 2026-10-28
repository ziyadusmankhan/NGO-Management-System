<?php

namespace App\Domain\Operations\Models;

use App\Domain\Audit\Concerns\RecordsAudit;
use App\Domain\Security\Concerns\EncryptsSensitive;
use App\Domain\Users\Models\User;
use Illuminate\Database\Eloquent\Model;

class TaskComment extends Model
{
    use EncryptsSensitive, RecordsAudit;

    protected $table = 'operation_task_comments';

    protected $guarded = ['id'];

    public function task()
    {
        return $this->belongsTo(Task::class);
    }

    public function user()
    {
        return $this->belongsTo(User::class)->withTrashed();
    }
}
