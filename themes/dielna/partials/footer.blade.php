{{-- Pätička Dielne: odber noviniek na šalviovom podklade, tmavá pätička s textom, podpisom a stĺpcami odkazov. --}}
@php
    $footerItems = $menus->items('footer');
    $opt = fn (string $key) => trim((string) $themeOptions->get($key)) ?: __('theme::dielna.'.$key);
@endphp
@if (! empty($newsletterEnabled))
    <section class="bg-sage-light" aria-labelledby="newsletter-heading">
        <div class="container-shop grid items-center gap-8 py-14 lg:grid-cols-2">
            <div>
                <p class="dielna-kicker">{{ $opt('newsletter_kicker') }}</p>
                <h2 id="newsletter-heading" class="dielna-heading mt-1 text-3xl sm:text-4xl">{{ $opt('newsletter_title') }}</h2>
                <p class="mt-3 max-w-md text-muted">{{ $opt('newsletter_text') }}</p>
            </div>
            <div class="[&_.btn]:px-6 [&_.btn]:py-3 [&_.input]:rounded-full [&_.input]:px-5 [&_.input]:py-3 [&_form>p]:hidden [&_label]:text-muted">
                @include('marketing::newsletter-form')
            </div>
        </div>
    </section>
@endif

<footer class="bg-ink text-[color-mix(in_srgb,var(--dielna-bg)_82%,transparent)]">
    <div class="container-shop grid gap-10 py-14 text-sm sm:grid-cols-2 lg:grid-cols-4">
        <div>
            <p class="font-serif text-2xl text-cream">{{ $shop->shop_name }}</p>
            <p class="mt-3 leading-relaxed">{{ $opt('footer_text') }}</p>
            @if (filled($themeOptions->get('footer_signature')))
                <p class="mt-4 font-hand text-2xl text-[color-mix(in_srgb,var(--brand-primary)_45%,white)]">– {{ $themeOptions->get('footer_signature') }}</p>
            @endif
        </div>

        @if (! empty($navigationCategories) && $navigationCategories->isNotEmpty())
            <nav aria-label="{{ __('theme::dielna.footer_shop') }}">
                <p class="font-medium text-cream">{{ __('theme::dielna.footer_shop') }}</p>
                <ul class="mt-3 space-y-2">
                    @foreach ($navigationCategories->take(6) as $footerCategory)
                        <li><a href="{{ $footerCategory->url() }}" class="hover:text-cream">{{ $footerCategory->name }}</a></li>
                    @endforeach
                </ul>
            </nav>
        @endif

        @if ($footerItems !== [] || (! empty($footerPages) && $footerPages->isNotEmpty()))
            <nav aria-label="{{ __('theme::dielna.footer_info') }}">
                <p class="font-medium text-cream">{{ __('theme::dielna.footer_info') }}</p>
                <ul class="mt-3 space-y-2">
                    @if ($footerItems !== [])
                        @foreach ($footerItems as $footerItem)
                            <li><a href="{{ $footerItem['url'] }}" class="hover:text-cream" @if ($footerItem['new_window']) target="_blank" rel="noopener" @endif>{{ $footerItem['label'] }}</a></li>
                        @endforeach
                    @else
                        @foreach ($footerPages as $footerPage)
                            <li><a href="{{ $footerPage->url() }}" class="hover:text-cream">{{ $footerPage->name }}</a></li>
                        @endforeach
                    @endif
                    @if (Route::has('returns.lookup'))
                        <li><a href="{{ route('returns.lookup') }}" class="hover:text-cream">{{ __('returns::returns.lookup_title') }}</a></li>
                    @endif
                </ul>
            </nav>
        @endif

        <div>
            <p class="font-medium text-cream">{{ __('theme::dielna.footer_contact') }}</p>
            <ul class="mt-3 space-y-2">
                <li><a href="mailto:{{ $shop->contact_email }}" class="hover:text-cream">{{ $shop->contact_email }}</a></li>
                @if ($shop->contact_phone)
                    <li><a href="tel:{{ preg_replace('/\s+/', '', $shop->contact_phone) }}" class="hover:text-cream">{{ $shop->contact_phone }}</a></li>
                @endif
                @if ($company->hasAddress())
                    <li>{{ $company->street }}, {{ trim($company->zip.' '.$company->city) }}</li>
                @endif
            </ul>
        </div>
    </div>

    <div class="border-t border-white/10">
        <div class="container-shop flex flex-col gap-2 py-5 text-xs sm:flex-row sm:justify-between">
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
