{{-- Kategórie ako farebná mozaika: prvá veľká, ďalšie štyri menšie, s fotkou a počtom produktov. --}}
@php
    $mosaic = ! empty($homeCategories) ? $homeCategories->take(5)->values() : collect();
@endphp
@if ($mosaic->isNotEmpty())
    <section class="{{ $wrap }} py-10" aria-labelledby="home-categories">
        <div class="flex items-end justify-between gap-4">
            <h2 id="home-categories" class="es-h2">{{ $opt('categories_title') }}</h2>
            @if (Route::has('catalog.search'))
                <a href="{{ route('catalog.search') }}" class="es-link">{{ __('theme::esencia.all_products') }}</a>
            @endif
        </div>
        <div class="mt-6 grid grid-cols-1 gap-4 sm:grid-cols-2 lg:auto-rows-[11rem] lg:grid-cols-4">
            @foreach ($mosaic as $index => $homeCategory)
                @php
                    $big = $index === 0 && $mosaic->count() >= 3;
                    $categoryCount = rescue(fn () => $homeCategory->products()->visible()->count(), 0, false);
                    $categoryImage = $homeCategory->imageUrl();
                @endphp
                <a href="{{ $homeCategory->url() }}" @class(['group relative flex overflow-hidden rounded-[1.75rem] bg-t'.(($index % 6) + 1), 'min-h-[18rem] sm:col-span-2 lg:col-span-2 lg:row-span-2 lg:min-h-0' => $big, 'min-h-44' => ! $big])>
                    <span class="relative z-10 flex flex-col p-6 sm:p-7">
                        <span @class(['font-head leading-none', 'text-4xl sm:text-5xl' => $big, 'text-2xl' => ! $big])>{{ $homeCategory->name }}</span>
                        @if ($categoryCount > 0)
                            <span class="mt-2 text-sm text-muted">{{ trans_choice('theme::esencia.products_count', $categoryCount, ['count' => $categoryCount]) }}</span>
                        @endif
                        <span class="mt-auto grid size-11 place-items-center rounded-full bg-ink text-white transition group-hover:translate-x-1">@include('theme::partials.icon', ['name' => 'arrow', 'class' => 'size-5', 'stroke' => 2])</span>
                    </span>
                    @if ($categoryImage)
                        <img {{ responsive_img($categoryImage, $big ? '(min-width: 1024px) 30vw, 60vw' : '(min-width: 1024px) 12vw, 50vw') }} alt="" loading="lazy"
                             @class(['absolute bottom-0 right-0 h-full object-cover mix-blend-multiply [mask-image:linear-gradient(to_right,transparent,black_35%)]', 'w-3/5' => $big, 'w-1/2' => ! $big])>
                    @endif
                </a>
            @endforeach
        </div>
    </section>
@endif
