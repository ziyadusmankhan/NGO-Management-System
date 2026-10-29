<details class="qcard" open>
    <summary>Deliveries, invoices and returns</summary>
    <h2>Receipts</h2>@forelse($purchase->receipts as $r)
        <p>#{{ $r->id }} · {{ $r->reference }} · {{ $r->received_on->format('d M Y') }} · Accepted
            {{ $quantity($r->accepted_milli) }}, rejected {{ $quantity($r->rejected_milli) }} · {{ $r->actor?->name }} ·
    {{ $r->status }} {{ $r->void_reason }}</p>@empty<p>No receipts yet.</p>@endforelse
    <h2>Invoices</h2>@forelse($purchase->invoices as $invoice)
        <p><strong>#{{ $invoice->id }} · {{ $invoice->reference }}</strong> · {{ $invoice->status }} · Due
            {{ $invoice->due_on->format('d M Y') }} {{ $invoice->void_reason }}</p>@foreach($invoice->lines as $l)
                <p>Line #{{ $l->id }} · Receipt #{{ $l->receipt_id }} · Quantity {{ $quantity($l->quantity_milli) }} · PKR
    {{ $money($l->amount_minor) }} · Unpaid {{ $money($purchase->invoiceBalance($l)) }}</p>@endforeach @empty<p>No
        invoices yet.</p>@endforelse
    <h2>Returns</h2>@forelse($purchase->returns as $r)
        <p>#{{ $r->id }} · {{ $r->credit_reference }} · {{ $quantity($r->quantity_milli) }} returned · Credit PKR
            {{ $money($r->credit_minor) }} · Refund due PKR
            {{ $money(app(\App\Domain\Procurement\Services\ProcurementOperationsService::class)->refundRemaining($purchase, $r)) }}
    · {{ $r->status }} {{ $r->void_reason }}</p>@empty<p>No returns.</p>@endforelse
</details>
<div class="qcard qtable">
    <h2>Payments and refunds</h2>
    <table>
        <thead>
            <tr>
                <th>ID / type</th>
                <th>Date</th>
                <th>Amount</th>
                <th>Invoice line</th>
                <th>Actor / status</th>
                <th>Ledger entry</th>
            </tr>
        </thead>
        <tbody>@forelse($purchase->movements as $m)
            <tr>
                <td>#{{ $m->id }} · {{ ucfirst($m->kind) }}</td>
                <td>{{ $m->payment_date->format('d M Y') }}</td>
                <td>PKR {{ $m->amount }}</td>
                <td>#{{ $m->invoice_line_id }}</td>
                <td>User #{{ $m->recorded_by }} · {{ $m->status }}<small>{{ $m->void_reason }}</small></td>
                <td>{{ $m->ledger()?->transaction_number ?? 'Missing — needs reconciliation' }}</td>
        </tr>@empty<tr>
                <td colspan="6">No payments recorded.</td>
            </tr>@endforelse
        </tbody>
    </table>
</div>
<details class="qcard">
    <summary>Private evidence</summary>@foreach($purchase->documents as $doc)
        <p>@if(auth()->user()?->hasPermission('procurement.view'))<a href="{{ route('operations.procurement.documents', [$purchase, $doc]) }}">{{ $doc->original_name }}</a>@endif ·
            {{ str($doc->kind)->headline() }} · Revision {{ $doc->revision }} · Record {{ $doc->record_id ?? '—' }}</p>
    @endforeach
</details>
<details class="qcard">
    <summary>Revisions and retained activity</summary>@foreach($purchase->revisions as $r)
        <p>Revision {{ $r->revision }} · {{ $r->stage }} · PKR {{ $money($r->total_minor) }} · {{ $r->reason }}</p>
    @endforeach @foreach($purchase->reviews as $r)
    <p>{{ str($r->stage)->headline() }} · {{ $r->decision }} · {{ $r->actor?->name }} · {{ $r->notes }}</p>@endforeach
    @foreach($purchase->events as $event)
        <p>{{ $event->created_at->timezone('Asia/Karachi')->format('d M Y H:i') }} · {{ $event->actor?->name }} ·
            {{ str($event->action)->headline() }}</p>
        <pre>{{ json_encode(['before' => $event->before_values, 'after' => $event->after_values], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE) }}</pre>
    @endforeach
</details>