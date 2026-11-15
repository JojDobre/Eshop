{{-- Recenzie: citáty z Prispôsobenia (s produktom), inak schválené hodnotenia produktov. Označenie „Overený nákup“. --}}
@php
    $quotes = collect(range(1, 3))
        ->map(fn ($i) => ['text' => trim((string) $themeOptions->get('review_'.$i.'_text')), 'author' => trim((string) $themeOptions->get('review_'.$i.'_author')), 'product' => trim((string) $themeOptions->get('review_'.$i.'_product')), 'rating' => 5])
        ->filter(fn ($quote) => $quote['text'] !== '');

    if ($quotes->isEmpty() && class_exists(\Modules\Reviews\Models\Review::class) && ! empty($reviewsEnabled)) {
        $quotes = rescue(fn () => \Modules\Reviews\Models\Review::query()->approved()->with('product')->where('rating', '>=', 4)->whereNotNull('body')->latest()->limit(3)->get()
            ->map(fn ($review) => ['text' => \Illuminate\Support\Str::limit($review->body, 200), 'author' => $review->author_name, 'product' => (string) $review->product?->name, 'rating' => $review->rating]), collect(), false);
    }

    $summary = trim((string) $themeOptions->get('reviews_summary'));
@endphp
@if ($quotes->isNotEmpty())
    <section class="{{ $wrap }} py-10" aria-labelledby="home-reviews">
        <div class="flex flex-wrap items-end justify-between gap-4">
            <h2 id="home-reviews" class="es-h2">{{ $opt('reviews_title') }}</h2>
            @if ($summary !== '')
                <p class="flex items-center gap-2 rounded-full bg-soft px-4 py-2 text-sm"><span class="text-[#f5a300]" aria-hidden="true">★★★★★</span>{{ $summary }}</p>
            @endif
        </div>
        <div class="mt-6 grid gap-4 md:grid-cols-3">
            @foreach ($quotes as $quote)
                <figure class="rounded-[1.75rem] border-2 border-line p-7">
                    <p class="text-lg text-[#f5a300]" aria-label="{{ $quote['rating'] }}/5">{{ str_repeat('★', (int) $quote['rating']) }}</p>
                    <blockquote class="mt-3 text-lg font-medium leading-snug">„{{ $quote['text'] }}“</blockquote>
                    <figcaption class="mt-5 text-sm">
                        @if ($quote['author'] !== '')<b class="block">{{ $quote['author'] }}</b>@endif
                        <span class="flex items-center gap-1 text-xs font-semibold text-ac">@include('theme::partials.icon', ['name' => 'check', 'class' => 'size-3.5', 'stroke' => 2.5]){{ __('theme::esencia.verified') }}@if ($quote['product'] !== '') · {{ $quote['product'] }}@endif</span>
                    </figcaption>
                </figure>
            @endforeach
        </div>
    </section>
@endif
