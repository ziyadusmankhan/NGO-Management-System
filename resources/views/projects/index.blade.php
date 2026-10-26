@extends('layouts.app')
@section('title','Projects')
@push('styles')@include('projects.style')@endpush
@section('content')
<div class="qard-page project-page">
<div class="business-hero"><div><h1>Community Projects</h1><p class="qmuted">Proposals, approved budgets, direct payments and implementation.</p></div><div class="qactions">
@can('projects.manage')<a class="qbtn" href="{{ route('projects.create') }}">New project</a>@endcan
@can('projects.reports')<a class="qbtn secondary" href="{{ route('projects.reports') }}">Reports & exports</a>@endcan
@can('projects.categories')<a class="qbtn secondary" href="{{ route('projects.categories') }}">Categories</a>@endcan
</div></div>@include('qard.partials.feedback')
<form class="qcard" method="get"><div class="qgrid"><x-business-field name="search" label="Project name or number" :value="request('search')" :required="false"/>
<label>Lifecycle<select name="status"><option value="">All statuses</option>@foreach(['draft','approved','in_progress','completed','closed','cancelled'] as $s)<option value="{{ $s }}" @selected(request('status')===$s)>{{ str($s)->headline() }}</option>@endforeach</select></label>
<label>Attention<select name="reminders"><option value="">All projects</option><option value="1" @selected(request('reminders'))>Review due / completion overdue</option></select></label>
<label>Records<select name="archived"><option value="0">Current</option><option value="1" @selected(request('archived'))>Archived</option></select></label></div><div class="qactions"><button class="qbtn">Filter</button><a href="{{ route('projects.index') }}">Clear</a></div></form>
<div class="qcard qtable"><table><thead><tr><th>Project</th><th>Original fund</th><th>Lifecycle / revision</th><th>Approved budget</th><th>Progress</th><th>Attention</th></tr></thead><tbody>
@forelse($projects as $p)<tr><td><a href="{{ route('projects.show',$p) }}">{{ $p->number }} · {{ $p->name }}</a></td><td>{{ $p->fund?->name ?? 'Not assigned' }}</td><td>{{ str($p->status)->headline() }}<small>Revision {{ $p->revision }} · {{ str($p->proposal()->stage)->headline() }} {{ $p->payments_frozen?'· Payments paused':'' }}</small></td><td>PKR {{ \App\Domain\Finance\Support\Money::format($p->approved()?->budget ?? '0') }}</td><td>{{ $p->progress_percent }}%</td><td>{{ $p->reminder?->review_due?'Review due. ':'' }}{{ $p->reminder?->completion_overdue?'Completion overdue.':'' }}</td></tr>
@empty<tr><td colspan="6">No projects yet. Create a category, then add your first proposal.</td></tr>@endforelse
</tbody></table>{{ $projects->links('pagination::bootstrap-5') }}</div>
<p class="qmuted">Reminders refresh daily at 08:15 Asia/Karachi when the scheduler runs. Approval does not issue a payment.</p></div>
@endsection
