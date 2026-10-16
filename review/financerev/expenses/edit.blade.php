@extends('layouts.app')

@section('title', 'Edit Expense')

@section('content')

<div style="
    max-width:1000px;
    margin:0 auto;
">

    <div style="
        display:flex;
        justify-content:space-between;
        align-items:center;
        margin-bottom:20px;
    ">

        <div>
            <h1 style="margin:0;">
                Edit {{ $expense->expense_number }}
            </h1>

            <p style="
                margin:6px 0 0;
                color:var(--muted);
            ">
                Update the expense record and its financial information.
            </p>
        </div>

        <a
            href="{{ route('finance.expenses.show', $expense) }}"
            class="ui-btn"
        >
            <i class="bi bi-arrow-left"></i>
            Back
        </a>

    </div>


    <div class="card" style="
        background:#fff;
        border:1px solid var(--border);
        border-radius:14px;
        padding:24px;
    ">

        <form
            method="POST"
            action="{{ route('finance.expenses.update', $expense) }}"
        >

            @csrf
            @method('PUT')

            <div style="
                display:grid;
                grid-template-columns:repeat(2,minmax(0,1fr));
                gap:18px;
            ">

                <div>
                    <label class="label">
                        Expense Number
                    </label>

                    <input
                        class="control"
                        value="{{ $expense->expense_number }}"
                        readonly
                    >
                </div>


                <div>
                    <label class="label">
                        Expense Date
                    </label>

                    <input
                        type="date"
                        name="expense_date"
                        class="control"
                        value="{{ old('expense_date', $expense->expense_date->toDateString()) }}"
                        required
                    >
                </div>


                <div>
                    <label class="label">
                        Payee / Supplier
                    </label>

                    <input
                        name="payee_name"
                        class="control"
                        value="{{ old('payee_name', $expense->payee_name) }}"
                        required
                    >
                </div>


                <div>
                    <label class="label">
                        Fund
                    </label>

                    <select
                        name="fund_id"
                        class="select"
                        required
                    >

                        @foreach($funds as $fund)

                            <option
                                value="{{ $fund->id }}"
                                @selected(
                                    old(
                                        'fund_id',
                                        $expense->fund_id
                                    ) == $fund->id
                                )
                            >
                                {{ $fund->name }}
                            </option>

                        @endforeach

                    </select>
                </div>


                <div>
                    <label class="label">
                        Amount
                    </label>

                    <input
                        type="number"
                        name="amount"
                        class="control"
                        min="0.01"
                        step="0.01"
                        value="{{ old('amount', $expense->amount) }}"
                        required
                    >
                </div>


                <div>
                    <label class="label">
                        Category
                    </label>

                    <select
                        name="category"
                        class="select"
                        required
                    >

                        @foreach([
                            'welfare' => 'Welfare',
                            'project' => 'Community Project',
                            'admin' => 'Administration',
                            'education' => 'Education',
                            'medical' => 'Medical',
                            'other' => 'Other',
                        ] as $value => $label)

                            <option
                                value="{{ $value }}"
                                @selected(
                                    old(
                                        'category',
                                        $expense->category
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
                        Payment Method
                    </label>

                    <select
                        name="payment_method"
                        class="select"
                        required
                    >

                        @foreach([
                            'cash' => 'Cash',
                            'bank' => 'Bank Transfer',
                            'mobile' => 'Mobile Payment',
                            'vendor' => 'Direct Vendor',
                        ] as $value => $label)

                            <option
                                value="{{ $value }}"
                                @selected(
                                    old(
                                        'payment_method',
                                        $expense->payment_method
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
                        Status
                    </label>

                    <select
                        name="status"
                        class="select"
                        required
                    >

                        @foreach([
                            'pending' => 'Pending',
                            'approved' => 'Approved',
                            'rejected' => 'Rejected',
                        ] as $value => $label)

                            <option
                                value="{{ $value }}"
                                @selected(
                                    old(
                                        'status',
                                        $expense->status
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
                        Transaction Reference
                    </label>

                    <input
                        name="transaction_reference"
                        class="control"
                        value="{{ old(
                            'transaction_reference',
                            $expense->transaction_reference
                        ) }}"
                    >
                </div>


                <div>
                    <label class="label">
                        Receipt Number
                    </label>

                    <input
                        name="receipt_number"
                        class="control"
                        value="{{ old(
                            'receipt_number',
                            $expense->receipt_number
                        ) }}"
                    >
                </div>


                <div style="
                    grid-column:1/-1;
                ">
                    <label class="label">
                        Description
                    </label>

                    <textarea
                        name="description"
                        class="control"
                        required
                    >{{ old('description', $expense->description) }}</textarea>
                </div>


                <div style="
                    grid-column:1/-1;
                ">
                    <label class="label">
                        Purpose Notes
                    </label>

                    <textarea
                        name="purpose_notes"
                        class="control"
                    >{{ old('purpose_notes', $expense->purpose_notes) }}</textarea>
                </div>


                <div style="
                    grid-column:1/-1;
                ">
                    <label class="label">
                        Internal Notes
                    </label>

                    <textarea
                        name="internal_notes"
                        class="control"
                    >{{ old('internal_notes', $expense->internal_notes) }}</textarea>
                </div>


                <div style="
                    grid-column:1/-1;
                ">

                    <label style="
                        display:flex;
                        align-items:center;
                        gap:8px;
                    ">

                        <input
                            type="hidden"
                            name="has_supporting_document"
                            value="0"
                        >

                        <input
                            type="checkbox"
                            name="has_supporting_document"
                            value="1"
                            @checked(
                                old(
                                    'has_supporting_document',
                                    $expense->has_supporting_document
                                )
                            )
                        >

                        Supporting document verified

                    </label>

                </div>

            </div>


            <input
                type="hidden"
                name="approved_by"
                value="{{ $expense->approved_by }}"
            >

            <input
                type="hidden"
                name="approved_at"
                value="{{ $expense->approved_at?->toDateTimeString() }}"
            >


            <div style="
                margin-top:24px;
                display:flex;
                justify-content:flex-end;
                gap:10px;
            ">

                <a
                    href="{{ route('finance.expenses.show', $expense) }}"
                    class="ui-btn"
                >
                    Cancel
                </a>

                <button
                    type="submit"
                    class="ui-btn primary"
                >
                    <i class="bi bi-check-lg"></i>
                    Save Changes
                </button>

            </div>

        </form>

    </div>

</div>

@endsection