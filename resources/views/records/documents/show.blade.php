@extends('layouts.app')
@section('title',$document->number)
@push('styles')@include('records.audit.style')@endpush
@section('content')<div class="audit-page">
<a href="{{ route('records.documents.index') }}">Back to Documents</a>
<h1>{{ $document->title }}</h1>
<p>{{ $document->number }} · {{ $document->category }} · {{ $document->parent_reference??'Organization record' }} · {{ $document->archived_at?'Archived':'Active' }}</p>@include('qard.partials.feedback')
<div class="audit-card">
<h2>Retained versions</h2>
<p>Validation only / not malware-scanned. Original bytes are retained; unknown historical upload details are not reconstructed.</p>
<table>
<thead>
<tr>
<th>Version</th>
<th>File</th>
<th>Uploaded</th>
<th>Status</th>
<th>Download</th>
</tr>
</thead>
<tbody>@foreach($document->versions->sortByDesc('version') as $v)<tr>
<td>{{ $v->version }}</td>
<td>{{ $v->original_name??'Original filename unavailable' }}<br>{{ $v->mime_type??'Unknown type' }} · {{ $v->size===null?'Unknown size':number_format($v->size/1024,1).' KB' }}</td>
<td>{{ $v->uploaded_at?->timezone('Asia/Karachi')->format('d M Y H:i')??'Unavailable' }}<br>{{ $v->uploaded_by?'User #'.$v->uploaded_by:'Uploader unavailable' }}</td>
<td>{{ ucfirst($v->status) }}<br><small>{{ $v->validation_status }}</small><details><summary>SHA-256 checksum</summary><code style="overflow-wrap:anywhere">{{ $v->sha256??'Unavailable' }}</code></details></td>
<td>@if($v->status==='available'&&($document->parent_type||auth()->user()->hasPermission('documents.download')))<a href="{{ route('records.documents.download',[$document,$v]) }}">Download v{{ $v->version }}</a>@endif</td>
</tr>@endforeach</tbody>
</table>
</div>
@if(!$document->parent_type&&!$document->authoritative)
@can('documents.manage')<form class="audit-card audit-grid" method="post" action="{{ route('records.documents.change',[$document,'manage']) }}">@csrf<input type="hidden" name="revision" value="{{ $document->revision }}">
<label>Title<input name="title" value="{{ $document->title }}" required>
</label>
<label>Category<select name="category">@foreach(\App\Domain\Documents\Services\DocumentService::CATEGORIES as $c)<option @selected($document->category===$c)>{{ $c }}</option>@endforeach</select>
</label>
<button class="audit-btn">Save metadata</button>
</form>
@if(!$document->archived_at)<form class="audit-card" method="post" enctype="multipart/form-data" action="{{ route('records.documents.versions',$document) }}">@csrf<input type="hidden" name="revision" value="{{ $document->revision }}">
<input type="hidden" name="title" value="{{ $document->title }}">
<input type="hidden" name="category" value="{{ $document->category }}">
<label>New file version<input type="file" name="document" required>
</label>
<button class="audit-btn">Add version</button>
</form>@endif @endcan
@can('documents.share')<form class="audit-card" method="post" action="{{ route('records.documents.change',[$document,'share']) }}">@csrf<input type="hidden" name="revision" value="{{ $document->revision }}">
<h2>Allowed roles</h2>
<p>Requires recent password confirmation. <a href="{{ route('security.confirm') }}">Confirm your password</a> before changing access.</p>@foreach($roles as $role)<label>
<input type="checkbox" name="roles[]" value="{{ $role->id }}" @checked($document->roles->contains('id',$role->id))> {{ $role->name }}</label>
<br>@endforeach<button class="audit-btn">Save allowed roles</button>
</form>@endcan
@php($action=$document->archived_at?'restore':'archive')@can('documents.'.$action)<form method="post" action="{{ route('records.documents.change',[$document,$action]) }}">@csrf<input type="hidden" name="revision" value="{{ $document->revision }}">
<button class="audit-btn">{{ ucfirst($action) }} document</button>
</form>@endcan
@else<div class="audit-card">Linked records preserve their original evidence and source permissions. Use the original module for workflow changes.</div>@endif</div>@endsection
