<?php

namespace App\Domain\Business\Models;

use Illuminate\Database\Eloquent\Model;

class BusinessHandover extends Model
{
    protected $guarded = ['id'];

    protected function casts(): array
    {
        return ['handed_over_on' => 'date'];
    }

    public function movement()
    {
        return $this->belongsTo(BusinessMovement::class, 'movement_id');
    }
}
