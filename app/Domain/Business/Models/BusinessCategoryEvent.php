<?php

namespace App\Domain\Business\Models;

use App\Domain\Audit\Concerns\RecordsAudit;
use Illuminate\Database\Eloquent\Model;

class BusinessCategoryEvent extends Model
{
    use RecordsAudit;

    protected $guarded = ['id'];

    protected function casts(): array
    {
        return ['before_values' => 'array', 'after_values' => 'array'];
    }
}
