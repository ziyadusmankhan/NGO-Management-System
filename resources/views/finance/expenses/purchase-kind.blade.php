<div class="card" style="padding:16px;margin:16px 0">
<label for="purchase_kind"><strong>What is this expense for?</strong></label>
<select id="purchase_kind" name="purchase_kind" required class="form-select">
<option value="">Choose a classification</option>
@foreach(['service'=>'Services','labor'=>'Labor','other_non_goods'=>'Other non-goods expense'] as $value=>$label)<option value="{{ $value }}" @selected(old('purchase_kind',$expense->purchase_kind??'')===$value)>{{ $label }}</option>@endforeach
</select><p>New goods and asset purchases require Procurement, with quotations, acceptance and an invoice before payment. Classify expenses accurately; existing historical entries retain their history.</p>
@if(auth()->user()->hasPermission('procurement.manage'))@if(auth()->user()?->hasPermission('procurement.view'))<a href="{{ route('operations.procurement.create') }}">Create a purchase request for goods or assets</a>@endif
@endif
</div>
