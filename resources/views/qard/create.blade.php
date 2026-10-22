@extends('layouts.app')
@section('title', 'Qard-e-Hasana')
@push('styles') @include('qard.partials.style') @endpush
@section('content')
<div class="qard-page"><h1>{{ $loan ? 'Edit '.$loan->number : 'New Qard application' }}</h1><p class="qmuted">Save a draft, then submit it for verification. No money is paid at this stage.</p>
@include('qard.partials.feedback')
<form class="qcard" method="post" action="{{ $loan ? route('qard.update',$loan) : route('qard.store') }}">@csrf @if($loan) @method('PUT') @endif
<div class="qgrid">
<div><label for="beneficiary_id">Verified, approved borrower</label><select id="beneficiary_id" name="beneficiary_id" required><option value="">Select borrower</option>@foreach($beneficiaries as $beneficiary)<option value="{{ $beneficiary->id }}" @selected((string)old('beneficiary_id',$loan?->beneficiary_id)===(string)$beneficiary->id)>{{ $beneficiary->full_name }} · {{ $beneficiary->beneficiary_number }}</option>@endforeach</select><small>Complete beneficiary verification and approval first.</small></div>
<div><label for="fund_id">Original fund</label><select id="fund_id" name="fund_id" required><option value="">Select fund</option>@foreach($funds as $fund)<option value="{{ $fund->id }}" @selected((string)old('fund_id',$loan?->fund_id)===(string)$fund->id)>{{ $fund->name }}</option>@endforeach</select><small>Active unrestricted general or Qard-designated funds only.</small></div>
<div><label for="principal">Requested principal (PKR)</label><input id="principal" name="principal" inputmode="decimal" pattern="\d{1,13}(\.\d{1,2})?" value="{{ old('principal',$loan?->principal) }}" required></div>
<div><label for="frequency">Repayment frequency</label><select id="frequency" name="frequency">@foreach(['monthly','weekly'] as $frequency)<option value="{{ $frequency }}" @selected(old('frequency',$loan?->frequency ?? 'monthly')===$frequency)>{{ ucfirst($frequency) }}</option>@endforeach</select></div>
<div><label for="installment_count">Number of installments</label><input id="installment_count" type="number" name="installment_count" min="1" max="600" value="{{ old('installment_count',$loan?->installment_count ?? 1) }}" required></div>
<div><label for="first_due_date">First repayment due</label><input id="first_due_date" type="date" name="first_due_date" value="{{ old('first_due_date',$loan?->first_due_date?->toDateString()) }}" required><small>After expected signing and disbursement. Include any agreed grace period in this date.</small></div>
</div><div style="margin-top:18px"><label for="purpose">Purpose and need</label><textarea id="purpose" name="purpose" maxlength="5000" required>{{ old('purpose',$loan?->purpose) }}</textarea></div>
<div class="qactions"><button class="qbtn">Save draft</button><a class="qbtn secondary" href="{{ $loan ? route('qard.show',$loan) : route('qard.index') }}">Cancel</a></div></form></div>
@endsection
