<?php

namespace App\Domain\Qard\Models;

use Illuminate\Database\Eloquent\Model;

class QardFollowup extends Model
{
    protected $guarded = ['id'];
    protected function casts(): array { return ['next_followup_date' => 'date']; }
    public function actor() { return $this->belongsTo(\App\Domain\Users\Models\User::class, 'actor_id')->withTrashed(); }
}
