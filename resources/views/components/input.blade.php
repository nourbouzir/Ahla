@props(['name', 'label', 'type' => 'text', 'value' => null, 'autocomplete' => null, 'required' => false])
<div class="mt-4">
    <label class="label" for="{{ $name }}">{{ $label }}@if ($required) <span aria-hidden="true">*</span>@endif</label>
    <input class="field" id="{{ $name }}" name="{{ $name }}" type="{{ $type }}"
        @if ($type !== 'password') value="{{ old($name, $value) }}" @endif
        @if ($autocomplete) autocomplete="{{ $autocomplete }}" @endif
        @if ($required) required @endif
        @error($name) aria-invalid="true" aria-describedby="{{ $name }}-error" @enderror
        {{ $attributes }}>
    @error($name)<p class="error-text" id="{{ $name }}-error">{{ $message }}</p>@enderror
</div>
