@props(['label', 'value', 'note' => null, 'href' => null])
<div {{ $attributes->class(['ui-metric']) }}><div class="ui-metric-label">{{ $label }}</div><div class="ui-metric-value">{{ $value }}</div>@if($note)<div class="ui-metric-note">@if($href)<a href="{{ $href }}">{{ $note }}</a>@else{{ $note }}@endif</div>@endif</div>
