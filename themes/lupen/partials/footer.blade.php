{{-- Pätička Lupeňa: text o obchode, kategórie, doručenie, predajňa s otváracími hodinami a odber noviniek. --}}
@php
    $footerItems = $menus->items('footer');
    $footerText = trim((string) $themeOptions->get('footer_text')) ?: __('theme::lupen.footer_text');
    $lines = fn (string $key) => collect(preg_split('/\R/', (string) $themeOptions->get($key)))->map(fn ($line) => trim($line))->filter();
    $delivery = $lines('footer_delivery');
    $hours = $lines('footer_hours');
@endphp
<footer class="bg-soft">
    @if (! empty($newsletterEnabled))
        <div class="container-shop pt-12">
            <div class="leaf-sm grid items-center gap-6 bg-card p-6 ring-1 ring-line sm:p-8 lg:grid-cols-2 [&_.input]:rounded-full [&_.btn]:px-6">
                <p class="font-head text-3xl">{{ __('marketing::shop.newsletter_title') }}</p>
                @include('marketing::newsletter-form')
            </div>
        </div>
    @endif
    <div class="container-shop grid gap-10 py-14 text-[15px] sm:grid-cols-2 lg:grid-cols-4">
        <div>
            <p class="font-head text-3xl">{{ $shop->shop_name }}</p>
            <p class="mt-3 leading-relaxed text-muted">{{ $footerText }}</p>
        </div>
        @if (! empty($navigationCategories) && $navigationCategories->isNotEmpty())
            <nav aria-label="{{ __('theme::lupen.footer_shop') }}">
                <p class="font-bold">{{ __('theme::lupen.footer_shop') }}</p>
                <ul class="mt-3 space-y-2 text-muted">
                    @foreach ($navigationCategories->take(6) as $footerCategory)
                        <li><a href="{{ $footerCategory->url() }}" class="hover:text-ink">{{ $footerCategory->name }}</a></li>
                    @endforeach
                </ul>
            </nav>
        @endif
        <div>
            @if ($delivery->isNotEmpty())
                <p class="font-bold">{{ __('theme::lupen.footer_delivery') }}</p>
                <ul class="mt-3 space-y-2 text-muted">
                    @foreach ($delivery as $line)<li>{{ $line }}</li>@endforeach
                </ul>
            @endif
            <p @class(['font-bold', 'mt-6' => $delivery->isNotEmpty()])>{{ __('theme::lupen.footer_info') }}</p>
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
            </ul>
        </div>
        <div>
            <p class="font-bold">{{ __('theme::lupen.footer_store') }}</p>
            <ul class="mt-3 space-y-2 text-muted">
                @foreach ($hours as $line)<li>{{ $line }}</li>@endforeach
                @if ($hours->isEmpty() && $company->hasAddress())
                    <li>{{ $company->street }}, {{ trim($company->zip.' '.$company->city) }}</li>
                @endif
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
