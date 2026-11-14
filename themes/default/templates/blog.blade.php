@extends('theme::layouts.app')

{{--
    Zoznam článkov. Rozloženie nastavíte v Vzhľad → Prispôsobiť → Blog: poradie sekcií, mriežka/zoznam/veľké karty,
    počet stĺpcov a bočný panel s kategóriami, štítkami a najnovšími článkami.
--}}
@php
    $blogHeading = isset($blogCategory) ? $blogCategory->name : (isset($blogTag) ? '#'.$blogTag->name : __('theme::blog.title'));
    $blogSections = $themeOptions->get('blog_index_sections', []) ?: [['section' => 'header'], ['section' => 'categories'], ['section' => 'posts']];
    $blogSidebar = $themeOptions->get('blog_sidebar', 'none');
    $blogLayout = $themeOptions->get('blog_list_layout', 'grid');
    $blogColumns = (int) $themeOptions->get('blog_columns', '3');
    // Zvýraznený článok iba na prvej strane zoznamu; v zozname sa potom neopakuje.
    $featuredPost = collect($blogSections)->contains('section', 'featured') && $posts->currentPage() === 1 ? $posts->first() : null;
    $listedPosts = $featuredPost ? $posts->getCollection()->slice(1) : $posts->getCollection();
@endphp

@section('title', $blogHeading)
@push('head')
    <link rel="alternate" type="application/rss+xml" title="{{ __('theme::blog.title') }}" href="{{ route('blog.rss') }}">
@endpush

@section('content')
    <div class="container-shop py-10">
        <div @class([
            'grid gap-10',
            'lg:grid-cols-[minmax(0,1fr)_17rem]' => $blogSidebar === 'right',
            'lg:grid-cols-[17rem_minmax(0,1fr)]' => $blogSidebar === 'left',
        ])>
            <div @class(['min-w-0 space-y-10', 'lg:order-2' => $blogSidebar === 'left'])>
                @foreach ($blogSections as $blogSection)
                    @switch($blogSection['section'] ?? '')
                        @case('header')
                            <header @class(['max-w-3xl', 'mx-auto text-center' => ($blogSection['layout'] ?? '') === 'reverse'])>
                                <h1 class="text-3xl font-semibold tracking-tight">{{ $blogHeading }}</h1>
                                @if (isset($blogCategory) && $blogCategory->description)
                                    <p class="mt-3 text-zinc-600">{{ $blogCategory->description }}</p>
                                @endif
                            </header>
                            @break

                        @case('categories')
                            @if ($blogCategories->isNotEmpty())
                                <nav @class(['flex flex-wrap gap-2', 'justify-center' => ($blogSection['layout'] ?? '') === 'reverse']) aria-label="{{ __('theme::blog.categories') }}">
                                    <a href="{{ route('blog.index') }}" @class(['blog-chip rounded-full border px-4 py-1.5 text-sm', 'is-active border-zinc-900 bg-zinc-900 text-white' => ! isset($blogCategory) && ! isset($blogTag), 'border-zinc-200 hover:border-zinc-400' => isset($blogCategory) || isset($blogTag)])>{{ __('theme::blog.all_posts') }}</a>
                                    @foreach ($blogCategories as $category)
                                        <a href="{{ $category->url() }}" @class(['blog-chip rounded-full border px-4 py-1.5 text-sm', 'is-active border-zinc-900 bg-zinc-900 text-white' => isset($blogCategory) && $blogCategory->is($category), 'border-zinc-200 hover:border-zinc-400' => ! (isset($blogCategory) && $blogCategory->is($category))])>{{ $category->name }}</a>
                                    @endforeach
                                </nav>
                            @endif
                            @break

                        @case('featured')
                            @if ($featuredPost)
                                <article class="group relative grid items-center gap-6 overflow-hidden rounded-2xl bg-zinc-50 lg:grid-cols-2">
                                    <div @class(['aspect-[16/10] h-full w-full bg-zinc-100', 'lg:order-2' => ($blogSection['layout'] ?? '') === 'reverse'])>
                                        @if ($featuredCover = $featuredPost->coverUrl())
                                            <img src="{{ $featuredCover }}" alt="" class="h-full w-full object-cover transition duration-500 group-hover:scale-[1.02]">
                                        @endif
                                    </div>
                                    <div class="p-6 sm:p-10">
                                        <p class="text-xs font-semibold uppercase tracking-wide text-primary">{{ __('theme::blog.featured') }}</p>
                                        <h2 class="blog-featured-title mt-2 text-2xl font-semibold tracking-tight sm:text-3xl">
                                            <a href="{{ $featuredPost->url() }}" class="after:absolute after:inset-0">{{ $featuredPost->name }}</a>
                                        </h2>
                                        @if ($themeOptions->get('blog_show_excerpt', true) && $featuredPost->excerpt)
                                            <p class="mt-3 line-clamp-4 text-zinc-600">{{ $featuredPost->excerpt }}</p>
                                        @endif
                                    </div>
                                </article>
                            @endif
                            @break

                        @case('posts')
                            @if ($posts->isEmpty())
                                <p class="text-zinc-600">{{ __('theme::blog.empty') }}</p>
                            @elseif ($listedPosts->isNotEmpty())
                                <div @class([
                                    'grid gap-10',
                                    'sm:grid-cols-2' => $blogLayout === 'grid' || $blogLayout === 'cards',
                                    'lg:grid-cols-3' => $blogLayout === 'grid' && $blogColumns >= 3 && $blogSidebar === 'none',
                                    'xl:grid-cols-4' => $blogLayout === 'grid' && $blogColumns >= 4 && $blogSidebar === 'none',
                                    'xl:grid-cols-3' => $blogLayout === 'grid' && $blogColumns >= 3 && $blogSidebar !== 'none',
                                ])>
                                    @foreach ($listedPosts as $post)
                                        @include('theme::partials.blog-card', ['post' => $post, 'cardLayout' => $blogLayout === 'cards' ? 'large' : $blogLayout])
                                    @endforeach
                                </div>
                            @endif
                            <div>{{ $posts->links('theme::partials.pagination') }}</div>
                            @break
                    @endswitch
                @endforeach
            </div>

            @if ($blogSidebar !== 'none')
                @include('theme::partials.blog.sidebar')
            @endif
        </div>
    </div>
@endsection
