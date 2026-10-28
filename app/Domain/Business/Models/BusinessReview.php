<?php

namespace App\Domain\Business\Models;

use App\Domain\Security\Concerns\EncryptsSensitive;
use App\Domain\Users\Models\User;
use Illuminate\Database\Eloquent\Model;

class BusinessReview extends Model
{
    use EncryptsSensitive;

    protected $guarded = ['id'];

    protected function casts(): array
    {
        return ['consenting_members' => 'array', 'purchase_exceptions' => 'array', 'resolution_date' => 'date'];
    }

    public function actor()
    {
        return $this->belongsTo(User::class, 'actor_id')->withTrashed();
    }
}
