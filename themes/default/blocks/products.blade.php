@if (! empty($block['products']) && $block['products']->isNotEmpty())
    <section id="{{ $blockId }}" class="container-shop py-14">
        @if (! empty($block['title']))
            <h2 class="text-xl font-semibold tracking-tight">{{ $block['title'] }}</h2>
        @endif
        <div class="mt-6">
            @include('theme::partials.product-grid', ['products' => $block['products']])
        </div>
    </section>
@endif
