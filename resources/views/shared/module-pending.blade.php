@extends('layouts.app')

@section('content')
<section class="card p-4">
    <h1>{{ $moduleTitle }}</h1>
    <p>No records to display.</p>
    <p>This section is awaiting implementation. Record creation will be available when its workflow is ready.</p>
    <a href="{{ route('dashboard') }}">Back to dashboard</a>
</section>
@endsection
