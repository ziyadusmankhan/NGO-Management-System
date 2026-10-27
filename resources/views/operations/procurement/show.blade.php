@extends('layouts.app')
@section('title', $purchase->number)
@push('styles')@include('operations.procurement.style')@endpush
@section('content')
@php($can = fn($p) => auth()->user()->hasPermission('procurement.' . $p))
@php($money = fn($v) => \App\Domain\Finance\Support\Money::format(\App\Domain\Finance\Support\Money::decimal((int) $v)))
@php($quantity = fn($v) => \App\Domain\Procurement\Services\ProcurementMath::display((int) $v))
@php($open = $purchase->status === 'ordered' && !$purchase->frozen && !$purchase->trashed())
<div class="qard-page procurement-page">
    <h1>{{ $purchase->number }} · {{ $revision->title }}</h1>
    <p>{{ ucfirst($purchase->parent_type) }} {{ $purchase->parent_id ? '#' . $purchase->parent_id : '' }} ·
        {{ $purchase->fund->name }} · {{ ucfirst($purchase->status) }} · Revision {{ $purchase->revision }}:
        {{ str($revision->stage)->headline() }}</p>
    @include('qard.partials.feedback')
    <div class="qactions"><a class="qbtn secondary"
            href="{{ route('operations.procurement.index') }}">Back</a>@if($revision->stage === 'draft' && $can('manage') && !$purchase->trashed())<a
            class="qbtn" href="{{ route('operations.procurement.edit', $purchase) }}">Edit draft</a>@endif
        @if($approved?->issued_at)<a class="qbtn secondary"
        href="{{ route('operations.procurement.order', $purchase) }}">Print purchase order</a>@endif</div>
    @if($purchase->frozen)
        <p class="notice">Payments and delivery processing are paused while the amendment is unresolved. The previous
    approved budget remains reserved.</p>@endif
    <div class="qgrid">
        <div class="qcard"><strong>Approved order</strong>
            <h2>PKR {{ $money($approved?->total_minor ?? 0) }}</h2>
        </div>
        <div class="qcard"><strong>Gross paid</strong>
            <h2>PKR {{ $money($purchase->paidMinor()) }}</h2>
        </div>
        <div class="qcard"><strong>Refunds received</strong>
            <h2>PKR {{ $money($purchase->refundedMinor()) }}</h2>
        </div>
        <div class="qcard"><strong>Outstanding reservation</strong>
            <h2>PKR {{ $money($purchase->reservation()) }}</h2>
        </div>
    </div>
    <div class="qcard">
        <h2>Current proposal</h2>
        <p>{{ $revision->description }}</p>
        <p>Supplier: <strong>{{ $revision->supplier_snapshot['name'] }}</strong> · Deliver to
            {{ $revision->delivery_location }} by {{ $revision->required_on->format('d M Y') }}</p>
        <div class="qtable">
            <table>
                <thead>
                    <tr>
                        <th>Item</th>
                        <th>Quantity</th>
                        <th>Unit price</th>
                        <th>Total</th>
                    </tr>
                </thead>
                <tbody>@foreach($revision->items as $i)
                    <tr>
                        <td>{{ $i['description'] }}<small>Parent budget item {{ $i['budget_item_id'] ?? '—' }}</small>
                        </td>
                        <td>{{ $quantity($i['quantity_milli']) }} {{ $i['unit'] }}</td>
                        <td>{{ $money($i['unit_minor']) }}</td>
                        <td>{{ $money($i['amount_minor']) }}</td>
                </tr>@endforeach
                </tbody>
            </table>
        </div>
        <p>Exception reason: {{ $revision->exception_reason ?: 'None' }}</p>
    </div>
    @if($approved && $approved->revision !== $revision->revision)
        <div class="qcard">
            <h2>Approved revision {{ $approved->revision }} remains authoritative</h2>@foreach($approved->items as $i)
                <p>{{ $i['description'] }} · {{ $quantity($i['quantity_milli']) }} {{ $i['unit'] }} · PKR
            {{ $money($i['amount_minor']) }}</p>@endforeach
    </div>@endif
    <div class="qcard">
        <h2>Quotation comparison</h2>@forelse($purchase->quotes as $q)
            <p>Revision {{ $q->revision }} · {{ $q->supplier_snapshot['name'] }} · {{ $q->reference }} · <strong>PKR
        {{ $money($q->total_minor) }}</strong> · Valid through {{ $q->valid_until->format('d M Y') }}</p>@empty
                <p>No quotations recorded yet.</p>@endforelse
    </div>
    @if($revision->stage === 'draft' && $can('quotations') && !$purchase->trashed())
        <details class="qcard">
            <summary>Add supplier quotation</summary>
            <form method="post" enctype="multipart/form-data"
                action="{{ route('operations.procurement.quote', $purchase) }}">
                @include('operations.procurement.action-fields')
                <div class="qgrid"><label>Supplier<select name="supplier_id"
                            required>@foreach($suppliers->where('active', true) as $s)
                            <option value="{{ $s->id }}">{{ $s->name }}</option>@endforeach
                        </select></label><x-business-field name="reference" label="Quotation reference" /><x-business-field
                        name="quoted_on" label="Quotation date" type="date"
                        :value="now('Asia/Karachi')->toDateString()" /><x-business-field name="valid_until"
                        label="Valid through" type="date" /><x-business-field name="terms"
                        label="Delivery and payment terms" /></div>@foreach($revision->items as $n => $i)<input type="hidden"
                            name="items[{{ $n }}][key]" value="{{ $i['key'] }}"><label>{{ $i['description'] }} — all-inclusive price
                            per {{ $i['unit'] }}<input name="items[{{ $n }}][unit_price]" inputmode="decimal"
                        required></label>@endforeach<label>Quotation evidence<input name="evidence" type="file"
                        accept=".pdf,.jpg,.jpeg,.png" required></label><button class="qbtn">Save quotation</button>
            </form>
    </details>@endif
    @if(in_array($revision->stage, ['coordinator', 'secretary', 'committee']) && $can(['coordinator' => 'recommend', 'secretary' => 'review', 'committee' => 'approve'][$revision->stage]))
        <details class="qcard" open>
            <summary>{{ str($revision->stage)->headline() }} decision</summary>
            <form method="post" enctype="multipart/form-data"
                action="{{ route('operations.procurement.decide', [$purchase, $revision->stage]) }}">
                @include('operations.procurement.action-fields')
                <div class="qgrid"><label>Decision<select name="decision">
                            <option value="accept">Accept</option>
                            <option value="reject">Reject for correction</option>
                        </select></label><x-business-field name="notes" label="Decision and reasons" /></div>
                @if($revision->stage === 'committee')
                    <div class="qgrid"><label>Decision committee<select name="committee_id">
                                <option value="">Choose committee</option>@foreach($committees as $c)
                                <option value="{{ $c->id }}">{{ $c->name }}</option>@endforeach
                            </select></label><x-business-field name="resolution_reference" label="Resolution reference"
                            :required="false" /><x-business-field name="resolution_date" label="Resolution date" type="date"
                            :required="false" /><label>Resolution attachment<input name="evidence" type="file"
                                accept=".pdf,.jpg,.jpeg,.png"></label></div>
                    <p>Select consenting members belonging to the chosen committee:</p>
                    @foreach($committees as $c)@foreach($c->members as $member)<label><input type="checkbox"
                        name="consenting_member_ids[]" value="{{ $member->id }}"> {{ $member->full_name }} ·
                    {{ $c->name }}</label>@endforeach @endforeach<label><input type="checkbox" name="accept_exception"
                value="1"> Explicitly accept the recorded quotation exception</label>@endif<button
                    class="qbtn">Record decision</button>
            </form>
    </details>@endif
    @include('operations.procurement.delivery-forms')
    @include('operations.procurement.payment-forms')
    @include('operations.procurement.history')
    @php($availableActions = [
        'submit' => $revision->stage === 'draft' && !in_array($purchase->status, ['closed', 'cancelled']),
        'issue' => $purchase->status === 'approved' && $approved && !$approved->issued_at && !$purchase->frozen,
        'amend' => in_array($revision->stage, ['approved', 'rejected', 'withdrawn', 'draft']) && !in_array($purchase->status, ['closed', 'cancelled']),
        'withdraw' => !in_array($revision->stage, ['approved', 'withdrawn']) && !in_array($purchase->status, ['closed', 'cancelled']),
        'resume' => $purchase->frozen && in_array($revision->stage, ['rejected', 'withdrawn']),
        'close' => $open,
        'cancel' => !$purchase->frozen && !in_array($purchase->status, ['closed', 'cancelled']),
        'archive' => in_array($purchase->status, ['closed', 'cancelled']),
    ])
    <details class="qcard" open>
        <summary>Workflow actions</summary>
        @foreach(['submit' => 'manage', 'issue' => 'issue', 'amend' => 'manage', 'withdraw' => 'manage', 'resume' => 'manage', 'close' => 'close', 'cancel' => 'close', 'archive' => 'manage'] as $action => $permission)
            @if($can($permission) && !$purchase->trashed() && $availableActions[$action])
                <form method="post" action="{{ route('operations.procurement.' . $action, $purchase) }}">
                    @include('operations.procurement.action-fields')@if(!in_array($action, ['submit', 'issue']))<label>Reason for
                    {{ $action }}<input name="reason" required></label>@endif<button
            class="qbtn secondary">{{ str($action)->headline() }}</button></form>@endif
        @endforeach
        <p>Actions require the correct stage and independent accounts. Close only after delivery and settlement;
            cancellation also requires accepted goods and bills to be resolved.</p>
    </details>
    @if($can('void'))
        <details class="qcard">
            <summary>Correct a recording error</summary>
            <form method="post" action="{{ route('operations.procurement.void', $purchase) }}">
                @include('operations.procurement.action-fields')
                <div class="qgrid"><label>Record type<select
                            name="record_type">@foreach(['movement', 'receipt', 'invoice', 'return'] as $t)
                            <option value="{{ $t }}">{{ ucfirst($t) }}</option>@endforeach
                        </select></label><x-business-field name="record_id"
                        label="Record ID from history" /><x-business-field name="reason"
                        label="Why this record is incorrect" /></div><button class="qbtn secondary">Void with retained
                    history</button>
            </form>
            <p>An actual supplier return uses the returns workflow. Void dependent records first. Voids never automatically
                reopen spending on a closed order.</p>
    </details>@endif
</div>@endsection