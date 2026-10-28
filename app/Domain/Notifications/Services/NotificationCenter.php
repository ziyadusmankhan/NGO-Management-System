<?php

namespace App\Domain\Notifications\Services;

use App\Domain\Business\Models\BusinessReminder;
use App\Domain\Notifications\Models\NotificationState;
use App\Domain\Operations\Models\Task;
use App\Domain\Procurement\Models\ProcurementReminder;
use App\Domain\Procurement\Services\ProcurementReportService;
use App\Domain\Projects\Models\ProjectReminder;
use App\Domain\Qard\Models\QardReminder;
use App\Domain\Users\Models\User;
use App\Domain\Welfare\Assistance\Models\WelfareAssistanceReminder;
use Carbon\CarbonImmutable;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Validation\ValidationException;

class NotificationCenter
{
    public function items(User $user, bool $includeDismissed = false): Collection
    {
        if (! Schema::hasTable('user_notification_states')) {
            return collect();
        }

        $items = collect();
        $today = today('Asia/Karachi');

        if ($user->hasPermission('welfare.assistance.view')) {
            WelfareAssistanceReminder::with('assistance.beneficiary')->whereNull('resolved_at')->get()->each(function ($reminder) use ($items, $today) {
                $assistance = $reminder->assistance;
                if (! $assistance) {
                    return;
                }
                $items->push($this->item(
                    'welfare:'.$reminder->id,
                    'welfare',
                    str($reminder->kind)->replace('_', ' ')->headline().' — '.$assistance->assistance_number,
                    ($assistance->beneficiary?->full_name ?? 'Beneficiary').' requires welfare follow-up.',
                    $reminder->due_date,
                    route('welfare.assistance.show', $assistance),
                    $reminder->due_date?->lt($today) ? 'urgent' : 'due'
                ));
            });
        }

        if ($user->hasPermission('qard.view')) {
            QardReminder::with('loan.beneficiary')->whereNull('resolved_at')->get()->each(function ($reminder) use ($items) {
                $loan = $reminder->loan;
                if (! $loan) {
                    return;
                }
                $items->push($this->item(
                    'qard:'.$reminder->id.':'.sha1((string) $reminder->message),
                    'qard',
                    'Qard follow-up — '.$loan->number,
                    $reminder->message,
                    $reminder->updated_at,
                    route('qard.show', $loan),
                    str_contains(strtolower($reminder->message), 'overdue') ? 'urgent' : 'due'
                ));
            });
        }

        if ($user->hasPermission('business.view')) {
            BusinessReminder::with('application.beneficiary')->whereNull('resolved_at')->get()->each(function ($reminder) use ($items) {
                $application = $reminder->application;
                if (! $application) {
                    return;
                }
                $items->push($this->item(
                    'business:'.$reminder->id.':'.sha1((string) $reminder->message),
                    'business',
                    'Business follow-up — '.$application->number,
                    $reminder->message,
                    $application->next_review_date ?? $reminder->updated_at,
                    route('business.applications.show', $application),
                    str_contains(strtolower($reminder->message), 'overdue') ? 'urgent' : 'due'
                ));
            });
        }

        if ($user->hasPermission('projects.view')) {
            ProjectReminder::with('project')->whereNull('resolved_at')->where(function ($query) {
                $query->where('review_due', true)->orWhere('completion_overdue', true);
            })->get()->each(function ($reminder) use ($items) {
                $project = $reminder->project;
                if (! $project) {
                    return;
                }
                $parts = array_filter([$reminder->review_due ? 'Review is due.' : null, $reminder->completion_overdue ? 'Planned completion is overdue.' : null]);
                $items->push($this->item(
                    'projects:'.$reminder->id.':'.sha1(implode('|', $parts)),
                    'projects',
                    'Project attention — '.$project->number,
                    implode(' ', $parts),
                    $project->next_review_date ?? $reminder->updated_at,
                    route('projects.show', $project),
                    $reminder->completion_overdue ? 'urgent' : 'due'
                ));
            });
        }

        if ($user->hasPermission('procurement.view')) {
            $visible = app(ProcurementReportService::class)->visible($user)->pluck('id');
            ProcurementReminder::with('procurement')->whereIn('procurement_id', $visible)->where(function ($query) {
                $query->where('delivery_due', true)->orWhere('invoice_due', true)->orWhere('refund_due', true);
            })->get()->each(function ($reminder) use ($items) {
                $procurement = $reminder->procurement;
                if (! $procurement) {
                    return;
                }
                $parts = array_filter([
                    $reminder->delivery_due ? 'Delivery is overdue.' : null,
                    $reminder->invoice_due ? 'An invoice payment is overdue.' : null,
                    $reminder->refund_due ? 'A supplier refund is overdue.' : null,
                ]);
                $items->push($this->item(
                    'procurement:'.$reminder->id.':'.sha1(implode('|', $parts)),
                    'procurement',
                    'Procurement attention — '.$procurement->number,
                    implode(' ', $parts),
                    $reminder->updated_at,
                    route('operations.procurement.show', $procurement),
                    'urgent'
                ));
            });
        }

        if ($user->hasPermission('tasks.view')) {
            Task::whereNotIn('status', ['completed', 'cancelled'])->whereNotNull('due_on')
                ->whereDate('due_on', '<=', $today->copy()->addDays(7))
                ->when(! $user->hasRole('administrator'), fn ($query) => $query->where(fn ($query) => $query->where('assigned_user_id', $user->id)->orWhere('created_by', $user->id)))
                ->get()->each(function ($task) use ($items, $today) {
                    $items->push($this->item(
                        'tasks:'.$task->id.':'.$task->due_on->toDateString(),
                        'operations',
                        'Task due — '.$task->number,
                        $task->title,
                        $task->due_on,
                        route('operations.tasks.show', $task),
                        $task->due_on->lt($today) ? 'urgent' : 'upcoming'
                    ));
                });
        }

        $states = NotificationState::where('user_id', $user->id)->whereIn('notification_key', $items->pluck('key'))->get()->keyBy('notification_key');

        return $items->map(function (array $item) use ($states) {
            $state = $states->get($item['key']);
            $item['read_at'] = $state?->read_at;
            $item['dismissed_at'] = $state?->dismissed_at;

            return $item;
        })->when(! $includeDismissed, fn (Collection $items) => $items->whereNull('dismissed_at'))
            ->sortBy([['read_at', 'asc'], ['due_at', 'asc'], ['key', 'asc']])->values();
    }

