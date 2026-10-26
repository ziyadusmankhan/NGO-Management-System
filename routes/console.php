<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Schedule::command('projects:refresh-reminders')->dailyAt('08:15')->timezone(config('projects.timezone'))->withoutOverlapping(30);

Schedule::command('business:refresh-reminders')->dailyAt('08:10')
    ->timezone(config('business.timezone'))->withoutOverlapping(30);

Schedule::command('qard:refresh-reminders')->dailyAt('08:05')
    ->timezone('Asia/Karachi')->withoutOverlapping(30);

Schedule::command('procurement:refresh-reminders')->dailyAt('08:15')
    ->timezone('Asia/Karachi')->withoutOverlapping(30);

Schedule::command('welfare:refresh-reminders')->dailyAt('08:00')
    ->timezone(config('welfare.timezone'))->withoutOverlapping(30);

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');
