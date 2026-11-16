{{-- Kategórie: fotky v tvare stodoly s názvom a počtom produktov. --}}
@php
    $tiles = ! empty($homeCategories) ? $homeCategories->take(6) : collect();
    $tiles->loadCount(['products' => fn ($query) => $query->visible()]);
@endphp
@if ($tiles->isNotEmpty())
    <section class="{{ $wrap }} py-16" aria-labelledby="home-categories">
        <div class="flex items-end justify-between gap-4">
            <h2 id="home-categories" class="dv-h2">{{ $opt('categories_title') }}</h2>
            @if (Route::has('catalog.search'))
                <a href="{{ route('catalog.search') }}" class="dv-link shrink-0">{{ __('theme::dvor.all_products') }} →</a>
            @endif
        </div>
        <ul @class(['mt-8 grid grid-cols-2 gap-4 sm:grid-cols-3 sm:gap-6', 'lg:grid-cols-6' => $tiles->count() >= 6, 'lg:grid-cols-4' => $tiles->count() === 4, 'lg:grid-cols-5' => $tiles->count() === 5])>
            @foreach ($tiles as $tile)
                <li>
                    <a href="{{ $tile->url() }}" class="dv-zoom group block text-center">
                        <span class="barn block aspect-[4/5] overflow-hidden bg-soft">@include('theme::partials.product-image', ['url' => $tile->imageUrl(), 'alt' => '', 'sizes' => '(min-width: 1024px) 16vw, 45vw'])</span>
                        <span class="mt-3 block font-head text-lg">{{ $tile->name }}</span>
                        <span class="text-sm text-muted">{{ trans_choice('theme::shop.products_count', $tile->products_count, ['count' => $tile->products_count]) }}</span>
                    </a>
                </li>
            @endforeach
        </ul>
    </section>
@endif
