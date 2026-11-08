{{--
    Výber výdajného miesta pre zvolenú dopravu. Kuriér buď otvorí vlastné okno s mapou
    (window.eshopPickupWidgets[nazov]), alebo sa miesto vyhľadá v zozname obchodu.
--}}
@php
    $pickupSelectors = $shippingMethods->mapWithKeys(fn ($method) => [
        $method->id => $method->type->requiresPickupPoint() ? ($method->carrierService()?->pickupSelector($method) ?? ['list' => false]) : null,
    ])->filter();
    $pickupScripts = $pickupSelectors->flatMap(fn ($selector) => $selector['scripts'] ?? [])->unique()->values();
    $pickupAdapters = $pickupSelectors->map(fn ($selector) => $selector['adapter'] ?? null)->filter()->unique()->values();
@endphp

@if ($pickupSelectors->isNotEmpty())
    <div x-data="pickupPoint(@js(['selectors' => $pickupSelectors, 'url' => route('checkout.pickup-points'), 'old' => old('pickup_point')]))"
         x-show="selectors[shipping]" x-cloak class="space-y-3 rounded-xl border border-zinc-200 p-4">
        <p class="font-medium">{{ __('theme::shop.pickup_point') }}</p>

        <template x-if="point && point.code">
            <div class="rounded-lg bg-zinc-50 p-3 text-sm">
                <p class="font-medium" x-text="point.name"></p>
                <p class="text-zinc-600" x-text="[point.street, [point.zip, point.city].filter(Boolean).join(' ')].filter(Boolean).join(', ')"></p>
            </div>
        </template>

        <template x-if="selectors[shipping] && selectors[shipping].widget">
            <button type="button" class="btn btn-secondary" @click="openWidget()"
                    x-text="point && point.code ? @js(__('theme::shop.pickup_point_change')) : @js(__('theme::shop.pickup_point_choose'))"></button>
        </template>

        <template x-if="selectors[shipping] && selectors[shipping].list">
            <div class="space-y-2">
                <label for="pickup-search" class="sr-only">{{ __('theme::shop.pickup_point_search') }}</label>
                <input id="pickup-search" type="search" x-model="query" @input.debounce.400ms="search()" class="input"
                       placeholder="{{ __('theme::shop.pickup_point_search') }}" autocomplete="off">
                <ul class="max-h-64 divide-y divide-zinc-100 overflow-y-auto rounded-lg border border-zinc-100" x-show="results.length" role="listbox">
                    <template x-for="result in results" :key="result.code">
                        <li>
                            <button type="button" class="block w-full px-3 py-2 text-left text-sm hover:bg-zinc-50" @click="choose(result)">
                                <span class="font-medium" x-text="result.name"></span>
                                <span class="block text-zinc-500" x-text="result.address"></span>
                            </button>
                        </li>
                    </template>
                </ul>
                <p class="text-sm text-zinc-500" x-show="searched && ! results.length">{{ __('theme::shop.pickup_point_none') }}</p>
            </div>
        </template>

        <template x-if="point">
            <div>
                <input type="hidden" name="pickup_point[code]" :value="point.code">
                <input type="hidden" name="pickup_point[name]" :value="point.name">
                <input type="hidden" name="pickup_point[street]" :value="point.street || ''">
                <input type="hidden" name="pickup_point[city]" :value="point.city || ''">
                <input type="hidden" name="pickup_point[zip]" :value="point.zip || ''">
                <input type="hidden" name="pickup_point[country]" :value="point.country || ''">
                <input type="hidden" name="pickup_point[carrier_code]" :value="point.carrier_code || ''">
            </div>
        </template>

        @error('pickup_point') <p class="field-error">{{ $message }}</p> @enderror
        @error('pickup_point.code') <p class="field-error">{{ $message }}</p> @enderror
    </div>

    @push('scripts')
        @foreach ($pickupScripts as $pickupScript)
            <script src="{{ $pickupScript }}"></script>
        @endforeach
        <script>
            window.eshopPickupWidgets = window.eshopPickupWidgets || {};
            @foreach ($pickupAdapters as $pickupAdapter)
                {!! $pickupAdapter !!}
            @endforeach
            document.addEventListener('alpine:init', () => {
                window.Alpine.data('pickupPoint', (config) => ({
                    selectors: config.selectors,
                    point: config.old && config.old.code ? config.old : null,
                    query: '',
                    results: [],
                    searched: false,
                    init() {
                        this.$watch('shipping', () => { this.point = null; this.results = []; this.query = ''; this.searched = false; });
                    },
                    openWidget() {
                        const selector = this.selectors[this.shipping];
                        const widget = window.eshopPickupWidgets[selector.widget];
                        if (widget) {
                            widget(selector.config || {}, (point) => { if (point) { this.point = point; } });
                        }
                    },
                    async search() {
                        if (this.query.trim().length < 2) { this.results = []; return; }
                        const response = await fetch(config.url + '?method=' + this.shipping + '&q=' + encodeURIComponent(this.query), { headers: { Accept: 'application/json' } });
                        this.results = (await response.json()).points || [];
                        this.searched = true;
                    },
                    choose(result) {
                        this.point = { code: result.code, name: result.name, street: result.address };
                        this.results = [];
                    },
                }));
            });
        </script>
    @endpush
@endif
