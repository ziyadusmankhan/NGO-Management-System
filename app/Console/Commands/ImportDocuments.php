<?php

namespace App\Console\Commands;

use App\Domain\Documents\Services\DocumentRegistry;
use Illuminate\Console\Command;

class ImportDocuments extends Command
{
    protected $signature = 'documents:import {--dry-run}';

    protected $description = 'Catalogue retained private evidence without changing source files';

    public function handle(DocumentRegistry $registry): int
    {
        if (! $registry->ready()) {
            $this->error('Install the document library migration first.');

            return self::FAILURE;
        }
        $this->line(json_encode($registry->import((bool) $this->option('dry-run')), JSON_PRETTY_PRINT));

        return self::SUCCESS;
    }
}
