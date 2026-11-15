{{-- Digitálne produkty: text s výhodami a vpravo tri natočené obálky z kategórie e-kníh. --}}
@php
    $digitalCategory = (int) $themeOptions->get('digital_category') > 0 ? \Modules\Catalog\Models\Category::query()->find((int) $themeOptions->get('digital_category')) : null;
    $digitalBooks = $digitalCategory
        ? \Modules\Catalog\Models\Product::query()->visible()->whereHas('categories', fn ($query) => $query->where('categories.id', $digitalCategory->id))->latest()->orderBy('id')->limit(3)->get()
        : collect();
    $points = collect(preg_split('/\R/', $opt('digital_points')))->map(fn ($line) => trim($line))->filter();
    $tilts = ['-rotate-3 w-24 sm:w-32', 'w-32 sm:w-40 relative z-10', 'rotate-3 w-24 sm:w-32'];
    $ordered = $digitalBooks->count() === 3 ? collect([$digitalBooks[1], $digitalBooks[0], $digitalBooks[2]]) : $digitalBooks;
@endphp
<section class="{{ $wrap }} pb-16" aria-labelledby="home-digital">
    <div @class(['grid items-center overflow-hidden rounded-[1.75rem] bg-soft2', 'lg:grid-cols-2' => $digitalBooks->isNotEmpty()])>
        <div class="p-7 sm:p-12">
            <p class="text-sm font-bold uppercase tracking-[0.18em] text-ac">{{ $opt('digital_kicker') }}</p>
            <h2 id="home-digital" class="kp-h2 mt-3">{{ $opt('digital_title') }}</h2>
            <p class="mt-4 max-w-md text-muted">{{ $opt('digital_text') }}</p>
            @if ($points->isNotEmpty())
                <ul class="mt-6 space-y-2">
                    @foreach ($points as $point)
                        <li class="flex items-center gap-2 font-semibold">@include('theme::partials.icon', ['name' => 'download', 'class' => 'size-4 shrink-0 text-ac', 'stroke' => 2.2]){{ $point }}</li>
                    @endforeach
                </ul>
            @endif
            @if ($digitalCategory)
                <a href="{{ $digitalCategory->url() }}" class="btn btn-ac mt-7">{{ $opt('digital_button') }}</a>
            @endif
        </div>
        @if ($ordered->isNotEmpty())
            <div class="flex items-end justify-center gap-3 px-6 pb-10 sm:gap-5 lg:p-8">
                @foreach ($ordered as $digitalBook)
                    <a href="{{ $digitalBook->url() }}" class="block shrink-0 {{ $ordered->count() === 3 ? $tilts[$loop->index] : 'w-32 sm:w-40' }}">
                        @if ($cover = $digitalBook->mainImageUrl())
                            <img {{ responsive_img($cover, '160px') }} alt="{{ $digitalBook->name }}" loading="lazy" class="book aspect-[2/3] w-full object-cover transition duration-300 hover:-translate-y-1.5">
                        @endif
                    </a>
                @endforeach
            </div>
        @endif
    </div>
</section>
