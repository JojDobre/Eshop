@php
    // Hlavná ponuka: položky z administrácie (Obsah → Ponuky), inak hlavné kategórie.
    $navItems = $menus->items('header');

    if ($navItems === [] && ! empty($navigationCategories)) {
        $navItems = $navigationCategories->map(fn ($category) => ['label' => $category->name, 'url' => $category->url(), 'new_window' => false, 'active' => false, 'children' => []])->all();
    }

    $centered = $themeOptions->get('header_layout') === 'center';
@endphp
<header class="sticky top-0 z-40 border-b border-zinc-100 bg-white/95 backdrop-blur" x-data="{ menu: false, search: false }">
    <div class="container-shop relative flex h-16 items-center gap-4">
        <button type="button" class="-ml-2 rounded-md p-2 text-zinc-600 hover:bg-zinc-100 md:hidden" @click="menu = ! menu"
                :aria-expanded="menu.toString()" aria-controls="mobile-menu" aria-label="{{ __('theme::shop.menu') }}">
            <svg class="size-6" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="M3.75 6.75h16.5M3.75 12h16.5m-16.5 5.25h16.5" /></svg>
        </button>

        <a href="{{ route('storefront.home') }}" @class(['flex min-w-0 items-center gap-3 font-semibold tracking-tight', 'md:absolute md:left-1/2 md:-translate-x-1/2' => $centered])>
            @if ($logo = $branding->logoUrl())
                <img src="{{ $logo }}" alt="{{ $shop->shop_name }}" class="h-8 w-auto shrink-0">
            @else
                <span class="shop-name text-lg" data-fit-text>{{ $shop->shop_name }}</span>
            @endif
        </a>

        @if ($navItems !== [] && ! $centered)
            <nav class="hidden flex-1 items-center gap-6 text-sm text-zinc-600 md:flex" aria-label="{{ __('theme::shop.categories') }}">
                @include('theme::partials.nav-items', ['items' => $navItems])
            </nav>
        @else
            <div class="flex-1"></div>
        @endif

        <div class="ml-auto flex items-center gap-1">
            @if (Route::has('catalog.search'))
                <form action="{{ route('catalog.search') }}" method="GET" role="search" class="hidden lg:block">
                    <label for="header-search" class="sr-only">{{ __('theme::shop.search') }}</label>
                    <input id="header-search" type="search" name="q" value="{{ request('q') }}" placeholder="{{ __('theme::shop.search_placeholder') }}"
                           class="input w-56 py-1.5">
                </form>
                <button type="button" class="rounded-md p-2 text-zinc-600 hover:bg-zinc-100 lg:hidden" @click="search = ! search" aria-label="{{ __('theme::shop.search') }}">
                    <svg class="size-6" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="m21 21-5.197-5.197m0 0A7.5 7.5 0 1 0 5.196 5.196a7.5 7.5 0 0 0 10.607 10.607Z" /></svg>
                </button>
            @endif

            <div class="hidden items-center sm:flex">
                @include('theme::partials.language-switcher')
                @include('theme::partials.currency-switcher')
            </div>

            @if (Route::has('customer.account'))
                <a href="{{ route('customer.account') }}" class="rounded-md p-2 text-zinc-700 hover:bg-zinc-100" aria-label="{{ empty($customer) ? __('theme::account.login') : __('theme::account.title') }}">
                    <svg class="size-6" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="M15.75 6a3.75 3.75 0 1 1-7.5 0 3.75 3.75 0 0 1 7.5 0ZM4.501 20.118a7.5 7.5 0 0 1 14.998 0A17.933 17.933 0 0 1 12 21.75c-2.676 0-5.216-.584-7.499-1.632Z" /></svg>
                </a>
            @endif

            @if (! empty($compareEnabled) && ($compareCount ?? 0) > 0)
                <a href="{{ route('compare.show') }}" class="rounded-md p-2 text-sm text-zinc-700 hover:bg-zinc-100">{{ __('catalog::shop.compare_link', ['count' => $compareCount]) }}</a>
            @endif
            @if (Route::has('cart.show'))
                <a href="{{ route('cart.show') }}" class="relative rounded-md p-2 text-zinc-700 hover:bg-zinc-100" aria-label="{{ trans_choice('theme::shop.cart_with_count', $cartCount ?? 0, ['count' => $cartCount ?? 0]) }}">
                    <svg class="size-6" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="M15.75 10.5V6a3.75 3.75 0 1 0-7.5 0v4.5m11.356-1.993 1.263 12c.07.665-.45 1.243-1.119 1.243H4.25a1.125 1.125 0 0 1-1.12-1.243l1.264-12A1.125 1.125 0 0 1 5.513 7.5h12.974c.576 0 1.059.435 1.119 1.007ZM8.625 10.5a.375.375 0 1 1-.75 0 .375.375 0 0 1 .75 0Zm7.5 0a.375.375 0 1 1-.75 0 .375.375 0 0 1 .75 0Z" /></svg>
                    <span @class(['absolute -right-0.5 -top-0.5 min-w-5 items-center justify-center rounded-full bg-primary px-1 text-[11px] font-semibold leading-5 text-primary-foreground', 'flex' => ($cartCount ?? 0) > 0, 'hidden' => ($cartCount ?? 0) === 0]) data-cart-count="flex">{{ $cartCount ?? 0 }}</span>
                </a>
            @endif
        </div>
    </div>

    @if (Route::has('catalog.search'))
        <div x-show="search" x-cloak class="border-t border-zinc-100 lg:hidden">
            <form action="{{ route('catalog.search') }}" method="GET" role="search" class="container-shop py-3">
                <label for="mobile-search" class="sr-only">{{ __('theme::shop.search') }}</label>
                <input id="mobile-search" type="search" name="q" value="{{ request('q') }}" placeholder="{{ __('theme::shop.search_placeholder') }}" class="input">
            </form>
        </div>
    @endif

    @if ($navItems !== [] && $centered)
        <nav class="hidden border-t border-zinc-100 md:block" aria-label="{{ __('theme::shop.categories') }}">
            <div class="container-shop flex h-12 items-center justify-center gap-8 text-sm text-zinc-600">
                @include('theme::partials.nav-items', ['items' => $navItems])
            </div>
        </nav>
    @endif

    @if ($navItems !== [])
        <nav id="mobile-menu" x-show="menu" x-cloak class="border-t border-zinc-100 md:hidden" aria-label="{{ __('theme::shop.categories') }}">
            @if ((isset($shopCurrencies) && $shopCurrencies->isMulticurrency()) || (! empty($alternateUrls) && count($alternateUrls) > 1))
                <div class="container-shop flex items-center gap-2 pt-3 sm:hidden">
                    @include('theme::partials.language-switcher')
                    @include('theme::partials.currency-switcher')
                </div>
            @endif
            <ul class="container-shop divide-y divide-zinc-100 py-2">
                @foreach ($navItems as $navItem)
                    <li>
                        <a href="{{ $navItem['url'] }}" class="block py-3 text-zinc-700" @if ($navItem['new_window']) target="_blank" rel="noopener" @endif>{{ $navItem['label'] }}</a>
                        @if ($navItem['children'] !== [])
                            <ul class="pb-2 pl-4">
                                @foreach ($navItem['children'] as $child)
                                    <li><a href="{{ $child['url'] }}" class="block py-2 text-sm text-zinc-600" @if ($child['new_window']) target="_blank" rel="noopener" @endif>{{ $child['label'] }}</a></li>
                                @endforeach
                            </ul>
                        @endif
                    </li>
                @endforeach
            </ul>
        </nav>
    @endif
</header>
