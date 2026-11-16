{{--
    Hlavička Esencie: farebný pruh s výhodami, logo, veľké vyhľadávanie, ikony a košík so sumou,
    pod tým ponuka kategórií so zvýraznenou položkou (napr. akcie) a odkazom vpravo.
--}}
@include('theme::partials.palette')
@php
    $navItems = $menus->items('header');

    if ($navItems === [] && ! empty($navigationCategories)) {
        $navItems = $navigationCategories->map(fn ($category) => ['label' => $category->name, 'url' => $category->url(), 'new_window' => false, 'active' => false, 'children' => []])->all();
    }

    $noticeItems = collect(preg_split('/\R/', (string) $themeOptions->get('notice_items')))->map(fn ($line) => trim($line))->filter()->take(4)->values();
    $highlight = trim((string) $themeOptions->get('nav_highlight'));
    $extra = trim((string) $themeOptions->get('nav_extra'));
    $cartSummary = ($themeOptions->get('cart_total', true) && ($cartCount ?? 0) > 0 && Route::has('cart.show'))
        ? rescue(fn () => app(\Modules\Cart\CartManager::class)->summary(), null, false)
        : null;
@endphp

@if ($noticeItems->isNotEmpty())
    <div class="bg-ac text-[13px] font-medium text-onac" role="region" aria-label="{{ __('theme::esencia.notice') }}">
        <ul class="container-shop flex items-center justify-center gap-6 py-2">
            @foreach ($noticeItems as $noticeItem)
                <li @class(['flex items-center gap-1.5', 'hidden md:flex' => ! $loop->first])>@include('theme::partials.icon', ['name' => 'check', 'class' => 'size-3.5', 'stroke' => 2.5]){{ $noticeItem }}</li>
            @endforeach
        </ul>
    </div>
@endif

