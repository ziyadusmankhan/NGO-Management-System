<?php

namespace App\Domain\Business\Models;

use App\Domain\Audit\Concerns\RecordsAudit;
use App\Domain\Security\Concerns\EncryptsSensitive;
use App\Domain\Users\Models\User;
use Illuminate\Database\Eloquent\Model;

class BusinessEvent extends Model
{
    use EncryptsSensitive;
    use RecordsAudit;

    protected $guarded = ['id'];

    protected function casts(): array
    {
        return ['before_values' => 'array', 'after_values' => 'array'];
    }

    public function actor()
    {
        return $this->belongsTo(User::class, 'actor_id')->withTrashed();
    }

    protected static function booted(): void
    {
        static::updating(fn () => throw new \LogicException('History is immutable.'));
        static::deleting(fn () => throw new \LogicException('History is retained.'));
    }
}
