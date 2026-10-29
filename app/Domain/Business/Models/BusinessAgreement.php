<?php

namespace App\Domain\Business\Models;

use App\Domain\Security\Concerns\EncryptsSensitive;
use Illuminate\Database\Eloquent\Model;

class BusinessAgreement extends Model
{
    use EncryptsSensitive;

    protected $guarded = ['id'];

    protected function casts(): array
    {
        return ['terms' => 'array', 'witnesses' => 'array', 'signed_on' => 'date'];
    }

    public function documents()
    {
        return $this->hasMany(BusinessDocument::class, 'agreement_id');
    }
}
