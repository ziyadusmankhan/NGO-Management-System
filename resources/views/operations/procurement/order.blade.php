@extends('layouts.app')
@section('title', 'Purchase order ' . $purchase->number)
@push('styles')@include('operations.procurement.style')@endpush
@section('content')
    <div class="qard-page procurement-page">
        <p class="no-print">Use Ctrl+P → Landscape → Print or Save as PDF. @if(auth()->user()?->hasPermission('procurement.view'))<a
                href="{{ route('operations.procurement.show', $purchase) }}">Back</a>@endif</p>
        <h1>Purchase order PO-{{ $purchase->number }}</h1>
        <p>Approved revision {{ $revision->revision }} · Issued
            {{ $revision->issued_at->timezone('Asia/Karachi')->format('d M Y') }} · Current status
            {{ $purchase->status }}{{ $purchase->frozen ? ' — On hold' : '' }}</p>
        <h2>{{ $revision->title }}</h2>
        <p>Supplier: {{ $revision->supplier_snapshot['name'] }} · {{ $revision->supplier_snapshot['contact'] ?? '' }}</p>
        <p>{{ $revision->supplier_snapshot['address'] ?? '' }}</p>
        <p>Delivery: {{ $revision->delivery_location }} · Required {{ $revision->required_on->format('d M Y') }}</p>
        <table>
            <thead>
                <tr>
                    <th>Description</th>
                    <th>Quantity / unit</th>
                    <th>All-inclusive unit price (PKR)</th>
                    <th>Total (PKR)</th>
                </tr>
            </thead>
            <tbody>@foreach($revision->items as $i)
                <tr>
                    <td>{{ $i['description'] }}</td>
                    <td>{{ \App\Domain\Procurement\Services\ProcurementMath::display($i['quantity_milli']) }}
                        {{ $i['unit'] }}</td>
                    <td>{{ \App\Domain\Finance\Support\Money::decimal($i['unit_minor']) }}</td>
                    <td>{{ \App\Domain\Finance\Support\Money::decimal($i['amount_minor']) }}</td>
            </tr>@endforeach
            </tbody>
        </table>
        <h2>Total PKR {{ \App\Domain\Finance\Support\Money::decimal($revision->total_minor) }}</h2>
        <p>Payment follows inspection, acceptance and invoicing. No supplier advance is authorized. This order is subject to
            its retained approval and any recorded cancellation.</p>
</div>@endsection