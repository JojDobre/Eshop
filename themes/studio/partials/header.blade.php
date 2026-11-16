{{--
    Hlavička Štúdia: pruh vo farbe značky (otváracie hodiny, oznam, telefón), logo s podtitulom,
    ponuka, košík a výrazné tlačidlo na rezerváciu. Farby šablóny z Prispôsobenia sa vkladajú ako premenné CSS.
--}}
@php
    $navItems = $menus->items('header');

    if ($navItems === [] && ! empty($navigationCategories)) {
        $navItems = $navigationCategories->map(fn ($category) => ['label' => $category->name, 'url' => $category->url(), 'new_window' => false, 'active' => false, 'children' => []])->all();
    }

    $noticeText = trim((string) $themeOptions->get('notice_text'));
    $topbarHours = trim((string) $themeOptions->get('topbar_hours'));
    $topbarPhone = $themeOptions->get('topbar_phone', true) ? trim((string) $shop->contact_phone) : '';
    $logoText = trim((string) $themeOptions->get('logo_text'));
    $bookLink = trim((string) $themeOptions->get('book_link')) ?: (Route::has('reservations.index') ? route('reservations.index') : null);
    $bookLabel = trim((string) $themeOptions->get('book_button')) ?: __('theme::studio.book_button');
    $cssColor = fn (string $key, string $fallback) => preg_match('/^#[0-9a-fA-F]{3,8}$/', (string) $themeOptions->get($key)) ? $themeOptions->get($key) : $fallback;
@endphp
<style>:root { --studio-bg: {{ $cssColor('color_background', '#f6f2ec') }}; --studio-stone: {{ $cssColor('color_stone', '#ebe4da') }}; --studio-blush: {{ $cssColor('color_blush', '#efe0d6') }}; --studio-ink: {{ $cssColor('color_ink', '#1f1c1a') }}; --studio-gold: {{ $cssColor('color_gold', '#b08d57') }}; }</style>

@if ($noticeText !== '' || $topbarHours !== '' || $topbarPhone !== '')
    <div class="bg-pine text-[13px] text-on-pine" role="region" aria-label="{{ __('theme::studio.notice') }}">
        <div class="container-shop flex items-center justify-between gap-4 py-2">
            <span class="hidden sm:inline">{{ $topbarHours }}</span>
            <span class="mx-auto text-center sm:mx-0">{{ $noticeText }}</span>
            @if ($topbarPhone !== '')
                <a href="tel:{{ preg_replace('/\s+/', '', $topbarPhone) }}" class="hidden hover:text-white sm:inline">{{ $topbarPhone }}</a>
            @else
                <span class="hidden sm:inline"></span>
            @endif
        </div>
    </div>
@endif

<header class="sticky top-0 z-40 border-b border-line bg-[color-mix(in_srgb,var(--studio-bg)_94%,transparent)] backdrop-blur" x-data="{ menu: false }">
    <div class="container-shop flex h-16 items-center gap-3 sm:h-[76px] sm:gap-8">
        <button type="button" class="-ml-1.5 grid size-10 shrink-0 place-items-center rounded-full hover:bg-stone lg:hidden" @click="menu = ! menu"
                :aria-expanded="menu.toString()" aria-controls="mobile-menu" aria-label="{{ __('theme::studio.menu') }}">
            <svg class="size-6" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" aria-hidden="true"><path stroke-linecap="round" d="M4 8h16M4 16h16" /></svg>
        </button>

        <a href="{{ route('storefront.home') }}" class="flex min-w-0 items-center gap-3 leading-none">
            @if ($logo = $branding->logoUrl())
                <img src="{{ $logo }}" alt="{{ $shop->shop_name }}" class="h-9 w-auto shrink-0 sm:h-10">
            @endif
            @if (! $logo || $themeOptions->get('show_shop_name', true))
                <span class="min-w-0">
                    <span class="shop-name block font-serif text-[26px] tracking-tight sm:text-[30px]" data-fit-text>{{ $shop->shop_name }}</span>
                    @if ($logoText !== '')
                        <span class="mt-0.5 hidden text-[10px] uppercase tracking-[0.3em] text-muted sm:block">{{ $logoText }}</span>
                    @endif
                </span>
            @endif
        </a>

        <nav class="studio-nav hidden items-center gap-8 text-[15px] font-medium lg:flex [&_a:hover]:text-pine [&_[aria-current]]:text-pine [&_.absolute]:bg-white" aria-label="{{ __('theme::shop.categories') }}">
            @include('theme::partials.nav-items', ['items' => $navItems])
        </nav>

        <div class="ml-auto flex shrink-0 items-center gap-1 sm:gap-2">
            <div class="hidden items-center gap-1 xl:flex">
                @include('theme::partials.language-switcher')
                @include('theme::partials.currency-switcher')
            </div>

            @if (Route::has('customer.account'))
                <a href="{{ route('customer.account') }}" class="hidden size-10 place-items-center rounded-full hover:bg-stone md:grid" aria-label="{{ empty($customer) ? __('theme::account.login') : __('theme::account.title') }}">
                    <svg class="size-5" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" aria-hidden="true"><circle cx="12" cy="8" r="4" /><path stroke-linecap="round" d="M4 21c1.5-4 4.5-6 8-6s6.5 2 8 6" /></svg>
                </a>
            @endif

            @if (Route::has('cart.show'))
                <a href="{{ route('cart.show') }}" class="relative grid size-10 place-items-center rounded-full hover:bg-stone">
                    <span class="sr-only">{{ trans_choice('theme::shop.cart_with_count', $cartCount ?? 0, ['count' => $cartCount ?? 0]) }}</span>
                    <svg class="size-5" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" aria-hidden="true"><path stroke-linejoin="round" d="M5 8h14l-1.2 11.2a2 2 0 0 1-2 1.8H8.2a2 2 0 0 1-2-1.8z" /><path stroke-linecap="round" d="M9 8V6a3 3 0 0 1 6 0v2" /></svg>
                    <span @class(['absolute -right-0.5 -top-0.5 grid h-5 min-w-5 place-items-center rounded-full px-1 tabular-nums bg-pine text-[11px] font-semibold text-white', 'hidden' => ($cartCount ?? 0) === 0]) aria-hidden="true" data-cart-count>{{ $cartCount ?? 0 }}</span>
                </a>
            @endif

            @if ($bookLink)
                <a href="{{ $bookLink }}" class="btn btn-pine px-4 py-2.5 text-sm sm:px-5">{{ $bookLabel }}</a>
            @endif
        </div>
    </div>

    <div id="mobile-menu" x-show="menu" x-cloak class="border-t border-line bg-ivory lg:hidden">
        @if (Route::has('catalog.search'))
            <form action="{{ route('catalog.search') }}" method="GET" role="search" class="container-shop pt-3">
                <label for="mobile-search" class="sr-only">{{ __('theme::shop.search') }}</label>
                <input id="mobile-search" type="search" name="q" value="{{ request('q') }}" placeholder="{{ __('theme::shop.search_placeholder') }}" class="input rounded-full">
            </form>
        @endif
        <ul class="container-shop divide-y divide-[var(--studio-line)] py-2 font-serif text-2xl">
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
        <div class="container-shop pb-3 xl:hidden">@include('theme::partials.preference-switchers-inline')</div>
    </div>
</header>
