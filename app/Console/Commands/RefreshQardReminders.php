<?php

namespace App\Console\Commands;

use App\Domain\Qard\Models\QardLoan;
use App\Domain\Qard\Services\QardMonitoringService;
use Illuminate\Console\Command;

class RefreshQardReminders extends Command
{
    protected $signature = 'qard:refresh-reminders';
    protected $description = 'Refresh Qard overdue and follow-up reminders without financial movements';

    public function handle(QardMonitoringService $monitoring): int
    {
        QardLoan::where('status', 'active')->orWhereHas('reminder', fn ($q) => $q->whereNull('resolved_at'))
            ->chunkById(100, function ($loans) use ($monitoring) {
                foreach ($loans as $loan) { $monitoring->refresh($loan); }
            });
        $this->info('Qard reminders refreshed. No payments or fees were created.');
        return self::SUCCESS;
    }
}
