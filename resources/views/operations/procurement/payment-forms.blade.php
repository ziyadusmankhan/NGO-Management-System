@if($open && $can('pay'))
    <details class="qcard">
        <summary>Record payment after acceptance</summary>
        <form method="post" enctype="multipart/form-data" action="{{ route('operations.procurement.pay', $purchase) }}">
            @include('operations.procurement.action-fields')<label>Accepted invoice line<select name="invoice_line_id"
                    required>@foreach($purchase->invoices->where('status', 'posted') as $invoice)@foreach($invoice->lines as $l)@if($purchase->invoiceBalance($l) > 0)
                        <option value="{{ $l->id }}">{{ $invoice->reference }} · Line #{{ $l->id }} ·
                            {{ $purchase->item($l->item_key)['description'] ?? '' }} · PKR
                    {{ $money($purchase->invoiceBalance($l)) }} unpaid</option>@endif @endforeach @endforeach
                </select></label>@include('operations.procurement.money-fields')<button class="qbtn">Record payment</button>
            <p>This posts one debit to the original fund. Approval and receipt create no payment.</p>
        </form>
</details>@endif
@if($purchase->parent_type !== 'business' && $can('returns'))
    <details class="qcard">
        <summary>Return goods with a supplier credit note</summary>
        <form method="post" enctype="multipart/form-data" action="{{ route('operations.procurement.return', $purchase) }}">
            @include('operations.procurement.action-fields')
            <div class="qgrid"><label>Original receipt<select
                        name="receipt_id">@foreach($purchase->receipts->where('status', 'posted') as $r)
                            <option value="{{ $r->id }}">#{{ $r->id }} · {{ $r->reference }} ·
                        {{ $purchase->item($r->item_key)['description'] ?? '' }}</option>@endforeach
                    </select></label><label>Invoice line (if billed)<select name="invoice_line_id">
                        <option value="">Unbilled goods</option>
                        @foreach($purchase->invoices->where('status', 'posted') as $invoice)@foreach($invoice->lines as $l)
                            <option value="{{ $l->id }}">{{ $invoice->reference }} · Line #{{ $l->id }} · Receipt
                        #{{ $l->receipt_id }}</option>@endforeach @endforeach
                    </select></label><x-business-field name="quantity" label="Quantity returned" /><x-business-field
                    name="credit_reference" label="Supplier credit-note reference" /><x-business-field name="returned_on"
                    label="Returned on" type="date" :value="now('Asia/Karachi')->toDateString()" /><x-business-field
                    name="refund_due_on" label="Refund due date (required if paid goods)" type="date"
                    :required="false" /><x-business-field name="reason" label="Reason" /><label>Return and credit-note
                    evidence<input type="file" name="evidence" accept=".pdf,.jpg,.jpeg,.png" required></label></div><button
                class="qbtn">Record return</button>
            <p>A credit note records what the supplier owes. Record actual cash separately below.</p>
        </form>
</details>@endif
@if($purchase->parent_type !== 'business' && $can('refund'))
    <details class="qcard">
        <summary>Record supplier refund received</summary>
        <form method="post" enctype="multipart/form-data" action="{{ route('operations.procurement.refund', $purchase) }}">
            @include('operations.procurement.action-fields')
            <div class="qgrid"><label>Return / credit note<select
                        name="return_id">@foreach($purchase->returns->where('status', 'posted') as $r)
                            <option value="{{ $r->id }}">#{{ $r->id }} · {{ $r->credit_reference }} · PKR
                                {{ $money(app(\App\Domain\Procurement\Services\ProcurementOperationsService::class)->refundRemaining($purchase, $r)) }}
                        due</option>@endforeach
                    </select></label><label>Original payment<select
                        name="original_payment_id">@foreach($purchase->movements->where('status', 'posted')->where('kind', 'payment') as $m)
                            <option value="{{ $m->id }}">Payment #{{ $m->id }} · Invoice line #{{ $m->invoice_line_id }} · PKR
                        {{ $m->amount }}</option>@endforeach
                    </select></label></div>@include('operations.procurement.money-fields')<button class="qbtn">Record refund
                received</button>
        </form>
</details>@endif