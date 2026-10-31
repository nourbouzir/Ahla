@props(['name', 'label', 'type' => 'text', 'value' => null, 'autocomplete' => null, 'required' => false, 'hint' => null])
<div class="mt-4">
    <label class="label" for="{{ $name }}">{{ $label }}@if ($required) <span class="text-zinc-500" aria-hidden="true">*</span>@endif</label>
    <input class="field" id="{{ $name }}" name="{{ $name }}" type="{{ $type }}"
        @if ($type !== 'password') value="{{ old($name, $value) }}" @endif
        @if ($autocomplete) autocomplete="{{ $autocomplete }}" @endif
        @if ($required) required @endif
        @php($describedBy = trim(($hint ? "$name-hint " : '').($errors->has($name) ? "$name-error" : '')))
        @if ($describedBy !== '') aria-describedby="{{ $describedBy }}" @endif
        @error($name) aria-invalid="true" @enderror
        {{ $attributes }}>
    @if ($hint)<p class="hint" id="{{ $name }}-hint">{{ $hint }}</p>@endif
    @error($name)<p class="error-text" id="{{ $name }}-error">{{ $message }}</p>@enderror
</div>
