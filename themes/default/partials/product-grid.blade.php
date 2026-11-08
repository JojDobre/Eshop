@php($gridColumns = (int) ($columns ?? $themeOptions->get('products_per_row', 4)))
<div @class(['grid grid-cols-2 gap-x-4 gap-y-8 sm:grid-cols-3', 'lg:grid-cols-4' => $gridColumns === 4])>
    @foreach ($products as $product)
        @include('theme::partials.product-card', ['product' => $product])
    @endforeach
</div>
