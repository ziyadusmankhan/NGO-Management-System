@extends('layouts.app')
@section('title', $purchase ? 'Edit purchase request' : 'New purchase request')
@push('styles')@include('operations.procurement.style')@endpush
@section('content')
<div class="qard-page procurement-page">
    <h1>{{ $purchase ? 'Edit ' . $purchase->number : 'New purchase request' }}</h1>@include('qard.partials.feedback')
    @unless($purchase)
        <form class="qcard" method="get">
            <h2>1. Choose what this purchase supports</h2>
            <div class="qgrid"><label>Purchase type<select name="parent_type">
                        <option value="office" @selected($type === 'office')>Office</option>
                        @if(auth()->user()->hasPermission('projects.view'))
                        <option value="project" @selected($type === 'project')>Project</option>@endif
                        @if(auth()->user()->hasPermission('business.view'))
                        <option value="business" @selected($type === 'business')>Small Business</option>@endif
                    </select></label><label>Approved project or business<select name="parent_id">
                        <option value="">Office — no parent</option>@if($type === 'project')@foreach($projects as $p)
                            <option value="{{ $p->id }}" @selected($parent?->id === $p->id)>{{ $p->number }} · {{ $p->name }}
                        </option>@endforeach @elseif($type === 'business')@foreach($businesses as $p)
                            <option value="{{ $p->id }}" @selected($parent?->id === $p->id)>{{ $p->number }} · {{ $p->name }}
                        </option>@endforeach @endif
                    </select></label></div><button class="qbtn secondary">Load purchase type / budget</button>
            <p>After changing the type, load it to see its available parent records, then select the record and load again.
            </p>
        </form>
    @endunless
    @if($type === 'office' || $parent)
    <form method="post" enctype="multipart/form-data"
        action="{{ $purchase ? route('operations.procurement.update', $purchase) : route('operations.procurement.store') }}"
        class="qcard">
        @csrf @if($purchase)@method('PUT')<input type="hidden" name="version" value="{{ $purchase->version }}">@endif
        <input type="hidden" name="parent_type" value="{{ $type }}"><input type="hidden" name="parent_id"
            value="{{ $parent?->id }}">
        <h2>2. Request details</h2>
        <div class="qgrid">
            <x-business-field name="title" label="Title" :value="old('title', $revision?->title)" />
            <x-business-field name="description" label="Purpose and specifications"
                :value="old('description', $revision?->description)" />
            <x-business-field name="delivery_location" label="Delivery location"
                :value="old('delivery_location', $revision?->delivery_location)" />
            <x-business-field name="required_on" label="Required delivery date" type="date"
                :value="old('required_on', $revision?->required_on?->toDateString())" />
            @if($parent)<input type="hidden" name="fund_id" value="{{ $parent->fund_id }}"><label>Original fund<input
            value="{{ $parent->fund?->name }}" disabled></label>@else<label>Unrestricted fund<select
                        name="fund_id" required>
                        <option value="">Select fund</option>
                        @foreach($funds as $f)@if(!$f->project_id && !app(\App\Domain\Finance\Funds\Services\FundPurposeService::class)->isRestricted($f))
                            <option value="{{ $f->id }}" @selected(old('fund_id', $purchase?->fund_id) == $f->id)>{{ $f->name }}
                        </option>@endif @endforeach
                    </select></label>@endif
            <label>Selected supplier<select name="supplier_id" required>
                    <option value="">Select supplier</option>@foreach($suppliers->where('active', true) as $s)
                        <option value="{{ $s->id }}" @selected(old('supplier_id', $revision?->supplier_id) == $s->id)>
                    {{ $s->name }}</option>@endforeach
                </select></label>
        </div>
        <p>Use the exact supplier approved in the parent budget. Add missing suppliers under Suppliers & procurement
            rules. Prices must include all taxes, delivery costs and discounts.</p>
        @php($defaultItems = $revision ? collect($revision->items)->map(fn($i) => ['key' => $i['key'], 'description' => $i['description'], 'unit' => $i['unit'], 'quantity' => \App\Domain\Procurement\Services\ProcurementMath::display($i['quantity_milli']), 'unit_price' => \App\Domain\Finance\Support\Money::decimal($i['unit_minor']), 'budget_item_id' => $i['budget_item_id']])->all() : [['description' => '', 'unit' => 'each', 'quantity' => '1', 'unit_price' => '']])
        <h2>3. Items</h2>
        <div id="purchase-items">
            @foreach(old('items', $defaultItems) as $index => $item)@include('operations.procurement.item-row')@endforeach
        </div><button type="button" class="qbtn secondary" id="add-purchase-item">Add item</button>
        <div class="qgrid"><x-business-field name="exception_reason"
                label="Why fewer quotations or a higher-priced supplier is necessary (if applicable)"
                :value="old('exception_reason', $revision?->exception_reason)" :required="false" /><label>Specifications
                / supporting document<input type="file" name="evidence" accept=".pdf,.jpg,.jpeg,.png"></label></div>
        <div class="qactions"><button class="qbtn">Save draft</button>@if(auth()->user()?->hasPermission('procurement.view'))<a
                href="{{ route('operations.procurement.index') }}">Cancel</a>@endif</div>
    </form>@endif
</div>
@endsection
@push('scripts')
<script src="{{ asset('js/procurement-form.js') }}"></script>@endpush