<?php

namespace App\Domain\Projects\Models;

use App\Domain\Audit\Concerns\RecordsAudit;
use App\Domain\Users\Models\User;
use Illuminate\Database\Eloquent\Model;

class ProjectEvent extends Model
{
    use RecordsAudit;

    protected $guarded = ['id'];

    protected function casts(): array
    {
        return ['before_values' => 'array', 'after_values' => 'array'];
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
