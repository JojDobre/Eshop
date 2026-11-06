@extends('theme::layouts.app')

@section('content')
    <section class="mx-auto max-w-6xl px-4 py-24 sm:px-6 sm:py-32">
        <div class="max-w-2xl">
            <h1 class="text-4xl font-semibold tracking-tight sm:text-5xl">{{ $shop->shop_name }}</h1>
            @if ($shop->tagline)
                <p class="mt-6 text-lg text-zinc-600">{{ $shop->tagline }}</p>
            @endif

            <div class="mt-10 inline-flex items-center gap-3 rounded-full border border-zinc-200 px-4 py-2 text-sm text-zinc-600">
                <span class="size-2 shrink-0 rounded-full bg-primary" aria-hidden="true"></span>
                {{ __('theme::shop.coming_soon') }}
            </div>
        </div>
    </section>
@endsection
