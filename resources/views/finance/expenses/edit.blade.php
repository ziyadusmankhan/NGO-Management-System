@extends('layouts.app')

@section('title', 'Edit Expense')

@push('styles')
<link rel="stylesheet" href="{{ asset('css/views/finance-expenses-edit-1.css') }}">
@endpush

@section('content')

<div class="page-wrap">

    <div class="page-header">

        <div>
            <h1>
                Edit {{ $expense->expense_number }}
            </h1>

            <p>
                Update the expense record and its financial information.
            </p>
        </div>

        @if(auth()->user()?->hasPermission('finance.view'))<a
            href="{{ route('finance.expenses.show', $expense) }}"
            class="ui-btn"
        >
            <i class="bi bi-arrow-left"></i>
            Back
        </a>@endif

    </div>


    <div class="card">

        <form
            id="editExpenseForm"
            method="POST"
            action="{{ route('finance.expenses.update', $expense) }}"
        >

            @csrf
@include('finance.expenses.purchase-kind')
            @method('PUT')

            <div class="grid">

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
                                data-type="{{ $fund->type }}"
                                data-purpose="{{ $fund->purpose }}"
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

                    <div
                        id="editFundRestrictionMessage"
                        style="
                            display:none;
                            margin-top:8px;
                            padding:9px 11px;
                            border-radius:8px;
                            background:#fff5e9;
                            border:1px solid #ead9bc;
                            color:#8a6637;
                            font-size:13px;
                        "
                    ></div>

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


                <div class="full">
                    <label class="label">
                        Description
                    </label>

                    <textarea
                        name="description"
                        class="control"
                        required
                    >{{ old('description', $expense->description) }}</textarea>
                </div>


                <div class="full">
                    <label class="label">
                        Purpose Notes
                    </label>

                    <textarea
                        name="purpose_notes"
                        class="control"
                    >{{ old('purpose_notes', $expense->purpose_notes) }}</textarea>
                </div>


                <div class="full">
                    <label class="label">
                        Internal Notes
                    </label>

                    <textarea
                        name="internal_notes"
                        class="control"
                    >{{ old('internal_notes', $expense->internal_notes) }}</textarea>
                </div>


                <div class="full">

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


            <div class="form-actions">

                @if(auth()->user()?->hasPermission('finance.view'))<a
                    href="{{ route('finance.expenses.show', $expense) }}"
                    class="ui-btn"
                >
                    Cancel
                </a>@endif

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
@push('scripts')
<script>
    const editFund =
        document.querySelector('select[name="fund_id"]');

    const editCategory =
        document.querySelector('select[name="category"]');

    const editForm =
        document.getElementById('editExpenseForm');

    const editMessage =
        document.getElementById(
            'editFundRestrictionMessage'
        );

    const allowedCategories = {
        general_welfare: [
            'welfare',
            'medical',
            'education',
        ],

        small_business: [
            'project',
            'other',
        ],

        qard: [
            'welfare',
            'medical',
            'education',
        ],

        education: [
            'education',
        ],

        emergency: [
            'medical',
            'welfare',
        ],

        community_development: [
            'project',
        ],
    };

    function editCategoryAllowed() {

        const option =
            editFund.options[
                editFund.selectedIndex
            ];

        if (!option || !option.value) {
            return true;
        }

        if (option.dataset.type === 'general') {
            return true;
        }

        const allowed =
            allowedCategories[
                option.dataset.purpose
            ] || [];

        return allowed.includes(
            editCategory.value
        );
    }

    function updateEditRestriction() {

        const option =
            editFund.options[
                editFund.selectedIndex
            ];

        if (
            !option ||
            !option.value ||
            option.dataset.type === 'general' ||
            editCategoryAllowed()
        ) {
            editMessage.style.display = 'none';
            editMessage.textContent = '';
            return;
        }

        const purpose =
            option.dataset.purpose
                .replaceAll('_', ' ');

        editMessage.textContent =
            `This category is not permitted for ` +
            `${option.textContent.trim()}. ` +
            `Configured purpose: ${purpose}.`;

        editMessage.style.display = 'block';
    }

    editFund.addEventListener(
        'change',
        updateEditRestriction
    );

    editCategory.addEventListener(
        'change',
        updateEditRestriction
    );

    editForm.addEventListener(
        'submit',
        function (event) {

            if (!editCategoryAllowed()) {

                event.preventDefault();

                alert(
                    'The selected expense category is not permitted for this fund.'
                );

                updateEditRestriction();
            }
        }
    );

    updateEditRestriction();
</script>
@endpush