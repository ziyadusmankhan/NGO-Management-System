<input type="hidden" name="idempotency_key" value="{{ \Illuminate\Support\Str::uuid() }}">
<div class="qgrid"><x-business-field name="amount" label="Amount (PKR)" inputmode="decimal" pattern="[0-9]+([.][0-9]{1,2})?"/>
<x-business-field name="payment_date" label="Payment / refund date" type="date" :value="now(config('projects.timezone'))->toDateString()"/>
<label>Method *<select name="payment_method">@foreach(['cash','bank_transfer','mobile_wallet','cheque'] as $v)<option value="{{ $v }}">{{ str($v)->headline() }}</option>@endforeach</select></label>
<x-business-field name="payee" label="Payee (exact approved name)"/>
<x-business-field name="receipt" label="Receipt number"/><x-business-field name="reference" label="Bank / transaction reference" :required="false"/>
<x-business-field name="notes" label="Notes" type="textarea" :required="false"/><x-business-field name="evidence" label="Receipt / payment evidence" type="file"/></div>
