{{-- Shop the look: fotka s očíslovanými bodmi, zoznam kúskov a pridanie celého outfitu naraz (ak nemajú varianty). --}}
@php
    $lookIds = collect(range(1, 3))->mapWithKeys(fn ($i) => [$i => (int) $themeOptions->get('look_product_'.$i)])->filter();
    $lookProducts = $lookIds->isEmpty() ? collect() : \Modules\Catalog\Models\Product::query()->visible()->with(['prices', 'groupPrices', 'taxRate', 'variants'])->whereIn('id', $lookIds->all())->get()->keyBy('id');
    $lookItems = $lookIds->map(function ($id, $i) use ($lookProducts, $themeOptions) {
        $spot = array_map('trim', explode(',', (string) $themeOptions->get('look_spot_'.$i)));

        return ['product' => $lookProducts->get($id), 'spot' => count($spot) === 2 && is_numeric($spot[0]) && is_numeric($spot[1]) ? [max(0, min(95, (float) $spot[0])), max(0, min(95, (float) $spot[1]))] : null];
    })->filter(fn ($item) => $item['product'] !== null)->values();
    $lookImage = $themeOptions->imageUrl('look_image');
    $lookTotal = $lookItems->sum(fn ($item) => (int) $item['product']->displayPrice($item['product']->priceFrom()));
    $lookAddable = $lookItems->every(fn ($item) => ! $item['product']->hasVariants() && $item['product']->isAvailable()) && Route::has('cart.add-many');
@endphp
@if ($lookItems->isNotEmpty())
    <section class="bg-soft" aria-labelledby="home-look">
        <div @class(['container-shop grid items-center gap-10 py-16', 'lg:grid-cols-[1.1fr_1fr]' => $lookImage])>
            @if ($lookImage)
                <div @class(['relative', 'lg:order-2' => $reverse])>
                    <img {{ responsive_img($lookImage, '(min-width: 1024px) 50vw, 100vw') }} alt="" class="aspect-[4/5] w-full object-cover" loading="lazy">
                    @foreach ($lookItems as $item)
                        @if ($item['spot'])
                            <a href="{{ $item['product']->url() }}" class="absolute grid size-9 -translate-x-1/2 -translate-y-1/2 place-items-center rounded-full bg-white text-sm font-bold shadow-lg ring-4 ring-white/40 transition hover:scale-110"
                               style="left: {{ $item['spot'][0] }}%; top: {{ $item['spot'][1] }}%" aria-label="{{ $loop->iteration }}. {{ $item['product']->name }}">{{ $loop->iteration }}</a>
                        @endif
                    @endforeach
                </div>
            @endif
            <div>
                <p class="cap text-muted">{{ $opt('look_label') }}</p>
                <h2 id="home-look" class="bt-h2 mt-3">{{ $opt('look_title') }}</h2>
                <p class="mt-4 max-w-md text-muted">{{ $opt('look_text') }}</p>
                <ol class="mt-8 divide-y divide-[var(--bt-line)] border-y border-line">
                    @foreach ($lookItems as $item)
                        @php
                            $itemPrice = $item['product']->displayPrice($item['product']->priceFrom());
                        @endphp
                        <li class="relative flex items-center gap-4 py-4">
                            <span class="grid size-8 shrink-0 place-items-center rounded-full bg-ink text-sm font-bold text-white" aria-hidden="true">{{ $loop->iteration }}</span>
                            <span class="size-16 shrink-0 overflow-hidden bg-white">@include('theme::partials.product-image', ['url' => $item['product']->mainImageUrl(), 'alt' => '', 'sizes' => '64px'])</span>
                            <span class="min-w-0 flex-1">
                                <a href="{{ $item['product']->url() }}" class="block font-medium after:absolute after:inset-0">{{ $item['product']->name }}</a>
                                @if ($itemPrice !== null)<span class="text-sm text-muted">{{ format_price($itemPrice) }}</span>@endif
                            </span>
                            <span class="cap" aria-hidden="true">{{ $item['product']->hasVariants() ? __('theme::butik.choose') : '→' }}</span>
                        </li>
                    @endforeach
                </ol>
                <div class="mt-6 flex flex-wrap items-center justify-between gap-4">
                    @if ($lookTotal > 0)
                        <p><span class="text-muted">{{ __('theme::butik.look_total') }}</span> <b class="font-head ml-2 text-4xl">{{ format_price($lookTotal) }}</b></p>
                    @endif
                    @if ($lookAddable)
                        <form method="POST" action="{{ route('cart.add-many') }}" data-ajax-cart>
                            @csrf
                            @foreach ($lookItems as $item)
                                <input type="hidden" name="product_ids[]" value="{{ $item['product']->id }}">
                            @endforeach
                            <button type="submit" class="btn btn-ink">
                                <span class="cart-icon-default">{{ __('theme::butik.look_add_all') }}</span>
                                <span class="cart-icon-done">✓</span>
                            </button>
                        </form>
                    @endif
                </div>
            </div>
        </div>
    </section>
@endif
