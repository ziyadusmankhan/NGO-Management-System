<?php

namespace App\Domain\Business\Models;

use App\Domain\Audit\Concerns\RecordsAudit;
use App\Domain\Security\Concerns\EncryptsSensitive;
use Illuminate\Database\Eloquent\Model;

class BusinessCategoryEvent extends Model
{
    use EncryptsSensitive;
    use RecordsAudit;

    protected $guarded = ['id'];

    protected function casts(): array
    {
        return ['before_values' => 'array', 'after_values' => 'array'];
    }
}
