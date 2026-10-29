@extends('layouts.app')
@section('title', 'Suppliers & procurement rules')
@push('styles')@include('operations.procurement.style')@endpush
@section('content')
    <div class="qard-page procurement-page">
        <h1>Suppliers & procurement rules</h1>@include('qard.partials.feedback')@if(auth()->user()?->hasPermission('procurement.view'))<a
            href="{{ route('operations.procurement.index') }}">Back to Procurement</a>@endif
        @if(auth()->user()->hasPermission('procurement.policy'))
            <form class="qcard" method="post" action="{{ route('operations.procurement.policy') }}">@csrf<h2>Quotation policy
                </h2><label>Required distinct suppliers (1–10)<input type="number" name="quote_count" min="1" max="10"
                        value="{{ $policy->quote_count }}" required></label>
                <p>Changes apply to new submissions. Committee exceptions require a reason and explicit acceptance.</p><button
                    class="qbtn">Save new policy version</button>
        </form>@endif
        @if(auth()->user()->hasPermission('procurement.suppliers'))
            <form class="qcard" method="post" action="{{ route('operations.procurement.suppliers.save') }}">@csrf<h2>Add
                    supplier</h2>@include('operations.procurement.supplier-fields', ['supplier' => null])<button class="qbtn">Add
                    supplier</button></form>
            @foreach($suppliers as $supplier)
                <details class="qcard">
                    <summary>{{ $supplier->name }} · {{ $supplier->active ? 'Active' : 'Archived' }}</summary>
                    <form method="post" action="{{ route('operations.procurement.suppliers.save', $supplier) }}">@csrf
                        @include('operations.procurement.supplier-fields')<button class="qbtn">Save supplier</button></form>
            </details>@endforeach
        @endif
</div>@endsection