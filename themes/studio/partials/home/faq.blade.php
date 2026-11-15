{{-- Časté otázky: rozbaľovacie odpovede (prvé tri majú predvolené texty, štvrtá iba ak je vyplnená). --}}
@php
    $questions = collect(range(1, 4))
        ->map(fn ($i) => $i <= 3
            ? ['q' => $opt('faq_'.$i.'_q'), 'a' => $opt('faq_'.$i.'_a')]
            : ['q' => trim((string) $themeOptions->get('faq_'.$i.'_q')), 'a' => trim((string) $themeOptions->get('faq_'.$i.'_a'))])
        ->filter(fn ($item) => $item['q'] !== '' && $item['a'] !== '');
@endphp
<section class="container-shop max-w-3xl pb-20" aria-labelledby="home-faq">
    <h2 id="home-faq" class="studio-h2 text-center">{{ $opt('faq_title') }}</h2>
    <div class="mt-8 divide-y divide-[var(--studio-line)] border-y border-line">
        @foreach ($questions as $question)
            <details class="group py-5" @if ($loop->first) open @endif>
                <summary class="flex cursor-pointer list-none items-center justify-between gap-4 text-lg font-semibold [&::-webkit-details-marker]:hidden">
                    {{ $question['q'] }}
                    <span class="grid size-8 shrink-0 place-items-center rounded-full border border-line transition group-open:rotate-45" aria-hidden="true">+</span>
                </summary>
                <p class="mt-3 leading-relaxed text-muted">{{ $question['a'] }}</p>
            </details>
        @endforeach
    </div>
</section>
