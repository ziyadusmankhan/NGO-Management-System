<?php

namespace App\Console\Commands;

use App\Domain\Audit\Models\AuditEvent;
use App\Domain\Audit\Services\AuditRecorder;
use Illuminate\Console\Command;

class AuditHealth extends Command
{
    protected $signature = 'audit:health';

    protected $description = 'Read-only audit storage and security-capture failure check';

    public function handle(AuditRecorder $recorder): int
    {
        if (! $recorder->available()) {
            $this->error('Audit storage is not installed.');

            return self::FAILURE;
        }
        $this->info('Audit storage readable: '.AuditEvent::count().' events.');
        if (is_file(storage_path('app/audit-health-failure.json'))) {
            $this->error('A security capture failure was recorded. Inspect the private operational log and failure marker before acknowledging it operationally.');

            return self::FAILURE;
        }
        $this->info('No security capture failure marker. No events were modified.');

        return self::SUCCESS;
    }
}
