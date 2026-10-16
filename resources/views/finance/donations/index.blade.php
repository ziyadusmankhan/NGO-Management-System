@extends('layouts.app')

@section('title', 'Donations')

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

    /* SUMMARY */

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

    /* MAIN */

    .main-card {
        background:#fff;
        border:1px solid var(--border);
        border-radius:14px;
        overflow:hidden;
    }

    .toolbar {
        padding:15px 17px;
        border-bottom:1px solid #eef1ef;
        display:flex;
        gap:9px;
        flex-wrap:wrap;
    }

    .search {
        position:relative;
        flex:1;
        min-width:230px;
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

    .search input:focus {
        background:#fff;
        border-color: #6e7772;
        box-shadow:0 0 0 3px rgba(23,63,53,.06);
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

    /* TABLE */

    .table-wrap {
        overflow-x:auto;
    }

    .donation-table {
        width:100%;
        min-width:1150px;
        border-collapse:collapse;
    }

    .donation-table th {
        padding:11px 18px;
        background:#fafcfb;
        border-bottom:1px solid #eef1ef;
        color: #6c7470;
        font-size: 13px;
        font-weight:700;
        text-transform:uppercase;
        letter-spacing:.6px;
        text-align:left;
        white-space:nowrap;
    }

    .donation-table td {
        padding:13px 18px;
        border-bottom:1px solid #f0f3f1;
        font-size: 14px;
        vertical-align:middle;
    }

    .donation-table tbody tr:hover {
        background:#fbfcfb;
    }

    .donation-table tbody tr:last-child td {
        border-bottom:none;
    }

    .donor {
        display:flex;
        align-items:center;
        gap:9px;
    }

    .avatar {
        width:35px;
        height:35px;
        border-radius:10px;
        background:var(--primary-soft);
        color:var(--primary);
        display:flex;
        align-items:center;
        justify-content:center;
        font-size: 14px;
        font-weight:700;
        flex-shrink:0;
    }
    .ledger-status {
    display:inline-flex;
    align-items:center;
    gap:5px;
    padding:5px 8px;
    border-radius:20px;
    font-size:13px;
    font-weight:650;
    white-space:nowrap;
    }

    .ledger-status.posted {
        background:#edf7f0;
        color:#397955;
    }

    .ledger-status.void {
        background:#f1eef7;
        color:#77668c;
    }

    .ledger-status.none {
        background:#f5f6f5;
        color:#7a8580;
    }

    .ledger-reference {
        margin-top:4px;
        font-size:12px;
        color:#6e7772;
    }

    .donor-name {
        font-size: 15px;
        font-weight:650;
    }

    .donor-meta {
        margin-top:3px;
        color: #6d7571;
        font-size: 13px;
    }

    .amount {
        font-weight:700;
        color:#4b5851;
    }

    .fund {
        display:inline-flex;
        padding:5px 8px;
        border-radius:20px;
        background:#eef3f8;
        color:#55758d;
        font-size: 13px;
        font-weight:650;
        white-space:nowrap;
    }

    .fund.restricted {
        background:#f1eef7;
        color:#77668c;
    }

    .fund.emergency {
        background:#fff5e9;
        color: #976d38;
    }

    .purpose {
        color:#68756f;
    }

    .payment {
        color:#6d7973;
        font-size: 13px;
    }

    .status {
        display:inline-flex;
        align-items:center;
        gap:5px;
        padding:5px 8px;
        border-radius:20px;
        font-size: 13px;
        font-weight:650;
        white-space:nowrap;
    }

    .status::before {
        content:"";
        width:5px;
        height:5px;
        border-radius:50%;
    }

    .status.received {
        background:#edf7f0;
        color:#397955;
    }

    .status.received::before {
        background:#4c9a6c;
    }

    .status.pending {
        background:#fff5e9;
        color: #976d38;
    }

    .status.pending::before {
        background:#d19a4d;
    }

    .status.restricted {
        background:#f1eef7;
        color:#77668c;
    }

    .status.restricted::before {
        background:#8c77a3;
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

    .action:hover {
        color:var(--primary);
        background:#f7faf8;
    }

    /* FOOTER */

    .table-footer {
        padding:14px 18px;
        border-top:1px solid #eef1ef;
        display:flex;
        justify-content:space-between;
        align-items:center;
        gap:10px;
    }

    .result {
        color: #6e7672;
        font-size: 13px;
    }

    .pagination {
        display:flex;
        gap:5px;
    }

    .page-btn {
        min-width:29px;
        height:29px;
        border:1px solid var(--border);
        background:#fff;
        border-radius:7px;
        color:#69756f;
        font-size: 13px;
    }

    .page-btn.active {
        background:var(--primary);
        border-color:var(--primary);
        color:#fff;
    }

    /* NOTICE */

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

        .summary-grid {
            grid-template-columns:repeat(2,1fr);
        }

        .search {
            min-width:100%;
        }

        .filter {
            flex:1;
            min-width:0;
        }

        .table-footer {
            flex-direction:column;
            align-items:flex-start;
        }
    }

    @media(max-width:450px) {
        .summary-grid {
            grid-template-columns:1fr;
        }
    }
</style>
@endpush


@section('content')

<div class="page-header">

    <div>

        <h1>Donations</h1>

        <p>
            Record and monitor donor contributions, purposes, funds and payment status.
        </p>

    </div>

    <div class="actions">

        <button class="ui-btn">
            <i class="bi bi-download"></i>
            Export
        </button>

        @if(auth()->user()->hasPermission('finance.donations.manage'))

            <a
                href="{{ route('finance.donations.create') }}"
                class="ui-btn primary"
            >
                <i class="bi bi-plus-lg"></i>
                New Donation
            </a>

        @endif

    </div>

</div>


<!-- SUMMARY -->

<div class="summary-grid">

    <div class="summary-card">

        <div class="summary-label">
            Donations This Month
        </div>

        <div class="summary-value">
            PKR {{ number_format($donationsThisMonth, 0) }}
        </div>

        <div class="summary-meta">
            {{ now()->format('F Y') }}
        </div>

    </div>


    <div class="summary-card">

        <div class="summary-label">
            Total Donations
        </div>

        <div class="summary-value">
            {{ number_format($totalDonations) }}
        </div>

        <div class="summary-meta">
            This year
        </div>

    </div>


    <div class="summary-card">

        <div class="summary-label">
            Restricted Funds
        </div>

        <div class="summary-value">
            PKR {{ number_format($restrictedFunds, 0) }}
        </div>

        <div class="summary-meta">
            Designated donations
        </div>

    </div>


    <div class="summary-card">

        <div class="summary-label">
            Unrestricted Funds
        </div>

        <div class="summary-value">
            PKR {{ number_format($unrestrictedFunds, 0) }}
        </div>

        <div class="summary-meta">
            General donations
        </div>

    </div>


    <div class="summary-card">

        <div class="summary-label">
            Pending
        </div>

        <div class="summary-value">
            {{ number_format($pendingDonations) }}
        </div>

        <div class="summary-meta">
            Awaiting confirmation
        </div>

    </div>

</div>


<div class="main-card">

    <form
        method="GET"
        action="{{ route('finance.donations.index') }}"
        class="toolbar"
        id="donationFilters"
    >

        <div class="search">

            <i class="bi bi-search"></i>

            <input
                id="donationSearch"
                name="search"
                type="text"
                value="{{ request('search') }}"
                placeholder="Search donor, donation ID, fund or purpose..."
            >

        </div>


        <select
            id="fundFilter"
            name="fund"
            class="filter"
        >
            <option value="">
                All Funds
            </option>

            @foreach($funds as $fund)
                <option
                    value="{{ $fund->id }}"
                    @selected((string) request('fund') === (string) $fund->id)
                >
                    {{ $fund->name }}
                </option>
            @endforeach
        </select>


        <select
            id="paymentFilter"
            name="payment_method"
            class="filter"
        >

            <option value="">
                All Payment Methods
            </option>

            <option value="cash" @selected(request('payment_method') === 'cash')>
                Cash
            </option>

            <option value="bank" @selected(request('payment_method') === 'bank')>
                Bank Transfer
            </option>

            <option value="mobile" @selected(request('payment_method') === 'mobile')>
                Mobile Payment
            </option>

            <option value="online" @selected(request('payment_method') === 'online')>
                Online
            </option>

        </select>


        <select
            id="statusFilter"
            name="status"
            class="filter"
        >

            <option value="">
                All Statuses
            </option>

            <option value="received" @selected(request('status') === 'received')>
                Received
            </option>

            <option value="pending" @selected(request('status') === 'pending')>
                Pending
            </option>

            <option value="restricted" @selected(request('status') === 'restricted')>
                Restricted
            </option>

        </select>

    </form>


    <div class="table-wrap">

        <table class="donation-table">

            <thead>

                <tr>
                    <th>Donor</th>
                    <th>Donation ID</th>
                    <th>Amount</th>
                    <th>Purpose</th>
                    <th>Fund</th>
                    <th>Payment</th>
                    <th>Date</th>
                    <th>Status</th>
                    <th>Ledger</th>
                    <th></th>
                </tr>

            </thead>


            <tbody id="donationBody">

                @php
                    $purposeLabels = [
                        'general' => 'General Welfare',
                        'business' => 'Small Business Assistance',
                        'qard' => 'Qard-e-Hasana',
                        'education' => 'Education Support',
                        'emergency' => 'Emergency Assistance',
                        'project' => 'Community Project',
                    ];

                    $paymentLabels = [
                        'cash' => 'Cash',
                        'bank' => 'Bank Transfer',
                        'mobile' => 'Mobile Payment',
                        'online' => 'Online',
                    ];

                    $fundLabels = [
                        'general' => 'General Fund',
                        'business' => 'Small Business Fund',
                        'qard' => 'Qard-e-Hasana Fund',
                        'education' => 'Education Fund',
                        'emergency' => 'Emergency Fund',
                    ];
                @endphp

                @forelse($donations as $donation)

                    @php
                        $initials = collect(
                            preg_split('/\s+/', trim($donation->donor_name))
                        )
                        ->take(2)
                        ->map(
                            fn ($part) =>
                                strtoupper(substr($part, 0, 1))
                        )
                        ->implode('');
                    @endphp

                    <tr>

                        <td>

                            <div class="donor">

                                <div class="avatar">
                                    {{ $initials ?: '?' }}
                                </div>

                                <div>

                                    <div class="donor-name">
                                        {{ $donation->donor_name }}
                                    </div>

                                    <div class="donor-meta">
                                        {{ ucfirst($donation->donor_type) }} Donor
                                    </div>

                                </div>

                            </div>

                        </td>

                        <td>
                            {{ $donation->donation_number }}
                        </td>

                        <td class="amount">
                            PKR {{ number_format($donation->amount, 0) }}
                        </td>

                        <td class="purpose">
                            {{ $purposeLabels[$donation->purpose] ?? $donation->purpose }}
                        </td>

                        <td>

                            <span class="fund">
                                {{ $donation->fund->name ?? 'Not assigned' }}
                            </span>

                        </td>

                        <td class="payment">
                            {{ $paymentLabels[$donation->payment_method] ?? $donation->payment_method }}
                        </td>

                        <td>
                            {{ $donation->donation_date->format('d M Y') }}
                        </td>

                        <td>

                            <span class="status {{ $donation->status }}">
                                {{ ucfirst($donation->status) }}
                            </span>

                        </td>

                        <td>

                                @if($donation->transaction)

                                    @php
                                        $ledgerStatus = $donation->transaction->status;

                                        $ledgerStatusLabel = match ($ledgerStatus) {
                                            'posted' => 'Posted',
                                            'void' => 'Voided',
                                            default => ucfirst($ledgerStatus),
                                        };

                                        $ledgerStatusClass = match ($ledgerStatus) {
                                            'posted' => 'posted',
                                            'void' => 'void',
                                            default => 'none',
                                        };
                                    @endphp

                                    <span class="ledger-status {{ $ledgerStatusClass }}">
                                        {{ $ledgerStatusLabel }}
                                    </span>

                                    <div class="ledger-reference">
                                        <a
                                            href="{{ route('finance.transactions.show', $donation->transaction) }}"
                                            style="color:inherit;text-decoration:none;"
                                        >
                                            {{ $donation->transaction->transaction_number }}
                                        </a>
                                    </div>

                                @else

                                    <strong style="font-size:13px;">
                                        Ledger
                                    </strong>

                                    <span style="
                                        display:block;
                                        margin-top:4px;
                                        color:#6e7772;
                                        font-size:12px;
                                    ">
                                        {{ $donation->transaction?->transaction_number ?? 'No Entry' }}
                                    </span>

                                @endif

                            </td>

                        <td>

                            <div style="
                                display:flex;
                                gap:5px;
                            ">

                                <a
                                    href="{{ route('finance.donations.show', $donation) }}"
                                    class="action"
                                    title="View donation"
                                >
                                    <i class="bi bi-eye"></i>
                                </a>

                                @if(auth()->user()->hasPermission('finance.donations.manage'))

                                    <a
                                        href="{{ route('finance.donations.edit', $donation) }}"
                                        class="action"
                                        title="Edit donation"
                                    >
                                        <i class="bi bi-pencil"></i>
                                    </a>

                                @endif

                            </div>

                        </td>

                    </tr>

                @empty

                    <tr>
                        <td colspan="9" style="text-align:center; padding:32px; color:var(--muted);">
                            No donations found.
                        </td>
                    </tr>

                @endforelse

            </tbody>

        </table>

    </div>


    <div class="table-footer">

        <div class="result">
            @if($donations->total() > 0)
                Showing {{ $donations->firstItem() }}–{{ $donations->lastItem() }} of {{ $donations->total() }} donations
            @else
                No donations found
            @endif
        </div>

        <div class="pagination">
            {{ $donations->onEachSide(1)->links() }}
        </div>

    </div>

</div>


<div class="notice">

    <i class="bi bi-shield-check"></i>

    <div>

        <strong>
            Donation and fund control
        </strong>

        <p>
            Each donation can be classified by purpose and linked to the
            appropriate fund. Restricted donations should remain associated
            with their approved purpose and fund.
        </p>

    </div>

</div>

@endsection


@push('scripts')
<script>

    // Filtering now happens server-side (see DonationController@index),
    // so the filter/search fields just auto-submit the form.

    const donationFilterForm =
        document.getElementById('donationFilters');

    const fund =
        document.getElementById('fundFilter');

    const payment =
        document.getElementById('paymentFilter');

    const status =
        document.getElementById('statusFilter');

    const search =
        document.getElementById('donationSearch');


    [fund, payment, status].forEach((el) => {
        el.addEventListener('change', () => donationFilterForm.submit());
    });


    let searchTimer;

    search.addEventListener('input', () => {
        clearTimeout(searchTimer);
        searchTimer = setTimeout(() => donationFilterForm.submit(), 450);
    });

</script>
@endpush