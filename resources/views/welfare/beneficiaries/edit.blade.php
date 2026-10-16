@extends('layouts.app')

@section('title', 'Edit Beneficiary')

@push('styles')
<style>
    .page-header {
        display:flex;
        justify-content:space-between;
        align-items:flex-start;
        gap:20px;
        margin-bottom:22px;
    }

    .page-header h1 {
        margin:0;
        font-size:25px;
        font-weight:700;
    }

    .page-header p {
        margin:6px 0 0;
        color:var(--muted);
    }

    .ui-btn {
        display:inline-flex;
        align-items:center;
        gap:7px;
        height:40px;
        padding:0 14px;
        border:1px solid var(--border);
        border-radius:9px;
        background:#fff;
        color:#596760;
        text-decoration:none;
        font-weight:650;
    }

    .ui-btn.primary {
        background:var(--primary);
        border-color:var(--primary);
        color:#fff;
    }

    .card {
        background:#fff;
        border:1px solid var(--border);
        border-radius:14px;
        overflow:hidden;
    }

    .card-head {
        padding:18px 20px;
        border-bottom:1px solid var(--border);
    }

    .card-head strong {
        font-size:16px;
    }

    .card-head span {
        display:block;
        margin-top:4px;
        color:var(--muted);
        font-size:14px;
    }

    .card-body {
        padding:22px;
    }

    .section-title {
        font-size:15px;
        font-weight:700;
        color:var(--primary);
        padding-bottom:9px;
        border-bottom:1px solid #edf1ef;
        margin-bottom:2px;
    }
</style>
@endpush

@section('content')

<div class="page-header">
    <div>
        <h1>Edit Beneficiary</h1>
        <p>{{ $beneficiary->beneficiary_number }} · {{ $beneficiary->full_name }}</p>
    </div>

    <div class="d-flex gap-2">
        <a href="{{ route('welfare.beneficiaries.show', $beneficiary) }}" class="ui-btn">
            <i class="bi bi-eye"></i>
            View
        </a>

        <a href="{{ route('welfare.beneficiaries.index') }}" class="ui-btn">
            Back
        </a>
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
                <a href="{{ route('welfare.beneficiaries.show', $beneficiary) }}" class="ui-btn">
                    Cancel
                </a>

                <button type="submit" class="ui-btn primary">
                    <i class="bi bi-check2"></i>
                    Update Beneficiary
                </button>
            </div>
        </form>

    </div>
</div>

@endsection