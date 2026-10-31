@extends('layouts.app')

@section('title', 'Restricted Funds')

@push('styles')
<link rel="stylesheet" href="{{ asset('css/views/finance-restricted-funds-index-1.css') }}">
@endpush

@section('content')
@if($errors->any()) @include('shared.action-feedback') @endif

<div class="page-header">

    <div>
        <h1>Restricted Funds</h1>

        <p>
            Monitor restricted and designated funds, permitted use and remaining balances.
        </p>
    </div>

    @include('shared.finance-welfare-export', ['exportKind'=>'restricted-funds'])
</div>

<div class="summary-grid">

    <div class="summary-card">
        <div class="summary-label">Restricted Funds</div>
        <div class="summary-value">
            {{ number_format($totalFunds) }}
        </div>
    </div>

    <div class="summary-card">
        <div class="summary-label">Total Received</div>
        <div class="summary-value">
            PKR {{ \App\Domain\Finance\Support\Money::format($totalReceived) }}
        </div>
    </div>

    <div class="summary-card">
        <div class="summary-label">Total Spent</div>
        <div class="summary-value">
            PKR {{ \App\Domain\Finance\Support\Money::format($totalSpent) }}
        </div>
    </div>

    <div class="summary-card">
        <div class="summary-label">Ledger Balance</div>
        <div class="summary-value">
            PKR {{ \App\Domain\Finance\Support\Money::format($totalBalance) }}
        </div>
    </div>

</div>

<div class="toolbar-card">

    <form
        method="GET"
        action="{{ route('finance.restricted-funds.index') }}"
        class="toolbar"
    >

        <div class="search">

            <i class="bi bi-search"></i>

            <input
                name="search"
                value="{{ request('search') }}"
                placeholder="Search restricted or designated funds..."
            >

        </div>

        <button
            class="ui-btn primary"
            type="submit"
        >
            <i class="bi bi-search"></i>
            Search
        </button>

    </form>

</div>

<div class="fund-grid">

    @forelse($funds as $fund)

        @php
            $received = $fund->financial_summary['received'];
            $spent = $fund->financial_summary['spent'];
            $balance = $fund->financial_summary['balance'];
        @endphp

        <div class="fund-card">

            <div class="fund-top">

                <div>
                    <div class="fund-name">
                        @if(auth()->user()?->hasPermission('finance.view'))<a href="{{ route('finance.funds.show', $fund) }}">{{ $fund->name }}</a>@endif
                    </div>

                    <div class="fund-reference">
                        {{ $fund->fund_reference }}
                    </div>
                </div>

                <span class="badge">
                    {{ ucfirst($fund->type) }}
                </span>

            </div>

            <div class="balance">

                <div class="balance-label">
                    Ledger Balance
                </div>

                <div class="balance-value">
                    PKR {{ \App\Domain\Finance\Support\Money::format($balance) }}
                </div>

            </div>

            <div class="stats">

                <div class="stat">
                    <span>Opening</span>
                    <strong>
                        PKR {{ \App\Domain\Finance\Support\Money::format($fund->opening_balance) }}
                    </strong>
                </div>

                <div class="stat">
                    <span>Received</span>
                    <strong>
                        PKR {{ \App\Domain\Finance\Support\Money::format($received) }}
                    </strong>
                </div>

                <div class="stat">
                    <span>Spent</span>
                    <strong>
                        PKR {{ \App\Domain\Finance\Support\Money::format($spent) }}
                    </strong>
                </div>

            </div>

            <div class="controls">

                <p>Allocated (unpaid): <strong>PKR {{ \App\Domain\Finance\Support\Money::format($fund->financial_summary['allocated']) }}</strong></p>
                <p>Available after allocations: <strong>PKR {{ \App\Domain\Finance\Support\Money::format($fund->financial_summary['available']) }}</strong></p>

                <div class="controls-title">
                    Financial Controls
                </div>

                <div class="control-list">

                    <div class="control-item enabled">
                        ✓
                        Spending Restricted
                    </div>

                    <div class="control-item {{ $fund->require_approval ? 'enabled' : '' }}">
                        {{ $fund->require_approval ? '✓' : '—' }}
                        Approval Required
                    </div>

                    <div class="control-item {{ $fund->require_documents ? 'enabled' : '' }}">
                        {{ $fund->require_documents ? '✓' : '—' }}
                        Documents Required
                    </div>

                    <div class="control-item {{ $fund->prevent_negative_balance ? 'enabled' : '' }}">
                        {{ $fund->prevent_negative_balance ? '✓' : '—' }}
                        Negative Balance Protection
                    </div>

                    <div class="control-item {{ $fund->protect_restricted_designation ? 'enabled' : '' }}">
                        {{ $fund->protect_restricted_designation ? '✓' : '—' }}
                        Designation Protection
                    </div>

                </div>

            </div>

        </div>

    @empty

        <div style="
            grid-column:1/-1;
            background:var(--legacy-surface-fff, #fff);
            border:1px solid var(--border);
            border-radius:14px;
            padding:50px;
            text-align:center;
            color:var(--legacy-text-89948f, #89948f);
        ">
            <i
                class="bi bi-shield-check"
                style="font-size:32px;"
            ></i>

            <div style="margin-top:10px;">
                No active restricted or designated funds found.
            </div>
        </div>

    @endforelse

</div>

@endsection
