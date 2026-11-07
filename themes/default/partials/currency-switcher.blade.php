{{-- Prepínač meny (iba pri obchode s viacerými menami). --}}
@if (isset($shopCurrencies) && $shopCurrencies->isMulticurrency())
    @php($currentCurrency = app(\App\Core\Support\CurrencyContext::class)->current())
    <form method="POST" action="{{ route('storefront.currency') }}" class="relative" x-data="{ open: false }" @click.outside="open = false" @keydown.escape="open = false">
        @csrf
        <button type="button" class="flex items-center gap-1 rounded-md px-2 py-2 text-sm font-medium text-zinc-700 hover:bg-zinc-100"
                @click="open = ! open" :aria-expanded="open.toString()" aria-haspopup="true" aria-label="{{ __('theme::shop.currency') }}">
            {{ $currentCurrency }}
            <svg class="size-4" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="m19.5 8.25-7.5 7.5-7.5-7.5" /></svg>
        </button>
        <div x-show="open" x-cloak class="absolute right-0 z-50 mt-1 min-w-32 rounded-xl border border-zinc-200 bg-white py-1 text-sm shadow-lg">
            @foreach ($shopCurrencies->enabled() as $currencyCode)
                <button type="submit" name="currency" value="{{ $currencyCode }}"
                        @class(['block w-full px-4 py-2 text-left hover:bg-zinc-50', 'font-semibold' => $currencyCode === $currentCurrency])
                        @if ($currencyCode === $currentCurrency) aria-current="true" @endif>
                    {{ $currencyCode }} <span class="text-zinc-500">{{ config('eshop.currencies.'.$currencyCode) }}</span>
                </button>
            @endforeach
        </div>
    </form>
@endif
