<?php

namespace App\Domain\Qard\Models;

use App\Domain\Security\Concerns\EncryptsSensitive;
use App\Domain\Users\Models\User;
use Illuminate\Database\Eloquent\Model;

class QardFollowup extends Model
{
    use EncryptsSensitive;

    protected $guarded = ['id'];

    protected function casts(): array
    {
        return ['next_followup_date' => 'date'];
    }

    public function actor()
    {
        return $this->belongsTo(User::class, 'actor_id')->withTrashed();
    }
}
