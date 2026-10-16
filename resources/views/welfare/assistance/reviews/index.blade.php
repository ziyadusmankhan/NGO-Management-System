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

    .stat-meta {
        margin-top:3px;
        color:#8a938e;
        font-size:12px;
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

    .number {
        font-weight:700;
    }

    .sub {
        margin-top:3px;
        color:#7b8580;
        font-size:12px;
    }

    .badge {
        display:inline-flex;
        padding:5px 9px;
        border-radius:20px;
        font-size:12px;
        font-weight:700;
        white-space:nowrap;
    }

    .overdue {
        background:#fceeee;
        color:#a34f4f;
    }

    .today {
        background:#fff6e8;
        color:#9b6c2e;
    }

    .upcoming {
        background:#edf3f8;
        color:#52718b;
    }

    .none {
        background:#f1f3f2;
        color:#6b7570;
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

<div class="page-header">

    <div>
        <h1>Assistance Reviews</h1>

        <p>
            Monitor welfare assistance review dates and record follow-up decisions.
        </p>
    </div>

    <a
        href="{{ route('welfare.assistance.index') }}"
        class="ui-btn"
    >
        <i class="bi bi-arrow-left"></i>
        Welfare Assistance
    </a>

</div>


<div class="stats">

    <a
        href="{{ route(
            'welfare.assistance.reviews.index',
            ['filter' => 'overdue']
        ) }}"
        class="stat"
    >
        <div class="stat-label">
            Overdue
        </div>

        <div class="stat-value">
            {{ $overdue }}
        </div>

        <div class="stat-meta">
            Review date has passed
        </div>
    </a>


    <a
        href="{{ route(
            'welfare.assistance.reviews.index',
            ['filter' => 'today']
        ) }}"
        class="stat"
    >
        <div class="stat-label">
            Due Today
        </div>

        <div class="stat-value">
            {{ $dueToday }}
        </div>

        <div class="stat-meta">
            Requires attention today
        </div>
    </a>


    <a
        href="{{ route(
            'welfare.assistance.reviews.index',
            ['filter' => 'upcoming']
        ) }}"
        class="stat"
    >
        <div class="stat-label">
            Upcoming
        </div>

        <div class="stat-value">
            {{ $upcoming }}
        </div>

        <div class="stat-meta">
            Next 30 days
        </div>
    </a>


    <a
        href="{{ route(
            'welfare.assistance.reviews.index',
            ['filter' => 'none']
        ) }}"
        class="stat"
    >
        <div class="stat-label">
            No Review Date
        </div>

        <div class="stat-value">
            {{ $noReviewDate }}
        </div>

        <div class="stat-meta">
            Approved assistance
        </div>
    </a>

</div>


<form
    method="GET"
    action="{{ route('welfare.assistance.reviews.index') }}"
    class="toolbar"
>

    <input
        type="search"
        name="search"
        class="search"
        value="{{ request('search') }}"
        placeholder="Search assistance or beneficiary..."
    >


    <select
        name="filter"
        class="filter"
    >

        <option value="">
            All Reviews
        </option>

        <option
            value="overdue"
            @selected(request('filter') === 'overdue')
        >
            Overdue
        </option>

        <option
            value="today"
            @selected(request('filter') === 'today')
        >
            Due Today
        </option>

        <option
            value="upcoming"
            @selected(request('filter') === 'upcoming')
        >
            Upcoming
        </option>

        <option
            value="none"
            @selected(request('filter') === 'none')
        >
            No Review Date
        </option>

    </select>


    <button
        type="submit"
        class="ui-btn"
    >
        <i class="bi bi-search"></i>
        Filter
    </button>

</form>


<div class="card">

    <div class="table-wrap">

        <table>

            <thead>

                <tr>
                    <th>Assistance</th>
                    <th>Beneficiary</th>
                    <th>Fund</th>
                    <th>Amount</th>
                    <th>Next Review</th>
                    <th>Last Review</th>
                    <th></th>
                </tr>

            </thead>


            <tbody>

                @forelse($assistances as $assistance)

                    @php
                        $reviewDate = $assistance->next_review_date;

                        if (!$reviewDate) {
                            $reviewLabel = 'No date';
                            $reviewClass = 'none';
                        } elseif ($reviewDate->isBefore(today())) {
                            $reviewLabel = 'Overdue';
                            $reviewClass = 'overdue';
                        } elseif ($reviewDate->isToday()) {
                            $reviewLabel = 'Due Today';
                            $reviewClass = 'today';
                        } else {
                            $reviewLabel = 'Upcoming';
                            $reviewClass = 'upcoming';
                        }

                        $lastReview = $assistance->reviews
                            ->sortByDesc('review_date')
                            ->first();
                    @endphp


                    <tr>

                        <td>

                            <div class="number">
                                {{ $assistance->assistance_number }}
                            </div>

                            <div class="sub">
                                {{ str_replace(
                                    '_',
                                    ' ',
                                    ucfirst($assistance->assistance_type)
                                ) }}
                            </div>

                        </td>


                        <td>

                            <div class="number">
                                {{ $assistance->beneficiary->full_name }}
                            </div>

                            <div class="sub">
                                {{ $assistance->beneficiary->beneficiary_number }}
                            </div>

                        </td>


                        <td>
                            {{ $assistance->fund?->name ?: '—' }}
                        </td>


                        <td>
                            PKR {{ number_format($assistance->amount, 0) }}
                        </td>


                        <td>

                            @if($reviewDate)
                                <div>
                                    {{ $reviewDate->format('d M Y') }}
                                </div>
                            @endif

                            <div
                                class="badge {{ $reviewClass }}"
                                style="margin-top:4px;"
                            >
                                {{ $reviewLabel }}
                            </div>

                        </td>


                        <td>

                            @if($lastReview)

                                <div>
                                    {{ $lastReview->review_date->format('d M Y') }}
                                </div>

                                <div class="sub">
                                    {{ ucfirst(
                                        str_replace(
                                            '_',
                                            ' ',
                                            $lastReview->outcome
                                        )
                                    ) }}
                                </div>

                            @else

                                —
                                
                            @endif

                        </td>


                        <td>

                            <a
                                href="{{ route(
                                    'welfare.assistance.reviews.create',
                                    $assistance
                                ) }}"
                                class="action"
                            >
                                <i class="bi bi-clipboard-check"></i>
                                Review
                            </a>

                        </td>

                    </tr>

                @empty

                    <tr>

                        <td
                            colspan="7"
                            class="empty"
                        >
                            No approved welfare assistance matches this review queue.
                        </td>

                    </tr>

                @endforelse

            </tbody>

        </table>

    </div>


    @if($assistances->hasPages())

        <div class="pagination">
            {{ $assistances->links() }}
        </div>

    @endif

</div>

@endsection