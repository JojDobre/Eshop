{{--
    Úvod: fotka cez celú šírku s tmavým prechodom, nadpis (slovo v *hviezdičkách* je kurzívou), vyhľadávanie,
    žánre ako čipy a vpravo karta „Kniha mesiaca“ s citátom kníhkupca.
--}}
@php
    $heroImage = $themeOptions->imageUrl('hero_image');
    $heroTitle = preg_replace('/\*(.+?)\*/u', '<em>$1</em>', e($opt('hero_title')));
    $genres = collect(preg_split('/\R/', (string) $themeOptions->get('hero_genres')))
        ->map(fn ($line) => array_map('trim', explode('|', $line, 2)))
        ->filter(fn ($parts) => $parts[0] !== '')
        ->map(fn ($parts) => ['label' => $parts[0], 'url' => $parts[1] ?? null])
        ->take(8);

    if ($genres->isEmpty() && ! empty($homeCategories)) {
        $genres = $homeCategories->take(6)->map(fn ($category) => ['label' => $category->name, 'url' => $category->url()]);
    }

    $monthProduct = (int) $themeOptions->get('month_product') > 0
        ? \Modules\Catalog\Models\Product::query()->visible()->with(['prices', 'groupPrices', 'taxRate', 'variants'])->find((int) $themeOptions->get('month_product'))
        : null;
    $monthPrice = $monthProduct?->displayPrice($monthProduct->priceFrom());
    $monthQuote = trim((string) $themeOptions->get('month_quote'));
    $monthAuthor = trim((string) $themeOptions->get('month_quote_author'));
@endphp
<section @class(['relative overflow-hidden', 'bg-ink' => ! $heroImage]) aria-labelledby="home-hero">
    @if ($heroImage)
        <img {{ responsive_img($heroImage, '100vw') }} alt="" class="absolute inset-0 size-full object-cover" fetchpriority="high">
        <div class="absolute inset-0 bg-gradient-to-r from-black/80 via-black/50 to-black/15"></div>
    @endif
    <div @class(['container-shop relative grid items-center gap-10 py-14 text-white sm:py-24', 'lg:grid-cols-[1.1fr_.9fr]' => $monthProduct])>
        <div class="min-w-0">
            <p class="text-sm font-bold uppercase tracking-[0.2em] text-white/80">{{ $opt('hero_kicker') }}</p>
            <h1 id="home-hero" class="mt-4 text-[2.7rem] leading-[1.03] [text-wrap:balance] sm:text-7xl">{!! $heroTitle !!}</h1>
            <p class="mt-5 max-w-lg text-lg text-white/85">{{ $opt('hero_text') }}</p>

            @if (Route::has('catalog.search'))
                <form action="{{ route('catalog.search') }}" method="GET" role="search" class="mt-7 flex max-w-lg items-center gap-2 rounded-2xl bg-card p-2 pl-4 text-ink">
                    <svg class="size-5 shrink-0 text-muted" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" aria-hidden="true"><circle cx="11" cy="11" r="7" /><path stroke-linecap="round" d="m20 20-3.5-3.5" /></svg>
                    <label for="hero-search" class="sr-only">{{ __('theme::shop.search') }}</label>
                    <input id="hero-search" type="search" name="q" placeholder="{{ __('theme::kapitola.search_placeholder') }}" class="min-w-0 flex-1 border-0 bg-transparent p-1 text-[15px] text-ink outline-none focus:ring-0">
                    <button type="submit" class="btn btn-ac shrink-0 !px-4 !py-2.5">{{ __('theme::kapitola.search') }}</button>
                </form>
            @endif

            @if ($genres->isNotEmpty())
                <ul class="mt-5 flex flex-wrap gap-2 text-sm font-semibold">
                    @foreach ($genres as $genre)
                        <li>
                            @if ($genre['url'])
                                <a href="{{ $genre['url'] }}" class="block rounded-full bg-white/15 px-3 py-1.5 backdrop-blur transition hover:bg-white hover:text-[#24161a]">{{ $genre['label'] }}</a>
                            @else
                                <span class="block rounded-full bg-white/15 px-3 py-1.5 backdrop-blur">{{ $genre['label'] }}</span>
                            @endif
                        </li>
                    @endforeach
                </ul>
            @endif
        </div>

        @if ($monthProduct)
            <article class="relative grid grid-cols-[auto_minmax(0,1fr)] items-center gap-4 rounded-[1.75rem] bg-[color-mix(in_srgb,var(--kp-card)_95%,transparent)] p-5 text-ink shadow-2xl backdrop-blur sm:gap-5 sm:p-6">
                <img src="{{ $monthProduct->mainImageUrl() }}" alt="{{ $monthProduct->name }}" class="book aspect-[2/3] w-24 object-cover sm:w-40">
                <div class="min-w-0">
                    <p class="text-xs font-bold uppercase tracking-[0.18em] text-ac">{{ __('theme::kapitola.month') }}</p>
                    <h2 class="mt-2 text-2xl leading-tight sm:text-3xl"><a href="{{ $monthProduct->url() }}" class="hover:underline">{{ $monthProduct->name }}</a></h2>
                    @if (filled($monthProduct->short_description))
                        <p class="text-sm text-muted">{{ strip_tags((string) $monthProduct->short_description) }}</p>
                    @endif
                    @if ($monthQuote !== '')
                        <p class="kp-heading mt-3 hidden italic leading-snug sm:block">„{{ $monthQuote }}“</p>
                        @if ($monthAuthor !== '')<p class="mt-1 hidden text-xs text-muted sm:block">— {{ $monthAuthor }}</p>@endif
                    @endif
                    <div class="mt-4 flex flex-wrap items-center gap-3">
                        @if ($monthPrice !== null)<b class="whitespace-nowrap text-xl">{{ format_price($monthPrice) }}</b>@endif
                        @if ($monthProduct->isAvailable() && ! $monthProduct->hasVariants() && Route::has('cart.add'))
                            <form method="POST" action="{{ route('cart.add') }}" data-ajax-cart>
                                @csrf
                                <input type="hidden" name="product_id" value="{{ $monthProduct->id }}">
                                <button type="submit" class="btn btn-ac whitespace-nowrap !px-4 !py-2 text-sm">{{ __('theme::kapitola.add_to_cart') }}</button>
                            </form>
                        @else
                            <a href="{{ $monthProduct->url() }}" class="btn btn-ac whitespace-nowrap !px-4 !py-2 text-sm">{{ __('theme::kapitola.choose') }}</a>
                        @endif
                    </div>
                </div>
            </article>
        @endif
    </div>
</section>
