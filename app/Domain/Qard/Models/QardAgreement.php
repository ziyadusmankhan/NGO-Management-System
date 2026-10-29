<?php

namespace App\Domain\Qard\Models;

use App\Domain\Security\Concerns\EncryptsSensitive;
use Illuminate\Database\Eloquent\Model;

class QardAgreement extends Model
{
    use EncryptsSensitive;

    protected $guarded = ['id'];

    protected function casts(): array
    {
        return ['witnesses' => 'array', 'legacy_witnesses' => 'boolean', 'terms' => 'array', 'signed_at' => 'datetime'];
    }
}
