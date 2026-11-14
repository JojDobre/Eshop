{{--
    Hlavička Farmy: oznam v hornom pruhu, logo s vyhľadávaním a košíkom, pod nimi ponuka a krátka informácia.
    Rozloženie „Logo v strede“ (header_layout = center): vyhľadávanie vľavo, logo v strede, ponuka zarovnaná na stred.
--}}
@php
    $navItems = $menus->items('header');

    if ($navItems === [] && ! empty($navigationCategories)) {
        $navItems = $navigationCategories->map(fn ($category) => ['label' => $category->name, 'url' => $category->url(), 'new_window' => false, 'active' => false, 'children' => []])->all();
    }

    $noticeText = trim((string) $themeOptions->get('notice_text'));
    $noticeUntil = trim((string) $themeOptions->get('notice_until'));

    if ($noticeText !== '' && $noticeUntil !== '' && preg_match('/^\d{4}-\d{2}-\d{2}$/', $noticeUntil) && now()->toDateString() > $noticeUntil) {
        $noticeText = '';
    }

    $noticeKey = 'farma-notice-'.substr(md5($noticeText), 0, 8);
    $centered = $themeOptions->get('header_layout') === 'center';
    $logoText = trim((string) $themeOptions->get('logo_text'));
@endphp
<style>:root { --farma-footer: {{ $themeOptions->get('footer_color', '#44702f') }}; }</style>

@if ($noticeText !== '')
    <div class="border-b border-[#f3dcc4] bg-soft text-sm" x-data="{ hidden: false }" x-init="try { hidden = localStorage.getItem('{{ $noticeKey }}') === '1' } catch (e) {}" x-show="! hidden" role="region" aria-label="{{ __('theme::farma.notice') }}">
        <div class="container-shop relative flex min-h-11 items-center justify-center gap-3 py-2 pr-10 sm:pr-8">
            @if (filled($themeOptions->get('notice_label')))
                <span class="hidden shrink-0 rounded-full bg-white px-2.5 py-0.5 text-xs font-black uppercase tracking-wide text-accent sm:inline">{{ $themeOptions->get('notice_label') }}</span>
            @endif
            <p class="text-zinc-700">
                {{ $noticeText }}
                @if (filled($themeOptions->get('notice_code')))
                    <strong class="ml-1 rounded-md border border-dashed border-primary/50 bg-white px-1.5 font-black text-zinc-900">{{ $themeOptions->get('notice_code') }}</strong>
                @endif
                @if ($noticeUntil !== '' && preg_match('/^\d{4}-\d{2}-\d{2}$/', $noticeUntil))
                    <span class="hidden text-muted sm:inline">· {{ __('theme::farma.notice_until', ['date' => \Illuminate\Support\Carbon::parse($noticeUntil)->format('j. n.')]) }}</span>
                @endif
            </p>
            @if (filled($themeOptions->get('notice_link')))
                <a href="{{ $themeOptions->get('notice_link') }}" class="farma-arrow hidden shrink-0 font-extrabold text-accent hover:underline sm:inline">{{ __('theme::farma.notice_more') }} <span aria-hidden="true">→</span></a>
            @endif
            <button type="button" class="absolute right-2 rounded-full p-1.5 text-zinc-400 hover:text-zinc-700 sm:right-4" aria-label="{{ __('theme::farma.close') }}"
                    @click="hidden = true; try { localStorage.setItem('{{ $noticeKey }}', '1') } catch (e) {}">
                <svg class="size-4" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" aria-hidden="true"><path stroke-linecap="round" d="M6 6l12 12M18 6 6 18" /></svg>
            </button>
        </div>
    </div>
@endif

