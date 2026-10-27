<?php

namespace App\Domain\Audit\Services;

use App\Domain\Audit\Models\AuditEvent;
use App\Domain\Business\Models\BusinessApplication;
use App\Domain\Committees\Models\Committee;
use App\Domain\Finance\Donations\Models\Donation;
use App\Domain\Finance\Expenses\Models\Expense;
use App\Domain\Finance\Funds\Models\Fund;
use App\Domain\Members\Models\Member;
use App\Domain\Procurement\Models\Procurement;
use App\Domain\Projects\Models\Project;
use App\Domain\Users\Models\User;
use Carbon\CarbonImmutable;
use Illuminate\Support\Facades\Route;

class AuditQuery
{
    public function query(array $f, ?int $snapshot = null)
    {
        $q = AuditEvent::query()->when($snapshot !== null, fn ($q) => $q->where('id', '<=', $snapshot));
        foreach (['module', 'action', 'outcome', 'actor_id'] as $key) {
            if (isset($f[$key]) && $f[$key] !== '') {
                $q->where($key, $f[$key]);
            }
        }
        if (isset($f['historical']) && $f['historical'] !== '') {
            $q->where('historical', (bool) $f['historical']);
        }
        if (! empty($f['reference'])) {
            $q->whereRaw("subject_reference LIKE ? ESCAPE '!'", ['%'.str_replace(['!', '%', '_'], ['!!', '!%', '!_'], $f['reference']).'%']);
        }
        if (! empty($f['from'])) {
            $q->where('occurred_at', '>=', CarbonImmutable::parse($f['from'], 'Asia/Karachi')->startOfDay()->utc());
        }
        if (! empty($f['to'])) {
            $q->where('occurred_at', '<', CarbonImmutable::parse($f['to'], 'Asia/Karachi')->addDay()->startOfDay()->utc());
        }

        return $q;
    }

    public function ordered($q)
    {
        return $q->orderByRaw('CASE WHEN occurred_at IS NULL THEN 1 ELSE 0 END')->orderByDesc('occurred_at')->orderByDesc('id');
    }

    public function columns(): array
    {
        return ['event_uuid' => 'Event', 'time' => 'Time (Asia/Karachi)', 'actor' => 'Actor', 'module' => 'Module', 'action' => 'Action', 'outcome' => 'Outcome', 'reference' => 'Record', 'summary' => 'Summary', 'reason' => 'Recorded reason', 'historical' => 'Historical', 'before' => 'Before', 'after' => 'After', 'context' => 'Context'];
    }

    public function row(AuditEvent $e): array
    {
        return ['event_uuid' => $e->event_uuid, 'time' => $e->occurred_at?->timezone('Asia/Karachi')->format('Y-m-d H:i:s') ?? 'Unavailable', 'actor' => $e->actor_name ?? ucfirst($e->actor_kind), 'module' => $e->module, 'action' => str($e->action)->headline()->toString(), 'outcome' => $e->outcome, 'reference' => $e->subject_reference ?? 'Unavailable', 'summary' => $e->summary, 'reason' => $e->reason ?? 'Unavailable', 'historical' => $e->historical ? 'Yes' : 'No', 'before' => json_encode($e->before_values, JSON_UNESCAPED_UNICODE), 'after' => json_encode($e->after_values, JSON_UNESCAPED_UNICODE), 'context' => json_encode($e->context, JSON_UNESCAPED_UNICODE)];
    }

    public function sourceLink(AuditEvent $e, $user): ?string
    {
        $map = [
            User::class => ['users.view', 'users.show'], Member::class => ['members.view', 'members.show'], Committee::class => ['committees.view', 'committees.show'],
            Fund::class => ['finance.view', 'finance.funds.show'], Donation::class => ['finance.view', 'finance.donations.show'], Expense::class => ['finance.view', 'finance.expenses.show'],
            Project::class => ['projects.view', 'projects.show'], BusinessApplication::class => ['business.view', 'business.applications.show'], Procurement::class => ['procurement.view', 'operations.procurement.show'],
        ];
        $m = $map[$e->subject_type] ?? null;
        if (! $m || ! $user->hasPermission($m[0]) || ! Route::has($m[1])) {
            return null;
        }

        return route($m[1],$e->subject_id);
    }
}
