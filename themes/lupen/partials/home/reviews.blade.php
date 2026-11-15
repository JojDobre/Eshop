{{-- Recenzie s fotkami doručených kytíc (z Prispôsobenia), inak schválené hodnotenia produktov. --}}
@php
    $quotes = collect(range(1, 3))
        ->map(fn ($i) => ['text' => trim((string) $themeOptions->get('review_'.$i.'_text')), 'author' => trim((string) $themeOptions->get('review_'.$i.'_author')), 'image' => $themeOptions->imageUrl('review_'.$i.'_image'), 'rating' => 5])
        ->filter(fn ($quote) => $quote['text'] !== '');

    if ($quotes->isEmpty() && class_exists(\Modules\Reviews\Models\Review::class) && ! empty($reviewsEnabled)) {
        $quotes = rescue(fn () => \Modules\Reviews\Models\Review::query()->approved()->with('product')->where('rating', '>=', 4)->whereNotNull('body')->latest()->limit(3)->get()
            ->map(fn ($review) => ['text' => \Illuminate\Support\Str::limit($review->body, 200), 'author' => $review->author_name, 'image' => $review->product?->mainImageUrl(), 'rating' => $review->rating]), collect(), false);
    }

    $summary = trim((string) $themeOptions->get('reviews_summary'));
@endphp
@if ($quotes->isNotEmpty())
    <section class="{{ $wrap }} pb-16 pt-10" aria-labelledby="home-reviews">
        <div class="flex flex-wrap items-end justify-between gap-4">
            <h2 id="home-reviews" class="lp-h2">{{ $opt('reviews_title') }}</h2>
            @if ($summary !== '')
                <p class="rounded-full bg-soft px-4 py-2 text-sm font-semibold"><span class="text-[#f5a300]" aria-hidden="true">★★★★★</span> {{ $summary }}</p>
            @endif
        </div>
        <div class="mt-8 grid gap-4 md:grid-cols-3">
            @foreach ($quotes as $quote)
                <figure class="leaf-sm bg-soft p-5 sm:p-6">
                    @if ($quote['image'])
                        <img {{ responsive_img($quote['image'], '(min-width: 768px) 30vw, 90vw') }} alt="" class="leaf-sm aspect-[4/3] w-full object-cover" loading="lazy">
                    @endif
                    <p class="mt-4 text-[#f5a300]" aria-label="{{ $quote['rating'] }}/5">{{ str_repeat('★', (int) $quote['rating']) }}</p>
                    <blockquote class="mt-2 text-lg font-medium leading-snug">„{{ $quote['text'] }}“</blockquote>
                    @if ($quote['author'] !== '')<figcaption class="mt-3 text-sm text-muted">{{ $quote['author'] }}</figcaption>@endif
                </figure>
            @endforeach
        </div>
    </section>
@endif
