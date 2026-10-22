<?php

namespace App\Domain\Qard\Models;

use Illuminate\Database\Eloquent\Model;

class QardAgreement extends Model
{
    protected $guarded = ['id'];
    protected function casts(): array { return ['terms' => 'array', 'signed_at' => 'datetime']; }
}
