<div class="item-row"><input type="hidden" name="items[{{ $index }}][key]" value="{{ $item['key'] ?? '' }}">
    <div class="qgrid">
        <label>Description<input name="items[{{ $index }}][description]" value="{{ $item['description'] ?? '' }}"
                required></label><label>Unit<input name="items[{{ $index }}][unit]" value="{{ $item['unit'] ?? 'each' }}"
                required></label><label>Quantity<input name="items[{{ $index }}][quantity]"
                value="{{ $item['quantity'] ?? '1' }}" inputmode="decimal" required></label><label>All-inclusive unit
            price (PKR)<input name="items[{{ $index }}][unit_price]" value="{{ $item['unit_price'] ?? '' }}"
                inputmode="decimal" required></label>
        @if($parent)<label>Approved budget item<select name="items[{{ $index }}][budget_item_id]" required>
                <option value="">Choose budget item</option>
                @foreach($budgets as $b)@php($bid = $type === 'project' ? $b->budget_item_id : $b->id)
                <option value="{{ $bid }}" @selected(($item['budget_item_id'] ?? null) == $bid)>{{ $b->description }} ·
                    {{ $type === 'project' ? $b->payee : $b->supplier }} · PKR
                    {{ $type === 'project' ? $b->amount : $b->organization_amount }}</option>@endforeach
            </select></label>@endif
    </div><button type="button" class="qbtn secondary remove-purchase-item">Remove item</button>
</div>