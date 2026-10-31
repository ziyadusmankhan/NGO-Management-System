@extends('layouts.app')
@section('title', 'Small Businesses')
@push('styles') @include('business.partials.style') @endpush
@section('content')
<div class="no-print" style="margin-bottom:16px">@include('shared.section-export',['exportKind'=>'business'])</div>

<div class="qard-page">
<div class="business-hero"><div><h1>Small Businesses</h1><p class="qmuted">Applications, staged funding, business monitoring and principal recovery.</p></div><div class="qactions">
@can('business.manage')@if(auth()->user()?->hasPermission('business.view') && auth()->user()?->hasPermission('business.manage'))<a class="qbtn" href="{{ route('business.applications.create') }}">New application</a>@endif
@endcan
@can('business.reports')@if(auth()->user()?->hasPermission('business.view') && auth()->user()?->hasPermission('business.reports'))<a class="qbtn secondary" href="{{ route('business.reports') }}">Reports</a>@endif
@endcan
@can('business.categories')@if(auth()->user()?->hasPermission('business.view') && auth()->user()?->hasPermission('business.categories'))<a class="qbtn secondary" href="{{ route('business.categories') }}">Categories</a>@endif
@endcan
</div></div>@include('qard.partials.feedback')
<form class="qcard" method="get"><div class="qgrid">
<div><label>Number, business or beneficiary<input name="search" value="{{ request('search') }}" placeholder="Search businesses"></label></div>
<div><label>Approval status<select name="status"><option value="">All stages</option>@foreach(['draft','submitted','implementation','secretary','committee','approved','signed','rejected','cancelled','closed'] as $status)<option value="{{ $status }}" @selected(request('status')===$status)>{{ str($status)->headline() }}</option>@endforeach</select></label></div>
<div><label>Support type<select name="support_type"><option value="">All support</option><option value="grant" @selected(request('support_type')==='grant')>Grant</option><option value="repayable" @selected(request('support_type')==='repayable')>Repayable principal</option></select></label></div>
<div><label>Attention<select name="reminders"><option value="">All cases</option><option value="1" @selected(request('reminders'))>Due reviews / repayments</option></select></label></div>
<div><label>Records<select name="archived"><option value="0">Current records</option><option value="1" @selected(request('archived'))>Archived history</option></select></label></div>
</div><div class="qactions"><button class="qbtn">Apply filters</button>@if(auth()->user()?->hasPermission('business.view'))<a class="qbtn secondary" href="{{ route('business.applications.index') }}">Clear</a>@endif</div></form>
<div class="qcard qtable"><table><thead><tr><th>Business / beneficiary</th><th>Original fund</th><th>Approval</th><th>Funding / operations</th><th>Principal</th><th>Attention</th></tr></thead><tbody>
@forelse($applications as $application)
<tr><td>@if(auth()->user()?->hasPermission('business.view'))<a href="{{ route('business.applications.show',$application) }}">{{ $application->number }} · {{ $application->name }}</a>@endif<small>{{ $application->beneficiary?->full_name }}</small></td><td>{{ $application->fund?->name }}<small>{{ str($application->support_type)->headline() }}</small></td><td>{{ str($application->status)->headline() }}<small>PKR {{ \App\Domain\Finance\Support\Money::format($application->approved_amount) }}</small></td><td>{{ str($application->funding_status)->headline() }}<small>{{ str($application->operating_status)->headline() }}</small></td><td>{{ $application->recoveryStatus() }}<small>Outstanding PKR {{ \App\Domain\Finance\Support\Money::format(\App\Domain\Finance\Support\Money::decimal($application->outstandingMinor())) }}</small></td><td>{{ !$application->reminder?->resolved_at ? $application->reminder?->message : '—' }}</td></tr>
@empty<tr><td colspan="6">No business applications match these filters. Add a category and a verified beneficiary to begin.</td></tr>@endforelse
</tbody></table>{{ $applications->links('pagination::bootstrap-5') }}</div>
<p class="qmuted">Reminders refresh daily at 08:10 Asia/Karachi when the scheduler is running. Recovery returns to the original fund once.</p>
</div>@endsection
