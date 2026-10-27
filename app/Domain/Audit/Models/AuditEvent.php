<?php

namespace App\Domain\Audit\Models;

use Illuminate\Database\Eloquent\Model;

class AuditEvent extends Model
{
    public $timestamps = false;

    protected $guarded = ['id'];

    protected function casts(): array
    {
        return ['before_values' => 'array', 'after_values' => 'array', 'context' => 'array', 'historical' => 'boolean', 'occurred_at' => 'immutable_datetime', 'captured_at' => 'immutable_datetime'];
    }

    protected static function booted(): void
    {
        static::updating(fn () => throw new \LogicException('Audit events are append-only.'));
        static::deleting(fn () => throw new \LogicException('Audit events cannot be deleted.'));
    }
}
