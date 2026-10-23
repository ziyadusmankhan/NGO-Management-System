<?php

namespace App\Console\Commands;

use App\Domain\Business\Models\BusinessApplication;
use App\Domain\Business\Services\BusinessMonitoringService;
use Illuminate\Console\Command;

class RefreshBusinessReminders extends Command
{
    protected $signature = 'business:refresh-reminders';

    protected $description = 'Refresh due business reviews and overdue principal reminders without issuing payments';

    public function handle(BusinessMonitoringService $service): int
    {
        BusinessApplication::query()->eachById(fn ($application) => $service->refresh($application));
        $this->info('Business reminders refreshed. No payments or automatic closures were created.');

        return self::SUCCESS;
    }
}
