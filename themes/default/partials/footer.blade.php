<footer class="mt-16 border-t border-zinc-100 bg-zinc-50">
    <div class="container-shop grid gap-10 py-12 text-sm text-zinc-600 sm:grid-cols-3">
        <div>
            <p class="font-semibold text-zinc-900">{{ $company->company_name ?: $shop->shop_name }}</p>
            @if ($themeOptions->get('show_tagline', true) && filled($shop->tagline))
                <p class="mt-1">{{ $shop->tagline }}</p>
            @endif
            @if ($company->hasAddress())
                <address class="mt-2 not-italic">
                    {{ $company->street }}<br>
                    {{ trim($company->zip.' '.$company->city) }}
                </address>
            @endif
            @if ($company->company_id)
                <p class="mt-2">{{ __('theme::shop.company_id') }}: {{ $company->company_id }}</p>
            @endif
            @if ($company->vat_id)
                <p>{{ __('theme::shop.vat_id') }}: {{ $company->vat_id }}</p>
            @endif
        </div>

        @php($footerItems = $menus->items('footer'))
        @if ($footerItems !== [])
            <nav aria-label="{{ __('theme::shop.information') }}">
                <p class="font-semibold text-zinc-900">{{ __('theme::shop.information') }}</p>
                <ul class="mt-2 space-y-1.5">
                    @foreach ($footerItems as $footerItem)
                        <li><a href="{{ $footerItem['url'] }}" class="hover:text-zinc-900" @if ($footerItem['new_window']) target="_blank" rel="noopener" @endif>{{ $footerItem['label'] }}</a></li>
                    @endforeach
                </ul>
            </nav>
        @elseif (! empty($footerPages) && $footerPages->isNotEmpty())
            <nav aria-label="{{ __('theme::shop.information') }}">
                <p class="font-semibold text-zinc-900">{{ __('theme::shop.information') }}</p>
                <ul class="mt-2 space-y-1.5">
                    @foreach ($footerPages as $footerPage)
                        <li><a href="{{ $footerPage->url() }}" class="hover:text-zinc-900">{{ $footerPage->name }}</a></li>
                    @endforeach
                </ul>
            </nav>
        @endif

        <div>
            <p class="font-semibold text-zinc-900">{{ __('theme::shop.contact') }}</p>
            <p class="mt-2"><a href="mailto:{{ $shop->contact_email }}" class="hover:text-zinc-900">{{ $shop->contact_email }}</a></p>
            @if ($shop->contact_phone)
                <p class="mt-1"><a href="tel:{{ preg_replace('/\s+/', '', $shop->contact_phone) }}" class="hover:text-zinc-900">{{ $shop->contact_phone }}</a></p>
            @endif
            @if (Route::has('returns.lookup'))
                <p class="mt-3"><a href="{{ route('returns.lookup') }}" class="hover:text-zinc-900">{{ __('returns::returns.lookup_title') }}</a></p>
            @endif
            @if (! empty($newsletterEnabled))
                <div class="mt-6">@include('marketing::newsletter-form')</div>
            @endif
        </div>
    </div>
    <div class="border-t border-zinc-200/60 py-4 text-center text-xs text-zinc-400">
        © {{ date('Y') }} {{ $company->company_name ?: $shop->shop_name }}
        @if (isset($cookieConsent) && $cookieConsent->isActive())
            · <button type="button" class="underline-offset-2 hover:underline" x-data @click="$dispatch('open-cookie-settings')">{{ __('theme::shop.cookies_settings') }}</button>
        @endif
    </div>
</footer>
