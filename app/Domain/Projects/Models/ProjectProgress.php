<?php

namespace App\Domain\Projects\Models;

use App\Domain\Security\Concerns\EncryptsSensitive;
use App\Domain\Users\Models\User;
use Illuminate\Database\Eloquent\Model;

class ProjectProgress extends Model
{
    use EncryptsSensitive;

    protected $guarded = ['id'];

    protected $table = 'project_progress';

    protected function casts(): array
    {
        return ['report_date' => 'date', 'next_review_date' => 'date'];
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
