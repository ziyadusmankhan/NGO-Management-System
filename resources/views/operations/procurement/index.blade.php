@extends('layouts.app')
@section('title', 'Procurement')
@push('styles')@include('operations.procurement.style')@endpush
@section('content')
<div class="qard-page procurement-page">
    <h1>Procurement</h1>
    <p>Purchase requests, supplier quotations, accepted deliveries and authorized payments.</p>
    @include('qard.partials.feedback')
    <div class="qactions">
        @if(auth()->user()->hasPermission('procurement.manage'))<a class="qbtn"
        href="{{ route('operations.procurement.create') }}">New purchase request</a>@endif
        @if(auth()->user()->hasPermission('procurement.suppliers') || auth()->user()->hasPermission('procurement.policy'))<a
            class="qbtn secondary" href="{{ route('operations.procurement.settings') }}">Suppliers & procurement
        rules</a>@endif
        @if(auth()->user()->hasPermission('procurement.reports'))<a class="qbtn secondary"
        href="{{ route('operations.procurement.reports') }}">Reports & exports</a>@endif
    </div>
    <form method="get" class="qcard qgrid"><label>Request number<input name="search"
                value="{{ request('search') }}"></label><label>Status<select name="status">
                <option value="">All</option>@foreach(['draft', 'approved', 'ordered', 'closed', 'cancelled'] as $s)
                <option value="{{ $s }}" @selected(request('status') === $s)>{{ ucfirst($s) }}</option>@endforeach
            </select></label><label><input type="checkbox" name="archived" value="1" @checked(request('archived'))>
            Include archived</label><button class="qbtn">Filter</button></form>
    <div class="qcard qtable">
        <table>
            <thead>
                <tr>
                    <th>Request</th>
                    <th>Purpose</th>
                    <th>Supplier</th>
                    <th>Stage</th>
                    <th>Order value</th>
                    <th>Attention</th>
                </tr>
            </thead>
            <tbody>
                @forelse($purchases as $p)@php($r = $p->approved() ?? $p->proposal())
                <tr>
                    <td><a
                            href="{{ route('operations.procurement.show', $p) }}">{{ $p->number }}</a><small>{{ $r->title }}</small>
                    </td>
                    <td>{{ ucfirst($p->parent_type) }} {{ $p->parent_id ? '#' . $p->parent_id : '' }}</td>
                    <td>{{ $r->supplier_snapshot['name'] }}</td>
                    <td>{{ ucfirst($p->status) }}<small>{{ str($p->proposal()->stage)->headline() }}{{ $p->frozen ? ' · Payments paused' : '' }}{{ $p->trashed() ? ' · Archived' : '' }}</small>
                    </td>
                    <td>PKR
                        {{ \App\Domain\Finance\Support\Money::format(\App\Domain\Finance\Support\Money::decimal($r->total_minor)) }}
                    </td>
                    <td>{{ $p->reminder?->delivery_due ? 'Delivery overdue. ' : '' }}{{ $p->reminder?->invoice_due ? 'Invoice overdue. ' : '' }}{{ $p->reminder?->refund_due ? 'Refund overdue.' : '' }}
                    </td>
                </tr>
                @empty<tr>
                    <td colspan="6">No purchase requests yet. Start with a supplier and a new request.</td>
                </tr>@endforelse
            </tbody>
        </table>
    </div>{{ $purchases->links() }}
</div>
@endsection