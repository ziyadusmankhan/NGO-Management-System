@extends('layouts.app')
@section('title', 'Create Welfare Assistance')
@section('content')
<div class="page-header mb-4">
    <h1>Create Welfare Assistance</h1>
    <p>Select an approved application with a signed agreement. Assistance starts pending approval.</p>
</div>
@if($errors->any())
    <div class="alert alert-danger"><ul class="mb-0">@foreach($errors->all() as $error)<li>{{ $error }}</li>@endforeach</ul></div>
@endif
@if($selectionError)<div class="alert alert-warning">{{ $selectionError }}</div>@endif
<div class="card mb-4"><div class="card-body">
    <form method="GET" action="{{ route('welfare.assistance.create') }}" class="row g-3">
        <div class="col-md-9">
            <label class="form-label" for="application_id">Eligible signed application</label>
            <select class="form-select" id="application_id" name="application_id" required>
                <option value="">Select an application</option>
                @foreach($applications as $application)
                    <option value="{{ $application->id }}" @selected($selected?->id === $application->id)>
                        {{ $application->application_number }} — {{ $application->beneficiary->full_name }} — PKR {{ $application->agreement->approved_amount }}
                    </option>
                @endforeach
            </select>
        </div>
        <div class="col-md-3 d-flex align-items-end"><button class="btn btn-success" type="submit">Review details</button></div>
    </form>
    @if($applications->isEmpty())
        <p class="mt-3 mb-0">No eligible applications are available. Complete final approval and agreement signatures, and assign an active fund first. Applications already linked to assistance are excluded, including archived assistance.</p>
    @endif
</div></div>
@if($selected)
    <div class="card"><div class="card-body">
        <h2 class="h5">Approved details</h2>
        <dl class="row">
            <dt class="col-sm-3">Application</dt><dd class="col-sm-9">@if(auth()->user()?->hasPermission('welfare.assistance.view'))<a href="{{ route('welfare.applications.show', $selected) }}">{{ $selected->application_number }}</a>@endif</dd>
            <dt class="col-sm-3">Agreement</dt><dd class="col-sm-9">
                @can('welfare.agreements.view')@if(auth()->user()?->hasPermission('welfare.agreements.view'))<a href="{{ route('welfare.applications.agreement.show', $selected) }}">{{ $selected->agreement->agreement_number }}</a>@endif
                @else {{ $selected->agreement->agreement_number }} @endcan · Signed
            </dd>
            <dt class="col-sm-3">Beneficiary</dt><dd class="col-sm-9">{{ $selected->beneficiary->full_name }}</dd>
            <dt class="col-sm-3">Authorized fund</dt><dd class="col-sm-9">{{ $selected->fund->name }}</dd>
            <dt class="col-sm-3">Approved amount</dt><dd class="col-sm-9">PKR {{ $selected->agreement->approved_amount }}</dd>
            <dt class="col-sm-3">Purpose</dt><dd class="col-sm-9" style="white-space:pre-wrap">{{ $selected->purpose_details }}</dd>
            <dt class="col-sm-3">Assistance type</dt><dd class="col-sm-9">{{ ucfirst(str_replace('_', ' ', $selected->request_type)) }}</dd>
            <dt class="col-sm-3">Schedule</dt><dd class="col-sm-9">{{ ucfirst(str_replace('_', ' ', $selected->frequency)) }} · {{ $selected->start_date?->format('d M Y') ?? 'No start date' }} to {{ $selected->end_date?->format('d M Y') ?? 'No end date' }}</dd>
        </dl>
        <form method="POST" action="{{ route('welfare.assistance.store') }}">
            @csrf
            <input type="hidden" name="application_id" value="{{ $selected->id }}">
            <div class="mb-3"><label class="form-label" for="next_review_date">Next review date (optional)</label><input class="form-control" type="date" id="next_review_date" name="next_review_date" value="{{ old('next_review_date') }}"></div>
            <div class="mb-3"><label class="form-label" for="internal_notes">Internal notes</label><textarea class="form-control" id="internal_notes" name="internal_notes" maxlength="5000" rows="3">{{ old('internal_notes') }}</textarea></div>
            <div class="mb-3"><label class="form-label" for="notes">Notes</label><textarea class="form-control" id="notes" name="notes" maxlength="5000" rows="3">{{ old('notes') }}</textarea></div>
            <p>These approved details are copied automatically. Creation records no payment or ledger transaction.</p>
            <button class="btn btn-success" type="submit">Create pending assistance</button>
            @if(auth()->user()?->hasPermission('welfare.assistance.view'))<a class="btn btn-outline-secondary" href="{{ route('welfare.assistance.index') }}">Cancel</a>@endif
        </form>
    </div></div>
@endif
@endsection
