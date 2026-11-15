{{-- Najnovšie články z blogu (iba so zapnutým modulom Blog). --}}
@php
    $posts = Route::has('blog.index') && class_exists(\Modules\Blog\Models\Post::class)
        ? rescue(fn () => \Modules\Blog\Models\Post::query()->published()->with('category')->latest('published_at')->limit(3)->get(), collect(), false)
        : collect();
@endphp
@if ($posts->isNotEmpty())
    <section class="{{ $wrap }} py-10" aria-labelledby="home-blog">
        <div class="flex items-end justify-between gap-4">
            <h2 id="home-blog" class="es-h2">{{ $opt('blog_title') }}</h2>
            <a href="{{ route('blog.index') }}" class="es-link">{{ __('theme::esencia.blog_link') }}</a>
        </div>
        <div class="mt-6 grid gap-6 md:grid-cols-3">
            @foreach ($posts as $post)
                <a href="{{ $post->url() }}" class="es-zoom group">
                    <span class="relative block aspect-[4/3] overflow-hidden rounded-[1.75rem] bg-t{{ $loop->iteration }}">
                        @if ($cover = $post->coverUrl())
                            <img {{ responsive_img($cover, '(min-width: 768px) 30vw, 95vw') }} alt="" class="size-full object-cover" loading="lazy">
                        @endif
                        @if ($post->category)
                            <span class="absolute left-4 top-4 rounded-full bg-white px-3 py-1 text-xs font-bold">{{ $post->category->name }}</span>
                        @endif
                    </span>
                    <span class="mt-4 block text-xl font-semibold leading-tight group-hover:underline">{{ $post->name }}</span>
                </a>
            @endforeach
        </div>
    </section>
@endif
