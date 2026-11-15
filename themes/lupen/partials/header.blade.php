{{--
    Hlavička Lupeňa: pruh s uzávierkou doručenia v ten istý deň, logo s podtitulom, ponuka, hľadanie, účet a košík.
--}}
@include('theme::partials.palette')
@php
    $navItems = $menus->items('header');

    if ($navItems === [] && ! empty($navigationCategories)) {
        $navItems = $navigationCategories->map(fn ($category) => ['label' => $category->name, 'url' => $category->url(), 'new_window' => false, 'active' => false, 'children' => []])->all();
    }

    $cutoff = trim((string) $themeOptions->get('cutoff_time'));
    $noticeText = preg_match('/^\d{1,2}:\d{2}$/', $cutoff)
        ? str_replace(':time', $cutoff, trim((string) $themeOptions->get('notice_text')) ?: __('theme::lupen.notice_text', ['time' => ':time']))
        : '';
    $logoText = trim((string) $themeOptions->get('logo_text'));
@endphp

@if ($noticeText !== '')
    <div class="bg-ac text-[13px] font-semibold text-onac" role="region" aria-label="{{ __('theme::lupen.notice') }}">
        <p class="container-shop flex items-center justify-center gap-2 py-2 text-center">@include('theme::partials.icon', ['name' => 'clock', 'class' => 'size-4 shrink-0']){{ $noticeText }}</p>
    </div>
@endif

<header class="sticky top-0 z-40 border-b border-line bg-[color-mix(in_srgb,var(--lp-bg)_94%,transparent)] backdrop-blur" x-data="{ menu: false }">
    <div class="container-shop flex h-16 items-center gap-3 sm:h-[74px] sm:gap-8">
        <button type="button" class="-ml-1.5 grid size-10 shrink-0 place-items-center rounded-full hover:bg-soft lg:hidden" @click="menu = ! menu" :aria-expanded="menu.toString()" aria-controls="mobile-menu" aria-label="{{ __('theme::lupen.menu') }}">
            <svg class="size-6" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" aria-hidden="true"><path stroke-linecap="round" d="M4 7h16M4 12h16M4 17h10" /></svg>
        </button>

        <a href="{{ route('storefront.home') }}" class="flex min-w-0 items-center gap-2.5">
            @if ($logo = $branding->logoUrl())
                <img src="{{ $logo }}" alt="{{ $shop->shop_name }}" class="h-9 w-auto shrink-0 sm:h-10">
            @else
                <span class="petal grid size-10 shrink-0 place-items-center bg-ac font-head text-xl text-onac" aria-hidden="true">{{ mb_strtolower(mb_substr($shop->shop_name, 0, 1)) }}</span>
            @endif
            @if (! $logo || $themeOptions->get('show_shop_name', true))
                <span class="min-w-0 leading-none">
                    <span class="shop-name block font-head text-[24px] sm:text-[26px]" data-fit-text>{{ $shop->shop_name }}</span>
                    @if ($logoText !== '')
                        <span class="mt-1 hidden text-[11px] font-semibold uppercase tracking-[0.2em] text-muted sm:block">{{ $logoText }}</span>
                    @endif
                </span>
            @endif
        </a>

        <nav class="hidden items-center gap-7 text-[15px] font-semibold lg:flex [&_.absolute]:bg-card [&_[aria-current]]:text-ac [&_a:hover]:text-ac" aria-label="{{ __('theme::shop.categories') }}">
            @include('theme::partials.nav-items', ['items' => $navItems])
        </nav>

        <div class="ml-auto flex shrink-0 items-center gap-1 sm:gap-2">
            <div class="hidden items-center gap-1 xl:flex">
                @include('theme::partials.language-switcher')
                @include('theme::partials.currency-switcher')
            </div>
            @if (Route::has('catalog.search'))
                <a href="{{ route('catalog.search') }}" class="hidden size-11 place-items-center rounded-full hover:bg-soft sm:grid" aria-label="{{ __('theme::shop.search') }}">
                    <svg class="size-5" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" aria-hidden="true"><circle cx="11" cy="11" r="7" /><path stroke-linecap="round" d="m20 20-3.5-3.5" /></svg>
                </a>
            @endif
            @if (Route::has('customer.account'))
                <a href="{{ route('customer.account') }}" class="hidden size-11 place-items-center rounded-full hover:bg-soft sm:grid" aria-label="{{ empty($customer) ? __('theme::account.login') : __('theme::account.title') }}">
                    <svg class="size-5" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" aria-hidden="true"><circle cx="12" cy="8" r="4" /><path stroke-linecap="round" d="M4 21c1.5-4 4.5-6 8-6s6.5 2 8 6" /></svg>
                </a>
            @endif
            @if (Route::has('cart.show'))
                <a href="{{ route('cart.show') }}" class="btn btn-ink !px-4 !py-2.5 text-sm">
                    <span class="sr-only">{{ trans_choice('theme::shop.cart_with_count', $cartCount ?? 0, ['count' => $cartCount ?? 0]) }}</span>
                    <span aria-hidden="true">{{ __('theme::lupen.cart') }} · <span data-cart-count>{{ $cartCount ?? 0 }}</span></span>
                </a>
            @endif
        </div>
    </div>

    <div id="mobile-menu" x-show="menu" x-cloak class="border-t border-line bg-page lg:hidden">
        @if (Route::has('catalog.search'))
            <form action="{{ route('catalog.search') }}" method="GET" role="search" class="container-shop pt-3">
                <label for="mobile-search" class="sr-only">{{ __('theme::shop.search') }}</label>
                <input id="mobile-search" type="search" name="q" value="{{ request('q') }}" placeholder="{{ __('theme::shop.search_placeholder') }}" class="input rounded-full">
            </form>
        @endif
        <ul class="container-shop divide-y divide-[var(--lp-line)] py-2 font-head text-2xl">
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
