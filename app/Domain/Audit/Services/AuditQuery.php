<?php

namespace App\Domain\Audit\Services;

use App\Domain\Audit\Models\AuditEvent;
use App\Domain\Business\Models\BusinessApplication;
use App\Domain\Committees\Models\Committee;
use App\Domain\Finance\Donations\Models\Donation;
use App\Domain\Finance\Expenses\Models\Expense;
use App\Domain\Finance\Funds\Models\Fund;
use App\Domain\Members\Models\Member;
use App\Domain\Operations\Models\Meeting;
use App\Domain\Operations\Models\Task;
use App\Domain\Procurement\Models\Procurement;
use App\Domain\Procurement\Services\ProcurementReportService;
use App\Domain\Projects\Models\Project;
use App\Domain\Qard\Models\QardLoan;
use App\Domain\Reports\Models\ReportRun;
use App\Domain\Users\Models\User;
use App\Domain\Welfare\Applications\Models\WelfareApplication;
use App\Domain\Welfare\Assistance\Models\WelfareAssistance;
use App\Domain\Welfare\Beneficiaries\Models\Beneficiary;
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
            Beneficiary::class => ['welfare.view', 'welfare.beneficiaries.show'], WelfareApplication::class => ['welfare.assistance.view', 'welfare.applications.show'], WelfareAssistance::class => ['welfare.assistance.view', 'welfare.assistance.show'],
            QardLoan::class => ['qard.view', 'qard.show'],
            Task::class => ['tasks.view', 'operations.tasks.show'], Meeting::class => ['meetings.view', 'operations.meetings.show'], ReportRun::class => ['reports.view', 'records.reports.show'],
        ];
        $m = $map[$e->subject_type] ?? null;
        if (! $m || ! $this->canSeeSubject($e, $user, $m[0]) || ! Route::has($m[1])) {
            return null;
        }

        return route($m[1], $e->subject_id);
    }

    public function visibleTo(AuditEvent $event, User $user): bool
    {
        if ($user->hasPermission('audit.view')) {
            return true;
        }
        if ($event->module === 'security') {
            return (int) $event->actor_id === $user->id;
        }
        $subjectPermissions = [
            User::class => 'users.view', Member::class => 'members.view', Committee::class => 'committees.view',
            Fund::class => 'finance.view', Donation::class => 'finance.view', Expense::class => 'finance.view',
            Beneficiary::class => 'welfare.view', WelfareApplication::class => 'welfare.assistance.view', WelfareAssistance::class => 'welfare.assistance.view',
            QardLoan::class => 'qard.view', BusinessApplication::class => 'business.view', Project::class => 'projects.view', Procurement::class => 'procurement.view',
            Task::class => 'tasks.view', Meeting::class => 'meetings.view', ReportRun::class => 'reports.view',
        ];
        if (isset($subjectPermissions[$event->subject_type])) {
            return $this->canSeeSubject($event, $user, $subjectPermissions[$event->subject_type]);
        }
        $permissions = [
            'accounts' => 'users.view', 'organization' => 'members.view', 'finance' => 'finance.view',
            'welfare' => 'welfare.assistance.view', 'qard' => 'qard.view', 'business' => 'business.view',
            'projects' => 'projects.view', 'procurement' => 'procurement.view', 'documents' => 'documents.view',
            'operations' => 'tasks.view', 'reports' => 'reports.view', 'audit' => 'audit.view',
        ];

        return isset($permissions[$event->module]) && $user->hasPermission($permissions[$event->module]);
    }

    private function canSeeSubject(AuditEvent $event, User $user, string $permission): bool
    {
        if (! $user->hasPermission($permission)) {
            return false;
        }
        if ($event->subject_type !== Procurement::class) {
            return true;
        }

        return app(ProcurementReportService::class)->visible($user)
            ->whereKey($event->subject_id)->exists();
    }
}
