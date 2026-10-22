<input type="hidden" name="idempotency_key" value="{{ old('idempotency_key', (string)\Illuminate\Support\Str::uuid()) }}">
<div class="qgrid">
<div><label for="amount">Amount (PKR)</label><input id="amount" name="amount" type="number" min="0.01" step="0.01" max="{{ $maximum }}" value="{{ old('amount',$maximum) }}" required @readonly($kind==='disbursement')><small>{{ $kind==='disbursement' ? 'Exactly the approved principal.' : 'Maximum remaining principal: PKR '.$maximum }}</small></div>
<div><label for="payment_date">Payment date</label><input id="payment_date" name="payment_date" type="date" max="{{ now('Asia/Karachi')->toDateString() }}" value="{{ old('payment_date',now('Asia/Karachi')->toDateString()) }}" required></div>
<div><label for="payment_method">Payment method</label><select id="payment_method" name="payment_method" required><option value="">Select method</option>@foreach(['cash','bank_transfer','mobile_wallet','cheque'] as $method)<option value="{{ $method }}" @selected(old('payment_method')===$method)>{{ str($method)->headline() }}</option>@endforeach</select></div>
<div><label for="reference">Transaction reference</label><input id="reference" name="reference" maxlength="255" value="{{ old('reference') }}"></div>
<div><label for="receipt">Receipt number</label><input id="receipt" name="receipt" maxlength="255" value="{{ old('receipt') }}"></div></div>
<div style="margin-top:15px"><label for="notes">Payment notes</label><textarea id="notes" name="notes" maxlength="5000">{{ old('notes') }}</textarea></div>
