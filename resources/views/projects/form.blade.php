@extends('layouts.app')
@section('title',$project?'Edit project revision':'New project')
@push('styles')@include('projects.style')@endpush
@section('content')
<div class="qard-page project-page"><div class="business-hero"><div><h1>{{ $project?'Edit '.$project->number.' · revision '.$project->revision:'New project proposal' }}</h1><p class="qmuted">Save a draft, check the budget, then submit for independent review.</p></div><a class="qbtn secondary" href="{{ $project?route('projects.show',$project):route('projects.index') }}">Back</a></div>
@include('qard.partials.feedback')
<form class="qcard" method="post" enctype="multipart/form-data" action="{{ $project?route('projects.update',$project):route('projects.store') }}">@csrf @if($project)@method('PUT')<input type="hidden" name="version" value="{{ $project->version }}">@endif
<h2>Project proposal</h2><div class="qgrid">
<x-business-field name="name" label="Project name" :value="$revision?->name"/>
<x-business-field name="location" label="Location" :value="$revision?->location"/>
<label>Category *<select name="category_id" required><option value="">Choose category</option>@foreach($categories->where('active',true) as $c)<option value="{{ $c->id }}" @selected(old('category_id',$revision?->category_id)==$c->id)>{{ $c->name }} · {{ str($c->purpose)->headline() }}</option>@endforeach</select></label>
<label>Original fund<select name="fund_id" @disabled($project?->fund_locked_at)><option value="">Assign later / create dedicated fund after saving</option>@foreach($funds as $f)<option value="{{ $f->id }}" @selected(old('fund_id',$project?->fund_id)==$f->id)>{{ $f->name }} · {{ str($f->purpose)->headline() }}{{ $f->project_id?' · Dedicated project fund':'' }}</option>@endforeach</select></label>
@if($project?->fund_locked_at)<input type="hidden" name="fund_id" value="{{ $project->fund_id }}">@endif
<label>Implementation committee *<select name="committee_id" required><option value="">Choose committee</option>@foreach($committees->where('type','implementation') as $c)<option value="{{ $c->id }}" @selected(old('committee_id',$revision?->committee_id)==$c->id)>{{ $c->name }}</option>@endforeach</select></label>
<label>Responsible person *<select name="responsible_id" required><option value="">Choose person</option>@foreach($users as $u)<option value="{{ $u->id }}" @selected(old('responsible_id',$revision?->responsible_id)==$u->id)>{{ $u->name }}</option>@endforeach</select></label>
<x-business-field name="beneficiaries" label="Estimated beneficiaries" type="number" min="0" :value="$revision?->beneficiaries??0"/>
<x-business-field name="planned_start" label="Planned start date" type="date" :value="$revision?->planned_start?->toDateString()"/>
<x-business-field name="planned_completion" label="Planned completion date" type="date" :value="$revision?->planned_completion?->toDateString()"/>
</div><div class="qgrid"><x-business-field name="description" label="What will the project do?" type="textarea" :value="$revision?->description"/><x-business-field name="outcomes" label="Expected outcomes" type="textarea" :value="$revision?->outcomes"/><x-business-field name="approach" label="Implementation approach" type="textarea" :value="$revision?->approach"/></div>
<h2>Itemized budget</h2><p class="qmuted">Keep existing item references when amending. Limits cannot fall below net expenditure. Changing suppliers needs renewed approval.</p>
@php($items=old('items',$budget->isNotEmpty()?$budget->toArray():[[]]))
<div id="project-budget">@foreach($items as $i=>$item)@include('projects.budget-line',compact('i','item'))@endforeach</div>
<button class="qbtn secondary" type="button" id="add-project-budget">Add budget item</button>
<template id="project-budget-template">@include('projects.budget-line',['i'=>'__INDEX__','item'=>[]])</template>
<div class="qgrid"><x-business-field name="evidence" label="Price checks / labor and service estimate evidence" type="file" :required="false"/><label>Other proposal documents / photos<input type="file" name="attachments[]" multiple accept=".pdf,.jpg,.jpeg,.png"><small>Up to 10 files, 10 MB each. PDF / JPG / PNG.</small></label></div>
<div class="qactions"><button class="qbtn">Save draft revision</button></div></form></div>
@endsection
@push('scripts')<script src="{{ asset('js/project-form.js') }}" defer></script>@endpush
