{{-- Kategórie v dlaždiciach. --}}
@if (! empty($homeCategories) && $homeCategories->isNotEmpty())
    <div class="{{ $layout === 'full' ? 'container-shop' : $wrap }} farma-section">
        <nav class="mt-3 grid grid-cols-2 gap-2.5 sm:mt-5 sm:gap-4 {{ $layout === 'narrow' ? 'lg:grid-cols-2' : 'lg:grid-cols-4' }}" aria-label="{{ __('theme::shop.categories') }}">
            @foreach ($homeCategories->take($layout === 'full' ? 8 : 4) as $homeCategory)
                <a href="{{ $homeCategory->url() }}" @class(['group flex h-26 justify-between overflow-hidden rounded-[20px] border border-transparent bg-cream transition hover:border-[var(--farma-cream-dark)] sm:h-32', 'flex-row-reverse' => $reverse])>
                    <span @class(['flex flex-col py-3.5 sm:py-5', 'pl-4 sm:pl-5' => ! $reverse, 'pr-4 text-right sm:pr-5' => $reverse])>
                        <span class="font-black leading-tight sm:text-lg">{{ $homeCategory->name }}</span>
                        <span @class(['mt-auto grid size-7 place-items-center rounded-full bg-white text-accent transition group-hover:translate-x-1 sm:size-8', 'ml-auto' => $reverse])>
                            <svg class="size-4" fill="none" viewBox="0 0 24 24" stroke-width="2.5" stroke="currentColor" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="M5 12h14M13 6l6 6-6 6" /></svg>
                        </span>
                    </span>
                    @if ($categoryImage = $homeCategory->imageUrl())
                        <img src="{{ $categoryImage }}" alt="" class="w-[42%] object-cover transition duration-500 group-hover:scale-105 {{ $reverse ? '[clip-path:ellipse(100%_95%_at_0%_50%)]' : '[clip-path:ellipse(100%_95%_at_100%_50%)]' }}" loading="lazy">
                    @endif
                </a>
            @endforeach
        </nav>
    </div>
@endif
