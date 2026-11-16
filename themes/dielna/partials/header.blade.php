{{--
    Hlavička Dielne: tmavý pruh s oznamom, logo s ručne písaným textom, ponuka a ikony (hľadanie, účet, košík).
    Farby šablóny z Prispôsobenia sa vkladajú ako premenné CSS.
--}}
@php
    $navItems = $menus->items('header');

    if ($navItems === [] && ! empty($navigationCategories)) {
        $navItems = $navigationCategories->map(fn ($category) => ['label' => $category->name, 'url' => $category->url(), 'new_window' => false, 'active' => false, 'children' => []])->all();
    }

    $noticeText = trim((string) $themeOptions->get('notice_text'));
    $logoText = trim((string) $themeOptions->get('logo_text'));
    $cssColor = fn (string $key, string $fallback) => preg_match('/^#[0-9a-fA-F]{3,8}$/', (string) $themeOptions->get($key)) ? $themeOptions->get($key) : $fallback;
@endphp
<style>:root { --dielna-bg: {{ $cssColor('color_background', '#f7f1e8') }}; --dielna-sand: {{ $cssColor('color_sand', '#efe5d6') }}; --dielna-sage: {{ $cssColor('color_sage', '#7d8b6a') }}; --dielna-ink: {{ $cssColor('color_ink', '#2b2520') }}; }</style>

@if ($noticeText !== '')
    <div class="bg-ink text-[13px] text-cream" role="region" aria-label="{{ __('theme::dielna.notice') }}">
        <p class="container-shop flex items-center justify-center gap-2 py-2 text-center">
            <svg class="size-3.5 shrink-0 text-[color-mix(in_srgb,var(--brand-primary)_45%,white)]" viewBox="0 0 24 24" fill="currentColor" aria-hidden="true"><path d="M12 21s-7.5-4.6-9.5-9.2C1.2 8.6 3.3 5 6.8 5c2 0 3.4 1.1 5.2 3 1.8-1.9 3.2-3 5.2-3 3.5 0 5.6 3.6 4.3 6.8C19.5 16.4 12 21 12 21z" /></svg>
            <span>{{ $noticeText }}</span>
        </p>
    </div>
@endif

