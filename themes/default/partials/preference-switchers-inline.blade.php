{{--
    Jazyk a mena v mobilnom menu: možnosti vedľa seba ako tlačidlá (bez rozbaľovacieho zoznamu, nič nevypadne
    mimo obrazovku). Zobrazí sa iba pri viacjazyčnom obchode alebo obchode s viacerými menami.
--}}
@php
    $inlineLocales = ! empty($alternateUrls) && count($alternateUrls) > 1;
    $inlineCurrencies = isset($shopCurrencies) && $shopCurrencies->isMulticurrency();
@endphp
@if ($inlineLocales || $inlineCurrencies)
    <div class="preference-switchers space-y-3 py-4">
        @if ($inlineLocales)
            <div class="flex items-center justify-between gap-3">
                <span class="text-sm text-zinc-500">{{ __('theme::shop.language') }}</span>
                <div class="flex flex-wrap justify-end gap-1.5">
                    @foreach ($alternateUrls as $alternateLocale => $alternateUrl)
                        <a href="{{ $alternateUrl }}" hreflang="{{ $alternateLocale }}" lang="{{ $alternateLocale }}"
                           @class(['preference-option rounded-full border px-3 py-1.5 text-sm font-semibold uppercase', 'is-active border-primary bg-primary text-primary-foreground' => $alternateLocale === app()->getLocale(), 'border-zinc-200 text-zinc-700' => $alternateLocale !== app()->getLocale()])
                           @if ($alternateLocale === app()->getLocale()) aria-current="true" @endif>{{ $shopLocales->urlCode($alternateLocale) }}</a>
                    @endforeach
                </div>
            </div>
        @endif
        @if ($inlineCurrencies)
            @php($inlineCurrent = app(\App\Core\Support\CurrencyContext::class)->current())
            <form method="POST" action="{{ route('storefront.currency') }}" class="flex items-center justify-between gap-3">
                @csrf
                <span class="text-sm text-zinc-500">{{ __('theme::shop.currency') }}</span>
                <div class="flex flex-wrap justify-end gap-1.5">
                    @foreach ($shopCurrencies->enabled() as $currencyCode)
                        <button type="submit" name="currency" value="{{ $currencyCode }}"
                                @class(['preference-option rounded-full border px-3 py-1.5 text-sm font-semibold', 'is-active border-primary bg-primary text-primary-foreground' => $currencyCode === $inlineCurrent, 'border-zinc-200 text-zinc-700' => $currencyCode !== $inlineCurrent])
                                @if ($currencyCode === $inlineCurrent) aria-current="true" @endif>{{ $currencyCode }}</button>
                    @endforeach
                </div>
            </form>
        @endif
    </div>
@endif
