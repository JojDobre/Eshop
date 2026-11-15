{{-- Recenzie: vlastné citáty z Prispôsobenia, inak schválené hodnotenia produktov (4 a 5 hviezdičiek). --}}
@php
    $quotes = collect(range(1, 3))
        ->map(fn ($i) => ['text' => trim((string) $themeOptions->get('review_'.$i.'_text')), 'author' => trim((string) $themeOptions->get('review_'.$i.'_author')), 'rating' => 5])
        ->filter(fn ($quote) => $quote['text'] !== '');

    if ($quotes->isEmpty() && class_exists(\Modules\Reviews\Models\Review::class) && ! empty($reviewsEnabled)) {
        $quotes = rescue(fn () => \Modules\Reviews\Models\Review::query()->approved()->where('rating', '>=', 4)->whereNotNull('body')->latest()->limit(3)->get()
            ->map(fn ($review) => ['text' => \Illuminate\Support\Str::limit($review->body, 220), 'author' => $review->author_name, 'rating' => $review->rating]), collect(), false);
    }
@endphp
@if ($quotes->isNotEmpty())
    <section class="{{ $wrap }} pb-16 sm:pb-20" aria-labelledby="home-reviews">
        <h2 id="home-reviews" class="dielna-h2 text-center">{{ $opt('reviews_title') }}</h2>
        <div class="mt-10 grid gap-5 md:grid-cols-3">
            @foreach ($quotes as $index => $quote)
                <figure @class(['rounded-2xl bg-white p-6 shadow-sm', 'md:-rotate-1' => $index === 1])>
                    <p class="text-accent" aria-label="{{ $quote['rating'] }}/5">{{ str_repeat('★', (int) $quote['rating']) }}</p>
                    <blockquote class="mt-3 font-serif text-lg leading-snug">„{{ $quote['text'] }}“</blockquote>
                    @if ($quote['author'] !== '')
                        <figcaption class="mt-4 text-sm text-muted">{{ $quote['author'] }}</figcaption>
                    @endif
                </figure>
            @endforeach
        </div>
    </section>
@endif
