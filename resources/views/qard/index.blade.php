@extends('layouts.app')
@section('title', 'Qard-e-Hasana')
@push('styles') @include('qard.partials.style') @endpush
@section('content')
<div class="qard-page"><h1>Qard-e-Hasana</h1><p class="qmuted">Interest-free loans, principal recovery, and borrower follow-ups.</p>
@include('qard.partials.feedback')
@if(auth()->user()->hasPermission('qard.manage'))<a class="qbtn" href="{{ route('qard.create') }}">New application</a>@endif
<form class="qcard" method="get"><div class="qgrid">
<div><label for="search">Loan number or borrower</label><input id="search" name="search" value="{{ request('search') }}"></div>
<div><label for="status">Status</label><select id="status" name="status"><option value="">All statuses</option>@foreach(['draft','submitted','implementation','secretary','committee','approved','signed','active','repaid','rejected'] as $status)<option value="{{ $status }}" @selected(request('status')===$status)>{{ str($status)->headline() }}</option>@endforeach</select></div>
<div><label for="reminders">Reminders</label><select id="reminders" name="reminders"><option value="">All loans</option><option value="1" @selected(request('reminders'))>Needs attention</option></select></div></div>
<div class="qactions"><button class="qbtn">Filter</button><a class="qbtn secondary" href="{{ route('qard.index') }}">Clear</a></div></form>
<div class="qcard qtable"><table><thead><tr><th>Loan / borrower</th><th>Fund</th><th>Principal</th><th>Status</th><th>Reminder</th></tr></thead><tbody>
@forelse($loans as $loan)<tr><td><a href="{{ route('qard.show',$loan) }}">{{ $loan->number }}</a><small>{{ $loan->beneficiary?->full_name }}</small></td><td>{{ $loan->fund?->name }}</td><td>PKR {{ \App\Domain\Finance\Support\Money::format($loan->principal) }}</td><td><span class="qbadge">{{ str($loan->status)->headline() }}</span></td><td>{{ !$loan->reminder?->resolved_at ? $loan->reminder?->message : '—' }}</td></tr>
@empty<tr><td colspan="5">No Qard applications found. Create an application to begin.</td></tr>@endforelse</tbody></table>{{ $loans->links('pagination::bootstrap-5') }}</div>
<p class="qmuted">Reminders refresh daily at 08:05 Asia/Karachi when the scheduler is running. Payments also refresh the affected loan.</p></div>
@endsection
