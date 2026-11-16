{{-- Hlavička Dvora: pruh s rozvozom (prvá informácia zvýraznená), logo so stodolou, ponuka, vyhľadávanie, účet a košík. --}}
@include('theme::partials.palette')
@php
    $navItems = $menus->items('header');

    if ($navItems === [] && ! empty($navigationCategories)) {
        $navItems = $navigationCategories->map(fn ($category) => ['label' => $category->name, 'url' => $category->url(), 'new_window' => false, 'active' => false, 'children' => []])->all();
    }

    $noticeItems = collect(preg_split('/\R/', (string) $themeOptions->get('notice_items')))->map(fn ($line) => trim($line))->filter()->take(3)->values();
    $logoText = trim((string) $themeOptions->get('logo_text'));
@endphp

@if ($noticeItems->isNotEmpty())
    <div class="bg-deep text-[13px] font-semibold text-white" role="region" aria-label="{{ __('theme::dvor.notice') }}">
        <ul class="container-shop flex items-center justify-center gap-6 py-2 text-center">
            @foreach ($noticeItems as $noticeItem)
                <li @class(['flex items-center gap-2' => $loop->first, 'hidden text-white/75' => ! $loop->first, 'md:block' => $loop->index === 1, 'lg:block' => $loop->index === 2])>
                    @if ($loop->first)<span class="size-2 shrink-0 rounded-full bg-sun" aria-hidden="true"></span>@endif{{ $noticeItem }}
                </li>
            @endforeach
        </ul>
    </div>
@endif

<header class="sticky top-0 z-40 border-b border-line bg-[color-mix(in_srgb,var(--dv-bg)_94%,transparent)] backdrop-blur" x-data="{ menu: false }">
    <div class="container-shop flex h-16 items-center gap-3 sm:h-[76px] sm:gap-6">
        <button type="button" class="-ml-1.5 grid size-10 shrink-0 place-items-center rounded-full hover:bg-soft lg:hidden" @click="menu = ! menu" :aria-expanded="menu.toString()" aria-controls="mobile-menu" aria-label="{{ __('theme::dvor.menu') }}">
            <svg class="size-6" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" aria-hidden="true"><path stroke-linecap="round" d="M4 7h16M4 12h16M4 17h10" /></svg>
        </button>

        <a href="{{ route('storefront.home') }}" class="flex min-w-0 items-center gap-2.5 leading-none">
            @if ($logo = $branding->logoUrl())
                <img src="{{ $logo }}" alt="{{ $shop->shop_name }}" class="h-10 w-auto shrink-0 sm:h-11">
            @else
                <span class="barn-sm grid size-10 shrink-0 place-items-end justify-center bg-ac pb-1.5 text-onac sm:size-11" aria-hidden="true">@include('theme::partials.icon', ['name' => 'egg', 'class' => 'size-5', 'stroke' => 2.2])</span>
            @endif
            @if (! $logo || $themeOptions->get('show_shop_name', true))
                <span class="min-w-0">
                    <span class="shop-name block whitespace-nowrap font-head text-[21px] sm:text-[26px]" data-fit-text>{{ $shop->shop_name }}</span>
                    @if ($logoText !== '')
                        <span class="mt-0.5 hidden whitespace-nowrap text-[11px] font-extrabold uppercase tracking-[0.18em] text-muted sm:block">{{ $logoText }}</span>
                    @endif
                </span>
            @endif
        </a>

        <nav class="ml-4 hidden items-center gap-6 whitespace-nowrap text-[15px] font-bold lg:flex [&_.absolute]:bg-card [&_[aria-current]]:text-ac [&_a:hover]:text-ac" aria-label="{{ __('theme::shop.categories') }}">
            @include('theme::partials.nav-items', ['items' => $navItems])
        </nav>

        <div class="ml-auto flex shrink-0 items-center gap-1 sm:gap-2">
            <div class="hidden items-center gap-1 xl:flex">
                @include('theme::partials.language-switcher')
                @include('theme::partials.currency-switcher')
            </div>
            @if (Route::has('catalog.search'))
                <a href="{{ route('catalog.search') }}" class="grid size-11 place-items-center rounded-full hover:bg-soft" aria-label="{{ __('theme::shop.search') }}">
                    <svg class="size-5" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" aria-hidden="true"><circle cx="11" cy="11" r="7" /><path stroke-linecap="round" d="m20 20-3.5-3.5" /></svg>
                </a>
            @endif
            @if (Route::has('customer.account'))
                <a href="{{ route('customer.account') }}" class="hidden size-11 place-items-center rounded-full hover:bg-soft sm:grid" aria-label="{{ empty($customer) ? __('theme::account.login') : __('theme::account.title') }}">
                    <svg class="size-5" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" aria-hidden="true"><circle cx="12" cy="8" r="4" /><path stroke-linecap="round" d="M4 21c1.5-4 4.5-6 8-6s6.5 2 8 6" /></svg>
                </a>
            @endif
            @if (Route::has('cart.show'))
                <a href="{{ route('cart.show') }}" class="btn btn-ac shrink-0 whitespace-nowrap !px-4 !py-2.5 text-sm">
                    @include('theme::partials.icon', ['name' => 'bag', 'class' => 'size-4'])
                    <span class="sr-only">{{ trans_choice('theme::shop.cart_with_count', $cartCount ?? 0, ['count' => $cartCount ?? 0]) }}</span>
                    <span aria-hidden="true"><span class="hidden sm:inline">{{ __('theme::dvor.cart') }} · </span><span data-cart-count>{{ $cartCount ?? 0 }}</span></span>
                </a>
            @endif
        </div>
    </div>

    <div id="mobile-menu" x-show="menu" x-cloak class="border-t border-line bg-page lg:hidden">
        @if (Route::has('catalog.search'))
            <form action="{{ route('catalog.search') }}" method="GET" role="search" class="container-shop pt-3">
                <label for="mobile-search" class="sr-only">{{ __('theme::shop.search') }}</label>
                <input id="mobile-search" type="search" name="q" value="{{ request('q') }}" placeholder="{{ __('theme::shop.search') }}" class="input">
            </form>
        @endif
        <ul class="container-shop divide-y divide-[var(--dv-line)] py-2 font-head text-2xl">
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
        <div class="container-shop pb-3">@include('theme::partials.preference-switchers-inline')</div>
    </div>
</header>
