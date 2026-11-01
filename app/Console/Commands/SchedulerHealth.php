<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Cache;

class SchedulerHealth extends Command
{
    protected $signature = 'operations:scheduler-health';

    protected $description = 'Fail when no successful scheduler run occurred in the last three minutes';

    public function handle(): int
    {
        $last = Cache::get('operations.scheduler.success');
        $failure = Cache::get('operations.scheduler.failure');
        if (! $last || now('UTC')->timestamp - $last > 180 || ($failure && $failure >= $last)
            || Cache::get('operations.scheduler.tasks', [])) {
            $this->error('Scheduler is missing, stale, or its latest run failed. Check scheduler logs.');

            return self::FAILURE;
        }
        $this->info('Scheduler ran successfully within the last three minutes.');

        return self::SUCCESS;
    }
}
