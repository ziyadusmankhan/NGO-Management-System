@extends('layouts.app')

@section('title', 'New Procurement')

@push('styles')
    <link rel="stylesheet" href="{{ asset('css/views/operations-procurement-create-1.css') }}">
@endpush


@section('content')

    <div class="page-header">

        <div>

            <h1>New Procurement Request</h1>

            <p>
                Create a purchasing request and route it through the appropriate approval process.
            </p>

        </div>

        <div class="actions">

            @if(auth()->user()?->hasPermission('procurement.view'))<a href="{{ route('operations.procurement.index') }}" class="ui-btn">
                <i class="bi bi-arrow-left"></i>
                Back
            </a>@endif

            <button class="ui-btn primary" onclick="previewCreateProcurement()">
                <i class="bi bi-check-lg"></i>
                Create Request
            </button>

        </div>

    </div>


    <div class="layout">

        <div>

            <!-- BASIC INFORMATION -->

            <div class="card">

                <div class="card-head">

                    <strong>Request Information</strong>

                    <span>
                        Define the procurement request and its purpose.
                    </span>

                </div>

                <div class="card-body">

                    <div class="grid">

                        <div class="full">

                            <label class="label">
                                Request Title <span class="required">*</span>
                            </label>

                            <input id="requestTitle" class="control" placeholder="e.g. Walking Path Materials">

                        </div>


                        <div>

                            <label class="label">
                                Procurement ID
                            </label>

                            <input class="control" value="PRC-00043" readonly>

                        </div>


                        <div>

                            <label class="label">
                                Request Date
                            </label>

                            <input id="requestDate" type="date" class="control" value="2026-08-18">

                        </div>


                        <div class="full">

                            <label class="label">
                                Procurement Description <span class="required">*</span>
                            </label>

                            <textarea id="description" class="control"
                                placeholder="Describe what needs to be purchased and why it is required..."></textarea>

                        </div>

                    </div>

                </div>

            </div>


            <!-- CATEGORY -->

            <div class="card">

                <div class="card-head">

                    <strong>Procurement Category</strong>

                    <span>
                        Select the organizational activity the procurement supports.
                    </span>

                </div>

                <div class="card-body">

                    <div class="category-grid">

                        <div class="category-option">

                            <input type="radio" name="category" id="project" value="Community Project" checked>

                            <label for="project">

                                <i class="bi bi-buildings"></i>

                                <strong>
                                    Community Project
                                </strong>

                                <span>
                                    Infrastructure / development
                                </span>

                            </label>

                        </div>


                        <div class="category-option">

                            <input type="radio" name="category" id="welfare" value="Welfare">

                            <label for="welfare">

                                <i class="bi bi-heart-handshake"></i>

                                <strong>
                                    Welfare
                                </strong>

                                <span>
                                    Direct beneficiary support
                                </span>

                            </label>

                        </div>


                        <div class="category-option">

                            <input type="radio" name="category" id="business" value="Small Business">

                            <label for="business">

                                <i class="bi bi-shop"></i>

                                <strong>
                                    Small Business
                                </strong>

                                <span>
                                    Livelihood assistance
                                </span>

                            </label>

                        </div>

                    </div>

                </div>

            </div>


            <!-- ITEMS -->

            <div class="card">

                <div class="card-head">

                    <strong>Procurement Items</strong>

                    <span>
                        Add each item, quantity and estimated unit cost.
                    </span>

                </div>

                <div class="card-body">

                    <div class="items-table-wrap">

                        <table class="items-table">

                            <thead>

                                <tr>
                                    <th style="width:31%">Item</th>
                                    <th style="width:15%">Quantity</th>
                                    <th style="width:22%">Unit Cost</th>
                                    <th style="width:22%">Total</th>
                                    <th style="width:10%"></th>
                                </tr>

                            </thead>

                            <tbody id="itemsBody">

                                <tr>

                                    <td>
                                        <input class="item-input item-name" placeholder="Item name" value="Cement Bags">
                                    </td>

                                    <td>
                                        <input class="item-input item-qty" type="number" min="1" value="50">
                                    </td>

                                    <td>

                                        <div class="money">

                                            <span>PKR</span>

                                            <input class="item-input item-cost" type="number" min="0" value="1500"
                                                style="padding-left:39px;">

                                        </div>

                                    </td>

                                    <td class="item-total">
                                        PKR 75,000
                                    </td>

                                    <td>

                                        <button type="button" class="remove-item" onclick="removeItem(this)">
                                            <i class="bi bi-trash"></i>
                                        </button>

                                    </td>

                                </tr>


                                <tr>

                                    <td>
                                        <input class="item-input item-name" placeholder="Item name" value="Sand">
                                    </td>

                                    <td>
                                        <input class="item-input item-qty" type="number" min="1" value="20">
                                    </td>

                                    <td>

                                        <div class="money">

                                            <span>PKR</span>

                                            <input class="item-input item-cost" type="number" min="0" value="2500"
                                                style="padding-left:39px;">

                                        </div>

                                    </td>

                                    <td class="item-total">
                                        PKR 50,000
                                    </td>

                                    <td>

                                        <button type="button" class="remove-item" onclick="removeItem(this)">
                                            <i class="bi bi-trash"></i>
                                        </button>

                                    </td>

                                </tr>

                            </tbody>

                        </table>

                    </div>


                    <div class="items-footer">

                        <button type="button" class="ui-btn" onclick="addItem()">
                            <i class="bi bi-plus-lg"></i>
                            Add Item
                        </button>

                        <div class="total-box">

                            <label>
                                Estimated Total
                            </label>

                            <strong id="itemsTotal">
                                PKR 125,000
                            </strong>

                        </div>

                    </div>

                </div>

            </div>


            <!-- FUND & ACTIVITY -->

            <div class="card">

                <div class="card-head">

                    <strong>Fund & Linked Activity</strong>

                    <span>
                        Connect the procurement to the appropriate source and organizational record.
                    </span>

                </div>

                <div class="card-body">

                    <div class="grid">

                        <div>

                            <label class="label">
                                Fund <span class="required">*</span>
                            </label>

                            <select id="fund" class="select">

                                <option value="Community Development Fund">
                                    Community Development Fund
                                </option>

                                <option value="General Fund">
                                    General Fund
                                </option>

                                <option value="Small Business Fund">
                                    Small Business Fund
                                </option>

                                <option value="Education Fund">
                                    Education Fund
                                </option>

                                <option value="Emergency Fund">
                                    Emergency Fund
                                </option>

                            </select>

                        </div>


                        <div>

                            <label class="label">
                                Linked Record Type
                            </label>

                            <select class="select">

                                <option>
                                    Community Project
                                </option>

                                <option>
                                    Welfare Activity
                                </option>

                                <option>
                                    Small Business Assistance
                                </option>

                            </select>

                        </div>


                        <div class="full">

                            <label class="label">
                                Linked Record
                            </label>

                            <select class="select">

                                <option>
                                    PRJ-00014 · Village Walking Path Improvement
                                </option>

                                <option>
                                    PRJ-00011 · Community Water Improvement
                                </option>

                                <option>
                                    SBA-00041 · Kasso General Store
                                </option>

                            </select>

                        </div>

                    </div>

                </div>

            </div>


            <!-- SUPPLIER -->

            <div class="card">

                <div class="card-head">

                    <strong>Supplier Information</strong>

                    <span>
                        Record the supplier selection or quotation status.
                    </span>

                </div>

                <div class="card-body">

                    <div class="supplier-grid">

                        <div class="supplier-option">

                            <input type="radio" name="supplier" id="supplierKnown" checked>

                            <label for="supplierKnown">

                                <strong>
                                    Existing Supplier
                                </strong>

                                <span>
                                    Select from registered suppliers
                                </span>

                            </label>

                        </div>


                        <div class="supplier-option">

                            <input type="radio" name="supplier" id="supplierNew">

                            <label for="supplierNew">

                                <strong>
                                    New Supplier
                                </strong>

                                <span>
                                    Create supplier during procurement
                                </span>

                            </label>

                        </div>


                        <div class="supplier-option">

                            <input type="radio" name="supplier" id="supplierQuotes">

                            <label for="supplierQuotes">

                                <strong>
                                    Quotation Comparison
                                </strong>

                                <span>
                                    Compare multiple supplier quotations
                                </span>

                            </label>

                        </div>

                    </div>


                    <div class="grid" style="margin-top:18px;">

                        <div>

                            <label class="label">
                                Supplier
                            </label>

                            <select class="select">

                                <option>
                                    Kasso Building Materials
                                </option>

                                <option>
                                    Kasso Food Supplier
                                </option>

                                <option>
                                    Local Market Supplies
                                </option>

                            </select>

                        </div>


                        <div>

                            <label class="label">
                                Supplier Contact
                            </label>

                            <input class="control" placeholder="Phone / email">

                        </div>

                    </div>

                </div>

            </div>


            <!-- QUOTATIONS -->

            <div class="card">

                <div class="card-head">

                    <strong>Quotations</strong>

                    <span>
                        Compare and attach supplier quotations where required.
                    </span>

                </div>

                <div class="card-body">

                    <div class="quotation">

                        <div class="quotation-icon">
                            <i class="bi bi-file-earmark-pdf"></i>
                        </div>

                        <div class="quotation-info">

                            <strong>
                                Kasso Building Materials
                            </strong>

                            <span>
                                Quotation · QTN-1024
                            </span>

                        </div>

                        <div class="quotation-price">
                            PKR 125,000
                        </div>

                    </div>


                    <div class="quotation">

                        <div class="quotation-icon">
                            <i class="bi bi-file-earmark-pdf"></i>
                        </div>

                        <div class="quotation-info">

                            <strong>
                                Local Construction Supplier
                            </strong>

                            <span>
                                Quotation · QTN-1021
                            </span>

                        </div>

                        <div class="quotation-price">
                            PKR 132,000
                        </div>

                    </div>


                    <div style="margin-top:13px;">

                        <label class="upload">

                            <i class="bi bi-cloud-arrow-up"></i>

                            <strong>
                                Upload quotation
                            </strong>

                            <span>
                                PDF, image or supplier document
                            </span>

                            <input type="file" multiple hidden>

                        </label>

                    </div>

                </div>

            </div>


            <!-- APPROVAL -->

            <div class="card">

                <div class="card-head">

                    <strong>Approval Requirements</strong>

                    <span>
                        Confirm the procurement is ready for committee or authorized review.
                    </span>

                </div>

                <div class="card-body">

                    <div class="approval-list">

                        <label class="approval-item">

                            <input type="checkbox" checked>

                            <div>

                                <strong>
                                    Procurement purpose verified
                                </strong>

                                <span>
                                    Request is connected to an approved organizational activity.
                                </span>

                            </div>

                        </label>


                        <label class="approval-item">

                            <input type="checkbox" checked>

                            <div>

                                <strong>
                                    Correct fund identified
                                </strong>

                                <span>
                                    Selected fund is appropriate for the requested purchase.
                                </span>

                            </div>

                        </label>


                        <label class="approval-item">

                            <input type="checkbox">

                            <div>

                                <strong>
                                    Quotations reviewed
                                </strong>

                                <span>
                                    Required supplier comparisons have been completed.
                                </span>

                            </div>

                        </label>


                        <label class="approval-item">

                            <input type="checkbox">

                            <div>

                                <strong>
                                    Authorized approval obtained
                                </strong>

                                <span>
                                    Appropriate authority has approved the procurement request.
                                </span>

                            </div>

                        </label>

                    </div>

                </div>

            </div>


            <!-- DOCUMENTS -->

            <div class="card">

                <div class="card-head">

                    <strong>Supporting Documents</strong>

                    <span>
                        Attach specifications, quotations, approvals and other records.
                    </span>

                </div>

                <div class="card-body">

                    <label class="upload">

                        <i class="bi bi-cloud-arrow-up"></i>

                        <strong>
                            Upload procurement documents
                        </strong>

                        <span>
                            Specifications, quotations, approvals, invoices or other evidence
                        </span>

                        <input type="file" multiple hidden>

                    </label>

                </div>

            </div>

        </div>


        <aside>

            <!-- PREVIEW -->

            <div class="preview">

                <div class="preview-label">
                    Procurement Preview
                </div>

                <div class="preview-icon">
                    <i class="bi bi-cart-check"></i>
                </div>

                <div class="preview-name" id="previewTitle">
                    New Procurement
                </div>

                <div class="preview-sub">
                    PRC-00043
                </div>

                <div class="preview-divider"></div>

                <div class="preview-row">

                    <span>
                        Category
                    </span>

                    <span id="previewCategory">
                        Community Project
                    </span>

                </div>


                <div class="preview-row">

                    <span>
                        Fund
                    </span>

                    <span id="previewFund">
                        Community Development Fund
                    </span>

                </div>


                <div class="preview-row">

                    <span>
                        Estimated Total
                    </span>

                    <span id="previewTotal">
                        PKR 125,000
                    </span>

                </div>


                <div class="preview-row">

                    <span>
                        Status
                    </span>

                    <span>
                        Draft
                    </span>

                </div>

            </div>


            <!-- CONTROL -->

            <div class="card">

                <div class="card-head">
                    <strong>Procurement Control</strong>
                </div>

                <div class="card-body">

                    <div class="notice">

                        <i class="bi bi-shield-check"></i>

                        <p>
                            Procurement should remain connected to an approved
                            purpose, authorized fund, supplier evidence and
                            appropriate approval before an order is placed.
                        </p>

                    </div>

                </div>

            </div>


            <!-- FLOW -->

            <div class="card">

                <div class="card-head">
                    <strong>Procurement Workflow</strong>
                </div>

                <div class="card-body">

                    <div class="flow">

                        <div class="flow-step">
                            <div class="flow-number">1</div>
                            <span>Request Created</span>
                        </div>

                        <div class="flow-step">
                            <div class="flow-number">2</div>
                            <span>Quotation / Supplier Review</span>
                        </div>

                        <div class="flow-step">
                            <div class="flow-number">3</div>
                            <span>Approval</span>
                        </div>

                        <div class="flow-step">
                            <div class="flow-number">4</div>
                            <span>Purchase Order</span>
                        </div>

                        <div class="flow-step">
                            <div class="flow-number">5</div>
                            <span>Receive Items</span>
                        </div>

                        <div class="flow-step">
                            <div class="flow-number">6</div>
                            <span>Invoice / Expense</span>
                        </div>

                    </div>

                </div>

            </div>

        </aside>

    </div>

