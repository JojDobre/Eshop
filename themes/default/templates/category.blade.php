@extends('theme::layouts.app')

@section('title', $category->meta_title ?: $category->name)
@section('meta_description', $category->meta_description ?: \Illuminate\Support\Str::limit(strip_tags((string) $category->description), 160))
@section('canonical', $category->url())

@section('content')
    <div class="container-shop py-8">
        @include('theme::partials.breadcrumbs', ['items' => [
            ...array_map(fn ($ancestor) => [$ancestor->name, $ancestor->url()], $category->ancestors()),
            [$category->name, null],
        ]])

        <header class="mt-6 max-w-3xl">
            <h1 class="text-3xl font-semibold tracking-tight">{{ $category->name }}</h1>
            @if ($category->description)
                <div class="prose prose-zinc mt-4 max-w-none text-zinc-600">{!! $category->description !!}</div>
            @endif
        </header>

        @if ($subcategories->isNotEmpty())
            <ul class="mt-6 flex flex-wrap gap-2">
                @foreach ($subcategories as $subcategory)
                    <li><a href="{{ $subcategory->url() }}" class="inline-block rounded-full border border-zinc-200 px-4 py-1.5 text-sm hover:border-zinc-400">{{ $subcategory->name }}</a></li>
                @endforeach
            </ul>
        @endif

        <div class="mt-10 flex flex-wrap items-center justify-between gap-4 border-b border-zinc-100 pb-4">
            <p class="text-sm text-zinc-500">{{ trans_choice('theme::shop.products_count', $products->total(), ['count' => $products->total()]) }}</p>
            @include('theme::partials.sort')
        </div>

        <div class="mt-8">
            @if ($products->isEmpty())
                <p class="py-16 text-center text-zinc-500">{{ __('theme::shop.no_products') }}</p>
            @else
                @include('theme::partials.product-grid', ['products' => $products])
                {{ $products->links('theme::partials.pagination') }}
            @endif
        </div>
    </div>
@endsection
