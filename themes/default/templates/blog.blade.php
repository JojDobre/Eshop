@extends('theme::layouts.app')

@php($blogHeading = isset($blogCategory) ? $blogCategory->name : (isset($blogTag) ? '#'.$blogTag->name : __('theme::blog.title')))

@section('title', $blogHeading)
@push('head')
    <link rel="alternate" type="application/rss+xml" title="{{ __('theme::blog.title') }}" href="{{ route('blog.rss') }}">
@endpush

@section('content')
    <div class="container-shop py-10">
        <header class="max-w-3xl">
            <h1 class="text-3xl font-semibold tracking-tight">{{ $blogHeading }}</h1>
            @if (isset($blogCategory) && $blogCategory->description)
                <p class="mt-3 text-zinc-600">{{ $blogCategory->description }}</p>
            @endif
        </header>

        @if ($blogCategories->isNotEmpty())
            <nav class="mt-6 flex flex-wrap gap-2" aria-label="{{ __('theme::blog.categories') }}">
                <a href="{{ route('blog.index') }}" @class(['rounded-full border px-4 py-1.5 text-sm', 'border-zinc-900 bg-zinc-900 text-white' => ! isset($blogCategory) && ! isset($blogTag), 'border-zinc-200 hover:border-zinc-400' => isset($blogCategory) || isset($blogTag)])>{{ __('theme::blog.all_posts') }}</a>
                @foreach ($blogCategories as $category)
                    <a href="{{ $category->url() }}" @class(['rounded-full border px-4 py-1.5 text-sm', 'border-zinc-900 bg-zinc-900 text-white' => isset($blogCategory) && $blogCategory->is($category), 'border-zinc-200 hover:border-zinc-400' => ! (isset($blogCategory) && $blogCategory->is($category))])>{{ $category->name }}</a>
                @endforeach
            </nav>
        @endif

        @if ($posts->isEmpty())
            <p class="mt-10 text-zinc-600">{{ __('theme::blog.empty') }}</p>
        @else
            <div class="mt-10 grid gap-10 sm:grid-cols-2 lg:grid-cols-3">
                @foreach ($posts as $post)
                    @include('theme::partials.blog-card', ['post' => $post])
                @endforeach
            </div>
            <div class="mt-10">{{ $posts->links('theme::partials.pagination') }}</div>
        @endif
    </div>
@endsection