@endsection


@push('scripts')
    <script>

        const requestTitle =
            document.getElementById('requestTitle');

        const fund =
            document.getElementById('fund');


        function money(value) {

            return 'PKR ' +
                Number(value || 0)
                    .toLocaleString('en-PK');

        }


        requestTitle.addEventListener('input', function () {

            document.getElementById('previewTitle')
                .textContent =
                this.value.trim() ||
                'New Procurement';

        });


        fund.addEventListener('change', function () {

            document.getElementById('previewFund')
                .textContent =
                this.value;

        });


        document
            .querySelectorAll(
                'input[name="category"]'
            )
            .forEach(input => {

                input.addEventListener(
                    'change',
                    function () {

                        document.getElementById(
                            'previewCategory'
                        ).textContent =
                            this.value;

                    }
                );

            });


        function updateItemsTotal() {

            let total = 0;


            document
                .querySelectorAll('#itemsBody tr')
                .forEach(row => {

                    const qty =
                        Number(
                            row.querySelector(
                                '.item-qty'
                            )?.value || 0
                        );

                    const cost =
                        Number(
                            row.querySelector(
                                '.item-cost'
                            )?.value || 0
                        );

                    const itemTotal =
                        qty * cost;


                    const totalCell =
                        row.querySelector(
                            '.item-total'
                        );

                    if (totalCell) {

                        totalCell.textContent =
                            money(itemTotal);

                    }


                    total += itemTotal;

                });


            document.getElementById(
                'itemsTotal'
            ).textContent =
                money(total);


            document.getElementById(
                'previewTotal'
            ).textContent =
                money(total);

        }


        function attachItemListeners(row) {

            row.querySelectorAll(
                '.item-qty, .item-cost'
            ).forEach(input => {

                input.addEventListener(
                    'input',
                    updateItemsTotal
                );

            });

        }


        function addItem() {

            const row =
                document.createElement('tr');

            row.innerHTML = `

                <td>

                    <input
                        class="item-input item-name"
                        placeholder="Item name"
                    >

                </td>


                <td>

                    <input
                        class="item-input item-qty"
                        type="number"
                        min="1"
                        value="1"
                    >

                </td>


                <td>

                    <div class="money">

                        <span>PKR</span>

                        <input
                            class="item-input item-cost"
                            type="number"
                            min="0"
                            value="0"
                            style="padding-left:39px;"
                        >

                    </div>

                </td>


                <td class="item-total">
                    PKR 0
                </td>


                <td>

                    <button
                        type="button"
                        class="remove-item"
                        onclick="removeItem(this)"
                    >
                        <i class="bi bi-trash"></i>
                    </button>

                </td>

            `;


            document
                .getElementById('itemsBody')
                .appendChild(row);


            attachItemListeners(row);

            updateItemsTotal();

        }


        function removeItem(button) {

            const rows =
                document.querySelectorAll(
                    '#itemsBody tr'
                );


            if (rows.length <= 1) {

                alert(
                    'At least one procurement item is required.'
                );

                return;

            }


            button
                .closest('tr')
                .remove();


            updateItemsTotal();

        }


        document
            .querySelectorAll(
                '#itemsBody tr'
            )
            .forEach(
                attachItemListeners
            );


        function previewCreateProcurement() {

            if (!requestTitle.value.trim()) {

                alert(
                    'Please enter the procurement request title.'
                );

                requestTitle.focus();

                return;

            }


            const rows =
                document.querySelectorAll(
                    '#itemsBody tr'
                );


            let validItem =
                false;


            rows.forEach(row => {

                const name =
                    row.querySelector(
                        '.item-name'
                    )?.value.trim();

                const qty =
                    Number(
                        row.querySelector(
                            '.item-qty'
                        )?.value || 0
                    );


                if (
                    name &&
                    qty > 0
                ) {

                    validItem = true;

                }

            });


            if (!validItem) {

                alert(
                    'Please add at least one valid procurement item.'
                );

                return;

            }


            alert(
                'UI preview only.\n\n' +
                'This procurement request is ready to be created once backend functionality is connected.'
            );

        }


        updateItemsTotal();

    </script>
@endpush