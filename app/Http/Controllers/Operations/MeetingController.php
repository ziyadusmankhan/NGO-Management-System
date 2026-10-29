<?php

namespace App\Http\Controllers\Operations;

use App\Domain\Committees\Models\Committee;
use App\Domain\Operations\Models\Meeting;
use App\Domain\Operations\Models\Task;
use App\Domain\Projects\Models\Project;
use App\Domain\Users\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

class MeetingController
{
    private function options(): array
    {
        return ['users' => User::where('status', 'active')->orderBy('name')->get(), 'committees' => Committee::where('status', 'active')->orderBy('name')->get(), 'projects' => Project::whereNotIn('status', ['closed', 'cancelled'])->orderBy('number')->get()];
    }

    private function data(Request $request): array
    {
        return $request->validate([
            'title' => ['required', 'string', 'max:180'], 'type' => ['required', Rule::in(['committee', 'general', 'special', 'project'])],
            'committee_id' => ['nullable', 'integer', 'exists:committees,id'], 'chair_user_id' => ['nullable', 'integer', 'exists:users,id'],
            'meeting_date' => ['required', 'date_format:Y-m-d'], 'starts_at' => ['nullable', 'date_format:H:i'], 'ends_at' => ['nullable', 'date_format:H:i', ...($request->filled('starts_at') ? ['after:starts_at'] : [])],
            'location' => ['nullable', 'string', 'max:255'], 'agenda' => ['required', 'string', 'max:10000'],
            'quorum_required' => ['required', 'integer', 'min:1', 'max:1000'], 'attendees' => ['required', 'array', 'min:1'],
            'attendees.*' => ['integer', 'distinct', Rule::exists('users', 'id')->where(fn ($q) => $q->where('status', 'active')->whereNull('deleted_at'))],
            'revision' => ['nullable', 'integer', 'min:1'],
        ]);
    }

    public function index(Request $request)
    {
        $filters = $request->validate(['search' => ['nullable', 'string', 'max:100'], 'status' => ['nullable', 'string', 'max:30'], 'type' => ['nullable', 'string', 'max:30'], 'committee_id' => ['nullable', 'integer'], 'archived' => ['nullable', 'boolean']]);
        $query = Meeting::with(['chair', 'committee'])->withCount(['attendees', 'tasks']);
        if (! empty($filters['archived'])) {
            $query->withTrashed();
        }
        if ($s = $filters['search'] ?? null) {
            $query->where(fn ($q) => $q->where('number', 'like', "%$s%")->orWhere('title', 'like', "%$s%"));
        }
        foreach (['status', 'type', 'committee_id'] as $key) {
            if (! empty($filters[$key])) {
                $query->where($key, $filters[$key]);
            }
        }
        $counts = ['total' => Meeting::count(), 'upcoming' => Meeting::where('status', 'scheduled')->whereDate('meeting_date', '>=', today('Asia/Karachi'))->count(), 'held' => Meeting::where('status', 'held')->count(), 'minutes_pending' => Meeting::where('status', 'scheduled')->whereDate('meeting_date', '<', today('Asia/Karachi'))->count()];
        $meetings = $query->orderByDesc('meeting_date')->orderByDesc('id')->paginate(25)->withQueryString();

        return view('operations.meetings.index', compact('meetings', 'filters', 'counts') + $this->options());
    }

    public function create()
    {
        return view('operations.meetings.create', $this->options());
    }

    public function store(Request $request)
    {
        $data = $this->data($request);
        $attendees = $data['attendees'];
        unset($data['attendees'], $data['revision']);
        $meeting = DB::transaction(function () use ($data, $attendees, $request) {
            $meeting = Meeting::create($data + ['created_by' => $request->user()->id]);
            $meeting->update(['number' => sprintf('MTG-%05d', $meeting->id)]);
            $meeting->attendees()->sync($attendees);

            return $meeting;
        });

        return redirect()->route('operations.meetings.show', $meeting)->with('success', 'Meeting scheduled.');
    }

    public function show(Meeting $meeting)
    {
        $meeting->load(['creator', 'chair', 'committee', 'attendees', 'tasks.assignee']);

        return view('operations.meetings.show', compact('meeting') + $this->options());
    }

    public function edit(Meeting $meeting)
    {
        abort_unless($meeting->status === 'scheduled' && ! $meeting->trashed(), 422);
        $meeting->load('attendees');

        return view('operations.meetings.create', ['meeting' => $meeting] + $this->options());
    }

