@extends('layouts.app')

@section('title', 'Funds')

@push('styles')
<style>
    .page-header {
        display:flex;
        justify-content:space-between;
        align-items:flex-end;
        gap:20px;
        margin-bottom:24px;
    }

    .page-header h1 {
        margin:0;
        font-size: 25px;
        font-weight:700;
        letter-spacing:-.5px;
    }

    .page-header p {
        margin:7px 0 0;
        color:var(--muted);
        font-size: 16px;
    }

    .actions {
        display:flex;
        gap:8px;
    }

    .ui-btn {
        height:40px;
        padding:0 14px;
        border-radius:10px;
        display:inline-flex;
        align-items:center;
        justify-content:center;
        gap:7px;
        font-size: 15px;
        font-weight:650;
        border:1px solid var(--border);
        background:#fff;
        color:#637069;
        text-decoration:none;
        cursor:pointer;
    }

    .ui-btn.primary {
        background:var(--primary);
        border-color:var(--primary);
        color:#fff;
    }

    .summary-grid {
        display:grid;
        grid-template-columns:repeat(5,minmax(0,1fr));
        gap:12px;
        margin-bottom:18px;
    }

    .summary-card {
        background:#fff;
        border:1px solid var(--border);
        border-radius:13px;
        padding:15px;
    }

    .summary-label {
        color:var(--muted);
        font-size: 14px;
    }

    .summary-value {
        margin-top:6px;
        font-size: 20px;
        font-weight:700;
    }

    .summary-meta {
        margin-top:4px;
        color: #6d7572;
        font-size: 13px;
    }

    .fund-grid {
        display:grid;
        grid-template-columns:repeat(3,minmax(0,1fr));
        gap:13px;
    }

    .fund-card {
        background:#fff;
        border:1px solid var(--border);
        border-radius:14px;
        padding:17px;
        transition:.15s;
    }

    .fund-card:hover {
        transform:translateY(-1px);
        box-shadow:0 5px 18px rgba(25,45,38,.04);
    }

    .fund-top {
        display:flex;
        justify-content:space-between;
        align-items:flex-start;
        gap:10px;
    }

    .fund-icon {
        width:42px;
        height:42px;
        border-radius:11px;
        background:var(--primary-soft);
        color:var(--primary);
        display:flex;
        align-items:center;
        justify-content:center;
        font-size: 18px;
    }

    .fund-badge {
        padding:5px 8px;
        border-radius:20px;
        font-size: 13px;
        font-weight:650;
        white-space:nowrap;
    }

    .fund-badge.general {
        background:#eef3f8;
        color:#55758d;
    }

    .fund-badge.restricted {
        background:#f1eef7;
        color:#77668c;
    }

    .fund-badge.emergency {
        background:#fff5e9;
        color: #976d38;
    }

    .fund-name {
        margin-top:14px;
        font-size: 16px;
        font-weight:700;
    }

    .fund-description {
        margin-top:4px;
        min-height:31px;
        color: #6c7570;
        font-size: 13px;
        line-height:1.5;
    }

    .balance {
        margin-top:16px;
        padding:12px;
        border:1px solid #edf1ee;
        background:#fafcfb;
        border-radius:10px;
    }

    .balance label {
        display:block;
        color: #6d7571;
        font-size: 13px;
        text-transform:uppercase;
    }

    .balance strong {
        display:block;
        margin-top:5px;
        font-size: 19px;
    }

    .fund-stats {
        display:grid;
        grid-template-columns:repeat(3,1fr);
        gap:7px;
        margin-top:10px;
    }

    .fund-stat {
        padding:9px;
        background:#fafcfb;
        border:1px solid #edf1ee;
        border-radius:8px;
    }

    .fund-stat label {
        display:block;
        color: #6d7571;
        font-size: 12px;
    }

    .fund-stat strong {
        display:block;
        margin-top:4px;
        font-size: 14px;
    }

    .fund-progress {
        margin-top:13px;
    }

    .progress-top {
        display:flex;
        justify-content:space-between;
        gap:10px;
        font-size: 13px;
    }

    .progress-top span:first-child {
        color: #6c7570;
    }

    .progress-top span:last-child {
        color:var(--primary);
        font-weight:650;
    }

    .progress {
        height:6px;
        margin-top:6px;
        background:#edf1ee;
        border-radius:20px;
        overflow:hidden;
    }

    .progress span {
        display:block;
        height:100%;
        background:var(--primary);
        border-radius:20px;
    }

    .fund-footer {
        margin-top:14px;
        padding-top:12px;
        border-top:1px solid #f0f3f1;
        display:flex;
        align-items:center;
        justify-content:space-between;
        gap:10px;
    }

    .fund-reference {
        color: #6c7470;
        font-size: 13px;
    }

    .action {
        width:30px;
        height:30px;
        border:1px solid var(--border);
        background:#fff;
        border-radius:8px;
        display:inline-flex;
        align-items:center;
        justify-content:center;
        color: #6c7771;
        text-decoration:none;
    }

    .toolbar-card {
        margin-bottom:18px;
        background:#fff;
        border:1px solid var(--border);
        border-radius:14px;
        padding:14px 16px;
    }

    .toolbar {
        display:flex;
        gap:9px;
        flex-wrap:wrap;
    }

    .search {
        position:relative;
        flex:1;
        min-width:220px;
    }

    .search i {
        position:absolute;
        left:13px;
        top:50%;
        transform:translateY(-50%);
        color: #6f7672;
        font-size: 16px;
    }

    .search input {
        width:100%;
        height:40px;
        border:1px solid var(--border);
        border-radius:9px;
        padding:0 12px 0 36px;
        background:#fafcfa;
        outline:none;
        font-size: 15px;
    }

    .filter {
        height:40px;
        min-width:145px;
        border:1px solid var(--border);
        border-radius:9px;
        padding:0 28px 0 12px;
        background:#fff;
        color:#68756f;
        font-size: 15px;
        outline:none;
    }

    .notice {
        margin-top:18px;
        display:flex;
        gap:10px;
        padding:14px 16px;
        background:#f7f9f8;
        border:1px solid #e5ebe8;
        border-radius:13px;
    }

    .notice i {
        color:var(--primary);
        font-size: 17px;
    }

    .notice strong {
        display:block;
        font-size: 14px;
    }

    .notice p {
        margin:4px 0 0;
        color: #6b7670;
        font-size: 13px;
        line-height:1.6;
    }

    @media(max-width:1100px) {
        .summary-grid {
            grid-template-columns:repeat(3,1fr);
        }

        .fund-grid {
            grid-template-columns:repeat(2,1fr);
        }
    }

    @media(max-width:700px) {
        .page-header {
            flex-direction:column;
            align-items:flex-start;
        }

        .actions {
            width:100%;
        }

        .actions .ui-btn {
            flex:1;
        }

        .summary-grid,
        .fund-grid {
            grid-template-columns:1fr;
        }

        .search {
            min-width:100%;
        }

        .filter {
            flex:1;
            min-width:0;
        }
    }
