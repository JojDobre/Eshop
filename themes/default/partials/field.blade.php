@php
    $required = $required ?? true;
    // „billing_address[city]“ → kľúč „billing_address.city“ (pre old() a chyby), id bez zátvoriek.
    $key = str_replace(['[', ']'], ['.', ''], $name);
    $id = str_replace('.', '_', $key);
    $errorBag = $errors->getBag($bag ?? 'default');
    $fieldValue = ($noOld ?? false) ? '' : old($key, $value ?? ($prefill[$name] ?? ''));
@endphp
<div class="{{ $class ?? '' }}">
    <label for="{{ $id }}" class="label">{{ $label }}@unless ($required) <span class="font-normal text-zinc-500">({{ __('theme::shop.optional') }})</span>@endunless</label>
    <input id="{{ $id }}" name="{{ $name }}" type="{{ $type ?? 'text' }}" value="{{ $fieldValue }}" autocomplete="{{ $autocomplete ?? 'on' }}"
           class="input mt-1.5" @if ($errorBag->has($key)) aria-invalid="true" aria-describedby="{{ $id }}-error" @elseif (! empty($help)) aria-describedby="{{ $id }}-help" @endif>
    @if ($errorBag->has($key))
        <p id="{{ $id }}-error" class="field-error">{{ $errorBag->first($key) }}</p>
    @elseif (! empty($help))
        <p id="{{ $id }}-help" class="mt-1 text-xs text-zinc-500">{{ $help }}</p>
    @endif
</div>