    public function update(Request $request, Meeting $meeting)
    {
        $data = $this->data($request);
        $attendees = $data['attendees'];
        DB::transaction(function () use ($meeting, $data, $attendees) {
            $locked = Meeting::lockForUpdate()->findOrFail($meeting->id);
            abort_unless($locked->status === 'scheduled', 422);
            if ((int) ($data['revision'] ?? 0) !== $locked->revision) {
                throw ValidationException::withMessages(['revision' => 'This meeting changed. Reload and try again.']);
            }
            unset($data['revision'], $data['attendees']);
            $locked->fill($data);
            $locked->revision++;
            $locked->save();
            $locked->attendees()->sync($attendees);
        });

        return redirect()->route('operations.meetings.show', $meeting)->with('success', 'Meeting updated.');
    }

    public function minutes(Request $request, Meeting $meeting)
    {
        $request->merge(['action_items' => collect($request->input('action_items', []))
            ->filter(fn ($item) => trim((string) ($item['title'] ?? '')) !== '')->values()->all()]);
        $data = $request->validate([
            'minutes' => ['required', 'string', 'max:20000'], 'decisions' => ['nullable', 'string', 'max:10000'],
            'attended' => ['required', 'array', 'min:1'], 'attended.*' => ['integer', 'distinct', 'exists:users,id'],
            'revision' => ['required', 'integer'], 'action_items' => ['nullable', 'array', 'max:25'],
            'action_items.*.title' => ['required_with:action_items', 'string', 'max:180'],
            'action_items.*.assigned_user_id' => ['required_with:action_items', 'integer', 'exists:users,id'],
            'action_items.*.due_on' => ['required_with:action_items', 'date_format:Y-m-d'],
            'action_items.*.priority' => ['required_with:action_items', Rule::in(['low', 'medium', 'high', 'urgent'])],
            'action_items.*.project_id' => ['nullable', 'integer', 'exists:projects,id'],
        ]);
        DB::transaction(function () use ($request, $meeting, $data) {
            $locked = Meeting::lockForUpdate()->findOrFail($meeting->id);
            abort_unless($locked->status === 'scheduled', 422, 'Minutes have already been finalized or the meeting was cancelled.');
            if ((int) $data['revision'] !== $locked->revision) {
                throw ValidationException::withMessages(['revision' => 'This meeting changed. Reload and try again.']);
            }
            $invited = $locked->attendees()->pluck('users.id')->all();
            if (array_diff($data['attended'], $invited)) {
                throw ValidationException::withMessages(['attended' => 'Attendance may only include invited users.']);
            }
            if (count($data['attended']) < $locked->quorum_required) {
                throw ValidationException::withMessages(['attended' => 'Attendance does not meet the recorded quorum.']);
            }
            DB::table('operation_meeting_attendees')->where('meeting_id', $locked->id)->update(['attended' => false]);
            DB::table('operation_meeting_attendees')->where('meeting_id', $locked->id)->whereIn('user_id', $data['attended'])->update(['attended' => true, 'response' => 'attended']);
            $locked->update(['minutes' => $data['minutes'], 'decisions' => $data['decisions'] ?? null, 'status' => 'held', 'minutes_recorded_at' => now(), 'revision' => $locked->revision + 1]);
            foreach ($data['action_items'] ?? [] as $item) {
                $task = Task::create($item + ['category' => 'follow_up', 'status' => 'pending', 'progress_percent' => 0, 'created_by' => $request->user()->id, 'source_meeting_id' => $locked->id, 'description' => 'Action item from '.$locked->number.' — '.$locked->title]);
                $task->update(['number' => sprintf('TSK-%05d', $task->id)]);
            }
        });

        return back()->with('success', 'Minutes, attendance and action items recorded.');
    }

    public function cancel(Request $request, Meeting $meeting)
    {
        $data = $request->validate(['reason' => ['required', 'string', 'max:2000'], 'revision' => ['required', 'integer']]);
        DB::transaction(function () use ($meeting, $data) {
            $locked = Meeting::lockForUpdate()->findOrFail($meeting->id);
            abort_unless($locked->status === 'scheduled', 422);
            if ((int) $data['revision'] !== $locked->revision) {
                throw ValidationException::withMessages(['revision' => 'This meeting changed. Reload and try again.']);
            }
            $locked->status = 'cancelled';
            $locked->decisions = 'Cancellation reason: '.$data['reason'];
            $locked->revision++;
            $locked->save();
        });

        return back()->with('success', 'Meeting cancelled with its reason retained.');
    }

    public function archive(Meeting $meeting)
    {
        abort_unless(in_array($meeting->status, ['held', 'cancelled'], true), 422);
        $meeting->delete();

        return redirect()->route('operations.meetings.index')->with('success', 'Meeting archived.');
    }

    public function restore(int $meeting)
    {
        $record = Meeting::onlyTrashed()->findOrFail($meeting);
        $record->restore();

        return redirect()->route('operations.meetings.show', $record)->with('success', 'Meeting restored.');
    }
}
