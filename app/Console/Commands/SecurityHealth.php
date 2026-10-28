<?php

namespace App\Console\Commands;

use App\Domain\Documents\Models\DocumentVersion;
use App\Domain\Documents\Services\DocumentRegistry;
use App\Domain\Security\Services\FieldEncryption;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Storage;

class SecurityHealth extends Command
{
    protected $signature = 'security:health';

    protected $description = 'Check private document integrity, encryption, session policy and deployment security without revealing personal values';

    public function handle(FieldEncryption $cipher, DocumentRegistry $registry): int
    {
        $issues = [];
        try {
            $cipher->lookup('phone', '03001234567');
        } catch (\Throwable) {
            $issues[] = 'Separate lookup key missing or invalid.';
        }
        if (! $cipher->sealed()) {
            $issues[] = 'Sensitive-field conversion is not sealed.';
        }
        try {
            if ($this->callSilent('security:encrypt', ['--verify' => true]) !== 0) {
                $issues[] = 'Protected field verification failed.';
            }
        } catch (\Throwable) {
            $issues[] = 'Protected field decryption or verification failed.';
        }
        if (! config('session.encrypt')) {
            $issues[] = 'Session payload encryption disabled.';
        }
        if ((int) config('session.lifetime') !== 30) {
            $issues[] = 'Expected 30-minute session lifetime.';
        }
        foreach (['private', 'local'] as $disk) {
            if (config("filesystems.disks.$disk.serve")) {
                $issues[] = "Direct serving enabled for $disk.";
            }
        }
        if (! $registry->ready()) {
            $issues[] = 'Document registry unavailable.';
        } else {
            DocumentVersion::query()->chunkById(100, function ($rows) use (&$issues, $registry) {
                foreach ($rows as $v) {
                    if ($v->status === 'missing') {
                        $issues[] = 'Historical file unavailable: version #'.$v->id;

                        continue;
                    }
                    if (! $registry->safePath($v->disk, $v->path) || ! Storage::disk($v->disk)->exists($v->path) || ! hash_equals((string) $v->sha256, hash_file('sha256', Storage::disk($v->disk)->path($v->path)))) {
                        $issues[] = 'Document integrity failed: version #'.$v->id;
                    }
                }
            });
        }
        if (app()->isProduction()) {
            if (! str_starts_with(config('app.url'), 'https://')) {
                $issues[] = 'Production APP_URL must use HTTPS.';
            }
            if (! config('session.secure')) {
                $issues[] = 'Production requires secure session cookies.';
            }
            if (config('app.debug')) {
                $issues[] = 'Production debug mode must be disabled.';
            }
        }
        if (in_array('*', config('security.trusted_proxies', []))) {
            $issues[] = 'Wildcard proxy trust is forbidden.';
        }
        foreach ($issues as $issue) {
            $this->error($issue);
        }
        $this->line('Upload assurance: validation only; malware scanning and host/backup encryption remain deployment requirements.');
        if (! $issues) {
            $this->info('Phase 11 security checks passed.');
        }

        return $issues ? 1 : 0;
    }
}
