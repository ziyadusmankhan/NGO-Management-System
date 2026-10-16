<?php

namespace App\Domain\Committees\Models;

use App\Domain\Members\Models\Member;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class Committee extends Model
{
    use HasFactory, SoftDeletes;

    protected $table = 'committees';

    protected $fillable = [
        'committee_number',
        'name',
        'description',
        'type',
        'status',
        'established_at',
    ];

    protected $casts = [
        'established_at' => 'date',
    ];

    public function members(): BelongsToMany
    {
        return $this->belongsToMany(
            Member::class,
            'committee_members'
        )
        ->withPivot([
            'position',
            'joined_at',
            'ended_at',
            'status',
            'notes',
        ])
        ->withTimestamps();
    }
}