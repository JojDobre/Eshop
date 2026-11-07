@php
    $key = str_replace(['[', ']'], ['.', ''], $name);
    $id = str_replace('.', '_', $key);
    $selected = old($key, ($value ?? null) ?: (($prefill[$name] ?? null) ?: ($company->country ?: $shop->country)));
@endphp
<div>
    <label for="{{ $id }}" class="label">{{ __('theme::shop.country') }}</label>
    <select id="{{ $id }}" name="{{ $name }}" class="input mt-1.5" autocomplete="country">
        @foreach (config('eshop.countries') as $code)
            <option value="{{ $code }}" @selected($selected === $code)>{{ __('countries.'.$code) }}</option>
        @endforeach
    </select>
    @error($key) <p class="field-error">{{ $message }}</p> @enderror
</div>