<header class="sticky top-0 z-40 border-b border-line bg-[color-mix(in_srgb,var(--dielna-bg)_94%,transparent)] backdrop-blur" x-data="{ menu: false }">
    <div class="container-shop flex h-16 items-center gap-3 sm:h-[72px] sm:gap-6">
        <button type="button" class="-ml-1.5 grid size-10 shrink-0 place-items-center rounded-full hover:bg-sand lg:hidden" @click="menu = ! menu"
                :aria-expanded="menu.toString()" aria-controls="mobile-menu" aria-label="{{ __('theme::dielna.menu') }}">
            <svg class="size-6" fill="none" viewBox="0 0 24 24" stroke-width="1.6" stroke="currentColor" aria-hidden="true"><path stroke-linecap="round" d="M4 7h16M4 12h16M4 17h10" /></svg>
        </button>

        <a href="{{ route('storefront.home') }}" class="flex min-w-0 items-center gap-2.5">
            @if ($logo = $branding->logoUrl())
                <img src="{{ $logo }}" alt="{{ $shop->shop_name }}" class="h-9 w-auto shrink-0 sm:h-10">
            @else
                <span class="grid size-9 shrink-0 place-items-center rounded-full bg-primary font-serif text-lg italic text-primary-foreground" aria-hidden="true">{{ mb_strtolower(mb_substr($shop->shop_name, 0, 1)) }}</span>
            @endif
            @if (! $logo || $themeOptions->get('show_shop_name', true))
                <span class="min-w-0 leading-tight">
                    <span class="shop-name block font-serif text-xl font-medium tracking-tight sm:text-[22px]" data-fit-text>{{ $shop->shop_name }}</span>
                    @if ($logoText !== '')
                        <span class="-mt-0.5 hidden font-hand text-[17px] text-accent sm:block">{{ $logoText }}</span>
                    @endif
                </span>
            @endif
        </a>

        <nav class="dielna-nav ml-4 hidden items-center gap-7 text-[15px] lg:flex [&_a:hover]:text-accent [&_[aria-current]]:text-accent [&_.absolute]:bg-white" aria-label="{{ __('theme::shop.categories') }}">
            @include('theme::partials.nav-items', ['items' => $navItems])
        </nav>

        <div class="ml-auto flex shrink-0 items-center gap-1 sm:gap-2">
            <div class="hidden items-center gap-1 sm:flex">
                @include('theme::partials.language-switcher')
                @include('theme::partials.currency-switcher')
            </div>

            @if (Route::has('catalog.search'))
                <form action="{{ route('catalog.search') }}" method="GET" role="search" class="relative hidden md:block" x-data="{ open: {{ request()->filled('q') ? 'true' : 'false' }} }">
                    <label for="header-search" class="sr-only">{{ __('theme::shop.search') }}</label>
                    <input id="header-search" type="search" name="q" value="{{ request('q') }}" placeholder="{{ __('theme::shop.search_placeholder') }}" x-show="open" x-cloak
                           class="w-56 rounded-full border border-line bg-white py-2 pl-4 pr-10 text-sm outline-none focus:border-[var(--dielna-ink)]">
                    <button type="submit" class="grid size-10 place-items-center rounded-full hover:bg-sand" :class="open && 'absolute right-0 top-0'" @click="if (! open) { $event.preventDefault(); open = true; $nextTick(() => document.getElementById('header-search').focus()) }" aria-label="{{ __('theme::shop.search') }}">
                        <svg class="size-5" fill="none" viewBox="0 0 24 24" stroke-width="1.6" stroke="currentColor" aria-hidden="true"><circle cx="11" cy="11" r="7" /><path stroke-linecap="round" d="m20 20-3.5-3.5" /></svg>
                    </button>
                </form>
            @endif

            @if (Route::has('customer.account'))
                <a href="{{ route('customer.account') }}" class="hidden size-10 place-items-center rounded-full hover:bg-sand sm:grid" aria-label="{{ empty($customer) ? __('theme::account.login') : __('theme::account.title') }}">
                    <svg class="size-5" fill="none" viewBox="0 0 24 24" stroke-width="1.6" stroke="currentColor" aria-hidden="true"><circle cx="12" cy="8" r="4" /><path stroke-linecap="round" d="M4 21c1.5-4 4.5-6 8-6s6.5 2 8 6" /></svg>
                </a>
            @endif

            @if (! empty($compareEnabled) && ($compareCount ?? 0) > 0)
                <a href="{{ route('compare.show') }}" class="hidden rounded-full px-3 py-2 text-sm hover:bg-sand sm:block">{{ __('catalog::shop.compare_link', ['count' => $compareCount]) }}</a>
            @endif

            @if (Route::has('cart.show'))
                <a href="{{ route('cart.show') }}" class="relative grid size-10 place-items-center rounded-full bg-ink text-cream">
                    <span class="sr-only">{{ trans_choice('theme::shop.cart_with_count', $cartCount ?? 0, ['count' => $cartCount ?? 0]) }}</span>
                    <svg class="size-5" fill="none" viewBox="0 0 24 24" stroke-width="1.6" stroke="currentColor" aria-hidden="true"><path stroke-linejoin="round" d="M5 8h14l-1.2 11.2a2 2 0 0 1-2 1.8H8.2a2 2 0 0 1-2-1.8z" /><path stroke-linecap="round" d="M9 8V6a3 3 0 0 1 6 0v2" /></svg>
                    <span class="absolute -right-1 -top-1 grid h-5 min-w-5 place-items-center rounded-full px-1 tabular-nums bg-primary text-[11px] font-semibold text-primary-foreground" aria-hidden="true" data-cart-count>{{ $cartCount ?? 0 }}</span>
                </a>
            @endif
        </div>
    </div>

    <div id="mobile-menu" x-show="menu" x-cloak class="border-t border-line bg-cream lg:hidden">
        @if (Route::has('catalog.search'))
            <form action="{{ route('catalog.search') }}" method="GET" role="search" class="container-shop pt-3">
                <label for="mobile-search" class="sr-only">{{ __('theme::shop.search') }}</label>
                <input id="mobile-search" type="search" name="q" value="{{ request('q') }}" placeholder="{{ __('theme::shop.search_placeholder') }}" class="input rounded-full">
            </form>
        @endif
        <ul class="container-shop divide-y divide-[var(--dielna-line)] py-2 font-serif text-lg">
            @foreach ($navItems as $navItem)
                <li>
                    <a href="{{ $navItem['url'] }}" class="block py-3">{{ $navItem['label'] }}</a>
                    @foreach ($navItem['children'] as $child)
                        <a href="{{ $child['url'] }}" class="block py-2 pl-4 font-sans text-sm text-muted">{{ $child['label'] }}</a>
                    @endforeach
                </li>
            @endforeach
            @if (Route::has('customer.account'))
                <li><a href="{{ route('customer.account') }}" class="block py-3">{{ empty($customer) ? __('theme::account.login') : __('theme::account.title') }}</a></li>
            @endif
        </ul>
        <div class="container-shop pb-3 sm:hidden">@include('theme::partials.preference-switchers-inline')</div>
    </div>
</header>
