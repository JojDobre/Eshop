{{-- Bočný panel blogu: kategórie, štítky a najnovšie články. --}}
@php
    $sidebarTags = class_exists(\Modules\Blog\Models\PostTag::class)
        ? \Modules\Blog\Models\PostTag::query()->whereHas('posts', fn ($query) => $query->published())->limit(20)->get()
        : collect();
    $sidebarLatest = \Modules\Blog\Models\Post::query()->published()->latest('published_at')->limit(4)->get();
@endphp
<aside class="blog-sidebar space-y-8 lg:sticky lg:top-24 lg:self-start" aria-label="{{ __('theme::blog.title') }}">
    @if ($blogCategories->isNotEmpty())
        <section>
            <h2 class="text-sm font-semibold uppercase tracking-wide text-zinc-500">{{ __('theme::blog.categories') }}</h2>
            <ul class="mt-3 space-y-1.5">
                @foreach ($blogCategories as $category)
                    <li><a href="{{ $category->url() }}" @class(['text-zinc-700 hover:text-primary', 'font-semibold text-primary' => isset($blogCategory) && $blogCategory->is($category)])>{{ $category->name }}</a></li>
                @endforeach
            </ul>
        </section>
    @endif

    @if ($sidebarLatest->isNotEmpty())
        <section>
            <h2 class="text-sm font-semibold uppercase tracking-wide text-zinc-500">{{ __('theme::blog.latest') }}</h2>
            <ul class="mt-3 space-y-3">
                @foreach ($sidebarLatest as $latestPost)
                    <li>
                        <a href="{{ $latestPost->url() }}" class="group flex items-center gap-3">
                            @if ($latestCover = $latestPost->coverUrl())
                                <img src="{{ $latestCover }}" alt="" loading="lazy" class="size-14 shrink-0 rounded-lg object-cover">
                            @endif
                            <span class="text-sm font-medium text-zinc-800 group-hover:text-primary">{{ $latestPost->name }}</span>
                        </a>
                    </li>
                @endforeach
            </ul>
        </section>
    @endif

    @if ($sidebarTags->isNotEmpty())
        <section>
            <h2 class="text-sm font-semibold uppercase tracking-wide text-zinc-500">{{ __('theme::blog.tags') }}</h2>
            <ul class="mt-3 flex flex-wrap gap-2">
                @foreach ($sidebarTags as $tag)
                    <li><a href="{{ $tag->url() }}" class="inline-block rounded-full bg-zinc-100 px-3 py-1 text-sm text-zinc-700 hover:bg-zinc-200">#{{ $tag->name }}</a></li>
                @endforeach
            </ul>
        </section>
    @endif
</aside>
