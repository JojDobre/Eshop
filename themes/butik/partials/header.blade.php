{{--
    Hlavička Butiku: tmavý posúvajúci sa pruh s výhodami, logo v strede, vľavo ponuka (so zvýraznenou položkou),
    vpravo hľadanie, účet a košík. Na mobile ponuka v rozbaľovacom paneli.
--}}
@include('theme::partials.palette')
@php
    $navItems = $menus->items('header');

    if ($navItems === [] && ! empty($navigationCategories)) {
        $navItems = $navigationCategories->map(fn ($category) => ['label' => $category->name, 'url' => $category->url(), 'new_window' => false, 'active' => false, 'children' => []])->all();
    }

    $noticeItems = collect(preg_split('/\R/', (string) $themeOptions->get('notice_items')))->map(fn ($line) => trim($line))->filter()->values();
    $highlight = trim((string) $themeOptions->get('nav_highlight'));
    $highlightLink = trim((string) $themeOptions->get('nav_highlight_link')) ?: '#';
@endphp

@if ($noticeItems->isNotEmpty())
    <div class="overflow-hidden bg-ink text-white" role="region" aria-label="{{ __('theme::butik.notice') }}">
        <p class="sr-only">{{ $noticeItems->implode(' · ') }}</p>
        <div class="bt-marquee py-2" aria-hidden="true">
            @foreach (range(1, 2) as $copy)
                <div class="flex shrink-0 items-center">
                    @foreach (range(1, max(1, (int) ceil(6 / $noticeItems->count()))) as $repeat)
                        @foreach ($noticeItems as $noticeItem)
                            <span class="cap px-6">{{ $noticeItem }}</span>
                            <svg class="size-2.5 shrink-0" viewBox="0 0 10 10" aria-hidden="true"><path d="M5 0l1.2 3.8L10 5 6.2 6.2 5 10 3.8 6.2 0 5l3.8-1.2z" fill="currentColor" /></svg>
                        @endforeach
                    @endforeach
                </div>
            @endforeach
        </div>
    </div>
@endif

