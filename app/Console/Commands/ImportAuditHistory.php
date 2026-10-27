<?php

namespace App\Console\Commands;

use App\Domain\Audit\Services\AuditHistoryImporter;
use Illuminate\Console\Command;

class ImportAuditHistory extends Command
{
    protected $signature = 'audit:import-history {--dry-run : Count candidates without writing any records}';

    protected $description = 'Import retained history idempotently without reconstructing unrecorded actions';

    public function handle(AuditHistoryImporter $importer): int
    {
        $this->line(json_encode($importer->run($this->option('dry-run')), JSON_PRETTY_PRINT));

        return self::SUCCESS;
    }
}
