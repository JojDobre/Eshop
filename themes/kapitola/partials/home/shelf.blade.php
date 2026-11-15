{{-- Z našej poličky: obálky kníh z vybranej kategórie stoja na poličke a majú poradové číslo. --}}
@php
    $shelfCategory = (int) $themeOptions->get('shelf_category') > 0 ? \Modules\Catalog\Models\Category::query()->find((int) $themeOptions->get('shelf_category')) : null;
    $shelfBooks = \Modules\Catalog\Models\Product::query()->visible()
        ->when($shelfCategory, fn ($query) => $query->whereHas('categories', fn ($categories) => $categories->where('categories.id', $shelfCategory->id)))
        ->latest()->orderBy('id')->limit(6)->get();
@endphp
@if ($shelfBooks->isNotEmpty())
    <section class="{{ $wrap }} pt-6" aria-labelledby="home-shelf">
        <div class="flex items-end justify-between gap-4">
            <div>
                <p class="text-sm font-bold uppercase tracking-[0.18em] text-ac">{{ $opt('shelf_kicker') }}</p>
                <h2 id="home-shelf" class="kp-h2 mt-2">{{ $opt('shelf_title') }}</h2>
            </div>
            @if ($shelfCategory)
                <a href="{{ $shelfCategory->url() }}" class="kp-link hidden shrink-0 sm:inline">{{ __('theme::kapitola.shelf_all') }}</a>
            @endif
        </div>
        <div class="mt-8">
            <ol class="flex items-end gap-4 overflow-x-auto px-3 pt-5 sm:justify-between sm:gap-6">
                @foreach ($shelfBooks as $book)
                    <li class="shrink-0">
                        <a href="{{ $book->url() }}" class="group relative block">
                            <span class="absolute -left-2 -top-3 z-10 grid size-8 place-items-center rounded-full bg-ac text-sm font-bold text-onac shadow" aria-hidden="true">{{ $loop->iteration }}</span>
                            <span class="sr-only">{{ $loop->iteration }}. {{ $book->name }}</span>
                            @if ($cover = $book->mainImageUrl())
                                <img {{ responsive_img($cover, '160px') }} alt="" loading="lazy" class="book aspect-[2/3] w-28 object-cover transition duration-300 group-hover:-translate-y-2 sm:w-36">
                            @else
                                <span class="book grid aspect-[2/3] w-28 place-items-center bg-soft2 p-3 text-center font-head sm:w-36">{{ $book->name }}</span>
                            @endif
                        </a>
                    </li>
                @endforeach
            </ol>
            <div class="shelfbar"></div>
        </div>
    </section>
@endif
