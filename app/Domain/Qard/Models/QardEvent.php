<?php

namespace App\Domain\Qard\Models;

use Illuminate\Database\Eloquent\Model;

class QardEvent extends Model
{
    protected $guarded = ['id'];
    protected function casts(): array { return ['before' => 'array', 'after' => 'array']; }
    public function actor() { return $this->belongsTo(\App\Domain\Users\Models\User::class, 'actor_id')->withTrashed(); }
}
