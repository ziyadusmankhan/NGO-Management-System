@extends('layouts.app')

@section('title', 'Record Welfare Assistance Payment')

@push('styles')
<style>
    .page-header {
        display:flex;
        justify-content:space-between;
        align-items:flex-start;
        gap:20px;
        margin-bottom:22px;
    }

    .page-header h1 {
        margin:0;
        font-size:25px;
        font-weight:700;
    }

    .page-header p {
        margin:6px 0 0;
        color:var(--muted);
    }

    .actions {
        display:flex;
        gap:8px;
    }

    .ui-btn {
        display:inline-flex;
        align-items:center;
        justify-content:center;
        gap:7px;
        min-height:40px;
        padding:0 14px;
        border:1px solid var(--border);
        border-radius:9px;
        background:#fff;
        color:#596760;
        text-decoration:none;
        font-weight:650;
        cursor:pointer;
    }

    .ui-btn.primary {
        background:var(--primary);
        border-color:var(--primary);
        color:#fff;
    }

    .layout {
        display:grid;
        grid-template-columns:minmax(0,1fr) 310px;
        gap:18px;
    }

    .card {
        background:#fff;
        border:1px solid var(--border);
        border-radius:14px;
        overflow:hidden;
        margin-bottom:18px;
    }

    .card-head {
        padding:17px 20px;
        border-bottom:1px solid var(--border);
    }

    .card-head strong {
        font-size:16px;
    }

    .card-head span {
        display:block;
        margin-top:4px;
        color:var(--muted);
        font-size:14px;
    }

    .card-body {
        padding:20px;
    }

    .grid {
        display:grid;
        grid-template-columns:repeat(2,minmax(0,1fr));
        gap:18px;
    }

    .full {
        grid-column:1/-1;
    }

    .summary-grid {
        display:grid;
        grid-template-columns:repeat(3,minmax(0,1fr));
        gap:10px;
        margin-bottom:20px;
    }

    .summary-box {
        padding:14px;
        border:1px solid #e8eeeb;
        border-radius:10px;
        background:#fafcfb;
    }

    .summary-box label {
        display:block;
        color:#6d7571;
        font-size:12px;
        text-transform:uppercase;
        letter-spacing:.4px;
    }

    .summary-box strong {
        display:block;
        margin-top:6px;
        font-size:18px;
    }

    .notice {
        display:flex;
        gap:10px;
        padding:13px;
        border-radius:10px;
        background:#f7f9f8;
        border:1px solid #e4ebe7;
        color:#66726c;
        font-size:14px;
        line-height:1.6;
    }

    .notice i {
        color:var(--primary);
        font-size:18px;
    }

    .side-number {
        text-align:center;
        font-size:28px;
        font-weight:700;
        color:var(--primary);
    }

    .side-label {
        text-align:center;
        color:var(--muted);
        margin-top:4px;
    }

    .side-row {
        display:flex;
        justify-content:space-between;
        gap:10px;
        padding:10px 0;
        border-bottom:1px solid #eef1ef;
        font-size:14px;
    }

    .side-row:last-child {
        border-bottom:none;
    }

    .side-row span:first-child {
        color:#6d7571;
    }

    .side-row span:last-child {
        font-weight:650;
        text-align:right;
    }

    @media(max-width:900px) {
        .layout {
            grid-template-columns:1fr;
        }
    }

    @media(max-width:650px) {
        .page-header {
            flex-direction:column;
        }

        .grid,
        .summary-grid {
            grid-template-columns:1fr;
        }

        .full {
            grid-column:auto;
        }
    }
</style>
@endpush

@section('content')

<div class="page-header">

    <div>
        <h1>Record Payment</h1>

        <p>
            {{ $assistance->assistance_number }}
            · {{ $assistance->beneficiary?->full_name }}
        </p>
    </div>

    <div class="actions">
        <a
            href="{{ route('welfare.assistance.show', $assistance) }}"
            class="ui-btn"
        >
            <i class="bi bi-arrow-left"></i>
            Back
        </a>
    </div>

</div>

@if($errors->any())
    <div class="alert alert-danger">
        <strong>Please correct the following:</strong>

        <ul class="mb-0 mt-2">
            @foreach($errors->all() as $error)
                <li>{{ $error }}</li>
            @endforeach
        </ul>
    </div>
@endif

