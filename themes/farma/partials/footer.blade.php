{{-- Pätička Farmy: odber noviniek, stĺpce s obchodom, informáciami a kontaktom vo farbe z nastavení. --}}
@php($footerItems = $menus->items('footer'))
<footer class="mt-16 bg-footer text-white">
    <div class="container-shop">
        @if (! empty($newsletterEnabled))
            <div class="flex flex-col gap-6 border-b border-white/15 py-10 md:flex-row md:items-center md:justify-between [&_.input]:rounded-full [&_.input]:border-0 [&_label]:text-white [&_p]:text-xl [&_p]:font-black [&_p]:text-white">
                @include('marketing::newsletter-form')
            </div>
        @endif

        <div class="grid gap-8 py-12 text-sm sm:grid-cols-2 lg:grid-cols-4">
            <div class="sm:col-span-2 lg:col-span-1">
                <p class="text-xl font-extrabold text-white">{{ $shop->shop_name }}</p>
                @if (filled($shop->tagline))
                    <p class="mt-3 max-w-xs">{{ $shop->tagline }}</p>
                @endif
            </div>

            @if (! empty($navigationCategories) && $navigationCategories->isNotEmpty())
                <nav aria-label="{{ __('theme::farma.shop') }}">
                    <p class="mb-3 font-black text-white">{{ __('theme::farma.shop') }}</p>
                    <ul class="space-y-2">
                        @foreach ($navigationCategories->take(6) as $footerCategory)
                            <li><a href="{{ $footerCategory->url() }}" class="hover:text-white">{{ $footerCategory->name }}</a></li>
                        @endforeach
                    </ul>
                </nav>
            @endif

            @if ($footerItems !== [] || (! empty($footerPages) && $footerPages->isNotEmpty()))
                <nav aria-label="{{ __('theme::shop.information') }}">
                    <p class="mb-3 font-black text-white">{{ __('theme::shop.information') }}</p>
                    <ul class="space-y-2">
                        @if ($footerItems !== [])
                            @foreach ($footerItems as $footerItem)
                                <li><a href="{{ $footerItem['url'] }}" class="hover:text-white" @if ($footerItem['new_window']) target="_blank" rel="noopener" @endif>{{ $footerItem['label'] }}</a></li>
                            @endforeach
                        @else
                            @foreach ($footerPages as $footerPage)
                                <li><a href="{{ $footerPage->url() }}" class="hover:text-white">{{ $footerPage->name }}</a></li>
                            @endforeach
                        @endif
                        @if (Route::has('returns.lookup'))
                            <li><a href="{{ route('returns.lookup') }}" class="hover:text-white">{{ __('returns::returns.lookup_title') }}</a></li>
                        @endif
                    </ul>
                </nav>
            @endif

            <div>
                <p class="mb-3 font-black text-white">{{ __('theme::shop.contact') }}</p>
                <ul class="space-y-2">
                    <li><a href="mailto:{{ $shop->contact_email }}" class="hover:text-white">{{ $shop->contact_email }}</a></li>
                    @if ($shop->contact_phone)
                        <li><a href="tel:{{ preg_replace('/\s+/', '', $shop->contact_phone) }}" class="hover:text-white">{{ $shop->contact_phone }}</a></li>
                    @endif
                    @if ($company->hasAddress())
                        <li>{{ $company->street }}, {{ trim($company->zip.' '.$company->city) }}</li>
                    @endif
                </ul>
            </div>
        </div>

        <div class="flex flex-col gap-2 border-t border-white/15 py-5 text-xs text-white/90 sm:flex-row sm:justify-between">
            <p>
                © {{ date('Y') }} {{ $company->company_name ?: $shop->shop_name }}
                @if ($company->company_id) · {{ __('theme::shop.company_id') }}: {{ $company->company_id }}@endif
                @if ($company->vat_id) · {{ __('theme::shop.vat_id') }}: {{ $company->vat_id }}@endif
            </p>
            @if (isset($cookieConsent) && $cookieConsent->isActive())
                <button type="button" class="text-left underline-offset-2 hover:underline" x-data @click="$dispatch('open-cookie-settings')">{{ __('theme::shop.cookies_settings') }}</button>
            @endif
        </div>
    </div>
</footer>
