@php($required = $required ?? true)
<div class="{{ $class ?? '' }}">
    <label for="{{ $name }}" class="label">{{ $label }}@unless ($required) <span class="font-normal text-zinc-500">({{ __('theme::shop.optional') }})</span>@endunless</label>
    <input id="{{ $name }}" name="{{ $name }}" type="{{ $type ?? 'text' }}" value="{{ old($name) }}" autocomplete="{{ $autocomplete ?? 'on' }}"
           class="input mt-1.5" @error($name) aria-invalid="true" aria-describedby="{{ $name }}-error" @enderror>
    @error($name)
        <p id="{{ $name }}-error" class="field-error">{{ $message }}</p>
    @enderror
</div>
