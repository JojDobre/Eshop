{{--
    Časté otázky: rozbaľovacie otázky s odpoveďami (formátovaný text). Rozloženie „side“ má nadpis vľavo a otázky
    vpravo, „stacked“ všetko pod sebou na stred. Pre vyhľadávače sa pridajú aj štruktúrované údaje FAQPage.
--}}
@php
    $faqItems = collect($block['items'] ?? [])->filter(fn ($item) => filled($item['question'] ?? null) && filled($item['answer'] ?? null))->values();
    $faqSide = ($block['layout'] ?? 'side') === 'side' && (filled($block['title'] ?? null) || filled($block['text'] ?? null));
@endphp
@if ($faqItems->isNotEmpty())
    <section id="{{ $blockId }}" @class(['block-section block-faq container-shop py-12', 'grid gap-8 lg:grid-cols-[1fr_1.6fr] lg:gap-16' => $faqSide])>
        @if (filled($block['title'] ?? null) || filled($block['text'] ?? null))
            <div @class(['mx-auto max-w-3xl text-center' => ! $faqSide])>
                @if (filled($block['title'] ?? null))
                    <h2 class="block-title text-2xl font-semibold tracking-tight sm:text-3xl">{{ $block['title'] }}</h2>
                @endif
                @if (filled($block['text'] ?? null))
                    <p class="mt-3 whitespace-pre-line text-zinc-600">{{ $block['text'] }}</p>
                @endif
            </div>
        @endif
        <div @class(['divide-y divide-zinc-200 border-y border-zinc-200', 'mx-auto mt-8 max-w-3xl' => ! $faqSide])>
            @foreach ($faqItems as $faqItem)
                <details class="group py-5" @if ($loop->first) open @endif>
                    <summary class="flex cursor-pointer list-none items-center justify-between gap-4 text-lg font-semibold [&::-webkit-details-marker]:hidden">
                        {{ $faqItem['question'] }}
                        <span class="text-2xl leading-none text-primary transition group-open:rotate-45" aria-hidden="true">+</span>
                    </summary>
                    <div class="prose prose-zinc mt-2 max-w-none text-zinc-600">{{ safe_html($faqItem['answer']) }}</div>
                </details>
            @endforeach
        </div>
    </section>
    <script type="application/ld+json">{!! json_encode([
        '@'.'context' => 'https://schema.org',
        '@type' => 'FAQPage',
        'mainEntity' => $faqItems->map(fn ($item) => [
            '@type' => 'Question',
            'name' => $item['question'],
            'acceptedAnswer' => ['@type' => 'Answer', 'text' => trim(strip_tags((string) $item['answer']))],
        ])->all(),
    ], JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE | JSON_HEX_TAG) !!}</script>
@endif
