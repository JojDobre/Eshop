<form method="GET" class="flex items-center gap-2 text-sm">
    @foreach (request()->except(['sort', 'page']) as $key => $value)
        @if (is_string($value))
            <input type="hidden" name="{{ $key }}" value="{{ $value }}">
        @endif
    @endforeach
    <label for="sort" class="text-zinc-500">{{ __('theme::shop.sort_by') }}</label>
    <select id="sort" name="sort" class="input w-auto py-1.5" onchange="this.form.submit()">
        @foreach (['newest', 'price_asc', 'price_desc', 'name'] as $option)
            <option value="{{ $option }}" @selected($sort === $option)>{{ __('theme::shop.sort.'.$option) }}</option>
        @endforeach
    </select>
    <noscript><button type="submit" class="btn btn-secondary py-1.5">{{ __('theme::shop.apply') }}</button></noscript>
</form>
