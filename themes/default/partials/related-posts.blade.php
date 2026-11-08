{{-- Súvisiace články (pri produkte, v kategórii, pod článkom). --}}
@if (! empty($relatedPosts) && $relatedPosts->isNotEmpty())
    <section class="container-shop py-12" aria-labelledby="related-posts">
        <h2 id="related-posts" class="text-xl font-semibold tracking-tight">{{ $relatedTitle ?? __('theme::blog.related') }}</h2>
        <div class="mt-6 grid gap-8 sm:grid-cols-2 lg:grid-cols-3">
            @foreach ($relatedPosts as $post)
                @include('theme::partials.blog-card', ['post' => $post])
            @endforeach
        </div>
    </section>
@endif
