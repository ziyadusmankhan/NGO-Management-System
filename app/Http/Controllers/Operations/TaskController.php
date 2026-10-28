<?php

namespace App\Http\Controllers\Operations;

use App\Domain\Committees\Models\Committee;
use App\Domain\Operations\Models\Task;
use App\Domain\Operations\Models\TaskComment;
use App\Domain\Projects\Models\Project;
use App\Domain\Users\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

class TaskController
{
    private function data(Request $request): array
    {
        return $request->validate([
            'title' => ['required', 'string', 'max:180'],
            'description' => ['nullable', 'string', 'max:5000'],
            'category' => ['required', Rule::in(['community', 'welfare', 'finance', 'administration', 'follow_up', 'project', 'other'])],
            'priority' => ['required', Rule::in(['low', 'medium', 'high', 'urgent'])],
            'assigned_user_id' => ['nullable', 'integer', Rule::exists('users', 'id')->where(fn ($q) => $q->where('status', 'active')->whereNull('deleted_at'))],
            'committee_id' => ['nullable', 'integer', Rule::exists('committees', 'id')->where(fn ($q) => $q->where('status', 'active')->whereNull('deleted_at'))],
            'project_id' => ['nullable', 'integer', 'exists:projects,id'],
            'starts_on' => ['nullable', 'date_format:Y-m-d'],
            'due_on' => ['required', 'date_format:Y-m-d', ...($request->filled('starts_on') ? ['after_or_equal:starts_on'] : [])],
            'expected_outcome' => ['nullable', 'string', 'max:3000'],
            'revision' => ['nullable', 'integer', 'min:1'],
        ]);
    }

    private function options(): array
    {
        return [
            'users' => User::where('status', 'active')->orderBy('name')->get(),
            'committees' => Committee::where('status', 'active')->orderBy('name')->get(),
            'projects' => Project::whereNotIn('status', ['closed', 'cancelled'])->orderBy('number')->get(),
        ];
    }

    public function index(Request $request)
    {
        $filters = $request->validate([
            'search' => ['nullable', 'string', 'max:100'], 'status' => ['nullable', 'string', 'max:30'],
            'priority' => ['nullable', 'string', 'max:20'], 'assigned_user_id' => ['nullable', 'integer'],
            'overdue' => ['nullable', 'boolean'], 'archived' => ['nullable', 'boolean'],
        ]);
        $query = Task::query()->with(['assignee', 'committee', 'project', 'creator']);
        if (! empty($filters['archived'])) {
            $query->withTrashed();
        }
        if ($s = $filters['search'] ?? null) {
            $query->where(fn ($q) => $q->where('number', 'like', "%$s%")->orWhere('title', 'like', "%$s%"));
        }
        foreach (['status', 'priority', 'assigned_user_id'] as $key) {
            if (! empty($filters[$key])) {
                $query->where($key, $filters[$key]);
            }
        }
        if (! empty($filters['overdue'])) {
            $query->whereDate('due_on', '<', today('Asia/Karachi'))->whereNotIn('status', ['completed', 'cancelled']);
        }
        $counts = [
            'total' => Task::count(), 'open' => Task::whereNotIn('status', ['completed', 'cancelled'])->count(),
            'overdue' => Task::whereDate('due_on', '<', today('Asia/Karachi'))->whereNotIn('status', ['completed', 'cancelled'])->count(),
            'completed' => Task::where('status', 'completed')->count(),
        ];
        $tasks = $query->orderByRaw("CASE priority WHEN 'urgent' THEN 1 WHEN 'high' THEN 2 WHEN 'medium' THEN 3 ELSE 4 END")
            ->orderBy('due_on')->orderBy('id')->paginate(25)->withQueryString();

        return view('operations.tasks.index', compact('tasks', 'filters', 'counts') + $this->options());
    }

    public function create()
    {
        return view('operations.tasks.create', $this->options());
    }

    public function store(Request $request)
    {
        $data = $this->data($request);
        $task = DB::transaction(function () use ($data, $request) {
            $task = Task::create($data + ['created_by' => $request->user()->id]);
            $task->update(['number' => sprintf('TSK-%05d', $task->id)]);

            return $task;
        });

        return redirect()->route('operations.tasks.show', $task)->with('success', 'Task created and assigned.');
    }

    public function show(Task $task)
    {
        $task->load(['assignee', 'committee', 'project', 'creator', 'sourceMeeting', 'comments.user']);

        return view('operations.tasks.show', compact('task') + $this->options());
    }

    public function edit(Task $task)
    {
        abort_if($task->trashed(), 422);

        return view('operations.tasks.create', ['task' => $task] + $this->options());
    }

    public function update(Request $request, Task $task)
    {
        $data = $this->data($request);
        DB::transaction(function () use ($task, $data) {
            $locked = Task::lockForUpdate()->findOrFail($task->id);
            if ((int) ($data['revision'] ?? 0) !== $locked->revision) {
                throw ValidationException::withMessages(['revision' => 'This task changed. Reload and try again.']);
            }
            unset($data['revision']);
            $locked->fill($data);
            $locked->revision++;
            $locked->save();
        });

        return redirect()->route('operations.tasks.show', $task)->with('success', 'Task updated.');
    }

    public function status(Request $request, Task $task)
    {
        $data = $request->validate([
            'status' => ['required', Rule::in(['pending', 'in_progress', 'blocked', 'completed', 'cancelled'])],
            'progress_percent' => ['required', 'integer', 'between:0,100'],
            'completion_notes' => ['nullable', 'string', 'max:3000'], 'revision' => ['required', 'integer'],
        ]);
        DB::transaction(function () use ($task, $data) {
            $locked = Task::lockForUpdate()->findOrFail($task->id);
            if ((int) $data['revision'] !== $locked->revision) {
                throw ValidationException::withMessages(['revision' => 'This task changed. Reload and try again.']);
            }
            if ($data['status'] === 'completed' && (int) $data['progress_percent'] !== 100) {
                throw ValidationException::withMessages(['progress_percent' => 'A completed task must be 100%.']);
            }
            if ($data['status'] !== 'completed' && (int) $data['progress_percent'] === 100) {
                throw ValidationException::withMessages(['progress_percent' => 'Choose Completed when progress reaches 100%.']);
            }
            $locked->fill($data);
            $locked->completed_at = $data['status'] === 'completed' ? now() : null;
            $locked->revision++;
            $locked->save();
        });

        return back()->with('success', 'Task status recorded.');
    }

    public function comment(Request $request, Task $task)
    {
        abort_unless($request->user()->hasPermission('tasks.manage') || $task->assigned_user_id === $request->user()->id, 403);
        $data = $request->validate(['body' => ['required', 'string', 'max:3000']]);
        TaskComment::create(['task_id' => $task->id, 'user_id' => $request->user()->id, 'body' => $data['body']]);

        return back()->with('success', 'Update added.');
    }

    public function archive(Request $request, Task $task)
    {
        abort_unless(in_array($task->status, ['completed', 'cancelled'], true), 422, 'Only completed or cancelled tasks may be archived.');
        $task->delete();

        return redirect()->route('operations.tasks.index')->with('success', 'Task archived.');
    }

    public function restore(Request $request, int $task)
    {
        $record = Task::onlyTrashed()->findOrFail($task);
        $record->restore();

        return redirect()->route('operations.tasks.show', $record)->with('success', 'Task restored.');
    }
}
