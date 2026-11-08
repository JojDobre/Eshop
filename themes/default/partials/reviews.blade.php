{{-- Hodnotenia produktu (modul Hodnotenia): súhrn, zoznam a formulár. --}}
@php($r = $productReviews)
<section id="hodnotenia" class="mt-16 border-t border-zinc-100 pt-10" aria-labelledby="reviews-heading">
    <h2 id="reviews-heading" class="text-2xl font-semibold tracking-tight">{{ __('reviews::shop.heading') }}</h2>

    @if (session('review_status'))
        <p role="status" class="mt-4 rounded-lg bg-emerald-50 px-4 py-3 text-sm text-emerald-800">{{ session('review_status') }}</p>
    @endif

    <div class="mt-6 grid gap-10 lg:grid-cols-3">
        <div>
            @if ($r['count'] > 0)
                <p class="flex items-center gap-3">
                    <span class="text-4xl font-semibold">{{ number_format($r['average'], 1, ',', '') }}</span>
                    @include('theme::partials.rating-stars', ['rating' => $r['average'], 'size' => 'text-xl'])
                </p>
                <p class="mt-1 text-sm text-zinc-500">{{ trans_choice('reviews::shop.count', $r['count'], ['count' => $r['count']]) }}</p>
                <ul class="mt-4 space-y-1 text-sm">
                    @foreach ($r['distribution'] as $stars => $total)
                        <li class="flex items-center gap-2">
                            <span class="w-6 text-zinc-600">{{ $stars }}★</span>
                            <span class="h-1.5 flex-1 overflow-hidden rounded-full bg-zinc-100"><span class="block h-full rounded-full bg-amber-400" style="width: {{ $r['count'] ? round($total / $r['count'] * 100) : 0 }}%"></span></span>
                            <span class="w-8 text-right text-zinc-500">{{ $total }}</span>
                        </li>
                    @endforeach
                </ul>
            @else
                <p class="text-sm text-zinc-500">{{ __('reviews::shop.none') }}</p>
            @endif
            <p class="mt-4 text-xs text-zinc-500">{{ $r['verificationInfo'] }}</p>
        </div>

        <div class="space-y-6 lg:col-span-2">
            @foreach ($r['items'] as $review)
                <article class="border-b border-zinc-100 pb-6">
                    <div class="flex flex-wrap items-center gap-x-3 gap-y-1">
                        @include('theme::partials.rating-stars', ['rating' => $review->rating])
                        @if ($review->title)<h3 class="font-medium">{{ $review->title }}</h3>@endif
                    </div>
                    <p class="mt-1 text-xs text-zinc-500">
                        {{ $review->author_name }} · {{ $review->created_at->format(app()->getLocale() === 'en' ? 'j M Y' : 'j. n. Y') }}
                        @if ($review->isVerified()) · <span class="font-medium text-emerald-700">{{ __('reviews::shop.verified') }}</span>@endif
                    </p>
                    @if ($review->body)<p class="mt-2 whitespace-pre-line text-sm text-zinc-700">{{ $review->body }}</p>@endif
                    @if ($review->pros || $review->cons)
                        <dl class="mt-2 grid gap-2 text-sm sm:grid-cols-2">
                            @if ($review->pros)<div><dt class="font-medium text-emerald-700">{{ __('reviews::shop.pros') }}</dt><dd class="whitespace-pre-line text-zinc-700">{{ $review->pros }}</dd></div>@endif
                            @if ($review->cons)<div><dt class="font-medium text-red-700">{{ __('reviews::shop.cons') }}</dt><dd class="whitespace-pre-line text-zinc-700">{{ $review->cons }}</dd></div>@endif
                        </dl>
                    @endif
                    @if ($review->reply)
                        <div class="mt-3 rounded-lg bg-zinc-50 p-3 text-sm"><p class="font-medium">{{ __('reviews::shop.reply') }}</p><p class="mt-1 whitespace-pre-line text-zinc-700">{{ $review->reply }}</p></div>
                    @endif
                </article>
            @endforeach

            @if ($r['canReview'])
                @include('reviews::form', ['action' => $r['action'], 'guest' => ! auth('customer')->check(), 'formId' => 'review-form'])
            @elseif ($r['verifiedOnly'])
                <p class="text-sm text-zinc-500">{{ __('reviews::shop.only_buyers') }}</p>
            @endif
        </div>
    </div>
</section>
