<div class="business-budget"><div class="qgrid">
<div><label>Item description *<input name="items[{{ $i }}][description]" value="{{ $item['description'] ?? '' }}" required maxlength="255"></label></div>
<div><label>Total item cost (PKR) *<input name="items[{{ $i }}][cost]" value="{{ $item['cost'] ?? '' }}" required inputmode="decimal"></label></div>
<div><label>Applicant's own share (PKR) *<input name="items[{{ $i }}][applicant_amount]" value="{{ $item['applicant_amount'] ?? '0.00' }}" required inputmode="decimal"></label></div>
<div><label>Release method *<select name="items[{{ $i }}][release_method]">@foreach(['cash'=>'Beneficiary cash release','supplier'=>'Supplier payment','asset'=>'Asset purchase and handover'] as $value=>$label)<option value="{{ $value }}" @selected(($item['release_method'] ?? 'cash')===$value)>{{ $label }}</option>@endforeach</select></label></div>
<div><label>Selected supplier<input name="items[{{ $i }}][supplier]" value="{{ $item['supplier'] ?? '' }}" maxlength="255"></label></div>
</div><details><summary>Supplier price checks or exception</summary>
@for($q=0;$q<max(2,count($item['price_checks'] ?? []));$q++)<div class="qgrid">
<div><label>Quote {{ $q+1 }} supplier<input name="items[{{ $i }}][price_checks][{{ $q }}][supplier]" value="{{ $item['price_checks'][$q]['supplier'] ?? '' }}"></label></div>
<div><label>Quoted cost (PKR)<input name="items[{{ $i }}][price_checks][{{ $q }}][amount]" value="{{ $item['price_checks'][$q]['amount'] ?? '' }}" inputmode="decimal"></label></div>
<div><label>Quote reference<input name="items[{{ $i }}][price_checks][{{ $q }}][reference]" value="{{ $item['price_checks'][$q]['reference'] ?? '' }}"></label></div>
</div>@endfor
<label>Reason if required price checks cannot be obtained<textarea name="items[{{ $i }}][exception_reason]">{{ $item['exception_reason'] ?? '' }}</textarea></label></details>
<button class="qbtn secondary" type="button" data-remove-item>Remove this draft item</button></div>
