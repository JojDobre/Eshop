{{--
    Adopcia zvieratiek: produkty z vybranej kategórie (cena = mesačný príspevok) v kartách s fotkou v tvare stodoly,
    menom, krátkym popisom (vek, povaha), cenovkou a začiatkom popisu (čo adoptívny rodič dostane).
--}}
@php
    $animalsCategory = (int) $themeOptions->get('animals_category') > 0 ? \Modules\Catalog\Models\Category::query()->find((int) $themeOptions->get('animals_category')) : null;
    $animals = $animalsCategory
        ? \Modules\Catalog\Models\Product::query()->visible()->with(['prices', 'groupPrices', 'taxRate', 'variants'])
            ->whereHas('categories', fn ($query) => $query->where('categories.id', $animalsCategory->id))->oldest()->orderBy('id')->limit(4)->get()
        : collect();
@endphp
@if ($animals->isNotEmpty())
    <section class="{{ $wrap }} py-16" aria-labelledby="home-animals">
        <div class="grid items-end gap-6 lg:grid-cols-[1.3fr_1fr]">
            <div>
                <p class="dv-kicker">{{ $opt('animals_kicker') }}</p>
                <h2 id="home-animals" class="dv-h2 mt-2">{{ $opt('animals_title') }}</h2>
            </div>
            <p class="max-w-xl text-muted lg:justify-self-end">{{ $opt('animals_text') }}</p>
        </div>
        <ul @class(['mt-10 grid gap-5 min-[480px]:grid-cols-2', 'lg:grid-cols-4' => $animals->count() >= 4, 'lg:grid-cols-3' => $animals->count() === 3])>
            @foreach ($animals as $animal)
                @php
                    $animalPrice = $animal->displayPrice($animal->priceFrom());
                    $animalAbout = \Illuminate\Support\Str::limit(trim(strip_tags((string) $animal->description)), 110);
                @endphp
                <li class="dv-card dv-zoom relative flex flex-col overflow-hidden">
                    <span class="barn-sm block aspect-[4/3] overflow-hidden bg-soft">@include('theme::partials.product-image', ['url' => $animal->mainImageUrl(), 'alt' => '', 'sizes' => '(min-width: 1024px) 22vw, 90vw'])</span>
                    <div class="flex flex-1 flex-col p-5">
                        <div class="flex items-start justify-between gap-3">
                            <div class="min-w-0">
                                <h3 class="font-head text-2xl leading-none">{{ $animal->name }}</h3>
                                @if (filled($animal->short_description))<p class="mt-1 text-sm text-muted">{{ strip_tags((string) $animal->short_description) }}</p>@endif
                            </div>
                            @if ($animalPrice !== null)
                                <p class="pricetag shrink-0 flex-col !items-end !gap-0 text-sm font-extrabold leading-tight"><span class="whitespace-nowrap">{{ format_price($animalPrice) }}</span><span class="text-[11px] font-semibold text-muted">{{ $opt('animals_unit') }}</span></p>
                            @endif
                        </div>
                        @if ($animalAbout !== '')<p class="mt-3 text-[15px] leading-snug">{{ $animalAbout }}</p>@endif
                        <div class="mt-auto pt-4"><a href="{{ $animal->url() }}" class="btn btn-line w-full !py-2.5 text-sm after:absolute after:inset-0" aria-label="{{ __('theme::dvor.adopt_label', ['name' => $animal->name]) }}">@include('theme::partials.icon', ['name' => 'heart', 'class' => 'size-4']){{ __('theme::dvor.adopt') }}</a></div>
                    </div>
                </li>
            @endforeach
        </ul>
    </section>
@endif
