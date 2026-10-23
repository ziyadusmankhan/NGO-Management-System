@props(['label', 'name', 'value' => '', 'type' => 'text', 'required' => true])
@php($oldKey = trim(str_replace(['[', ']'], ['.', ''], $name), '.'))
<div><label>{{ $label }} @if($required)<span aria-label="required">*</span>@endif
@if($type === 'textarea')
<textarea name="{{ $name }}" @required($required) {{ $attributes }}>{{ old($oldKey, $value) }}</textarea>
@elseif($type === 'file')
<input type="file" name="{{ $name }}" accept=".pdf,.jpg,.jpeg,.png" @required($required) {{ $attributes }}>
<small class="qmuted">PDF, JPG or PNG · maximum 10 MB per file · stored privately</small>
@else
<input type="{{ $type }}" name="{{ $name }}" value="{{ old($oldKey, $value) }}" @required($required) {{ $attributes }}>
@endif
</label></div>
