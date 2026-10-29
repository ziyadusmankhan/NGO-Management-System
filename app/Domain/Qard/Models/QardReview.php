<?php

namespace App\Domain\Qard\Models;

use App\Domain\Security\Concerns\EncryptsSensitive;
use App\Domain\Users\Models\User;
use Illuminate\Database\Eloquent\Model;

class QardReview extends Model
{
    use EncryptsSensitive;

    protected $guarded = ['id'];

    public function actor()
    {
        return $this->belongsTo(User::class, 'actor_id')->withTrashed();
    }
}
