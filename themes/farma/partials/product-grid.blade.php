@php($gridColumns = (int) ($columns ?? $themeOptions->get('products_per_row', 4)))
<div @class(['grid grid-cols-2 gap-x-3 gap-y-8 sm:gap-x-6 sm:gap-y-10', 'lg:grid-cols-4' => $gridColumns === 4, 'lg:grid-cols-3' => $gridColumns !== 4])>
    @foreach ($products as $product)
        @include('theme::partials.product-card', ['product' => $product])
    @endforeach
</div>
