@extends('layouts.app')

@section('title', 'Record Welfare Assistance Payment')

@push('styles')
<link rel="stylesheet" href="{{ asset('css/views/welfare-assistance-payments-create-1.css') }}">
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
        @if(auth()->user()?->hasPermission('welfare.assistance.view'))<a
            href="{{ route('welfare.assistance.show', $assistance) }}"
            class="ui-btn"
        >
            <i class="bi bi-arrow-left"></i>
            Back
        </a>@endif
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

                        @if(auth()->user()?->hasPermission('welfare.assistance.view'))<a
                            href="{{ route('welfare.assistance.show', $assistance) }}"
                            class="ui-btn"
                        >
                            Cancel
                        </a>@endif

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
                    background:var(--legacy-surface-eef1ef, #eef1ef);
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
