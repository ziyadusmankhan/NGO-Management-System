<?php

namespace App\Console\Commands;

use App\Domain\Audit\Services\AuditRecorder;
use App\Domain\Security\Services\FieldEncryption;
use App\Domain\Security\Services\SensitiveFields;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class ConvertSensitiveFields extends Command
{
    protected $signature = 'security:encrypt {--dry-run : Read-only inventory and collision detection} {--verify : Read-only verification of ciphertext and exact indexes}';

    protected $description = 'Resumable conversion of designated personal fields, with per-row verification before replacing plaintext';

    public function handle(FieldEncryption $cipher): int
    {
        if (! $cipher->installed()) {
            $this->error('Apply the additive Phase 11 migrations first.');

            return 1;
        }
        $cipher->lookup('phone', '03001234567');
        $dry = $this->option('dry-run');
        $verify = $this->option('verify');
        $seen = [];
        $collisions = [];
        foreach (DB::table('members')->select('id', 'cnic')->orderBy('id')->cursor() as $row) {
            $value = $cipher->decode($row->cnic);
            $digest = $cipher->lookup('cnic', $value);
            if ($digest && isset($seen[$digest])) {
                $collisions[] = [$seen[$digest], $row->id];
            } elseif ($digest) {
                $seen[$digest] = $row->id;
            }
        }
        if ($collisions) {
            $this->error('CNIC normalization collisions. Resolve these member IDs before conversion: '.json_encode($collisions));

            return 1;
        }
        $counts = ['rows' => 0, 'plaintext_fields' => 0, 'verified_fields' => 0, 'lookup_mismatches' => 0];
        foreach (SensitiveFields::TABLES as $table => $fields) {
            DB::table($table)->orderBy('id')->chunkById(100, function ($rows) use ($table, $fields, $cipher, $dry, $verify, &$counts) {
                foreach ($rows as $row) {
                    $counts['rows']++;
                    $convert = function () use ($table, $row, $fields, $cipher, $dry, $verify, &$counts) {
                        $current = ($dry || $verify) ? $row : DB::table($table)->where('id', $row->id)->lockForUpdate()->first();
                        $updates = [];
                        foreach ($fields as $field) {
                            $raw = $current->{$field};
                            $encrypted = is_string($raw) && str_starts_with($raw, FieldEncryption::PREFIX);
                            $plain = $cipher->decode($raw);
                            if ($raw !== null && ! $encrypted) {
                                $counts['plaintext_fields']++;
                                $encoded = $cipher->encode($plain);
                                if ($cipher->decode($encoded) !== $plain) {
                                    throw new \RuntimeException('Encryption round-trip verification failed.');
                                }$updates[$field] = $encoded;
                            } elseif ($encrypted) {
                                $counts['verified_fields']++;
                            }
                            if (SensitiveFields::indexed($field)) {
                                $digest = $cipher->lookup($field, $plain);
                                if ($current->{$field.'_lookup'} !== $digest) {
                                    $counts['lookup_mismatches']++;
                                    $updates[$field.'_lookup'] = $digest;
                                }
                            }
                        }
                        if (! $dry && ! $verify && $updates) {
                            DB::table($table)->where('id', $row->id)->update($updates);
                        }
                    };
                    if ($dry || $verify) {
                        $convert();
                    } else {
                        DB::transaction($convert, 5);
                    }
                }
            });
        }
        if (! $dry && ! $verify) {
            DB::transaction(function () {
                DB::table('security_state')->updateOrInsert(['key' => 'lookup_fingerprint'], ['value' => hash('sha256', base64_decode(config('security.lookup_key'), true))]);
                DB::table('security_state')->where('key', 'encryption')->update(['value' => 'sealed']);
                app(AuditRecorder::class)->record('security', 'sensitive_field_conversion_completed', ['actor_kind' => 'system']);
            });
        }
        $this->line(json_encode($counts, JSON_PRETTY_PRINT));
        if ($verify && ($counts['plaintext_fields'] || $counts['lookup_mismatches'])) {
            return 1;
        }

        return 0;
    }
}
