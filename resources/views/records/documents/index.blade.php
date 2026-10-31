@extends('layouts.app')
@section('title','Documents')
@push('styles')@include('records.audit.style')@endpush
@section('content')
<div class="no-print" style="margin-bottom:16px">@include('shared.section-export',['exportKind'=>'documents'])</div>
<div class="audit-page">
<h1>Documents Library</h1>
<p>Private evidence and organization records you are authorized to access.</p>@include('qard.partials.feedback')
@can('documents.upload')@if(auth()->user()?->hasPermission('documents.view') && auth()->user()?->hasPermission('documents.upload'))<a class="audit-btn" href="{{ route('records.documents.create') }}">Upload document</a>@endif
@endcan
<form class="audit-card audit-grid" method="get">
<label>Title<input name="search" value="{{ request('search') }}">
</label>
<label>Source record<input name="reference" value="{{ request('reference') }}">
</label>
<label>Module<select name="module">
<option value="">All accessible modules</option>@foreach(['organization','welfare','qard','business','projects','procurement'] as $m)<option @selected(request('module')===$m)>{{ $m }}</option>@endforeach</select>
</label>
<label>Category<select name="category">
<option value="">All</option>@foreach(\App\Domain\Documents\Services\DocumentService::CATEGORIES as $c)<option @selected(request('category')===$c)>{{ $c }}</option>@endforeach</select>
</label>
<label>Uploader ID<input type="number" name="uploader" value="{{ request('uploader') }}">
</label>
<label>From upload date<input type="date" name="from" value="{{ request('from') }}">
</label>
<label>Through upload date<input type="date" name="to" value="{{ request('to') }}">
</label>
<label>
<input type="checkbox" name="archived" value="1" @checked(request('archived'))> Include archived</label>
<button class="audit-btn">Apply filters</button>
</form>
<div class="audit-card">
<table>
<thead>
<tr>
<th>Document</th>
<th>Category / source</th>
<th>File versions</th>
<th>Status</th>
</tr>
</thead>
<tbody>@forelse($documents as $d)<tr>
<td>
@if(auth()->user()?->hasPermission('documents.view'))<a href="{{ route('records.documents.show',$d) }}">{{ $d->number }}</a>@endif
<br>{{ $d->title }}</td>
<td>{{ $d->category }}<br>{{ $d->parent_reference ?? 'Organization record' }}</td>
<td>{{ $d->versions->count() }}</td>
<td>{{ $d->archived_at?'Archived':'Active' }}@if($d->versions->contains('status','missing')) · Missing file @endif</td>
</tr>@empty<tr>
<td colspan="4">No accessible documents match these filters.</td>
</tr>@endforelse</tbody>
</table>{{ $documents->links() }}</div>
</div>@endsection
