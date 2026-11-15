{{-- Pätička Kapitoly: Klub čitateľov na fotke (odber noviniek), stĺpce s kategóriami, pomocou a adresou kníhkupectva. --}}
@php
    $footerItems = $menus->items('footer');
    $opt = fn (string $key) => trim((string) $themeOptions->get($key)) ?: __('theme::kapitola.'.$key);
    $hours = collect(preg_split('/\R/', (string) $themeOptions->get('footer_hours')))->map(fn ($line) => trim($line))->filter();
    $newsletterImage = $themeOptions->imageUrl('newsletter_image');
@endphp
@if (! empty($newsletterEnabled))
    <section class="relative overflow-hidden bg-[#1a1416]" aria-labelledby="newsletter-heading">
        @if ($newsletterImage)
            <img {{ responsive_img($newsletterImage, '100vw') }} alt="" class="absolute inset-0 size-full object-cover" loading="lazy">
        @endif
        <div class="absolute inset-0 bg-black/60"></div>
        <div class="container-shop relative grid items-center gap-8 py-16 text-white lg:grid-cols-2">
            <div>
                <h2 id="newsletter-heading" class="font-head text-4xl sm:text-5xl">{{ $opt('newsletter_title') }}</h2>
                <p class="mt-3 text-white/85">{{ $opt('newsletter_text') }}</p>
            </div>
            <div class="[&_.btn]:bg-[var(--kp-ac2)] [&_.btn]:text-[var(--kp-onac2)] [&_.input]:border-0 [&_.input]:bg-white [&_.input]:text-[#24161a] [&_a]:underline [&_label]:!text-white/85 [&_.text-zinc-600]:!text-white/85">
                @include('marketing::newsletter-form')
            </div>
        </div>
    </section>
@endif

<footer class="bg-soft">
    <div class="container-shop grid gap-10 py-12 text-[15px] sm:grid-cols-2 lg:grid-cols-4">
        <div>
            <p class="font-head text-3xl">{{ $shop->shop_name }}</p>
            <p class="mt-3 leading-relaxed text-muted">{{ $opt('footer_text') }}</p>
        </div>
        @if (! empty($navigationCategories) && $navigationCategories->isNotEmpty())
            <nav aria-label="{{ __('theme::kapitola.footer_shop') }}">
                <p class="font-bold">{{ __('theme::kapitola.footer_shop') }}</p>
                <ul class="mt-3 space-y-2 text-muted">
                    @foreach ($navigationCategories->take(6) as $footerCategory)
                        <li><a href="{{ $footerCategory->url() }}" class="hover:text-ink">{{ $footerCategory->name }}</a></li>
                    @endforeach
                </ul>
            </nav>
        @endif
        <nav aria-label="{{ __('theme::kapitola.footer_info') }}">
            <p class="font-bold">{{ __('theme::kapitola.footer_info') }}</p>
            <ul class="mt-3 space-y-2 text-muted">
                @if ($footerItems !== [])
                    @foreach ($footerItems as $footerItem)
                        <li><a href="{{ $footerItem['url'] }}" class="hover:text-ink" @if ($footerItem['new_window']) target="_blank" rel="noopener" @endif>{{ $footerItem['label'] }}</a></li>
                    @endforeach
                @elseif (! empty($footerPages))
                    @foreach ($footerPages as $footerPage)
                        <li><a href="{{ $footerPage->url() }}" class="hover:text-ink">{{ $footerPage->name }}</a></li>
                    @endforeach
                @endif
                @if (Route::has('returns.lookup'))
                    <li><a href="{{ route('returns.lookup') }}" class="hover:text-ink">{{ __('returns::returns.lookup_title') }}</a></li>
                @endif
            </ul>
        </nav>
        <div>
            <p class="font-bold">{{ __('theme::kapitola.footer_store') }}</p>
            <ul class="mt-3 space-y-2 text-muted">
                @foreach ($hours as $line)<li>{{ $line }}</li>@endforeach
                <li><a href="mailto:{{ $shop->contact_email }}" class="hover:text-ink">{{ $shop->contact_email }}</a></li>
                @if ($shop->contact_phone)
                    <li><a href="tel:{{ preg_replace('/\s+/', '', $shop->contact_phone) }}" class="hover:text-ink">{{ $shop->contact_phone }}</a></li>
                @endif
            </ul>
        </div>
    </div>
    <div class="border-t border-line">
        <div class="container-shop flex flex-col gap-2 py-5 text-xs text-muted sm:flex-row sm:justify-between">
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
