{{-- Odporúčania kníhkupcov: fotka predajne s pozvánkou na podujatie a tri knihy s citátom a menom odporúčajúceho. --}}
@php
    $picksImage = $themeOptions->imageUrl('picks_image');
    $eventTitle = trim((string) $themeOptions->get('event_title'));
    $eventText = trim((string) $themeOptions->get('event_text'));
    $pickIds = collect(range(1, 3))->mapWithKeys(fn ($i) => [$i => (int) $themeOptions->get('pick_'.$i.'_product')])->filter();
    $pickProducts = $pickIds->isNotEmpty() ? \Modules\Catalog\Models\Product::query()->visible()->whereIn('id', $pickIds->values())->get()->keyBy('id') : collect();
    $picks = $pickIds->map(fn ($id, $i) => $pickProducts->has($id) ? [
        'product' => $pickProducts[$id],
        'text' => trim((string) $themeOptions->get('pick_'.$i.'_text')),
        'author' => trim((string) $themeOptions->get('pick_'.$i.'_author')),
    ] : null)->filter();
@endphp
@if ($picks->isNotEmpty())
    <section class="{{ $wrap }} pb-16" aria-labelledby="home-picks">
        <div @class(['grid items-center gap-8', 'lg:grid-cols-[1fr_1.3fr]' => $picksImage])>
            @if ($picksImage)
                <div @class(['relative', 'pb-10' => $eventTitle !== '', 'lg:order-2' => $reverse])>
                    <img {{ responsive_img($picksImage, '(min-width: 1024px) 40vw, 95vw') }} alt="" loading="lazy" class="aspect-[4/5] w-full rounded-[2rem] object-cover max-lg:aspect-[4/3]">
                    @if ($eventTitle !== '')
                        <div class="absolute inset-x-4 bottom-0 rounded-2xl bg-card p-5 shadow-xl ring-1 ring-line sm:inset-x-6">
                            <p class="flex items-center gap-2 font-bold">@include('theme::partials.icon', ['name' => 'cup', 'class' => 'size-5 shrink-0 text-ac']){{ $eventTitle }}</p>
                            @if ($eventText !== '')<p class="mt-0.5 text-sm text-muted">{{ $eventText }}</p>@endif
                        </div>
                    @endif
                </div>
            @endif
            <div class="min-w-0">
                <p class="text-sm font-bold uppercase tracking-[0.18em] text-ac">{{ $opt('picks_kicker') }}</p>
                <h2 id="home-picks" class="kp-h2 mt-2">{{ $opt('picks_title') }}</h2>
                <div class="mt-8 space-y-4">
                    @foreach ($picks as $pick)
                        <figure class="relative flex gap-4 rounded-2xl bg-card p-4 ring-1 ring-line sm:gap-5 sm:p-5">
                            @if ($cover = $pick['product']->mainImageUrl())
                                <img src="{{ $cover }}" alt="" loading="lazy" class="book aspect-[2/3] w-16 shrink-0 self-start object-cover sm:w-20">
                            @endif
                            <div class="min-w-0">
                                <p class="kp-heading text-xl leading-tight"><a href="{{ $pick['product']->url() }}" class="after:absolute after:inset-0 hover:underline">{{ $pick['product']->name }}</a>@if (filled($pick['product']->short_description)) <span class="font-sans text-sm font-normal text-muted">· {{ strip_tags((string) $pick['product']->short_description) }}</span>@endif</p>
                                @if ($pick['text'] !== '')<blockquote class="kp-heading mt-2 italic leading-snug">„{{ $pick['text'] }}“</blockquote>@endif
                                @if ($pick['author'] !== '')
                                    <figcaption class="mt-2 flex items-center gap-2 text-sm font-semibold"><span class="grid size-7 shrink-0 place-items-center rounded-full bg-ac2 text-xs text-onac2" aria-hidden="true">{{ mb_substr($pick['author'], 0, 1) }}</span>{{ $pick['author'] }}</figcaption>
                                @endif
                            </div>
                        </figure>
                    @endforeach
                </div>
            </div>
        </div>
    </section>
@endif
