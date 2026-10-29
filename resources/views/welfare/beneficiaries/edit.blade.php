@extends('layouts.app')

@section('title', 'Edit Beneficiary')

@push('styles')
<link rel="stylesheet" href="{{ asset('css/views/welfare-beneficiaries-edit-1.css') }}">
@endpush

@section('content')

<div class="page-header">
    <div>
        <h1>Edit Beneficiary</h1>
        <p>{{ $beneficiary->beneficiary_number }} · {{ $beneficiary->full_name }}</p>
    </div>

    <div class="d-flex gap-2">
        @if(auth()->user()?->hasPermission('welfare.view'))<a href="{{ route('welfare.beneficiaries.show', $beneficiary) }}" class="ui-btn">
            <i class="bi bi-eye"></i>
            View
        </a>@endif

        @if(auth()->user()?->hasPermission('welfare.view'))<a href="{{ route('welfare.beneficiaries.index') }}" class="ui-btn">
            Back
        </a>@endif
    </div>
</div>

@if ($errors->any())
    <div class="alert alert-danger mb-3">
        <strong>Please correct the following:</strong>

        <ul class="mb-0 mt-2">
            @foreach ($errors->all() as $error)
                <li>{{ $error }}</li>
            @endforeach
        </ul>
    </div>
@endif

<div class="card">
    <div class="card-head">
        <strong>Beneficiary Information</strong>
        <span>Editing the record does not change its existing verification or approval history.</span>
    </div>

    <div class="card-body">

        <form method="POST" action="{{ route('welfare.beneficiaries.update', $beneficiary) }}">
            @csrf
            @method('PUT')

            @include('welfare.beneficiaries.partials.form', ['beneficiary' => $beneficiary])

            <div class="d-flex justify-content-end gap-2 mt-4 pt-4 border-top">
                @if(auth()->user()?->hasPermission('welfare.view'))<a href="{{ route('welfare.beneficiaries.show', $beneficiary) }}" class="ui-btn">
                    Cancel
                </a>@endif

                <button type="submit" class="ui-btn primary">
                    <i class="bi bi-check2"></i>
                    Update Beneficiary
                </button>
            </div>
        </form>

    </div>
</div>

@endsection