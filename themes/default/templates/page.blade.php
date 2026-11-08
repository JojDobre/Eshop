@extends('theme::layouts.app')

@section('title', $page->meta_title ?: $page->name)
@section('meta_description', $page->meta_description ?: \Illuminate\Support\Str::limit(strip_tags((string) $page->content), 160))
@section('canonical', $page->url())
@isset($version)
    @section('noindex', true)
@endisset

@section('content')
    <article class="container-shop max-w-3xl py-10">
        @include('theme::partials.breadcrumbs', ['items' => [[$page->name, null]]])
        @isset($version)
            <h1 class="mt-6 text-3xl font-semibold tracking-tight">{{ $version->name }}</h1>
            <p class="mt-4 rounded-xl bg-zinc-50 p-4 text-sm text-zinc-700">
                {{ __('theme::shop.document_version', ['version' => $version->version, 'date' => $version->created_at->translatedFormat('j. n. Y')]) }}
                <a href="{{ $page->url() }}" class="underline underline-offset-2">{{ __('theme::shop.document_current') }}</a>
            </p>
            <div class="prose prose-zinc mt-8 max-w-none">{{ safe_html($version->content) }}</div>
        @else
            <h1 class="mt-6 text-3xl font-semibold tracking-tight">{{ $page->name }}</h1>
            <div class="prose prose-zinc mt-8 max-w-none">{{ safe_html($page->content) }}</div>
        @endisset
    </article>

    {{-- Bloky stránky (Obsah → Stránky → Bloky) pod textom. --}}
    @if (! isset($version) && ! empty($page->blocks))
        {!! render_blocks($page->blocks) !!}
    @endif
@endsection
