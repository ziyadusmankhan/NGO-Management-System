@php
    // Whitespace makes retained JSON readable without shrinking the whole record table.
    if(is_string($value) && in_array(substr($value,0,1),['{','[']) && json_validate($value)) {
        $value=json_encode(json_decode($value),JSON_PRETTY_PRINT|JSON_UNESCAPED_UNICODE|JSON_UNESCAPED_SLASHES);
    }
@endphp
@if($value instanceof \DateTimeInterface){{ $value->format($type==='datetime'?'d M Y H:i':'d M Y') }}@elseif($type==='money' && $value!==null){{ \App\Domain\Finance\Support\Money::format($value) }}@else{!! nl2br(e($value===null || $value===''?'-':$value)) !!}@endif
