<div @class(['grid grid-cols-2 gap-x-4 gap-y-8 sm:grid-cols-3', 'lg:grid-cols-4' => ($columns ?? 4) === 4])>
    @foreach ($products as $product)
        @include('theme::partials.product-card', ['product' => $product])
    @endforeach
</div>
