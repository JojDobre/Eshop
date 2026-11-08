{{--
    Hlavička Fresh: horná lišta s kontaktom, logo s veľkým vyhľadávaním a lišta kategórií vo farbe šablóny.
--}}
@php
    $navItems = $menus->items('header');

    if ($navItems === [] && ! empty($navigationCategories)) {
        $navItems = $navigationCategories->map(fn ($category) => ['label' => $category->name, 'url' => $category->url(), 'new_window' => false, 'active' => false, 'children' => []])->all();
    }
@endphp
<style>:root { --fresh-accent: {{ $themeOptions->get('accent_color', '#166534') }}; }</style>
<header class="sticky top-0 z-40 bg-white shadow-sm" x-data="{ menu: false }">
    @if ($themeOptions->get('show_contact_bar', true) && ($shop->contact_phone || $shop->contact_email))
        <div class="hidden border-b border-zinc-100 text-xs text-zinc-600 sm:block">
            <div class="container-shop flex h-8 items-center justify-end gap-6">
                @if ($shop->contact_phone)
                    <a href="tel:{{ preg_replace('/\s+/', '', $shop->contact_phone) }}" class="hover:text-zinc-900">{{ $shop->contact_phone }}</a>
                @endif
                @if ($shop->contact_email)
                    <a href="mailto:{{ $shop->contact_email }}" class="hover:text-zinc-900">{{ $shop->contact_email }}</a>
                @endif
            </div>
        </div>
    @endif

    <div class="container-shop flex h-20 items-center gap-4 sm:gap-8">
        <button type="button" class="-ml-2 rounded-md p-2 text-zinc-600 hover:bg-zinc-100 md:hidden" @click="menu = ! menu"
                :aria-expanded="menu.toString()" aria-controls="mobile-menu" aria-label="{{ __('theme::shop.menu') }}">
            <svg class="size-6" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="M3.75 6.75h16.5M3.75 12h16.5m-16.5 5.25h16.5" /></svg>
        </button>

        <a href="{{ route('storefront.home') }}" class="flex shrink-0 items-center gap-3 text-xl font-bold tracking-tight text-accent">
            @if ($logo = $branding->logoUrl())
                <img src="{{ $logo }}" alt="{{ $shop->shop_name }}" class="h-10 w-auto">
            @else
                {{ $shop->shop_name }}
            @endif
        </a>

        @if (Route::has('catalog.search'))
            <form action="{{ route('catalog.search') }}" method="GET" role="search" class="hidden flex-1 md:block">
                <label for="header-search" class="sr-only">{{ __('theme::shop.search') }}</label>
                <div class="relative">
                    <input id="header-search" type="search" name="q" value="{{ request('q') }}" placeholder="{{ __('theme::shop.search_placeholder') }}"
                           class="input rounded-full py-2.5 pl-11">
                    <svg class="pointer-events-none absolute left-4 top-1/2 size-5 -translate-y-1/2 text-zinc-400" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="m21 21-5.197-5.197m0 0A7.5 7.5 0 1 0 5.196 5.196a7.5 7.5 0 0 0 10.607 10.607Z" /></svg>
                </div>
            </form>
        @else
            <div class="flex-1"></div>
        @endif

        <div class="ml-auto flex items-center gap-1">
            @include('theme::partials.language-switcher')
            @include('theme::partials.currency-switcher')

            @if (Route::has('customer.account'))
                <a href="{{ route('customer.account') }}" class="rounded-full p-2 text-zinc-700 hover:bg-zinc-100" aria-label="{{ empty($customer) ? __('theme::account.login') : __('theme::account.title') }}">
                    <svg class="size-6" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="M15.75 6a3.75 3.75 0 1 1-7.5 0 3.75 3.75 0 0 1 7.5 0ZM4.501 20.118a7.5 7.5 0 0 1 14.998 0A17.933 17.933 0 0 1 12 21.75c-2.676 0-5.216-.584-7.499-1.632Z" /></svg>
                </a>
            @endif

            @if (Route::has('cart.show'))
                <a href="{{ route('cart.show') }}" class="relative flex items-center gap-2 rounded-full bg-accent px-4 py-2 text-sm font-semibold text-white hover:opacity-90" aria-label="{{ trans_choice('theme::shop.cart_with_count', $cartCount ?? 0, ['count' => $cartCount ?? 0]) }}">
                    <svg class="size-5" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="M2.25 3h1.386c.51 0 .955.343 1.087.835l.383 1.437M7.5 14.25a3 3 0 0 0-3 3h15.75m-12.75-3h11.218c1.121-2.3 2.1-4.684 2.924-7.138a60.114 60.114 0 0 0-16.536-1.84M7.5 14.25 5.106 5.272M6 20.25a.75.75 0 1 1-1.5 0 .75.75 0 0 1 1.5 0Zm12.75 0a.75.75 0 1 1-1.5 0 .75.75 0 0 1 1.5 0Z" /></svg>
                    <span>{{ $cartCount ?? 0 }}</span>
                </a>
            @endif
        </div>
    </div>

    @if (Route::has('catalog.search'))
        <form action="{{ route('catalog.search') }}" method="GET" role="search" class="container-shop pb-3 md:hidden">
            <label for="mobile-search" class="sr-only">{{ __('theme::shop.search') }}</label>
            <input id="mobile-search" type="search" name="q" value="{{ request('q') }}" placeholder="{{ __('theme::shop.search_placeholder') }}" class="input rounded-full">
        </form>
    @endif

    @if ($navItems !== [])
        <nav class="hidden bg-accent text-white md:block" aria-label="{{ __('theme::shop.categories') }}">
            <div class="container-shop flex h-12 items-center gap-8 text-sm font-medium [&_a:hover]:opacity-80 [&_button:hover]:opacity-80 [&_.absolute_a]:text-zinc-700">
                @include('theme::partials.nav-items', ['items' => $navItems])
            </div>
        </nav>

        <nav id="mobile-menu" x-show="menu" x-cloak class="border-t border-zinc-100 md:hidden" aria-label="{{ __('theme::shop.categories') }}">
            <ul class="container-shop divide-y divide-zinc-100 py-2">
                @foreach ($navItems as $navItem)
                    <li>
                        <a href="{{ $navItem['url'] }}" class="block py-3 text-zinc-700">{{ $navItem['label'] }}</a>
                        @foreach ($navItem['children'] as $child)
                            <a href="{{ $child['url'] }}" class="block py-2 pl-4 text-sm text-zinc-600">{{ $child['label'] }}</a>
                        @endforeach
                    </li>
                @endforeach
            </ul>
        </nav>
    @endif
</header>
