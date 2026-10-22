<?php

namespace App\Domain\Qard\Models;

use Illuminate\Database\Eloquent\Model;

class QardReview extends Model
{
    protected $guarded = ['id'];
    public function actor() { return $this->belongsTo(\App\Domain\Users\Models\User::class, 'actor_id')->withTrashed(); }
}
