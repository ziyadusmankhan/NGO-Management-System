<?php

namespace App\Domain\Security\Services;

use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class FieldEncryption
{
    public const PREFIX = 'encrypted:v1:';

    public function installed(): bool
    {
        if (Schema::hasTable('security_state')) {
            return true;
        }
        if (Schema::hasTable('migrations') && DB::table('migrations')->where('migration', '2026_10_27_000002_add_sensitive_field_security')->exists()) {
            throw new \RuntimeException('Required encryption state is unavailable.');
        }

        return false;
    }

    public function sealed(): bool
    {
        if (! $this->installed()) {
            return false;
        }
        $state = DB::table('security_state')->where('key', 'encryption')->value('value');
        if (! in_array($state, ['converting', 'sealed'], true)) {
            throw new \RuntimeException('Required encryption state is invalid.');
        }

        return $state === 'sealed';
    }

    public function encode(mixed $value): ?string
    {
        return $value === null ? null : self::PREFIX.Crypt::encryptString(json_encode($value, JSON_THROW_ON_ERROR | JSON_UNESCAPED_UNICODE));
    }

    public function decode(mixed $value): mixed
    {
        if ($value === null) {
            return null;
        }
        if (is_string($value) && str_starts_with($value, self::PREFIX)) {
            return json_decode(Crypt::decryptString(substr($value, strlen(self::PREFIX))), true, 512, JSON_THROW_ON_ERROR);
        }
        if ($this->sealed()) {
            throw new \RuntimeException('Unconverted protected field detected. Run the security health check.');
        }

        return $value;
    }

    public function normalize(string $field, ?string $value): ?string
    {
        if ($value === null || trim($value) === '') {
            return null;
        }
        // Ignore presentation separators, but never reduce an arbitrary string to an empty searchable value.
        $value = preg_replace('/[\s()+.-]/u', '', trim($value));
        if (! preg_match('/^\d+$/D', $value)) {
            return 'literal:'.mb_strtolower(trim($value));
        }
        if ($field !== 'cnic' && strlen($value) === 12 && str_starts_with($value, '92')) {
            $value = '0'.substr($value, 2);
        }

        return $value;
    }

    public function lookup(string $field, ?string $value): ?string
    {
        $normal = $this->normalize($field, $value);
        if ($normal === null) {
            return null;
        }
        $key = base64_decode((string) config('security.lookup_key'), true);
        if (! $key || strlen($key) !== 32) {
            throw new \RuntimeException('The separate security lookup key is missing or invalid.');
        }

        if ($this->installed()) {
            $fingerprint = DB::table('security_state')->where('key', 'lookup_fingerprint')->value('value');
            if ($fingerprint && ! hash_equals($fingerprint, hash('sha256', $key))) {
                throw new \RuntimeException('Lookup key changed. Restore the original key before using protected searches.');
            }
        }

        return hash_hmac('sha256', ($field === 'cnic' ? 'cnic' : 'phone').'|'.$normal, $key);
    }
}
