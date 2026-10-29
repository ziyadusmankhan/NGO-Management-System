<?php

namespace App\Domain\Business\Models;

use Illuminate\Database\Eloquent\Model;

class BusinessReminder extends Model
{
    protected $guarded = ['id'];

    protected function casts(): array
    {
        return ['resolved_at' => 'datetime'];
    }

    public function application()
    {
        return $this->belongsTo(BusinessApplication::class, 'business_application_id')->withTrashed();
    }
}
