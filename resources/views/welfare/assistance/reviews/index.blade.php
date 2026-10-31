@extends('layouts.app')

@section('title', 'Assistance Reviews')

@push('styles')
<link rel="stylesheet" href="{{ asset('css/views/welfare-assistance-reviews-index-1.css') }}">
@endpush


@section('content')
@if($errors->any()) @include('shared.action-feedback') @endif
@php
    $labels = ['overdue' => 'Overdue reviews', 'today' => 'Due today', 'upcoming' => 'Next 30 days', 'none' => 'No review date', 'follow_up' => 'Follow-up required', 'closure' => 'Awaiting closure', 'ended' => 'Period ended'];
@endphp
<div class="page-header">
    <div><h1>Assistance Reviews</h1><p>Review dates, follow-up actions and closure decisions · {{ $today->format('d M Y') }} ({{ config('welfare.timezone') }})</p></div>
    @if(auth()->user()?->hasPermission('welfare.assistance.view'))<a href="{{ route('welfare.assistance.index') }}" class="ui-btn">Welfare Assistance</a>@endif
    @include('shared.finance-welfare-export', ['exportKind'=>'reviews'])
</div>
<div class="stats">
    @foreach($labels as $filter => $label)
        @if(auth()->user()?->hasPermission('welfare.assistance.view') && auth()->user()?->hasPermission('welfare.reviews.view'))<a href="{{ route('welfare.assistance.reviews.index', ['filter' => $filter]) }}" class="stat">
            <div class="stat-label">{{ $label }}</div><div class="stat-value">{{ $counts[$filter] }}</div>
        </a>@endif
    @endforeach
</div>
<form method="GET" action="{{ route('welfare.assistance.reviews.index') }}" class="toolbar">
    <input type="search" name="search" class="search" value="{{ request('search') }}" placeholder="Search assistance or beneficiary..." aria-label="Search assistance or beneficiary">
    <select name="filter" class="filter" aria-label="Monitoring status">
        <option value="">All approved assistance</option>
        @foreach($labels as $filter => $label)<option value="{{ $filter }}" @selected(request('filter') === $filter)>{{ $label }}</option>@endforeach
    </select>
    <button type="submit" class="ui-btn">Filter</button>
</form>
<div class="card"><div class="table-wrap"><table>
    <thead><tr><th>Assistance</th><th>Beneficiary / Fund</th><th>Total approved</th><th>Next review / End date</th><th>Latest findings and action</th><th></th></tr></thead>
    <tbody>
        @forelse($assistances as $assistance)
            <tr>
                <td>@if(auth()->user()?->hasPermission('welfare.assistance.view'))<a href="{{ route('welfare.assistance.show', $assistance) }}">{{ $assistance->assistance_number }}</a>@endif<div class="sub">{{ \Illuminate\Support\Str::headline($assistance->frequency ?? 'one_time') }}</div></td>
                <td>{{ $assistance->beneficiary?->full_name ?? 'Unavailable' }}<div class="sub">{{ $assistance->fund?->name ?? 'Unavailable fund' }}</div></td>
                <td>PKR {{ \App\Domain\Finance\Support\Money::format($assistance->amount ?? '0.00') }}</td>
                <td>
                    @if($assistance->next_review_date)
                        {{ $assistance->next_review_date->format('d M Y') }}
                        <div class="sub">{{ $assistance->next_review_date->toDateString() < $today->toDateString() ? 'Overdue' : ($assistance->next_review_date->toDateString() === $today->toDateString() ? 'Due today' : 'Upcoming') }}</div>
                    @else
                        Not scheduled
                    @endif
                    @if($assistance->end_date)
                        <div class="sub">Ends {{ $assistance->end_date->format('d M Y') }}{{ $assistance->end_date->toDateString() < $today->toDateString() ? ' · Period ended — review for closure' : '' }}</div>
                    @endif
                </td>
                <td>
                    @if($assistance->latestReview)
                        <strong>{{ $assistance->latestReview->outcome === 'no_longer_needed' ? 'Awaiting approver closure' : \Illuminate\Support\Str::headline($assistance->latestReview->outcome) }}</strong>
                        <div class="sub">{{ $assistance->latestReview->review_date->format('d M Y') }} · {{ $assistance->latestReview->reviewedBy?->name ?? 'Unavailable reviewer' }}</div>
                        <div>{{ \Illuminate\Support\Str::limit($assistance->latestReview->notes, 160) }}</div>
                        @if($assistance->latestReview->follow_up_action)<div>Action: {{ \Illuminate\Support\Str::limit($assistance->latestReview->follow_up_action, 160) }}</div>@endif
                    @else
                        No review recorded
                    @endif
                </td>
                <td>
                    @if(auth()->user()->hasPermission('welfare.reviews.manage'))
                        @if(auth()->user()?->hasPermission('welfare.assistance.view') && auth()->user()?->hasPermission('welfare.reviews.view') && auth()->user()?->hasPermission('welfare.reviews.manage'))<a href="{{ route('welfare.assistance.reviews.create', $assistance) }}" class="action">Record review</a>@endif
                    @else
                        @if(auth()->user()?->hasPermission('welfare.assistance.view'))<a href="{{ route('welfare.assistance.show', $assistance) }}" class="action">View</a>@endif
                    @endif
                </td>
            </tr>
        @empty
            <tr><td colspan="6" class="empty">No approved assistance matches this monitoring queue.</td></tr>
        @endforelse
    </tbody>
</table></div><div class="pagination">{{ $assistances->links() }}</div></div>
<div class="card p-3 mt-3">
    <h2 class="h5">Scheduled reminders</h2>
    <p>The daily reminder check runs at 08:00 {{ config('welfare.timezone') }} when the application scheduler is running. Review queues above always use current records.</p>
    <ul>
        @forelse($reminders as $reminder)
            <li>@if(auth()->user()?->hasPermission('welfare.assistance.view'))<a href="{{ route('welfare.assistance.show', $reminder->assistance_id) }}">{{ $reminder->assistance->assistance_number }}</a>@endif
                · {{ ['review_due' => 'Review due', 'period_ended' => 'Period ended — closure decision needed', 'closure_requested' => 'Closure recommendation awaiting approval'][$reminder->kind] ?? \Illuminate\Support\Str::headline($reminder->kind) }}
                · {{ $reminder->due_date->format('d M Y') }}
            </li>
        @empty
            <li>No open scheduled reminders.</li>
        @endforelse
    </ul>
    {{ $reminders->links() }}
</div>
@endsection
