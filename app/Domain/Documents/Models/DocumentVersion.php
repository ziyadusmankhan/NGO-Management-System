<?php

namespace App\Domain\Documents\Models;

use Illuminate\Database\Eloquent\Model;

class DocumentVersion extends Model
{
    protected $table = 'library_document_versions';

    protected $guarded = ['id'];

    protected function casts(): array
    {
        return ['uploaded_at' => 'datetime', 'size' => 'integer'];
    }

    public function document()
    {
        return $this->belongsTo(LibraryDocument::class, 'document_id');
    }

    protected static function booted(): void
    {
        static::updating(fn () => throw new \LogicException('File versions are immutable.'));
        static::deleting(fn () => throw new \LogicException('Retain original evidence.'));
    }
}
