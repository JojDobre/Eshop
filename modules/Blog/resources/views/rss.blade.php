{!! '<'.'?xml version="1.0" encoding="UTF-8"?>' !!}
<rss version="2.0" xmlns:atom="http://www.w3.org/2005/Atom">
    <channel>
        <title>{{ $shop->shop_name }} – {{ __('theme::blog.title') }}</title>
        <link>{{ route('blog.index') }}</link>
        <description>{{ $shop->tagline ?: __('theme::blog.title') }}</description>
        <language>{{ app()->getLocale() }}</language>
        <atom:link href="{{ route('blog.rss') }}" rel="self" type="application/rss+xml" />
        @foreach ($posts as $post)
            <item>
                <title>{{ $post->name }}</title>
                <link>{{ $post->url() }}</link>
                <guid isPermaLink="true">{{ $post->url() }}</guid>
                <pubDate>{{ $post->published_at->toRfc2822String() }}</pubDate>
                @if ($post->excerpt)
                    <description>{{ $post->excerpt }}</description>
                @endif
            </item>
        @endforeach
    </channel>
</rss>
