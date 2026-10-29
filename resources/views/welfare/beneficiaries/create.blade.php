@extends('layouts.app')

@section('title', 'Add Beneficiary')

@push('styles')
<link rel="stylesheet" href="{{ asset('css/views/welfare-beneficiaries-create-1.css') }}">
@endpush

@section('content')

<div class="page-header">
    <div>
        <h1>Add Beneficiary</h1>
        <p>Register a new welfare beneficiary for verification and future assistance.</p>
    </div>

    @if(auth()->user()?->hasPermission('welfare.view'))<a href="{{ route('welfare.beneficiaries.index') }}" class="ui-btn">
        <i class="bi bi-arrow-left"></i>
        Back to Beneficiaries
    </a>@endif
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
        <span>Only the fields required for initial registration are collected here. Verification and approval are separate workflow stages.</span>
    </div>

    <div class="card-body">

        <form method="POST" action="{{ route('welfare.beneficiaries.store') }}">
            @csrf

            @include('welfare.beneficiaries.partials.form')

            <div class="d-flex justify-content-end gap-2 mt-4 pt-4 border-top">
                @if(auth()->user()?->hasPermission('welfare.view'))<a href="{{ route('welfare.beneficiaries.index') }}" class="ui-btn">
                    Cancel
                </a>@endif

                <button type="submit" class="ui-btn primary">
                    <i class="bi bi-person-plus"></i>
                    Save Beneficiary
                </button>
            </div>
        </form>

    </div>
</div>

@endsection