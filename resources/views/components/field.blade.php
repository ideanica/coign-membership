@props(['name', 'label', 'value' => '', 'type' => 'text'])
<div class="form-field"><label for="{{ $name }}">{{ $label }}</label><input {{ $attributes }} type="{{ $type }}" id="{{ $name }}" name="{{ $name }}" value="{{ $type === 'password' ? '' : old($name, $value) }}" @error($name) aria-invalid="true" @enderror>
    @if($slot->isNotEmpty())<small>{{ $slot }}</small>@endif
</div>
