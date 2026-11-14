@if (! empty($block['posts']) && $block['posts']->isNotEmpty())
    <section id="{{ $blockId }}" class="block-section container-shop py-14">
        <div class="block-header flex items-baseline justify-between gap-4">
            <h2 class="block-title text-xl font-semibold tracking-tight">{{ $block['title'] ?? __('theme::blog.latest') }}</h2>
            @if (Route::has('blog.index'))
                <a href="{{ route('blog.index') }}" class="block-link text-sm font-medium text-primary hover:underline">{{ __('theme::blog.all_posts') }}</a>
            @endif
        </div>
        <div class="mt-6 grid gap-8 sm:grid-cols-2 lg:grid-cols-3">
            @foreach ($block['posts'] as $post)
                @include('theme::partials.blog-card', ['post' => $post])
            @endforeach
        </div>
    </section>
@endif
