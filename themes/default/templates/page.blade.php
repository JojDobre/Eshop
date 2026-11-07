@extends('theme::layouts.app')

@section('title', $page->meta_title ?: $page->name)
@section('meta_description', $page->meta_description ?: \Illuminate\Support\Str::limit(strip_tags((string) $page->content), 160))
@section('canonical', $page->url())

@section('content')
    <article class="container-shop max-w-3xl py-10">
        @include('theme::partials.breadcrumbs', ['items' => [[$page->name, null]]])
        <h1 class="mt-6 text-3xl font-semibold tracking-tight">{{ $page->name }}</h1>
        <div class="prose prose-zinc mt-8 max-w-none">{!! $page->content !!}</div>
    </article>
@endsection
