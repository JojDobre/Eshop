{{-- Recenzie: citáty z Prispôsobenia, inak schválené hodnotenia produktov (4 a 5 hviezdičiek). --}}
@php
    $quotes = collect(range(1, 3))
        ->map(fn ($i) => ['text' => trim((string) $themeOptions->get('review_'.$i.'_text')), 'author' => trim((string) $themeOptions->get('review_'.$i.'_author'))])
        ->filter(fn ($quote) => $quote['text'] !== '');

    if ($quotes->isEmpty() && class_exists(\Modules\Reviews\Models\Review::class) && ! empty($reviewsEnabled)) {
        $quotes = rescue(fn () => \Modules\Reviews\Models\Review::query()->approved()->where('rating', '>=', 4)->whereNotNull('body')->latest()->limit(3)->get()
            ->map(fn ($review) => ['text' => \Illuminate\Support\Str::limit($review->body, 200), 'author' => $review->author_name]), collect(), false);
    }

    $reviewsSummary = trim((string) $themeOptions->get('reviews_summary'));
@endphp
@if ($quotes->isNotEmpty())
    <section class="{{ $wrap }} pb-16 sm:pb-20" aria-labelledby="home-reviews">
        <div class="flex flex-wrap items-end justify-between gap-4">
            <div>
                <p class="eyebrow">{{ __('theme::studio.reviews_kicker') }}</p>
                <h2 id="home-reviews" class="studio-h2 mt-3">{{ $opt('reviews_title') }}</h2>
            </div>
            @if ($reviewsSummary !== '')
                <p class="text-sm"><span class="text-gold" aria-hidden="true">★★★★★</span> {{ $reviewsSummary }}</p>
            @endif
        </div>
        <div class="mt-10 grid gap-5 md:grid-cols-3">
            @foreach ($quotes as $quote)
                <figure class="rounded-3xl bg-white p-7">
                    <blockquote class="font-serif text-2xl leading-snug">„{{ $quote['text'] }}“</blockquote>
                    @if ($quote['author'] !== '')
                        <figcaption class="mt-5 text-sm text-muted">{{ $quote['author'] }}</figcaption>
                    @endif
                </figure>
            @endforeach
        </div>
    </section>
@endif
