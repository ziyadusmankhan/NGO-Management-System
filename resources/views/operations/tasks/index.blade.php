@extends('layouts.app')
@section('title','Tasks')
@push('styles')@include('records.audit.style')@endpush
@section('content')
<div class="audit-page">
<div style="display:flex;justify-content:space-between;gap:16px;align-items:center"><div><h1>Tasks</h1><p>Assignments, deadlines and meeting follow-up work.</p></div>@can('tasks.manage')<a class="audit-btn" href="{{ route('operations.tasks.create') }}">Create task</a>@endcan</div>
@include('qard.partials.feedback')
<div class="audit-grid">@foreach(['total'=>'Total tasks','open'=>'Open','overdue'=>'Overdue','completed'=>'Completed'] as $key=>$label)<div class="audit-card"><small>{{ $label }}</small><h2>{{ $counts[$key] }}</h2></div>@endforeach</div>
<form class="audit-card audit-grid" method="get">
<label>Search<input name="search" value="{{ $filters['search'] ?? '' }}" placeholder="Number or title"></label>
<label>Status<select name="status"><option value="">All statuses</option>@foreach(['pending','in_progress','blocked','completed','cancelled'] as $v)<option value="{{ $v }}" @selected(($filters['status']??'')===$v)>{{ str($v)->replace('_',' ')->headline() }}</option>@endforeach</select></label>
<label>Priority<select name="priority"><option value="">All priorities</option>@foreach(['urgent','high','medium','low'] as $v)<option @selected(($filters['priority']??'')===$v)>{{ $v }}</option>@endforeach</select></label>
<label>Assigned user<select name="assigned_user_id"><option value="">Anyone</option>@foreach($users as $user)<option value="{{ $user->id }}" @selected(($filters['assigned_user_id']??null)==$user->id)>{{ $user->name }}</option>@endforeach</select></label>
<label><input type="checkbox" name="overdue" value="1" @checked($filters['overdue']??false)> Overdue only</label><label><input type="checkbox" name="archived" value="1" @checked($filters['archived']??false)> Include archived</label><button class="audit-btn">Apply filters</button>
</form>
<div class="audit-card audit-table"><table><thead><tr><th>Task</th><th>Owner</th><th>Priority</th><th>Status</th><th>Due</th><th>Progress</th></tr></thead><tbody>
@forelse($tasks as $task)<tr><td>@if($task->trashed())<strong>{{ $task->number }}</strong>@else<a href="{{ route('operations.tasks.show',$task) }}"><strong>{{ $task->number }}</strong><br>{{ $task->title }}</a>@endif @if($task->trashed())<span class="audit-tag">Archived</span>@endif<small>{{ str($task->category)->replace('_',' ')->headline() }}@if($task->project) · {{ $task->project->number }}@endif</small>@if($task->trashed() && auth()->user()->hasPermission('tasks.manage'))<form method="post" action="{{ route('operations.tasks.restore',$task->id) }}">@csrf<button class="audit-btn">Restore</button></form>@endif</td><td>{{ $task->assignee?->name ?? 'Unassigned' }}<small>{{ $task->committee?->name }}</small></td><td>{{ str($task->priority)->headline() }}</td><td><span class="audit-tag">{{ str($task->status)->replace('_',' ')->headline() }}</span></td><td class="{{ $task->overdue ? 'audit-error':'' }}">{{ $task->due_on?->format('d M Y') }}{{ $task->overdue ? ' · overdue':'' }}</td><td>{{ $task->progress_percent }}%</td></tr>@empty<tr><td colspan="6">No tasks match these filters.</td></tr>@endforelse
</tbody></table></div>{{ $tasks->links() }}
</div>
@endsection
