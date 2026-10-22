@extends('layouts.app')

@section('title', 'Fund Details')

@push('styles')
<style>
    .page-header { display:flex; justify-content:space-between; align-items:flex-start; gap:20px; margin-bottom:22px; }
    .identity { display:flex; align-items:center; gap:14px; }
    .icon-box { width:62px; height:62px; border-radius:17px; background:var(--primary-soft); color:var(--primary); display:flex; align-items:center; justify-content:center; font-size:22px; flex-shrink:0; }
    .title { font-size:23px; font-weight:700; letter-spacing:-.4px; }
    .meta { margin-top:4px; color:#6e7773; font-size:15px; }
    .actions { display:flex; gap:8px; flex-wrap:wrap; }
    .ui-btn { height:39px; padding:0 12px; border-radius:9px; display:inline-flex; align-items:center; justify-content:center; gap:7px; border:1px solid var(--border); background:#fff; color:#65726c; font-size:15px; font-weight:650; text-decoration:none; cursor:pointer; }
    .ui-btn.primary { background:var(--primary); border-color:var(--primary); color:#fff; }
    .card { background:#fff; border:1px solid var(--border); border-radius:14px; overflow:hidden; margin-bottom:18px; }
    .card-head { padding:16px 19px; border-bottom:1px solid #eef1ef; display:flex; justify-content:space-between; align-items:center; gap:15px; }
    .card-head strong { font-size:16px; }
    .card-head span { display:block; margin-top:4px; color:#6c7470; font-size:14px; }
    .card-body { padding:18px 19px; }
    .status, .restricted { padding:5px 8px; border-radius:20px; font-size:13px; font-weight:650; white-space:nowrap; }
    .status { background:#edf7f0; color:#397955; }
    .restricted { background:#f1eef7; color:#77668c; }
    .summary-grid { display:grid; grid-template-columns:repeat(3,minmax(0,1fr)); gap:10px; }
    .summary-box { padding:13px; background:#fafcfb; border:1px solid #edf1ee; border-radius:10px; }
    .summary-box.highlight { background:var(--primary-soft); border-color:#6f7673; }
    .summary-box label { display:block; color:#6d7571; font-size:13px; text-transform:uppercase; letter-spacing:.5px; }
    .summary-box strong { display:block; margin-top:5px; font-size:16px; overflow-wrap:anywhere; }
    .summary-box.highlight strong { color:var(--primary); }
    .table-wrap { overflow-x:auto; }
    .transaction-table { width:100%; min-width:850px; border-collapse:collapse; }
    .transaction-table th { padding:10px 12px; background:#fafcfb; border-bottom:1px solid #eef1ef; color:#6c7470; font-size:13px; text-transform:uppercase; letter-spacing:.5px; text-align:left; white-space:nowrap; }
    .transaction-table td { padding:12px; border-bottom:1px solid #f0f3f1; font-size:14px; vertical-align:middle; }
    .transaction-table tr:last-child td { border-bottom:none; }
    @media(max-width:900px) {
        .page-header { flex-direction:column; }
        .summary-grid { grid-template-columns:repeat(2,minmax(0,1fr)); }
    }
    @media(max-width:450px) {
        .summary-grid { grid-template-columns:1fr; }
        .card-head { align-items:flex-start; flex-direction:column; }
    }
    @media print {
        .actions { display:none; }
        .table-wrap { overflow:visible; }
        .transaction-table { min-width:0; }
    }
</style>
@endpush


@section('content')
@php
    $restricted = app(\App\Domain\Finance\Funds\Services\FundPurposeService::class)->isRestricted($fund);
    $money = fn ($value) => \App\Domain\Finance\Support\Money::format($value);
@endphp

<div class="page-header">
    <div class="identity">
        <div class="icon-box"><i class="bi bi-wallet2"></i></div>
        <div>
            <div class="title">{{ $fund->name }}</div>
            <div class="meta">{{ $fund->fund_reference }} · {{ \Illuminate\Support\Str::headline($fund->purpose) }} · {{ $fund->trashed() ? 'Archived' : ucfirst($fund->status) }}</div>
        </div>
    </div>
    <div class="actions">
        <a href="{{ route('finance.funds.index') }}" class="ui-btn">Back</a>
        <button type="button" class="ui-btn" onclick="window.print()">Print</button>
        @if(! $fund->trashed() && auth()->user()->hasPermission('finance.funds.manage'))
            <a href="{{ route('finance.funds.edit', $fund) }}" class="ui-btn primary">Edit Fund</a>
            <form method="POST" action="{{ route('finance.funds.destroy', $fund) }}" onsubmit="return confirm('Archive this fund? Its financial history will be retained.');">
                @csrf
                @method('DELETE')
                <button type="submit" class="ui-btn">Archive</button>
            </form>
        @endif
    </div>
</div>

@if(session('success'))
    <div class="alert alert-success" role="status">{{ session('success') }}</div>
@endif
@if($errors->any())
    <div class="alert alert-danger" role="alert"><ul class="mb-0">
        @foreach($errors->all() as $error)<li>{{ $error }}</li>@endforeach
    </ul></div>
@endif

<div class="card">
    <div class="card-head">
        <div><strong>Fund Overview</strong><span>{{ $fund->description ?: 'No description provided.' }}</span></div>
        <span class="{{ $restricted ? 'restricted' : 'status' }}">{{ $restricted ? 'Purpose restricted' : 'General use' }}</span>
    </div>
    <div class="card-body">
        <div class="summary-grid">
            @foreach([
                'balance' => 'Current Ledger Balance',
                'original_received' => 'Original Funding (Opening + Receipts)',
                'received' => 'New Receipts',
                'spent' => 'Spent',
                'allocated' => 'Allocated — Unpaid Approvals',
                'available' => 'Available After Allocations',
                'recovered' => 'Recoveries Received',
                'refunded' => 'Refunds Returned to Fund',
                'refunds_paid' => 'Refunds Paid Out',
            ] as $key => $label)
                <div class="summary-box {{ $key === 'balance' ? 'highlight' : '' }}">
                    <label>{{ $label }}</label>
                    <strong data-fund-total="{{ $key }}">PKR {{ $money($summary[$key]) }}</strong>
                </div>
            @endforeach
        </div>
        <p class="text-muted mt-3 mb-0">
            Opening PKR {{ $money($summary['opening_balance']) }} + new receipts + recoveries + refunds returned − spending − refunds paid out = ledger balance.
            Allocations include unpaid approved welfare assistance and undisbursed approved Qard principal; they are commitments, not additional ledger debits.
            Available after allocations = ledger balance − allocations. Recoveries and refunds are included once.
        </p>
        @if(\App\Domain\Finance\Support\Money::minor($summary['available']) < 0)
            <div class="alert alert-warning mt-3 mb-0" role="alert">Approved commitments exceed the ledger balance. The negative available amount shows the funding shortfall.</div>
        @endif
    </div>
</div>

<div class="card">
    <div class="card-head"><div><strong>Unpaid Approved Allocations</strong><span>Weekly and monthly assistance use the same total approved cap. Posted payments reduce allocations; voids restore the unpaid amount.</span></div></div>
    <div class="table-wrap">
        <table class="transaction-table">
            <thead><tr><th>Assistance</th><th>Beneficiary</th><th>Approved</th><th>Paid</th><th>Outstanding</th></tr></thead>
            <tbody>
                @forelse($allocations as $assistance)
                    <tr>
                        <td>
                            @if($assistance instanceof \App\Domain\Qard\Models\QardLoan)
                                @if(auth()->user()->hasPermission('qard.view'))
                                    <a href="{{ route('qard.show', $assistance) }}">{{ $assistance->number }}</a>
                                @else
                                    {{ $assistance->number }}
                                @endif
                            @elseif(auth()->user()->hasPermission('welfare.assistance.view'))
                                <a href="{{ route('welfare.assistance.show', $assistance) }}">{{ $assistance->assistance_number }}</a>
                            @else
                                {{ $assistance->assistance_number }}
                            @endif
                        </td>
                        <td>{{ $assistance->beneficiary?->full_name ?? 'Unavailable' }}</td>
                        <td>PKR {{ $money($assistance->amount) }}</td>
                        <td>PKR {{ $money($assistance->paid_amount) }}</td>
                        <td>PKR {{ $money($assistance->outstanding_amount) }}</td>
                    </tr>
                @empty
                    <tr><td colspan="5">No unpaid approved allocations.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>

<div class="card">
    <div class="card-head"><div><strong>Complete Transaction History</strong><span>Ordered by transaction date, then ledger ID. Voided and archived entries remain visible and have no effect on the current balance.</span></div></div>
    <div class="table-wrap">
        <table class="transaction-table">
            <thead><tr><th>Date</th><th>Ledger / Source</th><th>Type</th><th>Description / Audit</th><th>Credit</th><th>Debit</th><th>Status</th><th>Running Balance</th></tr></thead>
            <tbody>
                <tr>
                    <td>{{ ($fund->opening_balance_date ?? $fund->created_at)?->format('d M Y') }}</td>
                    <td>{{ $fund->fund_reference }}</td><td>Opening</td><td>Opening balance</td>
                    <td>PKR {{ $money($summary['opening_balance']) }}</td><td>—</td><td>Opening</td>
                    <td>PKR {{ $money($summary['opening_balance']) }}</td>
                </tr>
                @foreach($transactions as $transaction)
                    <tr>
                        <td>{{ $transaction->transaction_date->format('d M Y') }}</td>
                        <td>
                            <a href="{{ route('finance.transactions.show', $transaction) }}">{{ $transaction->transaction_number }}</a>
                            <div>
                                @if($transaction->source_url)
                                    <a href="{{ $transaction->source_url }}">{{ $transaction->source_label }}</a>
                                @else
                                    {{ $transaction->source_label ?? $transaction->reference ?? 'Source unavailable' }}
                                @endif
                            </div>
                            @if($transaction->assistance_number)<small>{{ $transaction->assistance_number }}</small>@endif
                        </td>
                        <td>{{ \Illuminate\Support\Str::headline($transaction->type) }}</td>
                        <td>
                            {{ $transaction->description }}
                            <div class="text-muted">Recorded by {{ $transaction->recordedBy?->name ?? 'System / unavailable' }}</div>
                            @if($transaction->void_reason)
                                <div>Void: {{ $transaction->void_reason }}</div>
                                <small>{{ $transaction->void_actor ?? 'Unavailable' }} · {{ $transaction->voided_at?->format('d M Y H:i') }}</small>
                            @endif
                        </td>
                        <td>{{ $transaction->direction === 'credit' ? 'PKR '.$money($transaction->amount) : '—' }}</td>
                        <td>{{ $transaction->direction === 'debit' ? 'PKR '.$money($transaction->amount) : '—' }}</td>
                        <td>{{ $transaction->trashed() ? 'Archived' : ucfirst($transaction->status) }}{{ $transaction->affects_balance ? '' : ' (excluded)' }}</td>
                        <td>PKR {{ $money($transaction->running_balance) }}</td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    </div>
</div>

<div class="card">
    <div class="card-head"><div><strong>Designation and Controls</strong><span>Spending must match the approved purpose when the fund is restricted, designated, or has spending restrictions enabled.</span></div></div>
    <div class="card-body">
        <p><strong>Type:</strong> {{ ucfirst($fund->type) }} · <strong>Purpose:</strong> {{ \Illuminate\Support\Str::headline($fund->purpose) }}</p>
        <p><strong>Responsible authority:</strong> {{ $fund->responsible_authority ?: 'Not assigned' }}</p>
        <p><strong>Usage rules:</strong> {{ $fund->usage_rules ?: 'No additional usage rules recorded.' }}</p>
        <p><strong>Internal notes:</strong> {{ $fund->internal_notes ?: 'None' }}</p>
        <p class="text-muted mb-0">Opening balance and designation cannot be rewritten after financial activity or linked commitments. Fund changes are recorded below.</p>
    </div>
</div>

<div class="card">
    <div class="card-head"><div><strong>Fund Change History</strong><span>Actor, time, and before/after values for changes recorded from Phase 3 onward.</span></div></div>
    <div class="table-wrap">
        <table class="transaction-table">
            <thead><tr><th>When</th><th>Action</th><th>Changed By</th><th>Changes</th></tr></thead>
            <tbody>
                @forelse($changes as $change)
                    <tr>
                        <td>{{ $change->changed_at->format('d M Y H:i') }}</td>
                        <td>{{ ucfirst($change->action) }}</td>
                        <td>{{ $change->changedBy?->name ?? 'Unavailable' }}</td>
                        <td>
                            @foreach($change->after_values as $field => $value)
                                @if(! array_key_exists($field, $change->before_values) || $change->before_values[$field] !== $value)
                                    <div><strong>{{ \Illuminate\Support\Str::headline($field) }}:</strong>
                                        {{ json_encode($change->before_values[$field] ?? null, JSON_UNESCAPED_UNICODE) }} → {{ json_encode($value, JSON_UNESCAPED_UNICODE) }}
                                    </div>
                                @endif
                            @endforeach
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="4">No fund changes recorded since audit tracking was enabled.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>
@endsection
