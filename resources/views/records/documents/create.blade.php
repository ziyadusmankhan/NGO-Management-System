@extends('layouts.app')
@section('title','Upload document')
@push('styles')@include('records.audit.style')@endpush
@section('content')<div class="audit-page">
<h1>Upload document</h1>@include('qard.partials.feedback')<form class="audit-card audit-grid" action="{{ route('records.documents.store') }}" method="post" enctype="multipart/form-data">@csrf
<label>Title<input name="title" value="{{ old('title') }}" required maxlength="200">
</label>
<label>Category<select name="category">@foreach(\App\Domain\Documents\Services\DocumentService::CATEGORIES as $c)<option @selected(old('category')===$c)>{{ $c }}</option>@endforeach</select>
</label>
<label>Source module<select name="parent_type">
<option value="">Standalone organization record</option>@foreach(array_keys(\App\Domain\Documents\Services\DocumentAccess::PARENTS) as $m)<option @selected(old('parent_type',request('parent_type'))===$m)>{{ $m }}</option>@endforeach</select>
</label>
<label>Source record ID (linked records only)<input type="number" name="parent_id" min="1" value="{{ old('parent_id',request('parent_id')) }}">
</label>
<label>File<input type="file" name="document" accept=".pdf,.jpg,.jpeg,.png,.webp,.docx,.xlsx,.pptx" required>
</label>
<p>Maximum 10 MB. Validation only; not malware-scanned. Linked uploads are supplementary: use the source workflow for signatures, approvals and payment evidence. Standalone files are initially private to you and administrators; share with selected roles from the detail page.</p>
<button class="audit-btn">Upload document</button>
</form>
</div>@endsection
