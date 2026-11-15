{{-- Pätička Butiku: farebný blok s odberom noviniek, tmavá pätička s logom, kategóriami, pomocou a sociálnymi sieťami. --}}
@php
    $footerItems = $menus->items('footer');
    $opt = fn (string $key) => trim((string) $themeOptions->get($key)) ?: __('theme::butik.'.$key);
    $socials = collect(['instagram' => 'Instagram', 'tiktok' => 'TikTok', 'pinterest' => 'Pinterest', 'facebook' => 'Facebook'])
        ->mapWithKeys(fn ($label, $key) => [$label => trim((string) $themeOptions->get('social_'.$key))])->filter();
@endphp
@if (! empty($newsletterEnabled))
    <section class="bg-ac text-onac" aria-labelledby="newsletter-heading">
        <div class="container-shop grid items-end gap-8 py-16 lg:grid-cols-2">
            <h2 id="newsletter-heading" class="font-head text-6xl sm:text-8xl">{!! collect(explode('|', $opt('newsletter_title')))->map(fn ($line) => e(trim($line)))->implode('<br>') !!}</h2>
            <div>
                <p class="text-lg">{{ $opt('newsletter_text') }}</p>
                <div class="mt-5 [&_.btn]:bg-[var(--bt-ink)] [&_.btn]:text-white [&_.input]:border-0 [&_.input]:border-b-2 [&_.input]:border-current [&_.input]:bg-transparent [&_.input]:px-0 [&_.input]:text-lg [&_.input]:text-current [&_.input]:placeholder:text-current [&_.input]:placeholder:opacity-70 [&_a]:underline [&_form>p]:text-sm [&_label]:!text-current [&_.text-zinc-600]:!text-current [&_label]:opacity-90">
                    @include('marketing::newsletter-form')
                </div>
            </div>
        </div>
    </section>
@endif

<footer class="bg-ink text-white/75">
    <div class="container-shop grid gap-10 py-14 text-sm sm:grid-cols-2 lg:grid-cols-5">
        <div class="lg:col-span-2">
            <p class="font-head text-5xl text-white">{{ $shop->shop_name }}</p>
            <p class="mt-4 max-w-sm leading-relaxed">{{ $opt('footer_text') }}</p>
        </div>

        @if (! empty($navigationCategories) && $navigationCategories->isNotEmpty())
            <nav aria-label="{{ __('theme::butik.footer_shop') }}">
                <p class="cap text-white">{{ __('theme::butik.footer_shop') }}</p>
                <ul class="mt-4 space-y-2">
                    @foreach ($navigationCategories->take(6) as $footerCategory)
                        <li><a href="{{ $footerCategory->url() }}" class="hover:text-white">{{ $footerCategory->name }}</a></li>
                    @endforeach
                </ul>
            </nav>
        @endif

        <nav aria-label="{{ __('theme::butik.footer_info') }}">
            <p class="cap text-white">{{ __('theme::butik.footer_info') }}</p>
            <ul class="mt-4 space-y-2">
                @if ($footerItems !== [])
                    @foreach ($footerItems as $footerItem)
                        <li><a href="{{ $footerItem['url'] }}" class="hover:text-white" @if ($footerItem['new_window']) target="_blank" rel="noopener" @endif>{{ $footerItem['label'] }}</a></li>
                    @endforeach
                @elseif (! empty($footerPages))
                    @foreach ($footerPages as $footerPage)
                        <li><a href="{{ $footerPage->url() }}" class="hover:text-white">{{ $footerPage->name }}</a></li>
                    @endforeach
                @endif
                @if (Route::has('returns.lookup'))
                    <li><a href="{{ route('returns.lookup') }}" class="hover:text-white">{{ __('returns::returns.lookup_title') }}</a></li>
                @endif
            </ul>
        </nav>

        <div>
            <p class="cap text-white">{{ __('theme::butik.footer_social') }}</p>
            <ul class="mt-4 space-y-2">
                @foreach ($socials as $network => $url)
                    <li><a href="{{ $url }}" target="_blank" rel="noopener" class="hover:text-white">{{ $network }}</a></li>
                @endforeach
                <li><a href="mailto:{{ $shop->contact_email }}" class="hover:text-white">{{ $shop->contact_email }}</a></li>
                @if ($shop->contact_phone)
                    <li><a href="tel:{{ preg_replace('/\s+/', '', $shop->contact_phone) }}" class="hover:text-white">{{ $shop->contact_phone }}</a></li>
                @endif
            </ul>
        </div>
    </div>

    <div class="border-t border-white/10">
        <div class="container-shop flex flex-col gap-2 py-5 text-xs text-white/55 sm:flex-row sm:justify-between">
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
