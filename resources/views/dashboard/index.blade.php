@extends('layouts.app')

@section('title', 'Dashboard')

@push('styles')

<style>

    /* =========================================
       STAT CARDS
    ========================================= */

    .stats-grid {
        display: grid;

        grid-template-columns:
            repeat(4, minmax(0, 1fr));

        gap: 16px;

        margin-bottom: 20px;
    }

    .stat-card {
        background: white;

        border: 1px solid var(--border);

        border-radius: 14px;

        padding: 18px;

        transition: transform .18s ease,
                    box-shadow .18s ease,
                    border-color .18s ease;
    }

    .stat-card:hover {
        transform: translateY(-2px);

        border-color: #6e7370;

        box-shadow:
            0 10px 30px rgba(25, 55, 45, .06);
    }

    .stat-top {
        display: flex;

        justify-content: space-between;
        align-items: flex-start;
    }

    .stat-icon {
        width: 40px;
        height: 40px;

        border-radius: 11px;

        display: flex;
        align-items: center;
        justify-content: center;

        color: var(--primary);

        background: var(--primary-soft);

        font-size: 18px;
    }

    .stat-trend {
        font-size: 16px;

        color: #448061;

        background: #eef8f1;

        padding: 5px 7px;

        border-radius: 20px;
    }

    .stat-label {
        margin-top: 17px;

        color: var(--muted);

        font-size: 16px;
    }

    .stat-value {
        margin-top: 4px;

        font-size: 24px;

        line-height: 1.1;

        font-weight: 700;

        letter-spacing: -.5px;
    }

    .stat-description {
        margin-top: 6px;

        font-size: 16px;

        color: #6f7672;
    }


    /* =========================================
       CONTENT CARDS
    ========================================= */

    .dashboard-grid {
        display: grid;

        grid-template-columns:
            minmax(0, 1.15fr)
            minmax(0, .85fr);

        gap: 18px;

        margin-bottom: 18px;
    }

    .dashboard-card {
        background: white;

        border: 1px solid var(--border);

        border-radius: 14px;

        overflow: hidden;
    }

    .card-header-custom {
        display: flex;

        justify-content: space-between;
        align-items: center;

        padding: 17px 18px;

        border-bottom: 1px solid #eef1ef;
    }

    .card-header-title {
        font-size: 16px;
        font-weight: 650;
    }

    .card-header-subtitle {
        margin-top: 3px;

        color: var(--muted);

        font-size: 16px;
    }

    .view-all {
        color: var(--primary);

        font-size: 16px;

        font-weight: 650;
    }

    .card-body-custom {
        padding: 18px;
    }


    /* =========================================
       FUND OVERVIEW
    ========================================= */

    .fund-layout {
        display: grid;

        grid-template-columns:
            180px minmax(0, 1fr);

        gap: 25px;

        align-items: center;
    }

    .fund-chart {
        width: 155px;
        height: 155px;

        border-radius: 50%;

        background:
            conic-gradient(
                #173f35 0% 38%,
                #508c75 38% 59%,
                #c89b3c 59% 73%,
                #7b8e86 73% 87%,
                #d8dfdc 87% 100%
            );

        display: flex;

        align-items: center;
        justify-content: center;

        margin: auto;
    }

    .fund-chart-inner {
        width: 99px;
        height: 99px;

        border-radius: 50%;

        background: white;

        display: flex;

        flex-direction: column;

        align-items: center;
        justify-content: center;
    }

    .fund-chart-inner strong {
        font-size: 18px;
    }

    .fund-chart-inner span {
        margin-top: 3px;

        color: var(--muted);

        font-size: 16px;
    }

    .fund-list {
        display: flex;

        flex-direction: column;

        gap: 12px;
    }

    .fund-row {
        display: grid;

        grid-template-columns: 9px 1fr auto;

        align-items: center;

        gap: 9px;

        font-size: 16px;
    }

    .fund-dot {
        width: 8px;
        height: 8px;

        border-radius: 50%;
    }

    .fund-name {
        color: #66736d;
    }

    .fund-amount {
        font-weight: 650;
    }


    /* =========================================
       PROJECT STATUS
    ========================================= */

    .status-chart {
        height: 205px;

        display: flex;

        align-items: flex-end;

        justify-content: space-around;

        padding-top: 15px;
    }

    .status-bar-item {
        height: 100%;

        display: flex;

        flex-direction: column;

        align-items: center;

        justify-content: flex-end;

        gap: 8px;
    }

    .status-bar {
        width: 32px;

        min-height: 15px;

        border-radius: 7px 7px 3px 3px;

        background: var(--primary);

        position: relative;
    }

    .status-bar.review {
        background: #c89b3c;
    }

    .status-bar.approved {
        background: #6c9583;
    }

    .status-bar.progress {
        background: #416f61;
    }

    .status-bar.completed {
        background: #9cab99;
    }

    .status-count {
        position: absolute;

        top: -18px;

        width: 100%;

        text-align: center;

        font-size: 16px;

        font-weight: 700;
    }

    .status-label {
        font-size: 16px;

        color: var(--muted);
    }


    /* =========================================
       TRANSACTIONS
    ========================================= */

    .table-wrap {
        overflow-x: auto;
    }

    .dashboard-table {
        width: 100%;

        border-collapse: collapse;

        min-width: 620px;
    }

    .dashboard-table th {
        padding: 10px 18px;

        color: #6f7672;

        font-size: 16px;

        font-weight: 650;

        text-transform: uppercase;

        letter-spacing: .6px;

        background: #fafcfb;
    }

    .dashboard-table td {
        padding: 13px 18px;

        border-top: 1px solid #f0f3f1;

        font-size: 16px;
    }

    .transaction-type {
        display: flex;

        align-items: center;

        gap: 8px;
    }

    .transaction-icon {
        width: 27px;
        height: 27px;

        border-radius: 8px;

        display: flex;

        align-items: center;
        justify-content: center;

        background: #eef6f1;

        color: var(--primary);

        font-size: 16px;
    }

    .transaction-name {
        font-weight: 600;
    }

    .transaction-sub {
        color: #6f7672;

        font-size: 16px;

        margin-top: 2px;
    }

    .amount {
        font-weight: 700;
    }

    .status-badge {
        display: inline-flex;

        padding: 5px 8px;

        border-radius: 30px;

        font-size: 16px;

        font-weight: 650;
    }

    .status-received {
        background: #edf7f0;
        color: #3d8059;
    }

    .status-approved {
        background: #edf3f8;
        color: #51708a;
    }


    /* =========================================
       LOWER GRID
    ========================================= */

    .lower-grid {
        display: grid;

        grid-template-columns:
            minmax(0, 1fr)
            minmax(0, 1fr);

        gap: 18px;
    }

    .task-item {
        display: flex;

        align-items: flex-start;

        gap: 11px;

        padding: 13px 0;

        border-bottom: 1px solid #f0f3f1;
    }

    .task-item:first-child {
        padding-top: 0;
    }

    .task-item:last-child {
        padding-bottom: 0;
        border-bottom: none;
    }

    .task-check {
        width: 17px;
        height: 17px;

        border: 1px solid #ced8d3;

        border-radius: 5px;

        flex-shrink: 0;
    }

    .task-info {
        min-width: 0;
        flex: 1;
    }

    .task-title {
        font-size: 16px;

        font-weight: 600;
    }

    .task-meta {
        margin-top: 4px;

        display: flex;

        gap: 8px;

        color: #6c7570;

        font-size: 16px;
    }

    .task-date {
        color: #a26351;
    }


    /* =========================================
       RESPONSIVE
    ========================================= */

    @media (max-width: 1150px) {

        .stats-grid {
            grid-template-columns:
                repeat(2, minmax(0, 1fr));
        }
    }

    @media (max-width: 900px) {

        .dashboard-grid,
        .lower-grid {
            grid-template-columns: 1fr;
        }
    }

    @media (max-width: 550px) {

        .stats-grid {
            grid-template-columns: 1fr;
        }

        .fund-layout {
            grid-template-columns: 1fr;
        }

        .fund-chart {
            margin-bottom: 10px;
        }
    }