<header class="sticky top-0 z-40 border-b border-line bg-white" x-data="{ menu: false, search: false }">
    <div class="container-shop grid h-16 grid-cols-[1fr_auto_1fr] items-center gap-4 sm:h-[68px]">
        <div class="flex items-center">
            <button type="button" class="-ml-2 grid size-10 place-items-center lg:hidden" @click="menu = ! menu" :aria-expanded="menu.toString()" aria-controls="mobile-menu" aria-label="{{ __('theme::butik.menu') }}">
                <svg class="size-6" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" aria-hidden="true"><path stroke-linecap="square" d="M3 7h18M3 12h18M3 17h18" /></svg>
            </button>
            <nav class="bt-nav hidden items-center gap-7 text-[13px] font-semibold uppercase tracking-[0.12em] lg:flex [&_.absolute]:bg-white [&_.absolute]:normal-case [&_.absolute]:tracking-normal [&_[aria-current]]:underline [&_a:hover]:text-ac" aria-label="{{ __('theme::shop.categories') }}">
                @include('theme::partials.nav-items', ['items' => $navItems])
                @if ($highlight !== '')
                    <a href="{{ $highlightLink }}" class="text-ac">{{ $highlight }}</a>
                @endif
            </nav>
        </div>

        <a href="{{ route('storefront.home') }}" class="flex items-center gap-3">
            @if ($logo = $branding->logoUrl())
                <img src="{{ $logo }}" alt="{{ $shop->shop_name }}" class="h-9 w-auto sm:h-10">
            @endif
            @if (! $logo || $themeOptions->get('show_shop_name', false))
                <span class="shop-name font-head text-[30px] sm:text-[36px]" style="letter-spacing: 0.04em" data-fit-text>{{ $shop->shop_name }}</span>
            @endif
        </a>

        <div class="flex items-center justify-end gap-4 text-[13px] font-semibold uppercase tracking-[0.12em] sm:gap-6">
            <div class="hidden items-center gap-1 normal-case tracking-normal xl:flex">
                @include('theme::partials.language-switcher')
                @include('theme::partials.currency-switcher')
            </div>
            @if (Route::has('catalog.search'))
                <button type="button" class="hidden hover:text-ac md:inline" @click="search = ! search; $nextTick(() => search && document.getElementById('header-search').focus())" :aria-expanded="search.toString()" aria-controls="header-search-panel">{{ __('theme::butik.search') }}</button>
            @endif
            @if (Route::has('customer.account'))
                <a href="{{ route('customer.account') }}" class="hidden hover:text-ac md:inline">{{ empty($customer) ? __('theme::butik.account') : __('theme::account.title') }}</a>
            @endif
            @if (Route::has('cart.show'))
                <a href="{{ route('cart.show') }}" class="flex items-center gap-2 hover:text-ac">
                    <span class="sr-only">{{ trans_choice('theme::shop.cart_with_count', $cartCount ?? 0, ['count' => $cartCount ?? 0]) }}</span>
                    <span class="hidden sm:inline" aria-hidden="true">{{ __('theme::butik.cart') }}</span>
                    <svg class="size-5 sm:hidden" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" aria-hidden="true"><path d="M5 8h14l-1.2 12H6.2z" /><path d="M9 8V6a3 3 0 0 1 6 0v2" /></svg>
                    <span class="grid h-6 min-w-6 place-items-center rounded-full px-1.5 tabular-nums bg-ac text-[11px] text-onac" aria-hidden="true" data-cart-count>{{ $cartCount ?? 0 }}</span>
                </a>
            @endif
        </div>
    </div>

    @if (Route::has('catalog.search'))
        <div id="header-search-panel" x-show="search" x-cloak class="border-t border-line bg-white" @keydown.escape.window="search = false">
            <form action="{{ route('catalog.search') }}" method="GET" role="search" class="container-shop flex items-center gap-4 py-4">
                <label for="header-search" class="sr-only">{{ __('theme::shop.search') }}</label>
                <input id="header-search" type="search" name="q" value="{{ request('q') }}" placeholder="{{ __('theme::shop.search_placeholder') }}" class="min-w-0 flex-1 border-0 border-b-2 border-ink bg-transparent py-2 text-xl outline-none focus:ring-0">
                <button type="submit" class="btn btn-ink">{{ __('theme::butik.search') }}</button>
            </form>
        </div>
    @endif

    <div id="mobile-menu" x-show="menu" x-cloak class="border-t border-line bg-white lg:hidden">
        @if (Route::has('catalog.search'))
            <form action="{{ route('catalog.search') }}" method="GET" role="search" class="container-shop pt-4">
                <label for="mobile-search" class="sr-only">{{ __('theme::shop.search') }}</label>
                <input id="mobile-search" type="search" name="q" value="{{ request('q') }}" placeholder="{{ __('theme::shop.search_placeholder') }}" class="input">
            </form>
        @endif
        <ul class="container-shop divide-y divide-[var(--bt-line)] py-2 font-head text-3xl">
            @foreach ($navItems as $navItem)
                <li>
                    <a href="{{ $navItem['url'] }}" class="block py-3">{{ $navItem['label'] }}</a>
                    @foreach ($navItem['children'] as $child)
                        <a href="{{ $child['url'] }}" class="block py-2 pl-4 font-sans text-sm normal-case">{{ $child['label'] }}</a>
                    @endforeach
                </li>
            @endforeach
            @if ($highlight !== '')
                <li><a href="{{ $highlightLink }}" class="block py-3 text-ac">{{ $highlight }}</a></li>
            @endif
            @if (Route::has('customer.account'))
                <li><a href="{{ route('customer.account') }}" class="block py-3">{{ empty($customer) ? __('theme::butik.account') : __('theme::account.title') }}</a></li>
            @endif
        </ul>
        <div class="container-shop pb-3 xl:hidden">@include('theme::partials.preference-switchers-inline')</div>
    </div>
</header>
