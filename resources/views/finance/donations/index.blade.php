@extends('layouts.app')

@section('title', 'Donations')

@push('styles')
<link rel="stylesheet" href="{{ asset('css/views/finance-donations-index-1.css') }}">
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

            @if(auth()->user()?->hasPermission('finance.view') && auth()->user()?->hasPermission('finance.donations.manage'))<a
                href="{{ route('finance.donations.create') }}"
                class="ui-btn primary"
            >
                <i class="bi bi-plus-lg"></i>
                New Donation
            </a>@endif

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
            PKR {{ \App\Domain\Finance\Support\Money::format($donationsThisMonth) }}
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
            PKR {{ \App\Domain\Finance\Support\Money::format($restrictedFunds) }}
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
            PKR {{ \App\Domain\Finance\Support\Money::format($unrestrictedFunds) }}
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
                            PKR {{ \App\Domain\Finance\Support\Money::format($donation->amount) }}
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
                                        @if(auth()->user()?->hasPermission('finance.view'))<a
                                            href="{{ route('finance.transactions.show', $donation->transaction) }}"
                                            style="color:inherit;text-decoration:none;"
                                        >
                                            {{ $donation->transaction->transaction_number }}
                                        </a>@endif
                                    </div>

                                @else

                                    <strong style="font-size:13px;">
                                        Ledger
                                    </strong>

                                    <span style="
                                        display:block;
                                        margin-top:4px;
                                        color:var(--legacy-text-6e7772, #6e7772);
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

                                @if(auth()->user()?->hasPermission('finance.view'))<a
                                    href="{{ route('finance.donations.show', $donation) }}"
                                    class="action"
                                    title="View donation"
                                >
                                    <i class="bi bi-eye"></i>
                                </a>@endif

                                @if(auth()->user()->hasPermission('finance.donations.manage'))

                                    @if(auth()->user()?->hasPermission('finance.view') && auth()->user()?->hasPermission('finance.donations.manage'))<a
                                        href="{{ route('finance.donations.edit', $donation) }}"
                                        class="action"
                                        title="Edit donation"
                                    >
                                        <i class="bi bi-pencil"></i>
                                    </a>@endif

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