@extends('theme::layouts.app')

{{--
    Článok. Hlavičku (klasická, obrázok cez celú šírku, obrázok vedľa nadpisu, bez obrázka), šírku textu a poradie
    sekcií nastavíte v Vzhľad → Prispôsobiť → Blog.
--}}
@php
    $postLayout = $themeOptions->get('blog_post_layout', 'classic');
    $postWidth = $themeOptions->get('blog_post_width', 'narrow') === 'wide' ? 'max-w-5xl' : 'max-w-3xl';
    $postSections = $themeOptions->get('blog_post_sections', []) ?: collect(['toc', 'content', 'products', 'tags', 'comments', 'related'])->map(fn ($section) => ['section' => $section])->all();
    $postCover = $postLayout === 'minimal' ? null : $post->coverUrl();
    $postDate = $post->published_at?->format(app()->getLocale() === 'en' ? 'j M Y' : 'j. n. Y');
@endphp

@section('title', $post->meta_title ?: $post->name)
@section('meta_description', $post->meta_description ?: ($post->excerpt ?: \Illuminate\Support\Str::limit($post->plainText(), 160)))
@if ($preview)
    @section('noindex', true)
@else
    @section('canonical', $post->url())
@endif
@if ($post->coverUrl())
    @section('og_image', $post->coverUrl())
@endif

@section('content')
    @if ($preview)
        <div class="bg-amber-100 px-4 py-2 text-center text-sm text-amber-900">{{ __('theme::blog.preview_notice') }}</div>
    @endif

    <article class="blog-post">
        @php
            $postMeta = trim(($postDate ? $postDate.' · ' : '').trans_choice('theme::blog.reading_time', $post->readingMinutes(), ['count' => $post->readingMinutes()]).($post->author ? ' · '.$post->author->name : ''));
            $postBreadcrumbs = array_values(array_filter([
                [__('theme::blog.title'), route('blog.index')],
                $post->category ? [$post->category->name, $post->category->url()] : null,
                [$post->name, null],
            ]));
        @endphp

        @if ($postLayout === 'hero' && $postCover)
            {{-- Obrázok cez celú šírku, nadpis na ňom. --}}
            <header class="relative isolate overflow-hidden bg-zinc-900 text-white">
                <img src="{{ $postCover }}" alt="" class="absolute inset-0 -z-10 h-full w-full object-cover opacity-60">
                <div class="absolute inset-0 -z-10 bg-gradient-to-t from-black/70 via-black/30 to-transparent"></div>
                <div class="container-shop flex min-h-[22rem] items-end py-12 sm:min-h-[30rem]">
                    <div class="mx-auto w-full {{ $postWidth }}">
                        <h1 class="text-3xl font-semibold tracking-tight sm:text-5xl">{{ $post->name }}</h1>
                        <p class="mt-4 text-sm text-white/80">{{ $postMeta }}</p>
                    </div>
                </div>
            </header>
            @if ($post->excerpt)
                <div class="container-shop pt-10"><p class="mx-auto {{ $postWidth }} text-lg text-zinc-700">{{ $post->excerpt }}</p></div>
            @endif
        @elseif ($postLayout === 'split' && $postCover)
            {{-- Obrázok vedľa nadpisu. --}}
            <header class="container-shop pt-10">
                @include('theme::partials.breadcrumbs', ['items' => $postBreadcrumbs])
                <div class="mt-6 grid items-center gap-8 lg:grid-cols-2">
                    <div>
                        <h1 class="text-3xl font-semibold tracking-tight sm:text-4xl">{{ $post->name }}</h1>
                        <p class="mt-4 text-sm text-zinc-500">{{ $postMeta }}</p>
                        @if ($post->excerpt)
                            <p class="mt-6 text-lg text-zinc-700">{{ $post->excerpt }}</p>
                        @endif
                    </div>
                    <img src="{{ $postCover }}" alt="" class="aspect-[4/3] w-full rounded-2xl object-cover">
                </div>
            </header>
        @else
            {{-- Klasická hlavička: nadpis, pod ním obrázok (pri „bez obrázka“ iba text). --}}
            <header class="container-shop pt-10">
                <div class="mx-auto {{ $postWidth }}">
                    @include('theme::partials.breadcrumbs', ['items' => $postBreadcrumbs])
                    <h1 class="mt-6 text-4xl font-semibold tracking-tight">{{ $post->name }}</h1>
                    <p class="mt-4 text-sm text-zinc-500">{{ $postMeta }}</p>
                    @if ($post->excerpt)
                        <p class="mt-6 text-lg text-zinc-700">{{ $post->excerpt }}</p>
                    @endif
                </div>
                @if ($postCover)
                    <img src="{{ $postCover }}" alt="" class="mx-auto mt-8 max-h-[32rem] w-full max-w-5xl rounded-2xl object-cover">
                @endif
            </header>
        @endif

        @foreach ($postSections as $postSection)
            @switch($postSection['section'] ?? '')
                @case('toc')
                    @if (count($toc) >= 2)
                        <nav class="container-shop mt-10" aria-labelledby="toc-heading">
                            <div class="mx-auto {{ $postWidth }} rounded-xl bg-zinc-50 p-5 text-sm">
                                <p id="toc-heading" class="font-semibold">{{ __('theme::blog.toc') }}</p>
                                <ol class="mt-2 space-y-1">
                                    @foreach ($toc as $heading)
                                        <li @class(['pl-4' => $heading['level'] === 3])><a href="#{{ $heading['id'] }}" class="text-zinc-700 hover:text-zinc-900 hover:underline">{{ $heading['text'] }}</a></li>
                                    @endforeach
                                </ol>
                            </div>
                        </nav>
                    @endif
                    @break

                @case('content')
                    <div @class(['blog-post-content mt-4', 'blog-post-wide' => $postWidth === 'max-w-5xl'])>{!! render_blocks($blocks) !!}</div>
                    @break

                @case('products')
                    @if ($post->products->isNotEmpty())
                        <section class="container-shop py-10" aria-labelledby="post-products">
                            <h2 id="post-products" class="block-title text-xl font-semibold tracking-tight">{{ __('theme::blog.recommended_products') }}</h2>
                            <div class="mt-6">@include('theme::partials.product-grid', ['products' => $post->products])</div>
                        </section>
                    @endif
                    @break

                @case('tags')
                    @if ($post->tags->isNotEmpty())
                        <div class="container-shop py-4">
                            <ul class="mx-auto flex {{ $postWidth }} flex-wrap gap-2">
                                @foreach ($post->tags as $tag)
                                    <li><a href="{{ $tag->url() }}" class="inline-block rounded-full bg-zinc-100 px-3 py-1 text-sm text-zinc-700 hover:bg-zinc-200">#{{ $tag->name }}</a></li>
                                @endforeach
                            </ul>
                        </div>
                    @endif
                    @break

                @case('comments')
                    @include('theme::partials.blog.post-comments')
                    @break

                @case('related')
                    @include('theme::partials.related-posts', ['relatedPosts' => $relatedPosts, 'relatedTitle' => __('theme::blog.more_posts')])
                    @break
            @endswitch
        @endforeach
    </article>
@endsection
