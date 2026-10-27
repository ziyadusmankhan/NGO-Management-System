<div class="qgrid">
    <x-business-field name="amount" label="Amount (PKR)" :value="old('amount')" />
    <x-business-field name="payment_date" label="Payment date" type="date"
        :value="old('payment_date', now('Asia/Karachi')->toDateString())" />
    <label>Method<select name="payment_method"
            required>@foreach(['cash', 'bank_transfer', 'mobile_wallet', 'cheque'] as $method)
            <option value="{{ $method }}">{{ str($method)->headline() }}</option>@endforeach
        </select></label>
    <x-business-field name="receipt" label="Receipt number" :value="old('receipt')" />
    <x-business-field name="reference" label="Transaction reference" :value="old('reference')" :required="false" />
    <x-business-field name="notes" label="Notes" :value="old('notes')" :required="false" />
    <label>Payment evidence (PDF/JPG/PNG, 10 MB)<input type="file" name="evidence" accept=".pdf,.jpg,.jpeg,.png"
            required></label>
</div>
<input type="hidden" name="idempotency_key"
    value="{{ old('idempotency_key', (string) \Illuminate\Support\Str::uuid()) }}">