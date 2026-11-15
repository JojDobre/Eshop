{{-- Pätička Štúdia: tmavý podklad s názvom, textom a sociálnymi sieťami, služby, informácie a odber noviniek. --}}
@php
    $footerItems = $menus->items('footer');
    $footerText = trim((string) $themeOptions->get('footer_text')) ?: __('theme::studio.footer_text');
    $footerServices = Route::has('reservations.index')
        ? rescue(fn () => \Modules\Reservations\Models\BookingService::query()->active()->orderBy('position')->orderBy('id')->limit(5)->get(), collect(), false)
        : collect();
    $socials = array_filter(['Instagram' => trim((string) $themeOptions->get('social_instagram')), 'Facebook' => trim((string) $themeOptions->get('social_facebook'))]);
@endphp
<footer class="bg-ink text-[color-mix(in_srgb,white_78%,var(--studio-ink))]">
    <div class="container-shop grid gap-10 py-14 text-sm sm:grid-cols-2 lg:grid-cols-4">
        <div>
            <p class="font-serif text-3xl text-white">{{ $shop->shop_name }}</p>
            <p class="mt-3 leading-relaxed">{{ $footerText }}</p>
            @if ($socials !== [])
                <div class="mt-5 flex gap-3">
                    @foreach ($socials as $network => $url)
                        <a href="{{ $url }}" target="_blank" rel="noopener" class="grid size-9 place-items-center rounded-full border border-white/20 hover:border-white hover:text-white" aria-label="{{ $network }}">
                            @if ($network === 'Instagram')
                                <svg class="size-4" fill="none" viewBox="0 0 24 24" stroke-width="1.6" stroke="currentColor" aria-hidden="true"><rect x="3" y="3" width="18" height="18" rx="5" /><circle cx="12" cy="12" r="4" /><circle cx="17.5" cy="6.5" r=".8" fill="currentColor" /></svg>
                            @else
                                <svg class="size-4" fill="currentColor" viewBox="0 0 24 24" aria-hidden="true"><path d="M14 8h3V4h-3c-2.8 0-4 1.7-4 4.3V10H7v4h3v8h4v-8h3l1-4h-4V8.6c0-.4.3-.6.6-.6z" /></svg>
                            @endif
                        </a>
                    @endforeach
                </div>
            @endif
        </div>

        @if ($footerServices->isNotEmpty())
            <nav aria-label="{{ __('theme::studio.footer_services') }}">
                <p class="font-semibold text-white">{{ __('theme::studio.footer_services') }}</p>
                <ul class="mt-3 space-y-2">
                    @foreach ($footerServices as $footerService)
                        <li><a href="{{ route('reservations.service', $footerService) }}" class="hover:text-white">{{ $footerService->name }}</a></li>
                    @endforeach
                </ul>
            </nav>
        @elseif (! empty($navigationCategories) && $navigationCategories->isNotEmpty())
            <nav aria-label="{{ __('theme::studio.shop_link') }}">
                <p class="font-semibold text-white">{{ __('theme::studio.shop_link') }}</p>
                <ul class="mt-3 space-y-2">
                    @foreach ($navigationCategories->take(5) as $footerCategory)
                        <li><a href="{{ $footerCategory->url() }}" class="hover:text-white">{{ $footerCategory->name }}</a></li>
                    @endforeach
                </ul>
            </nav>
        @endif

        <nav aria-label="{{ __('theme::studio.footer_info') }}">
            <p class="font-semibold text-white">{{ __('theme::studio.footer_info') }}</p>
            <ul class="mt-3 space-y-2">
                @if ($footerItems !== [])
                    @foreach ($footerItems as $footerItem)
                        <li><a href="{{ $footerItem['url'] }}" class="hover:text-white" @if ($footerItem['new_window']) target="_blank" rel="noopener" @endif>{{ $footerItem['label'] }}</a></li>
                    @endforeach
                @elseif (! empty($footerPages))
                    @foreach ($footerPages as $footerPage)
                        <li><a href="{{ $footerPage->url() }}" class="hover:text-white">{{ $footerPage->name }}</a></li>
                    @endforeach
                @endif
                <li><a href="mailto:{{ $shop->contact_email }}" class="hover:text-white">{{ $shop->contact_email }}</a></li>
                @if ($shop->contact_phone)
                    <li><a href="tel:{{ preg_replace('/\s+/', '', $shop->contact_phone) }}" class="hover:text-white">{{ $shop->contact_phone }}</a></li>
                @endif
            </ul>
        </nav>

        @if (! empty($newsletterEnabled))
            <div>
                <p class="font-semibold text-white">{{ __('theme::studio.footer_news') }}</p>
                <div class="mt-3 [&_.btn]:px-4 [&_.btn]:py-2.5 [&_.input]:rounded-full [&_.input]:border-white/10 [&_.input]:bg-white/10 [&_.input]:text-white [&_.input]:placeholder:text-white/50 [&_form>p]:text-xs [&_label]:text-white/70">
                    @include('marketing::newsletter-form')
                </div>
            </div>
        @endif
    </div>

    <div class="border-t border-white/10">
        <div class="container-shop flex flex-col gap-2 py-5 text-xs text-[color-mix(in_srgb,white_58%,var(--studio-ink))] sm:flex-row sm:justify-between">
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
