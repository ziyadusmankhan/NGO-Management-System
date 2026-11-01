<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Console\Events\ScheduledTaskFailed;
use Illuminate\Console\Events\ScheduledTaskFinished;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Event;

class RunScheduler extends Command
{
    protected $signature = 'operations:scheduler-run';

    protected $description = 'Run due tasks and record a successful scheduler heartbeat';

    public function handle(): int
    {
        $failed = false;
        Event::listen(ScheduledTaskFailed::class, function ($event) use (&$failed) {
            $failed = true;
            $failures = Cache::get('operations.scheduler.tasks', []);
            $failures[hash('sha256', $event->task->getSummaryForDisplay())] = now('UTC')->timestamp;
            Cache::forever('operations.scheduler.tasks', $failures);
        });
        Event::listen(ScheduledTaskFinished::class, function ($event) {
            if ($event->task->exitCode != 0) {
                return;
            }
            $failures = Cache::get('operations.scheduler.tasks', []);
            unset($failures[hash('sha256', $event->task->getSummaryForDisplay())]);
            Cache::forever('operations.scheduler.tasks', $failures);
        });
        $result = $this->call('schedule:run');
        if ($failed || $result !== 0) {
            Cache::forever('operations.scheduler.failure', now('UTC')->timestamp);

            return self::FAILURE;
        }
        Cache::forever('operations.scheduler.success', now('UTC')->timestamp);

        return self::SUCCESS;
    }
}
