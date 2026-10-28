<?php

namespace App\Domain\Audit\Services;

use App\Domain\Audit\Models\AuditEvent;
use App\Domain\Users\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

class AuditRecorder
{
    public function available(): bool
    {
        return Schema::hasTable('audit_events');
    }

    public function record(string $module, string $action, array $data = []): ?AuditEvent
    {
        if (! $this->available()) {
            // Existing installations may run earlier migrations before Phase 10 is installed.
            // Once installed, missing storage must never silently disable required auditing.
            if (Schema::hasTable('migrations') && DB::table('migrations')->where('migration', '2026_10_26_000002_create_audit_events')->exists()) {
                throw new \RuntimeException('Required audit storage is unavailable.');
            }

            return null;
        }
        $s = app(AuditSanitizer::class);
        $historical = $data['historical'] ?? false;
        $actor = array_key_exists('actor_id', $data) ? ($data['actor_id'] ? User::withTrashed()->find($data['actor_id']) : null) : auth()->user();
        $key = $data['source_key'] ?? null;
        if ($key && ($existing = AuditEvent::where('source_key', $key)->first())) {
            return $existing;
        }
        $request = app()->bound('request') ? request() : null;
        $correlation = $historical ? null : $request?->attributes->get('audit_correlation');
        $context = $s->values($data['context'] ?? []);
        if (! $historical) {
            $context['origin'] = $request?->route()?->getName() ?? (app()->runningInConsole() ? 'console' : 'application');
            if ($request?->route()) {
                $context['peer_address'] = filter_var($request->server('REMOTE_ADDR'), FILTER_VALIDATE_IP) ?: null;
                $context['browser'] = $s->text($request->userAgent(), 200);
            }
        }
        $attributes = [
            'event_uuid' => (string) Str::uuid(), 'module' => $module, 'action' => $action, 'outcome' => $data['outcome'] ?? 'success',
            'actor_id' => $actor?->id ?? ($data['actor_id'] ?? null), 'actor_name' => $actor ? $s->text($actor->name, 255) : null, 'actor_kind' => $actor ? 'user' : ($data['actor_kind'] ?? ($historical ? 'unknown' : 'system')),
            'subject_type' => $data['subject_type'] ?? null, 'subject_id' => isset($data['subject_id']) ? (string) $data['subject_id'] : null, 'subject_reference' => $s->text($data['subject_reference'] ?? null, 150),
            'occurred_at' => $historical ? ($data['occurred_at'] ?? null) : ($data['occurred_at'] ?? now('UTC')), 'captured_at' => now('UTC'), 'historical' => $historical, 'source_key' => $key, 'correlation_id' => $correlation,
            'summary' => $s->text($data['summary'] ?? str($action)->replace('_', ' ')->headline()->toString(), 500),
            'reason' => $s->text($data['reason'] ?? null), 'before_values' => $s->values($data['before'] ?? []), 'after_values' => $s->values($data['after'] ?? []), 'context' => $context,
        ];

        if (! $key) {
            return AuditEvent::create($attributes);
        }

        // firstOrCreate() opens an additional savepoint when the business action is
        // already inside a transaction. SQLite may then require a temporary
        // sub-journal and fail the otherwise valid business write. The unique
        // source_key remains the authority for idempotency, while insert-or-ignore
        // avoids that redundant nested transaction and remains concurrency-safe.
        $event = new AuditEvent($attributes);
        AuditEvent::query()->insertOrIgnore($event->getAttributes());

        return AuditEvent::where('source_key', $key)->firstOrFail();
    }

    public function security(string $action, array $data = []): void
    {
        try {
            $this->record('security', $action, $data);
        } catch (\Throwable $e) {
            // Never include credentials, exception messages, SQL bindings or request payloads.
            try {
                Log::channel('single')->error('AUDIT_SECURITY_CAPTURE_FAILED', ['action' => $action, 'exception_type' => get_class($e)]);
            } catch (\Throwable) { /* Logout must still succeed if the log sink also fails. */
            }
            $path = storage_path('app/audit-health-failure.json');
            @file_put_contents($path, json_encode(['at' => now('UTC')->toIso8601String(), 'action' => $action]));
        }
    }
}
