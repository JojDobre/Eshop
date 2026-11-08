{{--
    Ukážka výberu šablóny podľa obsahu: tento súbor sa použije iba pre stránku s adresou „o-nas“.
    Ostatné stránky používajú templates/page.blade.php z rodičovskej šablóny.
--}}
@extends('theme::layouts.app')

@section('title', $page->meta_title ?: $page->name)

@section('content')
    <article class="container-shop py-12">
        @if ($themeOptions->get('show_note'))
            <p class="starter-note">{{ __('theme::starter.note') }}</p>
        @endif
        <h1 class="mt-6 text-4xl font-semibold tracking-tight">{{ $page->name }}</h1>
        <div class="prose prose-zinc mt-8 max-w-none">{!! $page->content !!}</div>
        {!! render_blocks($page->blocks) !!}
    </article>
@endsection
