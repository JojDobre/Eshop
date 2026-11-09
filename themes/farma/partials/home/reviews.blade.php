{{-- Hodnotenia zákazníkov (4 a 5 hviezdičiek s textom). --}}
@if ($homeReviews->isNotEmpty())
    <section class="farma-section bg-cream py-14 sm:py-20" aria-labelledby="home-reviews">
        <div class="{{ $layout === 'narrow' ? 'container-shop max-w-4xl' : 'container-shop' }}">
            <h2 id="home-reviews" @class(['farma-h2', 'text-right' => $reverse])>{{ __('theme::farma.reviews_title') }}</h2>
            <div class="mt-8 grid gap-8 md:grid-cols-3">
                @foreach ($homeReviews as $homeReview)
                    <figure class="border-l-[3px] border-primary py-1 pl-5">
                        @include('theme::partials.rating-stars', ['rating' => $homeReview->rating, 'size' => 'text-sm'])
                        <blockquote class="mt-2.5 text-lg text-zinc-700">„{{ \Illuminate\Support\Str::limit($homeReview->body, 220) }}“</blockquote>
                        <figcaption class="mt-3 text-sm font-extrabold text-muted">{{ $homeReview->author_name }}</figcaption>
                    </figure>
                @endforeach
            </div>
        </div>
    </section>
@endif
