@extends('theme::layouts.app')

@section('title', $page->meta_title ?: $page->name)
@section('meta_description', $page->meta_description ?: \Illuminate\Support\Str::limit(strip_tags((string) $page->content), 160))
@section('canonical', $page->url())
@isset($version)
    @section('noindex', true)
@endisset

@section('content')
    @php
        $pageDisplay = $page->display ?? [];
        $pageHeader = isset($version) ? 'title' : ($pageDisplay['header'] ?? 'title');
        $pageWidth = ($pageDisplay['width'] ?? 'narrow') === 'wide' ? 'max-w-5xl' : 'max-w-3xl';
        $pageBreadcrumbs = $pageDisplay['breadcrumbs'] ?? true;
        $pageHeroImage = ! empty($pageDisplay['hero_image']) ? \Illuminate\Support\Facades\Storage::disk('public')->url($pageDisplay['hero_image']) : null;
    @endphp

    @if ($pageHeader === 'hero')
        @include('theme::partials.page-hero', ['heroTitle' => $page->name, 'heroText' => $pageDisplay['hero_text'] ?? null, 'heroImage' => $pageHeroImage, 'heroHeight' => $pageDisplay['hero_height'] ?? 'medium'])
    @endif

    @if ($pageHeader === 'none')
        <h1 class="sr-only">{{ $page->name }}</h1>
    @endif

    @if (isset($version) || filled(strip_tags((string) $page->content)) || $pageHeader === 'title' || ($pageBreadcrumbs && $pageHeader !== 'hero'))
        <article class="container-shop {{ $pageWidth }} py-10">
            @if ($pageBreadcrumbs)
                @include('theme::partials.breadcrumbs', ['items' => [[$page->name, null]]])
            @endif
            @isset($version)
                <h1 class="mt-6 text-3xl font-semibold tracking-tight">{{ $version->name }}</h1>
                <p class="mt-4 rounded-xl bg-zinc-50 p-4 text-sm text-zinc-700">
                    {{ __('theme::shop.document_version', ['version' => $version->version, 'date' => $version->created_at->translatedFormat('j. n. Y')]) }}
                    <a href="{{ $page->url() }}" class="underline underline-offset-2">{{ __('theme::shop.document_current') }}</a>
                </p>
                <div class="prose prose-zinc mt-8 max-w-none">{{ safe_html($version->content) }}</div>
            @else
                @if ($pageHeader === 'title')
                    <h1 class="mt-6 text-3xl font-semibold tracking-tight">{{ $page->name }}</h1>
                @endif
                @if (filled(strip_tags((string) $page->content, '<img><iframe>')))
                    <div @class(['prose prose-zinc max-w-none', 'mt-8' => $pageHeader === 'title' || $pageBreadcrumbs])>{{ safe_html($page->content) }}</div>
                @endif
            @endisset
        </article>
    @endif

    {{-- Bloky stránky (Obsah → Stránky → Bloky) pod textom. --}}
    @if (! isset($version) && ! empty($page->blocks))
        {!! render_blocks($page->blocks) !!}
    @endif
@endsection
