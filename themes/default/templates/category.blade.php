@extends('theme::layouts.app')

@php($seo = \App\Settings\SeoSettings::current())
@php($seoVariables = ['name' => $category->name, 'category' => $category->parent?->name, 'shop' => $shop->shop_name])
@section('title', $category->meta_title ?: (\App\Settings\SeoSettings::render($seo?->category_title, $seoVariables) ?? $category->name))
@section('meta_description', $category->meta_description ?: (\App\Settings\SeoSettings::render($seo?->category_description, $seoVariables) ?? \Illuminate\Support\Str::limit(strip_tags((string) $category->description), 160)))
@section('canonical', $category->url())
{{-- Filtrovaný alebo zoradený výpis sa neindexuje (stránkovanie áno). --}}
@if (($seo?->noindex_filtered ?? true) && array_diff(array_keys(request()->query()), ['page']) !== [])
    @section('noindex', true)
@endif

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

        {{-- Bloky kategórie (Katalóg → Kategórie → Bloky) nad zoznamom produktov, na celú šírku. --}}
        @if (! empty($category->blocks))
            </div>
            {!! render_blocks($category->blocks) !!}
            <div class="container-shop pb-8">
        @endif

        <div @class(['mt-10', 'lg:grid lg:grid-cols-4 lg:gap-10' => ! empty($filters)])>
            @if (! empty($filters))
                <aside class="mb-8 lg:mb-0" x-data="{ open: false }">
                    <button type="button" class="btn btn-secondary w-full lg:hidden" @click="open = ! open" :aria-expanded="open.toString()">
                        {{ __('catalog::shop.filters') }}@if (! empty($selectedFilters)) ({{ count($selectedFilters) }})@endif
                    </button>
                    <div class="mt-4 lg:mt-0 lg:block" :class="open ? 'block' : 'hidden'">
                        <h2 class="mb-4 hidden text-sm font-semibold lg:block">{{ __('catalog::shop.filters') }}</h2>
                        @include('theme::partials.filters')
                    </div>
                </aside>
            @endif

            <div @class(['lg:col-span-3' => ! empty($filters)])>
                <div class="flex flex-wrap items-center justify-between gap-4 border-b border-zinc-100 pb-4">
                    <p class="text-sm text-zinc-500">{{ trans_choice('theme::shop.products_count', $products->total(), ['count' => $products->total()]) }}</p>
                    @include('theme::partials.sort')
                </div>

                <div class="mt-8">
                    @if ($products->isEmpty())
                        <p class="py-16 text-center text-zinc-500">{{ __('theme::shop.no_products') }}</p>
                    @else
                        @include('theme::partials.product-grid', ['products' => $products, 'columns' => empty($filters) ? 4 : 3])
                        {{ $products->links('theme::partials.pagination') }}
                    @endif
                </div>
            </div>
        </div>
    </div>
    @include('theme::partials.related-posts', ['relatedPosts' => $relatedPosts ?? null])
@endsection
