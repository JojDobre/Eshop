{{-- Kategórie podľa remesla: fotky v oblúkoch s názvom a počtom produktov. --}}
@if (! empty($homeCategories) && $homeCategories->isNotEmpty())
    <section class="{{ $wrap }} py-16 sm:py-20" aria-labelledby="home-categories">
        <div @class(['flex items-end justify-between gap-4', 'flex-row-reverse text-right' => $reverse])>
            <div>
                <p class="dielna-kicker">{{ $opt('categories_kicker') }}</p>
                <h2 id="home-categories" class="dielna-h2 dielna-squiggle">{{ $opt('categories_title') }}</h2>
            </div>
            @if (Route::has('catalog.search'))
                <a href="{{ $homeCategories->first()->url() }}" class="dielna-link hidden sm:inline">{{ __('theme::dielna.show_all') }}</a>
            @endif
        </div>
        <div @class(['mt-10 grid grid-cols-2 gap-4 sm:grid-cols-3 sm:gap-6', 'lg:grid-cols-6' => $homeCategories->count() >= 6, 'lg:grid-cols-4' => $homeCategories->count() < 6])>
            @foreach ($homeCategories as $homeCategory)
                @php($categoryCount = rescue(fn () => $homeCategory->products()->visible()->count(), 0, false))
                <a href="{{ $homeCategory->url() }}" class="dielna-lift group text-center">
                    @if ($categoryImage = $homeCategory->imageUrl())
                        <img {{ responsive_img($categoryImage, '(min-width: 1024px) 15vw, 45vw') }} alt="" class="arch-sm aspect-[3/4] w-full object-cover" loading="lazy">
                    @else
                        <span class="arch-sm grid aspect-[3/4] w-full place-items-center bg-sand font-serif text-4xl italic text-accent" aria-hidden="true">{{ mb_substr($homeCategory->name, 0, 1) }}</span>
                    @endif
                    <span class="mt-3 block font-serif text-lg">{{ $homeCategory->name }}</span>
                    @if ($categoryCount > 0)
                        <span class="block text-xs text-muted">{{ trans_choice('theme::dielna.products_count', $categoryCount, ['count' => $categoryCount]) }}</span>
                    @endif
                </a>
            @endforeach
        </div>
    </section>
@endif
