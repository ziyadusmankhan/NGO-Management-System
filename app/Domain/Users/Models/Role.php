<?php

namespace App\Domain\Users\Models;

use App\Domain\Audit\Concerns\RecordsAudit;
use App\Domain\Audit\Services\AuditedRelations;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class Role extends Model
{
    use HasFactory;
    use RecordsAudit;

    protected $table = 'roles';

    protected $fillable = [
        'name',
        'slug',
        'description',
        'is_system',
        'status',
    ];

    protected $casts = [
        'is_system' => 'boolean',
    ];

    public function users(): BelongsToMany
    {
        return $this->belongsToMany(
            User::class,
            'user_roles'
        )->withTimestamps();
    }

    public function permissions(): BelongsToMany
    {
        return AuditedRelations::make($this, Permission::class, 'role_permissions', 'role_id', 'permission_id');
    }
}