<div class="layout">

    <div>

        <div class="card">

            <div class="card-head">
                <strong>Payment Details</strong>

                <span>
                    This payment will be posted to the selected assistance fund.
                </span>
            </div>

            <div class="card-body">

                <div class="summary-grid">

                    <div class="summary-box">
                        <label>Approved Assistance</label>
                        <strong>
                            PKR {{ \App\Domain\Finance\Support\Money::format($assistance->amount) }}
                        </strong>
                    </div>

                    <div class="summary-box">
                        <label>Paid So Far</label>
                        <strong>
                            PKR {{ \App\Domain\Finance\Support\Money::format($paidSoFar) }}
                        </strong>
                    </div>

                    <div class="summary-box">
                        <label>Remaining Approved Amount</label>
                        <strong>
                            PKR {{ \App\Domain\Finance\Support\Money::format($remaining) }}
                        </strong>
                    </div>

                </div>

                <p class="text-muted">
                    Remaining approval equals the approved amount minus all posted payments.
                    Weekly or monthly frequency does not reset this amount. Voided payments are excluded.
                </p>
                @if(\App\Domain\Finance\Support\Money::minor($remaining) === 0)
                    <div class="alert alert-warning" role="alert">
                        The approved amount has already been fully paid or exceeded. No additional payment can be recorded.
                    </div>
                @endif

                <form
                    method="POST"
                    action="{{ route('welfare.assistance.payments.store', $assistance) }}"
                >
                    @csrf
                    <input
                        type="hidden"
                        name="idempotency_key"
                        value="{{ old(
                            'idempotency_key',
                            (string) \Illuminate\Support\Str::uuid()
                        ) }}"
                    >

                    <div class="grid">

                        <div>
                            <label class="form-label">
                                Payment Amount (PKR)
                            </label>

                            <input
                                type="number"
                                name="amount"
                                class="form-control"
                                min="0.01"
                                step="0.01"
                                max="{{ $maximumPayment }}"
                                value="{{ old(
                                    'amount',
                                    $maximumPayment
                                ) }}"
                                required
                            >
                        </div>

                        <div>
                            <label class="form-label">
                                Payment Date
                            </label>

                            <input
                                type="date"
                                name="payment_date"
                                class="form-control"
                                value="{{ old(
                                    'payment_date',
                                    now()->format('Y-m-d')
                                ) }}"
                                required
                            >
                        </div>

                        <div>
                            <label class="form-label">
                                Payment Method
                            </label>

                            <select
                                name="payment_method"
                                class="form-select"
                                required
                            >
                                <option value="">Select method</option>

                                <option
                                    value="cash"
                                    {{ old('payment_method') === 'cash' ? 'selected' : '' }}
                                >
                                    Cash
                                </option>

                                <option
                                    value="bank_transfer"
                                    {{ old('payment_method') === 'bank_transfer' ? 'selected' : '' }}
                                >
                                    Bank Transfer
                                </option>

                                <option
                                    value="mobile_wallet"
                                    {{ old('payment_method') === 'mobile_wallet' ? 'selected' : '' }}
                                >
                                    Mobile Wallet
                                </option>

                                <option
                                    value="cheque"
                                    {{ old('payment_method') === 'cheque' ? 'selected' : '' }}
                                >
                                    Cheque
                                </option>
                            </select>
                        </div>

                        <div>
                            <label class="form-label">
                                Paid To
                            </label>

                            <input
                                type="text"
                                name="paid_to"
                                class="form-control"
                                value="{{ old(
                                    'paid_to',
                                    $assistance->beneficiary?->full_name
                                ) }}"
                            >
                        </div>

                        <div>
                            <label class="form-label">
                                Transaction Reference
                            </label>

                            <input
                                type="text"
                                name="transaction_reference"
                                class="form-control"
                                value="{{ old('transaction_reference') }}"
                            >
                        </div>

                        <div>
                            <label class="form-label">
                                Receipt Number
                            </label>

                            <input
                                type="text"
                                name="receipt_number"
                                class="form-control"
                                value="{{ old('receipt_number') }}"
                            >
                        </div>

                        <div class="full">
                            <label class="form-label">
                                Payment Notes
                            </label>

                            <textarea
                                name="notes"
                                rows="4"
                                class="form-control"
                            >{{ old('notes') }}</textarea>
                        </div>

                        <div class="full">

                            <div class="notice">
                                <i class="bi bi-info-circle"></i>

                                <div>
                                    This payment will create a
                                    <strong>posted debit transaction</strong>
                                    in the Finance ledger and reduce the
                                    selected fund's available balance.
                                </div>
                            </div>

                        </div>

                    </div>

                    <div class="d-flex justify-content-end gap-2 mt-4 pt-4 border-top">

                        <a
                            href="{{ route('welfare.assistance.show', $assistance) }}"
                            class="ui-btn"
                        >
                            Cancel
                        </a>

                        <button
                            type="submit"
                            class="ui-btn primary"
                            onclick="return confirm('Record and post this payment?')"
                            @disabled(\App\Domain\Finance\Support\Money::minor($maximumPayment) === 0)
                        >
                            <i class="bi bi-wallet2"></i>
                            Record Payment
                        </button>

                    </div>

                </form>

            </div>

        </div>

    </div>

    <aside>

        <div class="card">

            <div class="card-body">

                <div class="side-number">
                    PKR {{ \App\Domain\Finance\Support\Money::format($availableBalance) }}
                </div>

                <div class="side-label">
                    Available Fund Balance
                </div>

                <div style="
                    height:1px;
                    background:#eef1ef;
                    margin:16px 0;
                "></div>

                <div class="side-row">
                    <span>Fund</span>
                    <span>{{ $fund->name }}</span>
                </div>

                <div class="side-row">
                    <span>Beneficiary</span>
                    <span>
                        {{ $assistance->beneficiary?->full_name ?? '—' }}
                    </span>
                </div>

                <div class="side-row">
                    <span>Assistance</span>
                    <span>
                        {{ $assistance->assistance_number }}
                    </span>
                </div>

                <div class="side-row">
                    <span>Status</span>
                    <span>
                        {{ ucfirst($assistance->status) }}
                    </span>
                </div>

            </div>

        </div>

        <div class="card">

            <div class="card-head">
                <strong>Financial Control</strong>
            </div>

            <div class="card-body">

                <div class="notice">
                    <i class="bi bi-shield-check"></i>

                    <div>
                        The payment is checked against the fund's
                        available balance before the ledger transaction
                        is created.
                    </div>
                </div>

            </div>

        </div>

    </aside>

</div>

@endsection
