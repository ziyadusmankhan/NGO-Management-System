<input type="hidden" name="idempotency_key" value="{{ (string) \Illuminate\Support\Str::uuid() }}">
<div class="qgrid">
<x-business-field label="Amount (PKR)" name="amount" inputmode="decimal" pattern="[0-9]+(\.[0-9]{1,2})?" />
<x-business-field label="Payment date" name="payment_date" type="date" :value="now(config('business.timezone'))->toDateString()" />
<div><label>Method *<select name="payment_method" required>@foreach(['cash','bank_transfer','mobile_wallet','cheque'] as $method)<option value="{{ $method }}" @selected(old('payment_method')===$method)>{{ str($method)->headline() }}</option>@endforeach</select></label></div>
<x-business-field :label="$kind === 'disbursement' ? 'Paid to (approved supplier or beneficiary)' : 'Received from'" name="payee" :value="$kind === 'repayment' ? $application->beneficiary->full_name : ''" />
<x-business-field label="Receipt number" name="receipt" />
<x-business-field label="Transaction reference" name="reference" :required="false" />
<x-business-field label="Payment evidence" name="evidence" type="file" :required="$kind === 'disbursement'" />
</div><x-business-field label="Payment notes" name="notes" type="textarea" :required="false" />
