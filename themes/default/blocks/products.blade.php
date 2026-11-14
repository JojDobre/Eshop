@if (! empty($block['products']) && $block['products']->isNotEmpty())
    <section id="{{ $blockId }}" class="block-section container-shop py-14">
        @if (! empty($block['title']) || ! empty($block['link']))
            <div class="block-header flex items-end justify-between gap-4">
                @if (! empty($block['title']))
                    <h2 class="block-title text-xl font-semibold tracking-tight">{{ $block['title'] }}</h2>
                @endif
                @if (! empty($block['link']))
                    <a href="{{ $block['link'] }}" class="block-link shrink-0 text-sm font-medium text-primary hover:underline">{{ __('theme::shop.show_all') }}</a>
                @endif
            </div>
        @endif
        <div class="mt-6">
            @include('theme::partials.product-grid', ['products' => $block['products']])
        </div>
    </section>
@endif
