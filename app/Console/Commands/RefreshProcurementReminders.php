<?php

namespace App\Console\Commands;

use App\Domain\Procurement\Models\Procurement;
use App\Domain\Procurement\Services\ProcurementReportService;
use Illuminate\Console\Command;

class RefreshProcurementReminders extends Command
{
    protected $signature = 'procurement:refresh-reminders';

    protected $description = 'Refresh overdue delivery, invoice and refund reminders without issuing payments';

    public function handle(ProcurementReportService $service): int
    {
        Procurement::withTrashed()->each(fn ($p) => $service->refresh($p));
        $this->info('Procurement reminders refreshed (Asia/Karachi).');

        return self::SUCCESS;
    }
}
