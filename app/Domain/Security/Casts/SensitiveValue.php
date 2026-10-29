<?php

namespace App\Domain\Security\Casts;

use App\Domain\Security\Services\FieldEncryption;
use App\Domain\Security\Services\SensitiveFields;
use Illuminate\Contracts\Database\Eloquent\CastsAttributes;

class SensitiveValue implements CastsAttributes
{
    public bool $withoutObjectCaching = true;

    public function __construct(public string $kind = 'string') {}

    public function get($model, string $key, $value, array $attributes): mixed
    {
        $plain = app(FieldEncryption::class)->decode($value);
        if ($plain !== null && $this->kind === 'array' && is_string($plain)) {
            return json_decode($plain, true, 512, JSON_THROW_ON_ERROR);
        }

        return $plain;
    }

    public function set($model, string $key, $value, array $attributes): array
    {
        $encryption = app(FieldEncryption::class);
        if (! $encryption->installed()) {
            return [$key => $this->kind === 'array' && $value !== null ? json_encode($value, JSON_THROW_ON_ERROR) : $value];
        }
        $result = [$key => $encryption->encode($value)];
        if (SensitiveFields::indexed($key)) {
            $result[$key.'_lookup'] = $encryption->lookup($key, $value);
        }

        return $result;
    }
}
