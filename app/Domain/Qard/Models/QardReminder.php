<?php

namespace App\Domain\Qard\Models;

use Illuminate\Database\Eloquent\Model;

class QardReminder extends Model
{
    protected $guarded = ['id'];

    protected function casts(): array
    {
        return ['resolved_at' => 'datetime'];
    }

    public function loan()
    {
        return $this->belongsTo(QardLoan::class, 'qard_loan_id');
    }
}
