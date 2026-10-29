@extends('layouts.app')
@section('title','Global Search')
@push('styles')@include('records.audit.style')@endpush
@section('content')
<div class="audit-page">
    <h1>Global Search</h1>
    <p>Search only records your account is allowed to open. CNIC and phone searches require the complete value.</p>
    <form class="audit-card" method="get" action="{{ route('search.index') }}">
        <label>Search members, beneficiaries, applications, finance, programs, operations and records
            <input type="search" name="q" value="{{ $term }}" minlength="2" maxlength="100" required autofocus placeholder="Enter a name, number, complete CNIC or complete phone">
        </label>
        <button class="audit-btn">Search</button>
    </form>

    @if($term !== '')
        <p><strong>{{ $total }}</strong> authorized {{ Str::plural('result', $total) }} for “{{ $term }}”.</p>
        @forelse($groups as $group)
            <div class="audit-card audit-table">
                <h3>{{ $group['label'] }}</h3>
                <table>
                    <thead><tr><th>Type</th><th>Reference</th><th>Record</th><th>Status</th></tr></thead>
                    <tbody>
                    @foreach($group['results'] as $result)
                        <tr>
                            <td>{{ $result['type'] }}</td>
                            <td>{{ $result['reference'] ?: '—' }}</td>
                            <td><a href="{{ $result['url'] }}"><strong>{{ $result['title'] }}</strong></a></td>
                            <td><span class="audit-tag">{{ str($result['status'] ?: 'available')->replace('_',' ')->headline() }}</span></td>
                        </tr>
                    @endforeach
                    </tbody>
                </table>
            </div>
        @empty
            <div class="audit-card"><h3>No authorized records found</h3><p>Check the spelling or try a record number. Restricted records are never included.</p></div>
        @endforelse
    @endif
</div>
@endsection
