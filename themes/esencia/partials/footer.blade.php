{{-- Pätička Esencie: farebný blok s odberom noviniek, tmavá pätička so štítkami, kategóriami, informáciami a kontaktom. --}}
@php
    $footerItems = $menus->items('footer');
    $opt = fn (string $key) => trim((string) $themeOptions->get($key)) ?: __('theme::esencia.'.$key);
    $badges = collect(preg_split('/\R/', (string) $themeOptions->get('footer_badges')))->map(fn ($line) => trim($line))->filter()->take(4);
@endphp
@if (! empty($newsletterEnabled))
    <section class="container-shop pb-12 pt-4" aria-labelledby="newsletter-heading">
        <div class="grid items-center gap-8 rounded-[2rem] bg-ac p-8 text-onac sm:p-12 lg:grid-cols-2">
            <div>
                <h2 id="newsletter-heading" class="font-head text-4xl leading-none sm:text-5xl">{{ $opt('newsletter_title') }}</h2>
                <p class="mt-3 opacity-90">{{ $opt('newsletter_text') }}</p>
            </div>
            <div class="[&_.btn]:bg-[var(--es-ink)] [&_.btn]:px-6 [&_.btn]:py-3.5 [&_.btn]:text-white [&_.input]:rounded-full [&_.input]:border-0 [&_.input]:px-5 [&_.input]:py-3.5 [&_.input]:text-[var(--es-ink)] [&_a]:underline [&_form>p]:text-sm [&_label]:!text-current [&_.text-zinc-600]:!text-current [&_form>p]:opacity-90 [&_label]:opacity-90">
                @include('marketing::newsletter-form')
            </div>
        </div>
    </section>
@endif

<footer class="bg-ink text-white/80">
    <div class="container-shop grid gap-10 py-14 text-[15px] sm:grid-cols-2 lg:grid-cols-5">
        <div class="lg:col-span-2">
            <p class="font-head text-3xl text-white">{{ $shop->shop_name }}</p>
            <p class="mt-3 max-w-sm leading-relaxed">{{ $opt('footer_text') }}</p>
            @if ($badges->isNotEmpty())
                <ul class="mt-5 flex flex-wrap gap-2 text-xs font-semibold">
                    @foreach ($badges as $badge)
                        <li @class(['rounded-full px-3 py-1.5', 'es-sticker' => $loop->first, 'bg-white/10 text-white' => ! $loop->first])>{{ $badge }}</li>
                    @endforeach
                </ul>
            @endif
        </div>

        @if (! empty($navigationCategories) && $navigationCategories->isNotEmpty())
            <nav aria-label="{{ __('theme::esencia.footer_shop') }}">
                <p class="font-semibold text-white">{{ __('theme::esencia.footer_shop') }}</p>
                <ul class="mt-3 space-y-2">
                    @foreach ($navigationCategories->take(6) as $footerCategory)
                        <li><a href="{{ $footerCategory->url() }}" class="hover:text-white">{{ $footerCategory->name }}</a></li>
                    @endforeach
                </ul>
            </nav>
        @endif

        <nav aria-label="{{ __('theme::esencia.footer_info') }}">
            <p class="font-semibold text-white">{{ __('theme::esencia.footer_info') }}</p>
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
                @if (Route::has('returns.lookup'))
                    <li><a href="{{ route('returns.lookup') }}" class="hover:text-white">{{ __('returns::returns.lookup_title') }}</a></li>
                @endif
            </ul>
        </nav>

        <div>
            <p class="font-semibold text-white">{{ __('theme::esencia.footer_contact') }}</p>
            <ul class="mt-3 space-y-2">
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

    <div class="border-t border-white/10">
        <div class="container-shop flex flex-col gap-2 py-5 text-xs text-white/60 sm:flex-row sm:justify-between">
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
