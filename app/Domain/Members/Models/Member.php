<?php

namespace App\Domain\Members\Models;

use App\Domain\Audit\Concerns\RecordsAudit;
use App\Domain\Audit\Services\AuditedCommitteeRelations;
use App\Domain\Committees\Models\Committee;
use App\Domain\Security\Concerns\EncryptsSensitive;
use App\Domain\Users\Models\User;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Member extends Model
{
    use EncryptsSensitive;
    use HasFactory, SoftDeletes;
    use RecordsAudit;

    protected $table = 'members';

    protected $fillable = [
        'user_id',
        'member_number',
        'first_name',
        'last_name',
        'father_name',
        'cnic',
        'date_of_birth',
        'gender',
        'phone',
        'alternate_phone',
        'email',
        'address',
        'joining_date',
        'status',
        'notes',
    ];

    protected $casts = [
        'date_of_birth' => 'date',
        'joining_date' => 'date',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function committees(): BelongsToMany
    {
        return AuditedCommitteeRelations::make($this, Committee::class, 'member_id', 'committee_id')
            ->withPivot([
                'position',
                'joined_at',
                'ended_at',
                'status',
                'notes',
            ])
            ->withTimestamps();
    }

    public function getFullNameAttribute(): string
    {
        return trim(
            $this->first_name.' '.($this->last_name ?? '')
        );
    }
}
