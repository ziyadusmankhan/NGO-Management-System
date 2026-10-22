@extends('layouts.app')

@section('title', 'Assistance Reviews')

@push('styles')
<style>

    .page-header {
        display:flex;
        justify-content:space-between;
        align-items:flex-start;
        gap:20px;
        margin-bottom:24px;
    }

    .page-header h1 {
        margin:0;
        font-size:25px;
        font-weight:700;
        letter-spacing:-.5px;
    }

    .page-header p {
        margin:7px 0 0;
        color:var(--muted);
        font-size:15px;
    }

    .ui-btn {
        height:40px;
        padding:0 14px;
        border-radius:10px;
        display:inline-flex;
        align-items:center;
        justify-content:center;
        gap:7px;
        border:1px solid var(--border);
        background:#fff;
        color:#637069;
        font-size:14px;
        font-weight:650;
        text-decoration:none;
    }

    .stats {
        display:grid;
        grid-template-columns:repeat(4,minmax(0,1fr));
        gap:12px;
        margin-bottom:18px;
    }

    .stat {
        background:#fff;
        border:1px solid var(--border);
        border-radius:13px;
        padding:16px;
        text-decoration:none;
        color:inherit;
    }

    .stat-label {
        color:#6d7671;
        font-size:13px;
    }

    .stat-value {
        margin-top:5px;
        font-size:25px;
        font-weight:750;
    }

    .toolbar {
        background:#fff;
        border:1px solid var(--border);
        border-radius:14px;
        padding:14px;
        margin-bottom:18px;

        display:flex;
        gap:9px;
        flex-wrap:wrap;
        align-items:center;
    }

    .search {
        flex:1;
        min-width:220px;
        height:40px;
        padding:0 12px;
        border:1px solid var(--border);
        border-radius:9px;
        outline:none;
        font-size:14px;
    }

    .filter {
        height:40px;
        padding:0 11px;
        border:1px solid var(--border);
        border-radius:9px;
        background:#fff;
        font-size:14px;
    }

    .card {
        background:#fff;
        border:1px solid var(--border);
        border-radius:14px;
        overflow:hidden;
    }

    .table-wrap {
        overflow-x:auto;
    }

    table {
        width:100%;
        min-width:850px;
        border-collapse:collapse;
    }

    th {
        padding:11px 16px;
        background:#fafcfb;
        border-bottom:1px solid #edf1ee;
        color:#6d7671;
        font-size:12px;
        text-transform:uppercase;
        letter-spacing:.5px;
        text-align:left;
    }

    td {
        padding:14px 16px;
        border-bottom:1px solid #f0f3f1;
        font-size:14px;
        vertical-align:middle;
    }

    tr:last-child td {
        border-bottom:none;
    }

    .sub {
        margin-top:3px;
        color:#7b8580;
        font-size:12px;
    }

    .action {
        display:inline-flex;
        align-items:center;
        gap:5px;
        color:var(--primary);
        text-decoration:none;
        font-size:13px;
        font-weight:650;
    }

    .empty {
        padding:45px 20px;
        text-align:center;
        color:#737d78;
    }

    .pagination {
        padding:15px;
        border-top:1px solid #eef1ef;
    }

    @media(max-width:900px) {
        .stats {
            grid-template-columns:repeat(2,minmax(0,1fr));
        }
    }

    @media(max-width:600px) {
        .page-header {
            flex-direction:column;
        }

        .stats {
            grid-template-columns:1fr;
        }
    }

</style>
@endpush


@section('content')
@php
    $labels = ['overdue' => 'Overdue reviews', 'today' => 'Due today', 'upcoming' => 'Next 30 days', 'none' => 'No review date', 'follow_up' => 'Follow-up required', 'closure' => 'Awaiting closure', 'ended' => 'Period ended'];
@endphp
<div class="page-header">
    <div><h1>Assistance Reviews</h1><p>Review dates, follow-up actions and closure decisions · {{ $today->format('d M Y') }} ({{ config('welfare.timezone') }})</p></div>
    <a href="{{ route('welfare.assistance.index') }}" class="ui-btn">Welfare Assistance</a>
</div>
<div class="stats">
    @foreach($labels as $filter => $label)
        <a href="{{ route('welfare.assistance.reviews.index', ['filter' => $filter]) }}" class="stat">
            <div class="stat-label">{{ $label }}</div><div class="stat-value">{{ $counts[$filter] }}</div>
        </a>
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
                <td><a href="{{ route('welfare.assistance.show', $assistance) }}">{{ $assistance->assistance_number }}</a><div class="sub">{{ \Illuminate\Support\Str::headline($assistance->frequency ?? 'one_time') }}</div></td>
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
                        <a href="{{ route('welfare.assistance.reviews.create', $assistance) }}" class="action">Record review</a>
                    @else
                        <a href="{{ route('welfare.assistance.show', $assistance) }}" class="action">View</a>
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
            <li><a href="{{ route('welfare.assistance.show', $reminder->assistance_id) }}">{{ $reminder->assistance->assistance_number }}</a>
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
