@extends('layouts.app')
@section('title', $application ? 'Edit Business Application' : 'New Business Application')
@push('styles') @include('business.partials.style') @endpush
@section('content')
<div class="qard-page"><div class="business-hero"><div><h1>{{ $application ? 'Edit '.$application->number : 'New business application' }}</h1><p class="qmuted">Save a draft first. All funding requires verification, independent decisions, and a signed agreement.</p></div>@if(auth()->user()?->hasPermission('business.view'))<a class="qbtn secondary" href="{{ route('business.applications.index') }}">Back</a>@endif</div>
@include('qard.partials.feedback')
@if($categories->isEmpty())<p class="qalert">An administrator must add a business category before you can save an application.</p>@endif
<form method="post" enctype="multipart/form-data" action="{{ $application ? route('business.applications.update', $application) : route('business.applications.store') }}">@csrf
@if($application) @method('PUT')<input type="hidden" name="version" value="{{ $application->version }}">@endif
<div class="qcard"><h2>1. Applicant and business</h2><div class="qgrid">
<div><label>Verified, approved beneficiary *<select name="beneficiary_id" required><option value="">Select beneficiary</option>@foreach($beneficiaries as $beneficiary)<option value="{{ $beneficiary->id }}" @selected((string)old('beneficiary_id',$application?->beneficiary_id)===(string)$beneficiary->id)>{{ $beneficiary->beneficiary_number }} · {{ $beneficiary->full_name }}</option>@endforeach</select></label></div>
<div><label>Business category *<select name="category_id" required><option value="">Select category</option>@foreach($categories as $category)<option value="{{ $category->id }}" @selected((string)old('category_id',$application?->category_id)===(string)$category->id)>{{ $category->name }}</option>@endforeach</select></label></div>
<x-business-field label="Business name" name="name" :value="$application?->name ?? ''" />
<x-business-field label="Business location" name="location" :value="$application?->location ?? ''" />
<x-business-field label="Expected start date" name="expected_start_date" type="date" :value="$application?->expected_start_date?->toDateString() ?? now(config('business.timezone'))->addMonthNoOverflow()->toDateString()" />
</div><x-business-field label="Proposed business" name="description" type="textarea" :value="$application?->description ?? ''" />
<div class="qgrid"><x-business-field label="Skills and experience" name="skills" type="textarea" :value="$application?->skills ?? ''" /><x-business-field label="Customers and market assessment" name="market_assessment" type="textarea" :value="$application?->market_assessment ?? ''" /><x-business-field label="Risks and mitigation" name="risks" type="textarea" :value="$application?->risks ?? ''" /></div>
</div>
<div class="qcard"><h2>2. Support and funding</h2><div class="qgrid">
<div><label>Support type *<select name="support_type" id="business-support"><option value="grant" @selected(old('support_type',$application?->support_type)==='grant')>Grant — no repayment</option><option value="repayable" @selected(old('support_type',$application?->support_type)==='repayable')>Repayable — principal only</option></select></label></div>
<div><label>Original fund *<select name="fund_id" required><option value="">Select fund</option>@foreach($funds as $fund)<option value="{{ $fund->id }}" @selected((string)old('fund_id',$application?->fund_id)===(string)$fund->id)>{{ $fund->name }} · {{ str($fund->purpose)->headline() }}</option>@endforeach</select></label><small class="qmuted">Qard-designated funds only allow repayable support.</small></div>
<div data-repayment><label>Repayment frequency<select name="frequency"><option value="monthly" @selected(old('frequency',$application?->frequency)==='monthly')>Monthly</option><option value="weekly" @selected(old('frequency',$application?->frequency)==='weekly')>Weekly</option></select></label></div>
<div data-repayment><x-business-field label="Installment count" name="installment_count" type="number" :value="$application?->installment_count ?? 12" min="1" :max="config('business.maximum_installments')" :required="false" /></div>
</div><p class="qhint">Repayments begin after funding is finalized and the actual principal schedule is signed. First payment is due one interval after finalization. No interest or compulsory penalties.</p></div>
<div class="qcard"><h2>3. Investment budget</h2><p class="qmuted">Enter each purchase or cash allocation. The server calculates organization funding as total cost minus the applicant's own share.</p>
@php($items = old('items', $application ? $application->currentBudget()->get()->toArray() : [['description'=>'','cost'=>'','applicant_amount'=>'0.00','release_method'=>'cash','supplier'=>'','price_checks'=>[]]]))
<div id="business-budget-items">
@foreach($items as $i => $item) @include('business.partials.budget-row', ['i'=>$i, 'item'=>$item]) @endforeach
</div><button type="button" class="qbtn secondary" id="add-budget-item">Add budget item</button>
<p class="qhint">Supplier/asset purchases need two distinct price checks with references, or an exception reason which the committee explicitly approves. Upload combined quotation evidence below. Applicant contributions are not foundation donations.</p>
</div>
<div class="qcard"><h2>4. Projections and evidence</h2><div class="qgrid">
<x-business-field label="Expected monthly sales (PKR)" name="expected_sales" :value="$application?->expected_sales ?? '0.00'" inputmode="decimal" />
<x-business-field label="Expected monthly expenses (PKR)" name="expected_expenses" :value="$application?->expected_expenses ?? '0.00'" inputmode="decimal" />
<x-business-field label="Combined price-check evidence" name="price_check_file" type="file" :required="false" />
<x-business-field label="Application documents or photographs" name="attachments[]" type="file" multiple :required="false" />
</div>@if($application)<p class="qhint">Existing evidence remains in history. After a rejected application or revised approval, upload fresh evidence for the new revision.</p>@endif</div>
<div class="qactions"><button class="qbtn">Save draft</button>@if(auth()->user()?->hasPermission('business.view'))<a class="qbtn secondary" href="{{ route('business.applications.index') }}">Cancel</a>@endif</div>
</form>
<template id="budget-template">@include('business.partials.budget-row', ['i'=>'__INDEX__', 'item'=>['description'=>'','cost'=>'','applicant_amount'=>'0.00','release_method'=>'cash','price_checks'=>[]]])</template>
</div>
@endsection
@push('scripts')
<script>
document.addEventListener('DOMContentLoaded',function(){let count={{ count($items) }};document.getElementById('add-budget-item').addEventListener('click',()=>{document.getElementById('business-budget-items').insertAdjacentHTML('beforeend',document.getElementById('budget-template').innerHTML.replaceAll('__INDEX__',String(count++)));});document.getElementById('business-budget-items').addEventListener('click',e=>{if(e.target.matches('[data-remove-item]'))e.target.closest('.business-budget').remove();});const support=document.getElementById('business-support');const toggle=()=>document.querySelectorAll('[data-repayment]').forEach(el=>{el.hidden=support.value!=='repayable';el.querySelectorAll('input,select').forEach(input=>input.disabled=support.value!=='repayable');});support.addEventListener('change',toggle);toggle();});
</script>
@endpush
