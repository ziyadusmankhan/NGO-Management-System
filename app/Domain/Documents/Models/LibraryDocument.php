<?php

namespace App\Domain\Documents\Models;

use App\Domain\Users\Models\Role;
use Illuminate\Database\Eloquent\Model;

class LibraryDocument extends Model
{
    protected $table = 'library_documents';

    protected $guarded = ['id'];

    protected $attributes = ['revision' => 1, 'authoritative' => false];

    protected function casts(): array
    {
        return ['revision' => 'integer', 'authoritative' => 'boolean', 'archived_at' => 'datetime'];
    }

    public function versions()
    {
        return $this->hasMany(DocumentVersion::class, 'document_id');
    }

    public function roles()
    {
        return $this->belongsToMany(Role::class, 'library_document_roles', 'document_id', 'role_id');
    }

    protected static function booted(): void
    {
        static::deleting(fn () => throw new \LogicException('Archive documents; retain their history.'));
    }
}
