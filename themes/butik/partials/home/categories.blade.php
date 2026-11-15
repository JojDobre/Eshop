{{-- Kategórie: veľké fotky s názvom a počtom produktov. --}}
@php
    $tiles = ! empty($homeCategories) ? $homeCategories->filter(fn ($category) => filled($category->imageUrl()))->take(4)->values() : collect();
    $categoriesTitle = trim((string) $themeOptions->get('categories_title'));
@endphp
@if ($tiles->isNotEmpty())
    <section class="{{ $wrap }} pb-16" @if ($categoriesTitle !== '') aria-labelledby="home-categories" @endif>
        @if ($categoriesTitle !== '')
            <h2 id="home-categories" class="bt-h2 mb-8">{{ $categoriesTitle }}</h2>
        @endif
        <div @class(['grid gap-4 sm:grid-cols-2', 'lg:grid-cols-4' => $tiles->count() >= 4, 'lg:grid-cols-3' => $tiles->count() === 3])>
            @foreach ($tiles as $tile)
                @php
                    $tileCount = rescue(fn () => $tile->products()->visible()->count(), 0, false);
                @endphp
                <a href="{{ $tile->url() }}" class="bt-zoom group relative block aspect-[3/4] overflow-hidden bg-soft">
                    <img {{ responsive_img($tile->imageUrl(), '(min-width: 1024px) 25vw, (min-width: 640px) 50vw, 100vw') }} alt="" class="absolute inset-0 size-full object-cover" loading="lazy">
                    <span class="absolute inset-0 bg-gradient-to-t from-black/55 via-transparent"></span>
                    <span class="absolute inset-x-5 bottom-5 flex items-end justify-between gap-3 text-white">
                        <span class="font-head text-4xl">{{ $tile->name }}</span>
                        @if ($tileCount > 0)<span class="cap whitespace-nowrap">{{ trans_choice('theme::butik.products_count', $tileCount, ['count' => $tileCount]) }} →</span>@endif
                    </span>
                </a>
            @endforeach
        </div>
    </section>
@endif
