<?php

namespace App\Domain\Welfare\Assistance\Models;

use App\Domain\Users\Models\User;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class WelfareAssistanceReview extends Model
{
    use HasFactory;

    protected $fillable = [
        'assistance_id',
        'reviewed_by',
        'review_date',
        'outcome',
        'next_review_date',
        'notes',
    ];

    protected function casts(): array
    {
        return [
            'review_date' => 'date',
            'next_review_date' => 'date',
        ];
    }

    public function assistance(): BelongsTo
    {
        return $this->belongsTo(
            WelfareAssistance::class,
            'assistance_id'
        );
    }

    public function reviewedBy(): BelongsTo
    {
        return $this->belongsTo(
            User::class,
            'reviewed_by'
        );
    }
}