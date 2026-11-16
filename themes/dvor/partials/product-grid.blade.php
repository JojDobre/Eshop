@php($gridColumns = (int) ($columns ?? $themeOptions->get('products_per_row', 4)))
<div @class(['grid grid-cols-1 gap-5 min-[480px]:grid-cols-2 [&>*]:min-w-0', 'lg:grid-cols-4' => $gridColumns === 4, 'lg:grid-cols-3' => $gridColumns !== 4])>
    @foreach ($products as $product)
        @include('theme::partials.product-card', ['product' => $product])
    @endforeach
</div>