</style>
@endpush


@section('content')

<div class="page-header">

    <div>

        <h1>Funds</h1>

        <p>
            Manage organizational funds, balances, restricted designations and financial movement.
        </p>

    </div>

    <div class="actions">

        <button class="ui-btn">
            <i class="bi bi-download"></i>
            Export
        </button>

        <a
            href="{{ route('finance.funds.create') }}"
            class="ui-btn primary"
        >
            <i class="bi bi-plus-lg"></i>
            New Fund
        </a>

    </div>

</div>


<!-- SUMMARY -->

<div class="summary-grid">

    <div class="summary-card">

        <div class="summary-label">
            Total Funds
        </div>

        <div class="summary-value">
            {{ number_format($totalFunds) }}
        </div>

        <div class="summary-meta">
            Active organizational funds
        </div>

    </div>


    <div class="summary-card">

        <div class="summary-label">
            Total Balance
        </div>

        <div class="summary-value">
            PKR {{ number_format($totalBalance, 0) }}
        </div>

        <div class="summary-meta">
            Opening balances + received donations
        </div>

    </div>


    <div class="summary-card">

        <div class="summary-label">
            Restricted Balance
        </div>

        <div class="summary-value">
            PKR {{ number_format($restrictedBalance, 0) }}
        </div>

        <div class="summary-meta">
            Restricted / designated
        </div>

    </div>


    <div class="summary-card">

        <div class="summary-label">
            Allocated
        </div>

        <div class="summary-value">
            PKR {{ number_format($allocated, 0) }}
        </div>

        <div class="summary-meta">
            Awaiting Transactions module
        </div>

    </div>


    <div class="summary-card">

        <div class="summary-label">
            Available
        </div>

        <div class="summary-value">
            PKR {{ number_format($available, 0) }}
        </div>

        <div class="summary-meta">
            Current calculated balance
        </div>

    </div>

</div>


<!-- FILTERS -->

<div class="toolbar-card">

    <form
        method="GET"
        action="{{ route('finance.funds.index') }}"
        class="toolbar"
    >

        <div class="search">

            <i class="bi bi-search"></i>

            <input
                id="fundSearch"
                name="search"
                type="text"
                value="{{ request('search') }}"
                placeholder="Search fund name, reference or purpose..."
            >

        </div>


        <select
            id="fundType"
            name="type"
            class="filter"
        >

            <option value="">
                All Fund Types
            </option>

            <option
                value="general"
                @selected(request('type') === 'general')
            >
                General
            </option>

            <option
                value="restricted"
                @selected(request('type') === 'restricted')
            >
                Restricted
            </option>

            <option
                value="designated"
                @selected(request('type') === 'designated')
            >
                Designated
            </option>

        </select>


        <select
            id="fundStatus"
            name="status"
            class="filter"
        >

            <option value="">
                All Statuses
            </option>

            <option
                value="active"
                @selected(request('status') === 'active')
            >
                Active
            </option>

            <option
                value="inactive"
                @selected(request('status') === 'inactive')
            >
                Inactive
            </option>

        </select>


        <button
            type="submit"
            class="ui-btn primary"
        >
            <i class="bi bi-search"></i>
            Search
        </button>


        @if(request()->hasAny([
            'search',
            'type',
            'status'
        ]))

            <a
                href="{{ route('finance.funds.index') }}"
                class="ui-btn"
            >
                Clear
            </a>

        @endif

    </form>

