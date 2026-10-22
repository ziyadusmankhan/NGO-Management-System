<?php

namespace App\Console\Commands;

use App\Domain\Welfare\Assistance\Models\WelfareAssistance;
use App\Domain\Welfare\Assistance\Services\WelfareMonitoringService;
use Illuminate\Console\Command;

class RefreshWelfareReminders extends Command
{
    protected $signature = 'welfare:refresh-reminders';
    protected $description = 'Refresh in-app welfare review, expiry and closure reminders without issuing payments or closing assistance';

    public function handle(WelfareMonitoringService $monitoring): int
    {
        $count = 0;
        WelfareAssistance::withTrashed()->where(fn ($query) => $query->where('status', 'approved')
            ->orWhereHas('reminders', fn ($query) => $query->whereNull('resolved_at')))
            ->chunkById(100, function ($assistances) use ($monitoring, &$count) {
                foreach ($assistances as $assistance) {
                    $monitoring->refreshReminders($assistance);
                    $count++;
                }
            });
        $this->info("Refreshed monitoring reminders for {$count} assistance records. No payments or status changes were made.");

        return self::SUCCESS;
    }
}
