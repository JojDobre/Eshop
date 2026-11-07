<div>
    <label for="{{ $name }}" class="label">{{ __('theme::shop.country') }}</label>
    <select id="{{ $name }}" name="{{ $name }}" class="input mt-1.5" autocomplete="country">
        @foreach (config('eshop.countries') as $code)
            <option value="{{ $code }}" @selected(old($name, $company->country ?: $shop->country) === $code)>{{ __('countries.'.$code) }}</option>
        @endforeach
    </select>
    @error($name) <p class="field-error">{{ $message }}</p> @enderror
</div>
