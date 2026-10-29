<?php

namespace App\Domain\Security\Concerns;

use App\Domain\Security\Casts\SensitiveValue;
use App\Domain\Security\Services\SensitiveFields;

trait EncryptsSensitive
{
    public function initializeEncryptsSensitive(): void
    {
        $casts = $this->getCasts();
        foreach (SensitiveFields::fields($this->getTable()) as $field) {
            $this->mergeCasts([$field => SensitiveValue::class.':'.(($casts[$field] ?? null) === 'array' ? 'array' : 'string')]);
            if (SensitiveFields::indexed($field)) {
                $this->makeHidden($field.'_lookup');
            }
        }
    }
}
