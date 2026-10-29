@extends('layouts.app')
@section('title', 'Business Categories')
@push('styles') @include('business.partials.style') @endpush
@section('content')
<div class="qard-page"><div class="business-hero"><div><h1>Business categories</h1><p class="qmuted">Maintain your own categories. Deactivation keeps existing case history.</p></div>@if(auth()->user()?->hasPermission('business.view'))<a class="qbtn secondary" href="{{ route('business.applications.index') }}">Back to businesses</a>@endif</div>
@include('qard.partials.feedback')
<form class="qcard" method="post" action="{{ route('business.categories.store') }}">@csrf<h2>Add category</h2><div class="qgrid"><x-business-field name="name" label="Category name" maxlength="100" /><div><label>Status<select name="active"><option value="1">Active</option><option value="0">Inactive</option></select></label></div></div><div class="qactions"><button class="qbtn">Add category</button></div></form>
@forelse($categories as $category)
<form class="qcard" method="post" action="{{ route('business.categories.update', $category) }}">@csrf @method('PUT')<div class="qgrid"><div><label>Name<input name="name" value="{{ $category->name }}" required maxlength="100"></label></div><div><label>Status<select name="active"><option value="1" @selected($category->active)>Active</option><option value="0" @selected(!$category->active)>Inactive</option></select></label></div></div><div class="qactions"><button class="qbtn secondary">Save category</button></div></form>
@empty<p class="qmuted">No categories yet. Add the first category before creating an application.</p>@endforelse
</div>@endsection
