@extends('theme::layouts.app')

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

    <article>
        <header class="container-shop pt-10">
            <div class="mx-auto max-w-3xl">
                @include('theme::partials.breadcrumbs', ['items' => array_values(array_filter([
                    [__('theme::blog.title'), route('blog.index')],
                    $post->category ? [$post->category->name, $post->category->url()] : null,
                    [$post->name, null],
                ]))])
                <h1 class="mt-6 text-4xl font-semibold tracking-tight">{{ $post->name }}</h1>
                <p class="mt-4 text-sm text-zinc-500">
                    @if ($post->published_at){{ $post->published_at->format(app()->getLocale() === 'en' ? 'j M Y' : 'j. n. Y') }} · @endif
                    {{ trans_choice('theme::blog.reading_time', $post->readingMinutes(), ['count' => $post->readingMinutes()]) }}
                    @if ($post->author) · {{ $post->author->name }} @endif
                </p>
                @if ($post->excerpt)
                    <p class="mt-6 text-lg text-zinc-700">{{ $post->excerpt }}</p>
                @endif
            </div>
            @if ($post->coverUrl())
                <img src="{{ $post->coverUrl() }}" alt="" class="mx-auto mt-8 max-h-[32rem] w-full max-w-5xl rounded-2xl object-cover">
            @endif
        </header>

        @if (count($toc) >= 2)
            <nav class="container-shop mt-10" aria-labelledby="toc-heading">
                <div class="mx-auto max-w-3xl rounded-xl bg-zinc-50 p-5 text-sm">
                    <p id="toc-heading" class="font-semibold">{{ __('theme::blog.toc') }}</p>
                    <ol class="mt-2 space-y-1">
                        @foreach ($toc as $heading)
                            <li @class(['pl-4' => $heading['level'] === 3])><a href="#{{ $heading['id'] }}" class="text-zinc-700 hover:text-zinc-900 hover:underline">{{ $heading['text'] }}</a></li>
                        @endforeach
                    </ol>
                </div>
            </nav>
        @endif

        <div class="mt-4">{!! render_blocks($blocks) !!}</div>

        @if ($post->products->isNotEmpty())
            <section class="container-shop py-10" aria-labelledby="post-products">
                <h2 id="post-products" class="text-xl font-semibold tracking-tight">{{ __('theme::blog.recommended_products') }}</h2>
                <div class="mt-6">@include('theme::partials.product-grid', ['products' => $post->products])</div>
            </section>
        @endif

        @if ($post->tags->isNotEmpty())
            <div class="container-shop">
                <ul class="mx-auto flex max-w-3xl flex-wrap gap-2">
                    @foreach ($post->tags as $tag)
                        <li><a href="{{ $tag->url() }}" class="inline-block rounded-full bg-zinc-100 px-3 py-1 text-sm text-zinc-700 hover:bg-zinc-200">#{{ $tag->name }}</a></li>
                    @endforeach
                </ul>
            </div>
        @endif
    </article>

    @if ($commentsOpen || $post->comments->isNotEmpty())
        <section id="komentare" class="container-shop py-12">
            <div class="mx-auto max-w-3xl">
                <h2 class="text-xl font-semibold tracking-tight">{{ __('theme::blog.comments') }} ({{ $post->comments->count() }})</h2>
                @if (session('comment_status'))
                    <p role="status" class="mt-4 rounded-lg bg-emerald-50 px-4 py-3 text-sm text-emerald-800">{{ session('comment_status') }}</p>
                @endif
                <ul class="mt-6 space-y-6">
                    @foreach ($post->comments as $comment)
                        <li class="rounded-xl border border-zinc-100 p-4">
                            <p class="text-sm font-medium">{{ $comment->name }} <span class="font-normal text-zinc-500">· {{ $comment->created_at->format(app()->getLocale() === 'en' ? 'j M Y' : 'j. n. Y') }}</span></p>
                            <p class="mt-2 whitespace-pre-line text-zinc-700">{{ $comment->content }}</p>
                        </li>
                    @endforeach
                </ul>

                @if ($commentsOpen)
                    <form method="POST" action="{{ route('blog.comment', $post->slug) }}" class="mt-8 space-y-4">
                        @csrf
                        <input type="hidden" name="form_token" value="{{ \Illuminate\Support\Facades\Crypt::encryptString((string) time()) }}">
                        {{-- Skryté pole proti robotom (človek ho nevidí ani nevyplní). --}}
                        <div class="hidden" aria-hidden="true"><label>Web <input type="text" name="website" tabindex="-1" autocomplete="off"></label></div>
                        <div class="grid gap-4 sm:grid-cols-2">
                            @include('theme::partials.field', ['name' => 'name', 'label' => __('theme::blog.comment_name'), 'required' => true])
                            @include('theme::partials.field', ['name' => 'email', 'label' => __('theme::blog.comment_email'), 'type' => 'email', 'required' => true])
                        </div>
                        <div>
                            <label for="comment-content" class="block text-sm font-medium">{{ __('theme::blog.comment_text') }}</label>
                            <textarea id="comment-content" name="content" rows="4" required maxlength="3000" class="input mt-1">{{ old('content') }}</textarea>
                            @error('content') <p class="field-error">{{ $message }}</p> @enderror
                        </div>
                        <p class="text-xs text-zinc-500">{{ __('theme::blog.comment_privacy') }}</p>
                        <button type="submit" class="btn btn-primary">{{ __('theme::blog.comment_submit') }}</button>
                    </form>
                @endif
            </div>
        </section>
    @endif

    @include('theme::partials.related-posts', ['relatedPosts' => $relatedPosts, 'relatedTitle' => __('theme::blog.more_posts')])
@endsection
