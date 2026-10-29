<?php

namespace App\Domain\Projects\Models;

use App\Domain\Security\Concerns\EncryptsSensitive;
use App\Domain\Users\Models\User;
use Illuminate\Database\Eloquent\Model;

class ProjectReview extends Model
{
    use EncryptsSensitive;

    protected $guarded = ['id'];

    protected function casts(): array
    {
        return ['resolution' => 'array'];
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