</div>


<!-- FUNDS -->

<div
    class="fund-grid"
    id="fundGrid"
>

    @forelse($funds as $fund)

        @php

            $typeLabel = match ($fund->type) {
                'general' => 'General',
                'restricted' => 'Restricted',
                'designated' => 'Designated',
                default => ucfirst($fund->type),
            };

            $typeClass = match ($fund->type) {
                'restricted',
                'designated' => 'restricted',

                default => 'general',
            };

            $purposeLabel = match ($fund->purpose) {
                'general_welfare' =>
                    'General Welfare',

                'small_business' =>
                    'Small Business Assistance',

                'qard' =>
                    'Qard-e-Hasana',

                'education' =>
                    'Education Support',

                'emergency' =>
                    'Emergency Assistance',

                'community_development' =>
                    'Community Development',

                default =>
                    ucfirst(
                        str_replace(
                            '_',
                            ' ',
                            $fund->purpose
                        )
                    ),
            };

            $openingBalance = (float) $fund->opening_balance;
            $receivedDonations = (float) ($fund->received_donations ?? 0);
            $spent = (float) ($fund->spent_expenses ?? 0);
            $allocated = 0;

            $balance =
                $openingBalance
                + $receivedDonations
                - $spent
                - $allocated;

            $utilization = 0;

            $icon = match ($fund->purpose) {

                'small_business' =>
                    'bi-shop',

                'qard' =>
                    'bi-arrow-repeat',

                'education' =>
                    'bi-mortarboard',

                'emergency' =>
                    'bi-heart-pulse',

                'community_development' =>
                    'bi-buildings',

                default =>
                    'bi-wallet2',
            };

        @endphp


        <div class="fund-card">

            <div class="fund-top">

                <div class="fund-icon">

                    <i class="bi {{ $icon }}"></i>

                </div>


                <span
                    class="fund-badge {{ $typeClass }}"
                >
                    {{ $typeLabel }}
                </span>

            </div>


            <div class="fund-name">

                {{ $fund->name }}

            </div>


            <div class="fund-description">

                {{ $fund->description
                    ?: 'No description provided.'
                }}

            </div>


            <div class="balance">

                <label>
                    Current Balance
                </label>

                <strong>
                    PKR {{ number_format($balance, 0) }}
                </strong>

            </div>


            <div class="fund-stats">

                <div class="fund-stat">

                    <label>
                        Opening
                    </label>

                    <strong>
                        PKR {{ number_format($openingBalance, 0) }}
                    </strong>

                </div>


                <div class="fund-stat">

                    <label>
                        Received
                    </label>

                    <strong>
                        PKR {{ number_format($receivedDonations, 0) }}
                    </strong>

                </div>


                <div class="fund-stat">

                    <label>
                        Spent
                    </label>

                    <strong>
                        PKR {{ number_format($spent, 0) }}
                    </strong>

                </div>

            </div>


            <div class="fund-progress">

                <div class="progress-top">

                    <span>
                        Utilization
                    </span>

                    <span>
                        {{ $utilization }}%
                    </span>

                </div>


                <div class="progress">

                    <span
                        style="width:{{ $utilization }}%"
                    ></span>

                </div>

            </div>


            <div class="fund-footer">

                <div>

                    <span class="fund-reference">
                        {{ $fund->fund_reference }}
                    </span>

                    <div
                        style="
                            margin-top:3px;
                            color:#929b96;
                            font-size:11px;
                        "
                    >
                        {{ $purposeLabel }}
                    </div>

                </div>


                <a
                    href="{{ route(
                        'finance.funds.show',
                        $fund
                    ) }}"
                    class="action"
                    title="View fund"
                >
                    <i class="bi bi-eye"></i>
                </a>

            </div>

        </div>

    @empty

        <div
            style="
                grid-column:1/-1;
                background:#fff;
                border:1px solid var(--border);
                border-radius:14px;
                padding:50px;
                text-align:center;
                color:#89948f;
            "
        >

            <i
                class="bi bi-wallet2"
                style="
                    display:block;
                    font-size:32px;
                    margin-bottom:12px;
                "
            ></i>

            <strong>
                No funds found.
            </strong>

            <div style="
                margin-top:6px;
                font-size:13px;
            ">
                Create a fund or change your search/filter criteria.
            </div>

        </div>

    @endforelse

</div>
@if ($funds->hasPages())

    <div
        style="
            margin-top:18px;
            display:flex;
            justify-content:center;
        "
    >

        {{ $funds->links() }}

    </div>

@endif

<div class="notice">

    <i class="bi bi-shield-check"></i>

    <div>

        <strong>
            Restricted-fund control
        </strong>

        <p>
            Restricted or designated donations must remain associated with
            their specified purpose. Fund balances should provide a clear
            trail of received, allocated, spent, recovered and remaining amounts.
        </p>

    </div>

</div>

@endsection


@push('scripts')
<script>

    


    

</script>
@endpush