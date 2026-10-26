<?php

namespace App\Domain\Projects\Models;

use App\Domain\Users\Models\User;
use Illuminate\Database\Eloquent\Model;

class ProjectReminder extends Model
{
    protected $guarded = ['id'];

    protected function casts(): array
    {
        return ['review_due' => 'boolean', 'completion_overdue' => 'boolean', 'resolved_at' => 'datetime'];
    }

    public function project()
    {
        return $this->belongsTo(Project::class)->withTrashed();
    }

    public function actor()
    {
        return $this->belongsTo(User::class, 'actor_id')->withTrashed();
    }
}
