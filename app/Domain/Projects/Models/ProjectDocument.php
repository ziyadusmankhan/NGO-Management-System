<?php

namespace App\Domain\Projects\Models;

use App\Domain\Users\Models\User;
use Illuminate\Database\Eloquent\Model;

class ProjectDocument extends Model
{
    protected $guarded = ['id'];

    public function project()
    {
        return $this->belongsTo(Project::class)->withTrashed();
    }

    public function actor()
    {
        return $this->belongsTo(User::class, 'actor_id')->withTrashed();
    }
}
