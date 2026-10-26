<?php

namespace App\Domain\Projects\Services;

use App\Domain\Projects\Models\Project;
use App\Domain\Projects\Models\ProjectReminder;
use App\Domain\Users\Models\User;
use Carbon\CarbonImmutable;
use Illuminate\Support\Facades\DB;

class ProjectMonitoringService
{
    public function record(Project $p, string $action, array $input, User $actor): void
    {
        $w = app(ProjectWorkflowService::class);
        $w->authorize($actor, 'monitor');
        $d = ProjectRules::validate($action, $input);
        $w->withFiles([$action => $d['evidence'] ?? null], function ($files) use ($p, $action, $d, $actor, $w) {
            DB::transaction(function () use ($p, $action, $d, $actor, $w, $files) {
                $p = $w->lock($p);
                $w->current($p, $d);
                if ($action === 'start') {
                    if ($p->status !== 'approved' || $p->payments_frozen) {
                        $w->fail('status', 'Start an approved project after resolving amendments.');
                    }
                    if ($d['date'] < $p->approved()->approved_at->timezone(config('projects.timezone'))->toDateString()) {
                        $w->fail('date', 'Implementation cannot predate approval.');
                    }
                    $p->update(['status' => 'in_progress', 'started_on' => $d['date'], 'next_review_date' => CarbonImmutable::parse($d['date'])->addMonthNoOverflow()->toDateString()]);
                    $w->documents($p, $actor, $files);
                } else {
                    if ($p->status !== 'in_progress' || $d['date'] < $p->started_on->toDateString()) {
                        $w->fail('status', 'Record progress after the project has started, with a date on or after its start.');
                    }
                    $last = $p->progress()->latest('report_date')->first();
                    if ($last && $d['date'] < $last->report_date->toDateString()) {
                        $w->fail('date', 'Use a date on or after the latest progress report.');
                    }
                    if ($action === 'complete' && ($p->payments_frozen || (int) $d['percent'] !== 100)) {
                        $w->fail('percent', 'Resolve amendments and report 100% completion before marking Completed.');
                    }
                    $next = $action === 'complete' ? null : ($d['next_review_date'] ?? CarbonImmutable::parse($d['date'])->addMonthNoOverflow()->toDateString());
                    $report = $p->progress()->create(['actor_id' => $actor->id, 'kind' => $action, 'report_date' => $d['date'], 'percent' => $d['percent'], 'findings' => $d['findings'], 'risks' => $d['risks'] ?? null, 'next_review_date' => $next]);
                    $p->update(['progress_percent' => $d['percent'], 'next_review_date' => $next, 'status' => $action === 'complete' ? 'completed' : 'in_progress', 'completed_on' => $action === 'complete' ? $d['date'] : null]);
                    $w->documents($p, $actor, $files, ['progress_id' => $report->id]);
                }
                $w->event($p, $actor, $action, [], array_diff_key($d, ['evidence' => true]));
                $this->refresh($p);
            }, 5);
        });
    }

    public function refresh(Project $p): void
    {
        $today = now(config('projects.timezone'))->toDateString();
        $active = ! $p->trashed() && in_array($p->status, ['approved', 'in_progress']);
        $review = $active && $p->next_review_date && $p->next_review_date->toDateString() <= $today;
        $overdue = $active && $p->approved()?->planned_completion->toDateString() < $today;
        ProjectReminder::updateOrCreate(['project_id' => $p->id], ['review_due' => (bool) $review, 'completion_overdue' => (bool) $overdue, 'resolved_at' => $review || $overdue ? null : now()]);
    }
}