    public function unreadCount(User $user): int
    {
        return $this->items($user)->whereNull('read_at')->count();
    }

    public function change(User $user, string $key, string $action): void
    {
        $current = $this->items($user, true)->firstWhere('key', $key);
        if (! $current) {
            throw ValidationException::withMessages(['notification' => 'That notification is no longer available to your account.']);
        }
        $state = NotificationState::firstOrNew(['user_id' => $user->id, 'notification_key' => $key]);
        if ($action === 'read') {
            $state->read_at = now();
        } elseif ($action === 'unread') {
            $state->read_at = null;
        } elseif ($action === 'dismiss') {
            $state->read_at = now();
            $state->dismissed_at = now();
        } elseif ($action === 'restore') {
            $state->dismissed_at = null;
        } else {
            throw ValidationException::withMessages(['action' => 'Unsupported notification action.']);
        }
        $state->save();
    }

    public function markAllRead(User $user): void
    {
        $now = now();
        $rows = $this->items($user)->whereNull('read_at')->map(fn ($item) => [
            'user_id' => $user->id,
            'notification_key' => $item['key'],
            'read_at' => $now,
            'dismissed_at' => null,
            'created_at' => $now,
            'updated_at' => $now,
        ])->all();
        if ($rows) {
            DB::table('user_notification_states')->upsert($rows, ['user_id', 'notification_key'], ['read_at', 'updated_at']);
        }
    }

    private function item(string $key, string $module, string $title, string $message, mixed $dueAt, string $url, string $severity): array
    {
        return [
            'key' => $key,
            'module' => $module,
            'title' => $title,
            'message' => $message,
            'due_at' => $dueAt ? CarbonImmutable::parse($dueAt)->timezone('Asia/Karachi') : null,
            'url' => $url,
            'severity' => $severity,
        ];
    }
}
