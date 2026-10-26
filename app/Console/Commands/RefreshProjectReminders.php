<?php

namespace App\Console\Commands;

use App\Domain\Projects\Models\Project;
use App\Domain\Projects\Services\ProjectMonitoringService;
use Illuminate\Console\Command;

class RefreshProjectReminders extends Command
{
    protected $signature = 'projects:refresh-reminders';

    protected $description = 'Refresh project review and completion reminders without issuing payments';

    public function handle(ProjectMonitoringService $service): int
    {
        Project::withTrashed()->each(fn ($p) => $service->refresh($p));
        $this->info('Project reminders refreshed (Asia/Karachi).');

        return self::SUCCESS;
    }
}
