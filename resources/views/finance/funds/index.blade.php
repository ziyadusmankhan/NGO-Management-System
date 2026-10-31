@extends('layouts.app')

@section('title', 'Funds')

@push('styles')
<link rel="stylesheet" href="{{ asset('css/views/finance-funds-index-1.css') }}">
@endpush


@section('content')
@if($errors->any()) @include('shared.action-feedback') @endif

<div class="page-header">

    <div>

        <h1>Funds</h1>

        <p>
            Manage organizational funds, balances, restricted designations and financial movement.
        </p>

    </div>

    <div class="actions">

        @include('shared.finance-welfare-export', ['exportKind'=>'funds'])

        @if(auth()->user()->hasPermission('finance.funds.manage'))
            @if(auth()->user()?->hasPermission('finance.view') && auth()->user()?->hasPermission('finance.funds.manage'))<a href="{{ route('finance.funds.create') }}">
                New Fund
            </a>@endif
        @endif

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
            PKR {{ \App\Domain\Finance\Support\Money::format($totalBalance) }}
        </div>

        <div class="summary-meta">
            Active funds: opening + posted credits − posted debits
        </div>

    </div>


    <div class="summary-card">

        <div class="summary-label">
            Restricted Balance
        </div>

        <div class="summary-value">
            PKR {{ \App\Domain\Finance\Support\Money::format($restrictedBalance) }}
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
            PKR {{ \App\Domain\Finance\Support\Money::format($allocated) }}
        </div>

        <div class="summary-meta">
            Unpaid approved welfare assistance
        </div>

    </div>


    <div class="summary-card">

        <div class="summary-label">
            Available After Allocations
        </div>

        <div class="summary-value">
            PKR {{ \App\Domain\Finance\Support\Money::format($available) }}
        </div>

        <div class="summary-meta">
            Ledger balance less unpaid approvals
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

            <option value="archived" @selected(request('status') === 'archived')>Archived</option>

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

            @if(auth()->user()?->hasPermission('finance.view'))<a
                href="{{ route('finance.funds.index') }}"
                class="ui-btn"
            >
                Clear
            </a>@endif

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

            $openingBalance = $fund->financial_summary['opening_balance'];
            $receivedDonations = $fund->financial_summary['received'];
            $spent = $fund->financial_summary['spent'];
            $balance = $fund->financial_summary['balance'];
            $utilization = $fund->financial_summary['utilization_percent'];

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
                    {{ $fund->trashed() ? 'Archived · ' : '' }}{{ $typeLabel }}
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
                    PKR {{ \App\Domain\Finance\Support\Money::format($balance) }}
                </strong>

            </div>


            <div class="fund-stats">

                <div class="fund-stat">

                    <label>
                        Opening
                    </label>

                    <strong>
                        PKR {{ \App\Domain\Finance\Support\Money::format($openingBalance) }}
                    </strong>

                </div>


                <div class="fund-stat">

                    <label>
                        Received
                    </label>

                    <strong>
                        PKR {{ \App\Domain\Finance\Support\Money::format($receivedDonations) }}
                    </strong>

                </div>


                <div class="fund-stat">

                    <label>
                        Spent
                    </label>

                    <strong>
                        PKR {{ \App\Domain\Finance\Support\Money::format($spent) }}
                    </strong>

                </div>

            </div>


            <div class="fund-progress">

                <div class="progress-top"><span>Allocated (unpaid)</span><strong>PKR {{ \App\Domain\Finance\Support\Money::format($fund->financial_summary['allocated']) }}</strong></div>
                <div class="progress-top"><span>Available after allocations</span><strong>PKR {{ \App\Domain\Finance\Support\Money::format($fund->financial_summary['available']) }}</strong></div>

                <div class="progress-top">

                    <span>
                        Spent or allocated
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
                            color:var(--legacy-text-929b96, #929b96);
                            font-size:11px;
                        "
                    >
                        {{ $purposeLabel }}
                    </div>

                </div>


                @if(auth()->user()?->hasPermission('finance.view'))<a
                    href="{{ route(
                        'finance.funds.show',
                        $fund
                    ) }}"
                    class="action"
                    title="View fund"
                >
                    <i class="bi bi-eye"></i>
                </a>@endif

            </div>

        </div>

    @empty

        <div
            style="
                grid-column:1/-1;
                background:var(--legacy-surface-fff, #fff);
                border:1px solid var(--border);
                border-radius:14px;
                padding:50px;
                text-align:center;
                color:var(--legacy-text-89948f, #89948f);
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
