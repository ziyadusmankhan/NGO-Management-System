@extends('layouts.app')

@section('title', 'Edit Donation')

@push('styles')
<link rel="stylesheet" href="{{ asset('css/views/finance-donations-edit-1.css') }}">
@endpush


@section('content')
<div class="alert alert-info">For a donation restricted to a named project, first create its dedicated fund on the Project page. Select that fund below, with <strong>Restricted</strong> designation and <strong>Project</strong> purpose. Its remaining balance stays restricted after project closure.</div>

<div class="page-header">

    <div>

        <h1>
            Edit Donation
        </h1>

        <p>
            Update {{ $donation->donation_number }}.
        </p>

    </div>


    <div class="actions">

        @if(auth()->user()?->hasPermission('finance.view'))<a
            href="{{ route('finance.donations.show', $donation) }}"
            class="ui-btn"
        >
            <i class="bi bi-arrow-left"></i>
            Cancel
        </a>@endif

        <button
            type="submit"
            form="donationEditForm"
            class="ui-btn primary"
        >
            <i class="bi bi-check-lg"></i>
            Save Changes
        </button>

    </div>

</div>


@if ($errors->any())

    <div class="card">

        <div class="card-body">

            <strong style="color:var(--legacy-text-a85f58, #a85f58);">
                Please correct the following:
            </strong>

            <ul style="
                margin:8px 0 0;
                padding-left:20px;
                color:var(--legacy-text-a85f58, #a85f58);
                font-size:12px;
            ">

                @foreach ($errors->all() as $error)

                    <li>
                        {{ $error }}
                    </li>

                @endforeach

            </ul>

        </div>

    </div>

@endif


<form
    id="donationEditForm"
    method="POST"
    action="{{ route('finance.donations.update', $donation) }}"
>

    @csrf
    @method('PUT')


    <div class="layout">

        <div>

            <!-- DONOR -->

            <div class="card">

                <div class="card-head">

                    <strong>
                        Donor Information
                    </strong>

                    <span>
                        Update the contributor information.
                    </span>

                </div>


                <div class="card-body">

                    <div class="grid">

                        <div class="full">

                            <label class="label">
                                Donor Type
                            </label>

                            <div class="radio-grid">

                                @foreach([
                                    'individual' => 'Individual',
                                    'family' => 'Family',
                                    'organization' => 'Organization'
                                ] as $value => $label)

                                    <div class="radio-option">

                                        <input
                                            type="radio"
                                            name="donor_type"
                                            id="donorType{{ $value }}"
                                            value="{{ $value }}"
                                            @checked(
                                                old(
                                                    'donor_type',
                                                    $donation->donor_type
                                                ) === $value
                                            )
                                        >

                                        <label
                                            for="donorType{{ $value }}"
                                        >
                                            {{ $label }}
                                        </label>

                                    </div>

                                @endforeach

                            </div>

                        </div>


                        <div>

                            <label class="label">
                                Donor Name *
                            </label>

                            <input
                                type="text"
                                name="donor_name"
                                class="control"
                                value="{{ old('donor_name', $donation->donor_name) }}"
                                required
                            >

                            @error('donor_name')
                                <div class="error">
                                    {{ $message }}
                                </div>
                            @enderror

                        </div>


                        <div>

                            <label class="label">
                                Donation ID
                            </label>

                            <input
                                type="text"
                                class="control"
                                value="{{ $donation->donation_number }}"
                                readonly
                            >

                        </div>


                        <div>

                            <label class="label">
                                Phone
                            </label>

                            <input
                                type="text"
                                name="phone"
                                class="control"
                                value="{{ old('phone', $donation->phone) }}"
                            >

                        </div>


                        <div>

                            <label class="label">
                                Email
                            </label>

                            <input
                                type="email"
                                name="email"
                                class="control"
                                value="{{ old('email', $donation->email) }}"
                            >

                        </div>

                    </div>

                </div>

            </div>


            <!-- DETAILS -->

            <div class="card">

                <div class="card-head">

                    <strong>
                        Donation Details
                    </strong>

                    <span>
                        Amount, date and payment information.
                    </span>

                </div>


                <div class="card-body">

                    <div class="grid">

                        <div>

                            <label class="label">
                                Amount *
                            </label>

                            <input
                                type="number"
                                name="amount"
                                min="0.01"
                                step="0.01"
                                class="control"
                                value="{{ old('amount', $donation->amount) }}"
                                required
                            >

                        </div>


                        <div>

                            <label class="label">
                                Donation Date *
                            </label>

                            <input
                                type="date"
                                name="donation_date"
                                class="control"
                                value="{{ old(
                                    'donation_date',
                                    $donation->donation_date->format('Y-m-d')
                                ) }}"
                                required
                            >

                        </div>


                        <div class="full">

                            <label class="label">
                                Payment Method *
                            </label>

                            <div class="radio-grid">

                                @foreach([
                                    'cash' => 'Cash',
                                    'bank' => 'Bank Transfer',
                                    'mobile' => 'Mobile Payment',
                                    'online' => 'Online'
                                ] as $value => $label)

                                    <div class="radio-option">

                                        <input
                                            type="radio"
                                            name="payment_method"
                                            id="payment{{ $value }}"
                                            value="{{ $value }}"
                                            @checked(
                                                old(
                                                    'payment_method',
                                                    $donation->payment_method
                                                ) === $value
                                            )
                                        >

                                        <label
                                            for="payment{{ $value }}"
                                        >
                                            {{ $label }}
                                        </label>

                                    </div>

                                @endforeach

                            </div>

                        </div>


                        <div>

                            <label class="label">
                                Transaction Reference
                            </label>

                            <input
                                type="text"
                                name="transaction_reference"
                                class="control"
                                value="{{ old(
                                    'transaction_reference',
                                    $donation->transaction_reference
                                ) }}"
                            >

                        </div>


                        <div>

                            <label class="label">
                                Receipt Number
                            </label>

                            <input
                                type="text"
                                name="receipt_number"
                                class="control"
                                value="{{ old(
                                    'receipt_number',
                                    $donation->receipt_number
                                ) }}"
                            >

                        </div>

                    </div>

                </div>

            </div>


            <!-- CLASSIFICATION -->

            <div class="card">

                <div class="card-head">

                    <strong>
                        Purpose & Fund
                    </strong>

                    <span>
                        Update the donation classification.
                    </span>

                </div>


                <div class="card-body">

                    <div class="grid">

                        <div class="full">

                            <label class="label">
                                Designation *
                            </label>

                            <div class="radio-grid">

                                <div class="radio-option">

                                    <input
                                        type="radio"
                                        name="designation"
                                        id="designationUnrestricted"
                                        value="unrestricted"
                                        @checked(
                                            old(
                                                'designation',
                                                $donation->designation
                                            ) === 'unrestricted'
                                        )
                                    >

                                    <label for="designationUnrestricted">
                                        Unrestricted
                                    </label>

                                </div>


                                <div class="radio-option">

                                    <input
                                        type="radio"
                                        name="designation"
                                        id="designationRestricted"
                                        value="restricted"
                                        @checked(
                                            old(
                                                'designation',
                                                $donation->designation
                                            ) === 'restricted'
                                        )
                                    >

                                    <label for="designationRestricted">
                                        Restricted
                                    </label>

                                </div>

                            </div>

                        </div>


                        <div>

                            <label class="label">
                                Purpose *
                            </label>

                            <select
                                name="purpose"
                                class="select"
                            >

                                @foreach([
                                    'general' => 'General Welfare',
                                    'business' => 'Small Business Assistance',
                                    'qard' => 'Qard-e-Hasana',
                                    'education' => 'Education Support',
                                    'emergency' => 'Emergency Assistance',
                                    'project' => 'Community Project'
                                ] as $value => $label)

                                    <option
                                        value="{{ $value }}"
                                        @selected(
                                            old(
                                                'purpose',
                                                $donation->purpose
                                            ) === $value
                                        )
                                    >
                                        {{ $label }}
                                    </option>

                                @endforeach

                            </select>

                        </div>


                        <div>

                            <label class="label">
                                Fund
                            </label>

                            <select
                                name="fund_id"
                                class="select"
                            >

                                <option value="">
                                    Not assigned
                                </option>

                                @foreach ($funds as $fund)

                                    <option
                                        value="{{ $fund->id }}"
                                        @selected(
                                            old(
                                                'fund_id',
                                                $donation->fund_id
                                            ) == $fund->id
                                        )
                                    >
                                        {{ $fund->name }}{{ $fund->project_id ? ' · Dedicated project fund' : '' }}
                                        ({{ $fund->fund_reference }})
                                    </option>

                                @endforeach

                            </select>

                        </div>


                        <div class="full">

                            <label class="label">
                                Purpose Notes
                            </label>

                            <textarea
                                name="purpose_notes"
                                class="control"
                            >{{ old(
                                'purpose_notes',
                                $donation->purpose_notes
                            ) }}</textarea>

                        </div>


                        <div class="full">

                            <label class="label">
                                Internal Notes
                            </label>

                            <textarea
                                name="internal_notes"
                                class="control"
                            >{{ old(
                                'internal_notes',
                                $donation->internal_notes
                            ) }}</textarea>

                        </div>


                        <div>

                            <label class="label">
                                Status *
                            </label>

                            <select
                                name="status"
                                class="select"
                            >

                                @foreach([
                                    'received' => 'Received',
                                    'pending' => 'Pending',
                                    'restricted' => 'Restricted'
                                ] as $value => $label)

                                    <option
                                        value="{{ $value }}"
                                        @selected(
                                            old(
                                                'status',
                                                $donation->status
                                            ) === $value
                                        )
                                    >
                                        {{ $label }}
                                    </option>

                                @endforeach

                            </select>

                        </div>

                    </div>

                </div>

            </div>

        </div>


        <aside>

            <div class="side-card">

                <div class="side-title">
                    Donation Summary
                </div>

                <div class="side-number">
                    {{ $donation->donation_number }}
                </div>

                <div class="side-label">
                    Donation Reference
                </div>

                <div style="
                    height:1px;
                    background:var(--legacy-surface-eef1ef, #eef1ef);
                    margin:16px 0;
                "></div>

                <div class="side-row">
                    <span>Amount</span>
                    <span>
                        PKR {{ \App\Domain\Finance\Support\Money::format($donation->amount) }}
                    </span>
                </div>

                <div class="side-row">
                    <span>Donor</span>
                    <span>
                        {{ $donation->donor_name }}
                    </span>
                </div>

                <div class="side-row">
                    <span>Status</span>
                    <span>
                        {{ ucfirst($donation->status) }}
                    </span>
                </div>

                <div class="side-row">
                    <span>Created</span>
                    <span>
                        {{ $donation->created_at->format('d M Y') }}
                    </span>
                </div>

            </div>


            <div class="side-card">

                <div class="notice">

                    <i class="bi bi-shield-check"></i>

                    <p>
                        Donation reference numbers are system-generated
                        and cannot be changed. Changes to financial
                        information should remain traceable.
                    </p>

                </div>

            </div>

        </aside>

    </div>

</form>

@endsection