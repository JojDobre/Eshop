{{-- Pätička Dvora: zelený panel s odberom noviniek, plot z latiek a stĺpce nákup / rozvoz / farma. --}}
@php
    $footerItems = $menus->items('footer');
    $opt = fn (string $key) => trim((string) $themeOptions->get($key)) ?: __('theme::dvor.'.$key);
    $lines = fn (string $key) => collect(preg_split('/\R/', (string) $themeOptions->get($key)))->map(fn ($line) => trim($line))->filter();
    $delivery = $lines('footer_delivery');
    $hours = $lines('footer_hours');
@endphp
@if (! empty($newsletterEnabled))
    <section class="container-shop pb-16" aria-labelledby="newsletter-heading">
        <div class="grid items-center gap-8 rounded-[2rem] bg-ac p-8 text-onac sm:p-12 lg:grid-cols-[1.2fr_1fr]">
            <div>
                <h2 id="newsletter-heading" class="font-head text-4xl leading-tight sm:text-5xl">{{ $opt('newsletter_title') }}</h2>
                <p class="mt-3 max-w-md opacity-90">{{ $opt('newsletter_text') }}</p>
            </div>
            <div class="dv-news [&_.input]:!bg-white [&_.btn]:bg-[var(--dv-sun)] [&_.btn]:text-[var(--dv-onsun)] [&_.input]:border-0 [&_.input]:bg-white [&_.input]:text-[#13261a] [&_a]:underline [&_label]:!text-current [&_.text-zinc-600]:!text-current [&_.text-zinc-600]:opacity-85">
                @include('marketing::newsletter-form')
            </div>
        </div>
    </section>
@endif

<footer class="bg-soft">
    <div class="fence h-3" aria-hidden="true"></div>
    <div class="container-shop grid gap-10 py-12 text-[15px] sm:grid-cols-2 lg:grid-cols-4">
        <div>
            <p class="font-head text-3xl">{{ $shop->shop_name }}</p>
            <p class="mt-3 leading-relaxed text-muted">{{ $opt('footer_text') }}</p>
        </div>
        @if (! empty($navigationCategories) && $navigationCategories->isNotEmpty())
            <nav aria-label="{{ __('theme::dvor.footer_shop') }}">
                <p class="font-extrabold">{{ __('theme::dvor.footer_shop') }}</p>
                <ul class="mt-3 space-y-2 text-muted">
                    @foreach ($navigationCategories->take(6) as $footerCategory)
                        <li><a href="{{ $footerCategory->url() }}" class="hover:text-ink">{{ $footerCategory->name }}</a></li>
                    @endforeach
                </ul>
            </nav>
        @endif
        <div>
            @if ($delivery->isNotEmpty())
                <p class="font-extrabold">{{ __('theme::dvor.footer_delivery') }}</p>
                <ul class="mt-3 space-y-2 text-muted">
                    @foreach ($delivery as $line)<li>{{ $line }}</li>@endforeach
                </ul>
            @endif
            <p @class(['font-extrabold', 'mt-8' => $delivery->isNotEmpty()])>{{ __('theme::dvor.footer_info') }}</p>
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
        </div>
        <div>
            <p class="font-extrabold">{{ __('theme::dvor.footer_farm') }}</p>
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