</style>

@endpush


@section('content')

    <!-- PAGE HEADER -->

    <div class="page-heading">

        <div>

            <h1>
                Dashboard
            </h1>

            <p>
                Welcome back, {{ auth()->user()->name }}. Here's your organization's overview.
            </p>

        </div>

        <div class="date">
            {{ now()->format('l, d F Y') }}
        </div>

    </div>


    <!-- =========================================
         STATISTICS
    ========================================== -->

    <div class="stats-grid">

        <!-- Members -->

        <div class="stat-card">

            <div class="stat-top">

                <div class="stat-icon">
                    <i class="bi bi-people"></i>
                </div>

                <div class="stat-trend">
                    Live
                </div>

            </div>

            <div class="stat-label">
                Total Members
            </div>

            <div class="stat-value">
                {{ number_format($totalMembers) }}
            </div>

            <div class="stat-description">
                {{ number_format($activeMembers) }} currently active
            </div>

        </div>


        @can('projects.view')
        <div class="stat-card"><div class="stat-top"><div class="stat-icon"><i class="bi bi-kanban"></i></div></div><div class="stat-label">Active Projects</div><div class="stat-value">{{ $activeProjects }}</div><div class="stat-description"><a href="{{ route('projects.index',['status'=>'in_progress']) }}">Projects currently In Progress</a></div></div>
        @endcan

        <!-- Donations -->

        <div class="stat-card">

            <div class="stat-top">

                <div class="stat-icon">
                    <i class="bi bi-wallet2"></i>
                </div>

                <div class="stat-trend">
                    Live
                </div>

            </div>

            <div class="stat-label">
                Donations This Month
            </div>

            <div class="stat-value">
                PKR {{ \App\Domain\Finance\Support\Money::format($donationsThisMonth) }}
            </div>

            <div class="stat-description">
                {{ \App\Domain\Finance\Support\Money::format($donationsCountThisMonth) }} donations recorded
            </div>

        </div>


        <!-- Fund Balance -->

        <div class="stat-card">

            <div class="stat-top">

                <div class="stat-icon">
                    <i class="bi bi-safe2"></i>
                </div>

                <div class="stat-trend">
                    Live
                </div>

            </div>

            <div class="stat-label">
                Total Fund Balance
            </div>

            <div class="stat-value">
                PKR {{ \App\Domain\Finance\Support\Money::format($totalFundBalance) }}
            </div>

            <div class="stat-description">
                Restricted + unrestricted
            </div>

        </div>

    </div>


    <!-- =========================================
         FUND + PROJECT STATUS
    ========================================== -->

    <div class="dashboard-grid">

        <!-- Fund Overview -->

        <div class="dashboard-card">

            <div class="card-header-custom">

                <div>

                    <div class="card-header-title">
                        Fund Overview
                    </div>

                    <div class="card-header-subtitle">
                        Current distribution of organizational funds
                    </div>

                </div>

                <a
                    href="{{ route('finance.funds.index') }}"
                    class="view-all"
                >
                    View details
                </a>

            </div>

            <div class="card-body-custom">
                @php
                
                    $fundChartColors = [
                        '#173f35',
                        '#508c75',
                        '#c89b3c',
                        '#7b8e86',
                        '#6c9583',
                        '#9cab99',
                        '#55758d',
                        '#8c77a3',
                    ];

                    $chartTotal = max(\App\Domain\Finance\Support\Money::minor($totalFundBalance), 0);
                    $chartSegments = [];
                    $currentDegree = 0;

                    if ($chartTotal > 0) {
                        foreach ($fundOverview as $index => $fund) {
                            $balance = max(\App\Domain\Finance\Support\Money::minor($fund['balance']), 0);

                            if ($balance <= 0) {
                                continue;
                            }

                            $nextDegree = $currentDegree
                                + (($balance / $chartTotal) * 360);

                            $color = $fundChartColors[
                                $index % count($fundChartColors)
                            ];

                            $chartSegments[] =
                                $color . ' '
                                . number_format($currentDegree, 2, '.', '')
                                . 'deg '
                                . number_format($nextDegree, 2, '.', '')
                                . 'deg';

                            $currentDegree = $nextDegree;
                        }
                    }

                    if (empty($chartSegments)) {
                        $chartSegments[] = '#d8dfdc 0deg 360deg';
                    } elseif ($currentDegree < 360) {
                        $chartSegments[] =
                            '#d8dfdc '
                            . number_format($currentDegree, 2, '.', '')
                            . 'deg 360deg';
                    }

                    $fundChartGradient =
                        'conic-gradient('
                        . implode(', ', $chartSegments)
                        . ')';
                $fundTotalDisplay = \App\Domain\Finance\Support\Money::format($totalFundBalance);
                @endphp

                <div class="fund-layout">

                    <div
                        class="fund-chart"
                        style="background: {{ $fundChartGradient }};"
                    >

                        <div class="fund-chart-inner">

                            <strong>
                                {{ $fundTotalDisplay }}
                            </strong>

                            <span>
                                Total PKR
                            </span>

                        </div>

                    </div>


                    <div class="fund-list">

                        @forelse($fundOverview as $index => $fund)

                            @php
                                $fundColor = $fundChartColors[
                                    $index % count($fundChartColors)
                                ];
                            @endphp

                            <div class="fund-row">

                                <div
                                    class="fund-dot"
                                    style="background: {{ $fundColor }};"
                                ></div>

                                <div class="fund-name">
                                    {{ $fund['name'] }}
                                </div>

                                <div class="fund-amount">
                                    PKR {{ \App\Domain\Finance\Support\Money::format($fund['balance']) }}
                                </div>

                            </div>

                        @empty

                            <div style="
                                color:var(--muted);
                                padding:20px 0;
                                text-align:center;
                            ">
                                No active funds found.
                            </div>

                        @endforelse

                    </div>

                </div>

            </div>

        </div>


        @can('projects.view')
        <div class="dashboard-card"><div class="card-header-custom"><h3>Project Status</h3><a href="{{ route('projects.index') }}">View projects</a></div><div class="card-body">
        @foreach(['draft','approved','in_progress','completed','closed','cancelled'] as $status)<p>{{ str($status)->headline() }} <strong>{{ $projectStatusCounts[$status] ?? 0 }}</strong></p>@endforeach
        </div></div>
        @endcan

    </div>


    <!-- =========================================
         TRANSACTIONS
    ========================================== -->

    <div class="dashboard-card mb-3">

        <div class="card-header-custom">

            <div>

                <div class="card-header-title">
                    Recent Transactions
                </div>

                <div class="card-header-subtitle">
                    Latest financial activity recorded in the system
                </div>

            </div>

            <a
                href="{{ route('finance.transactions.index') }}"
                class="view-all"
            >
                View all 
            </a>

        </div>


        <div class="table-wrap">

            <table class="dashboard-table">

                <thead>

                    <tr>
                        <th>Date</th>
                        <th>Transaction</th>
                        <th>Fund</th>
                        <th>Amount</th>
                        <th>Status</th>
                    </tr>

                </thead>

                <tbody>

                    @forelse($recentTransactions as $transaction)

                        @php
                            $isCredit = $transaction->direction === 'credit';

                            $typeLabel = match ($transaction->type) {
                                'donation' => 'Donation',
                                'expense' => 'Expense',
                                default => ucfirst($transaction->type),
                            };

                            $icon = $isCredit
                                ? 'bi-arrow-down-left'
                                : 'bi-arrow-up-right';

                            $amountPrefix = $isCredit ? '+' : '-';

                            $statusClass = $transaction->status === 'posted'
                                ? 'status-received'
                                : 'status-approved';
                        @endphp

                        <tr>

                            <td>
                                {{ $transaction->transaction_date->format('d M Y') }}
                            </td>

                            <td>

                                <div class="transaction-type">

                                    <div class="transaction-icon">
                                        <i class="bi {{ $icon }}"></i>
                                    </div>

                                    <div>

                                        <div class="transaction-name">
                                            {{ $typeLabel }}
                                        </div>

                                        <div class="transaction-sub">
                                            {{ $transaction->transaction_number }}
                                        </div>

                                    </div>

                                </div>

                            </td>

                            <td>
                                {{ $transaction->fund?->name ?? '—' }}
                            </td>

                            <td class="amount">
                                {{ $amountPrefix }}
                                PKR {{ \App\Domain\Finance\Support\Money::format($transaction->amount) }}
                            </td>

                            <td>

                                <span class="status-badge {{ $statusClass }}">
                                    {{ ucfirst($transaction->status) }}
                                </span>

                            </td>

                        </tr>

                    @empty

                        <tr>

                            <td
                                colspan="5"
                                style="
                                    text-align:center;
                                    padding:30px;
                                    color:var(--muted);
                                "
                            >
                                No posted transactions found.
                            </td>

                        </tr>

                    @endforelse

                </tbody>

            </table>

        </div>

    </div>


    <!-- =========================================
         TASKS + NOTIFICATIONS
    ========================================== -->

    <div class="lower-grid">
        @include('dashboard.partials.in-development', [
            'title' => 'Upcoming Tasks',
            'message' => 'Task scheduling is in development. Upcoming tasks will appear here when this feature is available.',
        ])
        @include('dashboard.partials.in-development', [
            'title' => 'Recent Activity',
            'message' => 'The activity feed is in development. Organization updates will appear here when this feature is available.',
        ])
    </div>

@if($businessSummary !== null)
<div class="card" style="margin-top:20px;padding:20px"><h3><a href="{{ route('business.applications.index') }}">Small Business assistance</a></h3><p>Real posted funding and principal recovery through today.</p>
<div class="stats-grid">@foreach(['released'=>'Total released','recovered'=>'Principal recovered','outstanding'=>'Outstanding principal'] as $key=>$label)<div class="stat-card"><span>{{ $label }}</span><h3>PKR {{ \App\Domain\Finance\Support\Money::format(\App\Domain\Finance\Support\Money::decimal($businessSummary[$key])) }}</h3></div>@endforeach<div class="stat-card"><span>Businesses started</span><h3>{{ $businessSummary['started'] }}</h3></div></div></div>
@endif
@endsection
