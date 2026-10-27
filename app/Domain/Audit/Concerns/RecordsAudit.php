<?php

namespace App\Domain\Audit\Concerns;

use App\Domain\Audit\Services\AuditCapture;

trait RecordsAudit
{
    public function save(array $options = [])
    {
        return $this->getConnection()->transaction(fn () => parent::save($options));
    }

    public function delete()
    {
        return $this->getConnection()->transaction(fn () => parent::delete());
    }

    public static function bootRecordsAudit(): void
    {
        static::created(fn ($m) => app(AuditCapture::class)->created($m));
        static::updated(fn ($m) => app(AuditCapture::class)->updated($m));
        static::deleted(fn ($m) => app(AuditCapture::class)->deleted($m));
    }
}
