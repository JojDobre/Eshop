{{-- Filtre podľa parametrov. Formulár sa odošle pri každej zmene (bez JavaScriptu tlačidlom). --}}
<form method="GET" class="space-y-6 text-sm" x-data @change="$el.requestSubmit()">
    @if (request('sort'))
        <input type="hidden" name="sort" value="{{ request('sort') }}">
    @endif

    @foreach ($filters as $filter)
        @php($field = $filter['field'])
        <fieldset>
            <legend class="font-medium text-zinc-900">{{ $field->name }}@if ($field->unit && isset($filter['min'])) ({{ $field->unit }})@endif</legend>

            @if (isset($filter['options']))
                <div class="mt-2 space-y-1.5">
                    @foreach ($filter['options'] as $key => $label)
                        <label class="flex items-center gap-2">
                            <input type="checkbox" name="f[{{ $field->key }}][]" value="{{ $key }}"
                                   @checked(in_array((string) $key, (array) ($selectedFilters[$field->key] ?? []), true))
                                   class="size-4 rounded border-zinc-300 accent-[var(--brand-primary)]">
                            @if ($field->type === \Modules\Catalog\CustomFields\CustomFieldType::Color)
                                <span class="size-4 rounded-full border border-zinc-200" style="background: {{ preg_match('/^#[0-9a-fA-F]{6}$/', $key) ? $key : 'transparent' }}"></span>
                            @endif
                            <span>{{ $label }}</span>
                        </label>
                    @endforeach
                </div>
            @elseif (isset($filter['min']))
                <div class="mt-2 flex items-center gap-2">
                    <label class="sr-only" for="f-{{ $field->key }}-min">{{ __('catalog::shop.min') }}</label>
                    <input id="f-{{ $field->key }}-min" type="number" step="any" name="f[{{ $field->key }}][min]" value="{{ $selectedFilters[$field->key]['min'] ?? '' }}" placeholder="{{ $filter['min'] }}" class="input py-1.5">
                    <span class="text-zinc-400">–</span>
                    <label class="sr-only" for="f-{{ $field->key }}-max">{{ __('catalog::shop.max') }}</label>
                    <input id="f-{{ $field->key }}-max" type="number" step="any" name="f[{{ $field->key }}][max]" value="{{ $selectedFilters[$field->key]['max'] ?? '' }}" placeholder="{{ $filter['max'] }}" class="input py-1.5">
                </div>
            @else
                <label class="mt-2 flex items-center gap-2">
                    <input type="checkbox" name="f[{{ $field->key }}]" value="1" @checked(! empty($selectedFilters[$field->key])) class="size-4 rounded border-zinc-300 accent-[var(--brand-primary)]">
                    <span>{{ __('catalog::shop.yes') }}</span>
                </label>
            @endif
        </fieldset>
    @endforeach

    <noscript><button type="submit" class="btn btn-secondary w-full">{{ __('catalog::shop.apply') }}</button></noscript>

    @if (! empty($selectedFilters))
        <a href="{{ url()->current() }}{{ request('sort') ? '?sort='.urlencode(request('sort')) : '' }}" class="inline-block text-zinc-600 underline underline-offset-2 hover:text-zinc-900">{{ __('catalog::shop.reset_filters') }}</a>
    @endif
</form>
