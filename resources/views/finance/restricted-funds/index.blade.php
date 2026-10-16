@extends('layouts.app')

@section('title', 'Restricted Funds')

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
        font-size:25px;
        font-weight:700;
    }

    .page-header p {
        margin:7px 0 0;
        color:var(--muted);
        font-size:16px;
    }

    .summary-grid {
        display:grid;
        grid-template-columns:repeat(4,minmax(0,1fr));
        gap:12px;
        margin-bottom:18px;
    }

    .summary-card,
    .fund-card,
    .toolbar-card {
        background:#fff;
        border:1px solid var(--border);
        border-radius:14px;
    }

    .summary-card {
        padding:15px;
    }

    .summary-label {
        color:var(--muted);
        font-size:14px;
    }

    .summary-value {
        margin-top:6px;
        font-size:20px;
        font-weight:700;
    }

    .toolbar-card {
        padding:14px 16px;
        margin-bottom:18px;
    }

    .toolbar {
        display:flex;
        gap:9px;
    }

    .search {
        position:relative;
        flex:1;
    }

    .search i {
        position:absolute;
        left:13px;
        top:50%;
        transform:translateY(-50%);
        color:#6f7672;
    }

    .search input {
        width:100%;
        height:40px;
        border:1px solid var(--border);
        border-radius:9px;
        padding:0 12px 0 36px;
        background:#fafcfa;
    }

    .fund-grid {
        display:grid;
        grid-template-columns:repeat(2,minmax(0,1fr));
        gap:14px;
    }

    .fund-card {
        padding:18px;
    }

    .fund-top {
        display:flex;
        justify-content:space-between;
        align-items:flex-start;
        gap:12px;
    }

    .fund-name {
        font-size:17px;
        font-weight:700;
    }

    .fund-reference {
        margin-top:4px;
        color:#7a847f;
        font-size:13px;
    }

    .badge {
        padding:5px 9px;
        border-radius:20px;
        background:#f1eef7;
        color:#77668c;
        font-size:13px;
        font-weight:650;
    }

    .balance {
        margin-top:16px;
        padding:14px;
        background:#fafcfb;
        border:1px solid #edf1ee;
        border-radius:10px;
    }

    .balance-label {
        color:#6d7571;
        font-size:13px;
        text-transform:uppercase;
    }

    .balance-value {
        margin-top:5px;
        font-size:22px;
        font-weight:700;
        color:var(--primary);
    }

    .stats {
        display:grid;
        grid-template-columns:repeat(3,1fr);
        gap:8px;
        margin-top:10px;
    }

    .stat {
        padding:10px;
        background:#fafcfb;
        border:1px solid #edf1ee;
        border-radius:8px;
    }

    .stat span {
        display:block;
        color:#6d7571;
        font-size:12px;
    }

    .stat strong {
        display:block;
        margin-top:4px;
        font-size:14px;
    }

    .controls {
        margin-top:15px;
        padding-top:14px;
        border-top:1px solid #eef1ef;
    }

    .controls-title {
        font-size:14px;
        font-weight:700;
        margin-bottom:9px;
    }

    .control-list {
        display:grid;
        grid-template-columns:repeat(2,1fr);
        gap:7px;
    }

    .control-item {
        padding:8px 10px;
        border-radius:8px;
        background:#f7f9f8;
        font-size:12px;
        color:#65716b;
    }

    .control-item.enabled {
        color:#397955;
        background:#edf7f0;
    }

    @media(max-width:800px) {
        .summary-grid,
        .fund-grid {
            grid-template-columns:1fr;
        }
    }

    @media(max-width:600px) {
        .stats,
        .control-list {
            grid-template-columns:1fr;
        }
    }
</style>
@endpush

@section('content')

<div class="page-header">

    <div>
        <h1>Restricted Funds</h1>

        <p>
            Monitor restricted and designated funds, permitted use and remaining balances.
        </p>
    </div>

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
            PKR {{ number_format($totalReceived, 0) }}
        </div>
    </div>

    <div class="summary-card">
        <div class="summary-label">Total Spent</div>
        <div class="summary-value">
            PKR {{ number_format($totalSpent, 0) }}
        </div>
    </div>

    <div class="summary-card">
        <div class="summary-label">Available Balance</div>
        <div class="summary-value">
            PKR {{ number_format($totalBalance, 0) }}
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
            $received = (float) ($fund->received_donations ?? 0);
            $spent = (float) ($fund->spent_expenses ?? 0);

            $balance =
                (float) $fund->opening_balance
                + $received
                - $spent;
        @endphp

        <div class="fund-card">

            <div class="fund-top">

                <div>
                    <div class="fund-name">
                        {{ $fund->name }}
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
                    Available Balance
                </div>

                <div class="balance-value">
                    PKR {{ number_format($balance, 0) }}
                </div>

            </div>

            <div class="stats">

                <div class="stat">
                    <span>Opening</span>
                    <strong>
                        PKR {{ number_format($fund->opening_balance, 0) }}
                    </strong>
                </div>

                <div class="stat">
                    <span>Received</span>
                    <strong>
                        PKR {{ number_format($received, 0) }}
                    </strong>
                </div>

                <div class="stat">
                    <span>Spent</span>
                    <strong>
                        PKR {{ number_format($spent, 0) }}
                    </strong>
                </div>

            </div>

            <div class="controls">

                <div class="controls-title">
                    Financial Controls
                </div>

                <div class="control-list">

                    <div class="control-item {{ $fund->restrict_spending ? 'enabled' : '' }}">
                        {{ $fund->restrict_spending ? '✓' : '—' }}
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
            background:#fff;
            border:1px solid var(--border);
            border-radius:14px;
            padding:50px;
            text-align:center;
            color:#89948f;
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