<header class="sticky top-0 z-40 border-b border-line bg-white/95 backdrop-blur" x-data="{ menu: false }">
    <div class="container-shop flex h-16 items-center gap-3 sm:h-[72px] sm:gap-6">
        <button type="button" class="-ml-1.5 grid size-10 shrink-0 place-items-center rounded-full hover:bg-soft lg:hidden" @click="menu = ! menu"
                :aria-expanded="menu.toString()" aria-controls="mobile-menu" aria-label="{{ __('theme::esencia.menu') }}">
            <svg class="size-6" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" aria-hidden="true"><path stroke-linecap="round" d="M4 7h16M4 12h16M4 17h16" /></svg>
        </button>

        <a href="{{ route('storefront.home') }}" class="flex min-w-0 items-center gap-2">
            @if ($logo = $branding->logoUrl())
                <img src="{{ $logo }}" alt="{{ $shop->shop_name }}" class="h-9 w-auto shrink-0 sm:h-10">
            @else
                <span class="grid size-9 shrink-0 place-items-center rounded-xl bg-ac font-head text-lg text-onac" aria-hidden="true">{{ mb_strtolower(mb_substr($shop->shop_name, 0, 1)) }}</span>
            @endif
            @if (! $logo || $themeOptions->get('show_shop_name', true))
                <span class="shop-name min-w-0 font-head text-[22px] sm:text-[26px]" data-fit-text>{{ $shop->shop_name }}</span>
            @endif
        </a>

        @if (Route::has('catalog.search'))
            <form action="{{ route('catalog.search') }}" method="GET" role="search" class="mx-auto hidden max-w-xl flex-1 md:block">
                <label for="header-search" class="sr-only">{{ __('theme::shop.search') }}</label>
                <span class="relative block">
                    <svg class="pointer-events-none absolute left-4 top-1/2 size-5 -translate-y-1/2 text-muted" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" aria-hidden="true"><circle cx="11" cy="11" r="7" /><path stroke-linecap="round" d="m20 20-3.5-3.5" /></svg>
                    <input id="header-search" type="search" name="q" value="{{ request('q') }}" placeholder="{{ __('theme::shop.search_placeholder') }}"
                           class="w-full rounded-full border-2 border-transparent bg-soft py-2.5 pl-12 pr-4 text-[15px] outline-none transition focus:border-[var(--es-ac)] focus:bg-white">
                </span>
            </form>
        @endif

        <div class="ml-auto flex shrink-0 items-center gap-1 md:ml-0">
            <div class="hidden items-center gap-1 xl:flex">
                @include('theme::partials.language-switcher')
                @include('theme::partials.currency-switcher')
            </div>

            @if (Route::has('customer.account'))
                <a href="{{ route('customer.account') }}" class="hidden size-11 place-items-center rounded-full hover:bg-soft sm:grid" aria-label="{{ empty($customer) ? __('theme::account.login') : __('theme::account.title') }}">
                    <svg class="size-5" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" aria-hidden="true"><circle cx="12" cy="8" r="4" /><path stroke-linecap="round" d="M4 21c1.5-4 4.5-6 8-6s6.5 2 8 6" /></svg>
                </a>
            @endif

            @if (! empty($compareEnabled) && ($compareCount ?? 0) > 0)
                <a href="{{ route('compare.show') }}" class="hidden rounded-full px-3 py-2 text-sm hover:bg-soft sm:block">{{ __('catalog::shop.compare_link', ['count' => $compareCount]) }}</a>
            @endif

            @if (Route::has('cart.show'))
                <a href="{{ route('cart.show') }}" class="flex items-center gap-2 rounded-full bg-ink px-3.5 py-2.5 text-sm font-semibold text-white sm:px-4">
                    <span class="sr-only">{{ trans_choice('theme::shop.cart_with_count', $cartCount ?? 0, ['count' => $cartCount ?? 0]) }}</span>
                    <svg class="size-5" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" aria-hidden="true"><path stroke-linejoin="round" d="M5 8h14l-1.2 11.2a2 2 0 0 1-2 1.8H8.2a2 2 0 0 1-2-1.8z" /><path stroke-linecap="round" d="M9 8V6a3 3 0 0 1 6 0v2" /></svg>
                    @if ($themeOptions->get('cart_total', true))
                        <span @class(['hidden', 'sm:inline' => $cartSummary !== null]) aria-hidden="true" data-cart-total="sm:inline">{{ $cartSummary ? format_price($cartSummary->subtotal(), $cartSummary->currency) : '' }}</span>
                    @endif
                    <span @class(['es-sticker grid h-5 min-w-5 place-items-center rounded-full px-1 tabular-nums text-[11px]', 'hidden' => ($cartCount ?? 0) === 0]) aria-hidden="true" data-cart-count="grid">{{ $cartCount ?? 0 }}</span>
                </a>
            @endif
        </div>
    </div>

    <nav class="hidden lg:block" aria-label="{{ __('theme::shop.categories') }}">
        <div class="container-shop flex h-12 items-center gap-7 text-[15px] font-medium [&_.absolute]:bg-white [&_[aria-current]]:text-ac [&_a:hover]:text-ac">
            @include('theme::partials.nav-items', ['items' => $navItems])
            @if ($highlight !== '')
                <a href="{{ trim((string) $themeOptions->get('nav_highlight_link')) ?: '#' }}" class="rounded-full bg-ac px-3 py-1 text-onac hover:!text-onac">{{ $highlight }}</a>
            @endif
            @if ($extra !== '')
                <a href="{{ trim((string) $themeOptions->get('nav_extra_link')) ?: '#' }}" class="ml-auto flex items-center gap-1.5 text-muted">@include('theme::partials.icon', ['name' => 'sparkle', 'class' => 'size-4']){{ $extra }}</a>
            @endif
        </div>
    </nav>

    <div id="mobile-menu" x-show="menu" x-cloak class="border-t border-line bg-white lg:hidden">
        @if (Route::has('catalog.search'))
            <form action="{{ route('catalog.search') }}" method="GET" role="search" class="container-shop pt-3">
                <label for="mobile-search" class="sr-only">{{ __('theme::shop.search') }}</label>
                <input id="mobile-search" type="search" name="q" value="{{ request('q') }}" placeholder="{{ __('theme::shop.search_placeholder') }}" class="input rounded-full bg-soft">
            </form>
        @endif
        <ul class="container-shop divide-y divide-[var(--es-line)] py-2 font-head text-xl">
            @foreach ($navItems as $navItem)
                <li>
                    <a href="{{ $navItem['url'] }}" class="block py-3">{{ $navItem['label'] }}</a>
                    @foreach ($navItem['children'] as $child)
                        <a href="{{ $child['url'] }}" class="block py-2 pl-4 font-sans text-sm font-normal text-muted">{{ $child['label'] }}</a>
                    @endforeach
                </li>
            @endforeach
            @if ($highlight !== '')
                <li><a href="{{ trim((string) $themeOptions->get('nav_highlight_link')) ?: '#' }}" class="block py-3 text-ac">{{ $highlight }}</a></li>
            @endif
            @if (Route::has('customer.account'))
                <li><a href="{{ route('customer.account') }}" class="block py-3">{{ empty($customer) ? __('theme::account.login') : __('theme::account.title') }}</a></li>
            @endif
        </ul>
        <div class="container-shop pb-3 xl:hidden">@include('theme::partials.preference-switchers-inline')</div>
    </div>
</header>
