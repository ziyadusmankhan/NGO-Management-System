<?php

namespace App\Providers;

use App\Domain\Audit\Services\AuditRecorder;
use Illuminate\Auth\Events\Failed;
use Illuminate\Auth\Events\Login;
use Illuminate\Auth\Events\Logout;
use Illuminate\Console\Events\CommandFinished;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\ServiceProvider;

class AuditServiceProvider extends ServiceProvider
{
    public function boot(): void
    {
        Event::listen(Login::class, fn ($e) => app(AuditRecorder::class)->security('login', ['actor_id' => $e->user->id]));
        Event::listen(Logout::class, fn ($e) => app(AuditRecorder::class)->security('logout', ['actor_id' => $e->user?->id, 'actor_kind' => 'anonymous']));
        Event::listen(Failed::class, function ($e) {
            $identifier = (string) ($e->credentials['email'] ?? '');
            app(AuditRecorder::class)->security('login_failed', ['actor_id' => null, 'actor_kind' => 'anonymous', 'outcome' => 'blocked', 'summary' => 'Failed login for '.($identifier !== '' ? mb_substr($identifier, 0, 1).'***' : 'unknown account')]);
        });
        Event::listen(CommandFinished::class, function ($e) {
            if (! in_array($e->command, ['finance:sync-transactions'])) {
                return;
            }
            app(AuditRecorder::class)->security('maintenance_finished', ['actor_id' => null, 'actor_kind' => 'system', 'outcome' => $e->exitCode === 0 ? 'success' : 'blocked', 'summary' => $e->command.($e->exitCode === 0 ? ' completed' : ' failed')]);
        });
    }
}