<header class="sticky top-0 z-40 bg-white" x-data="{ menu: false }">
    <div class="border-b border-line">
        {{-- Tri stĺpce: vyhľadávanie je vždy v strede stránky bez ohľadu na šírku loga a ikon. --}}
        <div class="container-shop grid h-16 grid-cols-[1fr_auto] items-center gap-3 sm:h-20 md:grid-cols-[1fr_minmax(0,28rem)_1fr] md:gap-6">
            <div @class(['flex min-w-0 items-center gap-3', 'md:order-2 md:justify-center' => $centered])>

                <a href="{{ route('storefront.home') }}" @class(['flex min-w-0 items-center gap-3 text-lg font-extrabold tracking-tight text-[#4a6573] sm:text-2xl', 'md:flex-col md:gap-1 md:text-center' => $centered])>
                    @if ($logo = $branding->logoUrl())
                        <img src="{{ $logo }}" alt="{{ $shop->shop_name }}" class="h-9 w-auto shrink-0 sm:h-11">
                        @if ($themeOptions->get('show_shop_name'))
                            <span class="shop-name" data-fit-text>{{ $shop->shop_name }}</span>
                        @endif
                    @else
                        <span class="shop-name" data-fit-text>{{ $shop->shop_name }}</span>
                    @endif
                    @if ($logoText !== '')
                        <span @class(['hidden max-w-56 whitespace-pre-line border-line text-xs font-bold leading-snug tracking-normal text-muted lg:block', 'border-l pl-3' => ! $centered])>{{ $logoText }}</span>
                    @endif
                </a>
            </div>

            @if (Route::has('catalog.search'))
                <form action="{{ route('catalog.search') }}" method="GET" role="search" @class(['hidden w-full md:block', 'md:order-1' => $centered])>
                    <label for="header-search" class="sr-only">{{ __('theme::shop.search') }}</label>
                    <div class="relative">
                        <svg class="pointer-events-none absolute left-4 top-1/2 size-4.5 -translate-y-1/2 text-zinc-400" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" aria-hidden="true"><circle cx="11" cy="11" r="7" /><path stroke-linecap="round" d="m20 20-3.5-3.5" /></svg>
                        <input id="header-search" type="search" name="q" value="{{ request('q') }}" placeholder="{{ __('theme::shop.search_placeholder') }}"
                               class="w-full rounded-full border-0 bg-[#f5f3ef] py-3 pl-11 pr-4 text-sm font-semibold outline-none transition placeholder:text-zinc-400 focus:bg-white focus:ring-2 focus:ring-primary/30">
                    </div>
                </form>
            @else
                <div class="hidden md:block"></div>
            @endif

            <div class="flex items-center justify-end gap-2 md:order-3">
                {{-- Na mobile sú prepínače jazyka a meny v menu, aby mal názov obchodu miesto. --}}
                <div class="hidden items-center gap-1 sm:flex">
                    @include('theme::partials.language-switcher')
                    @include('theme::partials.currency-switcher')
                </div>

                @if (Route::has('customer.account'))
                    <a href="{{ route('customer.account') }}" class="hidden size-10 place-items-center rounded-full border border-line text-zinc-700 hover:border-primary sm:grid" aria-label="{{ empty($customer) ? __('theme::account.login') : __('theme::account.title') }}">
                        <svg class="size-5" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" aria-hidden="true"><circle cx="12" cy="8" r="4" /><path stroke-linecap="round" d="M4 21c1.5-4 4.5-6 8-6s6.5 2 8 6" /></svg>
                    </a>
                @endif

                @if (! empty($compareEnabled) && ($compareCount ?? 0) > 0)
                    <a href="{{ route('compare.show') }}" class="rounded-full px-3 py-2 text-sm font-bold text-zinc-700 hover:bg-cream">{{ __('catalog::shop.compare_link', ['count' => $compareCount]) }}</a>
                @endif

                @if (Route::has('cart.show'))
                    <a href="{{ route('cart.show') }}" class="flex items-center gap-2 rounded-full bg-soft px-3.5 py-2.5 text-sm font-black text-accent hover:brightness-95">
                        <span class="sr-only">{{ trans_choice('theme::shop.cart_with_count', $cartCount ?? 0, ['count' => $cartCount ?? 0]) }}</span>
                        <svg class="size-5" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" aria-hidden="true"><path stroke-linejoin="round" d="M5 8h14l-1 12H6z" /><path stroke-linecap="round" d="M9 8V6a3 3 0 0 1 6 0v2" /></svg>
                        <span class="grid size-5 place-items-center rounded-full bg-primary text-[11px] text-primary-foreground" aria-hidden="true" data-cart-count>{{ $cartCount ?? 0 }}</span>
                    </a>
                @endif

                <button type="button" class="-mr-1 grid size-10 shrink-0 place-items-center rounded-full border border-line text-zinc-700 md:hidden" @click="menu = ! menu"
                        :aria-expanded="menu.toString()" aria-controls="mobile-menu" aria-label="{{ __('theme::shop.menu') }}">
                    <svg class="size-5" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" aria-hidden="true"><path stroke-linecap="round" d="M4 7h16M4 12h16M4 17h16" /></svg>
                </button>
            </div>
        </div>
    </div>

    @if ($navItems !== [] || filled($themeOptions->get('header_note')))
        {{-- Na mobile sú kategórie v ponuke pod tlačidlom menu. --}}
        <div class="hidden border-b border-line md:block">
            <div @class(['container-shop flex h-12 items-center gap-6', 'justify-between' => ! $centered, 'justify-center' => $centered])>
                <nav class="farma-nav flex gap-6 overflow-x-auto whitespace-nowrap text-sm font-bold text-zinc-600 [&_a:hover]:text-accent [&_[aria-current]]:text-accent [&_.absolute_a]:font-semibold" aria-label="{{ __('theme::shop.categories') }}">
                    @include('theme::partials.nav-items', ['items' => $navItems])
                </nav>
                @if (filled($themeOptions->get('header_note')) && ! $centered)
                    <p class="hidden shrink-0 items-center gap-1.5 text-sm font-bold text-muted lg:flex">
                        <svg class="size-4.5 text-[var(--farma-footer)]" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" aria-hidden="true"><path stroke-linejoin="round" d="M3 6h11v10H3zM14 10h4l3 3v3h-7" /><circle cx="7" cy="18" r="2" /><circle cx="17" cy="18" r="2" /></svg>
                        {{ $themeOptions->get('header_note') }}
                    </p>
                @endif
            </div>
        </div>
    @endif

    <div id="mobile-menu" x-show="menu" x-cloak class="border-b border-line bg-white md:hidden">
        @if ((isset($shopCurrencies) && $shopCurrencies->isMulticurrency()) || (! empty($alternateUrls) && count($alternateUrls) > 1))
            <div class="container-shop flex items-center gap-2 pt-3 sm:hidden">
                @include('theme::partials.language-switcher')
                @include('theme::partials.currency-switcher')
            </div>
        @endif
        @if (Route::has('catalog.search'))
            <form action="{{ route('catalog.search') }}" method="GET" role="search" class="container-shop pt-3">
                <label for="mobile-search" class="sr-only">{{ __('theme::shop.search') }}</label>
                <input id="mobile-search" type="search" name="q" value="{{ request('q') }}" placeholder="{{ __('theme::shop.search_placeholder') }}" class="input rounded-full">
            </form>
        @endif
        <ul class="container-shop divide-y divide-[var(--farma-line)] py-2 font-bold">
            @foreach ($navItems as $navItem)
                <li>
                    <a href="{{ $navItem['url'] }}" class="block py-3">{{ $navItem['label'] }}</a>
                    @foreach ($navItem['children'] as $child)
                        <a href="{{ $child['url'] }}" class="block py-2 pl-4 text-sm font-semibold text-zinc-600">{{ $child['label'] }}</a>
                    @endforeach
                </li>
            @endforeach
            @if (Route::has('customer.account'))
                <li><a href="{{ route('customer.account') }}" class="block py-3">{{ empty($customer) ? __('theme::account.login') : __('theme::account.title') }}</a></li>
            @endif
        </ul>
    </div>
</header>
