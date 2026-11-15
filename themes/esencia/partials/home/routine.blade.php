{{-- Rutina v 3 krokoch: tri vybrané produkty (ID v Prispôsobení), súčet cien a pridanie všetkých do košíka. --}}
@php
    $routineIds = collect(range(1, 3))->mapWithKeys(fn ($i) => [$i => (int) $themeOptions->get('routine_product_'.$i)])->filter();
    $routineProducts = $routineIds->isEmpty() ? collect() : \Modules\Catalog\Models\Product::query()->visible()->with(['prices', 'groupPrices', 'taxRate', 'variants'])->whereIn('id', $routineIds->all())->get()->keyBy('id');
    $routineSteps = $routineIds->map(fn ($id, $i) => ['product' => $routineProducts->get($id), 'step' => $opt('routine_step_'.$i)])->filter(fn ($step) => $step['product'] !== null)->values();
    $routineTotal = $routineSteps->sum(fn ($step) => (int) $step['product']->displayPrice($step['product']->priceFrom()));
    $routineAddable = $routineSteps->every(fn ($step) => ! $step['product']->hasVariants() && $step['product']->isAvailable()) && Route::has('cart.add-many');
@endphp
@if ($routineSteps->isNotEmpty())
    <section class="{{ $wrap }} py-10" aria-labelledby="home-routine">
        <div class="grid items-center gap-8 rounded-[2rem] bg-ink p-6 text-white sm:p-10 lg:grid-cols-[1fr_1.6fr]">
            <div @class(['lg:order-2' => $reverse])>
                <span class="es-sticker rounded-full px-3 py-1 text-xs font-bold uppercase tracking-wider">{{ $opt('routine_label') }}</span>
                <h2 id="home-routine" class="es-h2 mt-4">{{ $opt('routine_title') }}</h2>
                <p class="mt-3 text-white/75">{{ $opt('routine_text') }}</p>
                @if ($routineTotal > 0)
                    <p class="mt-6 flex items-baseline gap-3"><span class="text-white/70">{{ __('theme::esencia.routine_total') }}</span><span class="font-head text-4xl">{{ format_price($routineTotal) }}</span></p>
                @endif
                @if ($routineAddable)
                    <form method="POST" action="{{ route('cart.add-many') }}" class="mt-6" data-ajax-cart>
                        @csrf
                        @foreach ($routineSteps as $step)
                            <input type="hidden" name="product_ids[]" value="{{ $step['product']->id }}">
                        @endforeach
                        <button type="submit" class="btn btn-ac">
                            <span class="cart-icon-default">{{ __('theme::esencia.routine_add_all') }}</span>
                            <span class="cart-icon-done">@include('theme::partials.icon', ['name' => 'check', 'class' => 'size-5', 'stroke' => 2.5])</span>
                        </button>
                    </form>
                @endif
            </div>
            <ol class="grid grid-cols-3 gap-3 sm:gap-4">
                @foreach ($routineSteps as $step)
                    @php
                        $stepPrice = $step['product']->displayPrice($step['product']->priceFrom());
                    @endphp
                    <li class="relative rounded-[1.5rem] bg-white p-2.5 text-ink sm:p-3">
                        <div class="relative aspect-square overflow-hidden rounded-[1rem] bg-t{{ $loop->iteration }}">
                            @include('theme::partials.product-image', ['url' => $step['product']->mainImageUrl(), 'alt' => $step['product']->name, 'class' => 'mix-blend-multiply'])
                            <span class="absolute left-2 top-2 grid size-8 place-items-center rounded-full bg-ac font-head text-sm text-onac" aria-hidden="true">{{ $loop->iteration }}</span>
                        </div>
                        <p class="mt-3 text-[11px] font-bold uppercase tracking-wider text-muted">{{ $step['step'] }}</p>
                        <a href="{{ $step['product']->url() }}" class="block text-sm font-semibold leading-tight after:absolute after:inset-0">{{ $step['product']->name }}</a>
                        @if ($stepPrice !== null)
                            <p class="mt-1 text-sm text-muted">{{ format_price($stepPrice) }}</p>
                        @endif
                    </li>
                @endforeach
            </ol>
        </div>
    </section>
@endif
