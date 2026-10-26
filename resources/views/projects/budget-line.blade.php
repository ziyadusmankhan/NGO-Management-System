<fieldset class="budget-line"><legend>Budget item</legend>
<input type="hidden" name="items[{{ $i }}][budget_item_id]" value="{{ $item['budget_item_id']??'' }}">
<div class="qgrid">
<x-business-field :name="'items['.$i.'][description]'" label="Item description" :value="$item['description']??''"/>
<label>Type *<select name="items[{{ $i }}][kind]">@foreach(['purchase'=>'Supplier purchase','labor'=>'Labor','service'=>'Service'] as $v=>$label)<option value="{{ $v }}" @selected(($item['kind']??'purchase')===$v)>{{ $label }}</option>@endforeach</select></label>
<x-business-field :name="'items['.$i.'][payee]'" label="Approved supplier / worker / service provider" :value="$item['payee']??''"/>
<x-business-field :name="'items['.$i.'][amount]'" label="Maximum budget (PKR)" :value="$item['amount']??''" inputmode="decimal" pattern="[0-9]+([.][0-9]{1,2})?"/>
</div><p class="qmuted">Purchases: enter two different suppliers' price checks, or request a committee exception. Labor/services: enter the estimate reference. Upload the supporting evidence below the budget.</p>
<div class="qgrid">@foreach([0,1] as $q)
<div><h4>Price check {{ $q+1 }}</h4><x-business-field :name="'items['.$i.'][price_checks]['.$q.'][supplier]'" label="Supplier" :value="$item['price_checks'][$q]['supplier']??''" :required="false"/>
<x-business-field :name="'items['.$i.'][price_checks]['.$q.'][amount]'" label="Quoted amount (PKR)" :value="$item['price_checks'][$q]['amount']??''" :required="false" inputmode="decimal"/>
<x-business-field :name="'items['.$i.'][price_checks]['.$q.'][reference]'" label="Quote reference / date" :value="$item['price_checks'][$q]['reference']??''" :required="false"/></div>@endforeach</div>
<div class="qgrid"><x-business-field :name="'items['.$i.'][estimate]'" label="Labor / service estimate reference and details" :value="$item['estimate']??''" type="textarea" :required="false"/>
<x-business-field :name="'items['.$i.'][exception_reason]'" label="Why fewer than two price checks? (committee must approve)" :value="$item['exception_reason']??''" type="textarea" :required="false"/></div>
<button type="button" class="qbtn secondary" data-remove-budget>Remove item</button></fieldset>
