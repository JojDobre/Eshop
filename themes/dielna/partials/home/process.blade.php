{{-- Ako to vzniká: tri kroky s fotkou na tmavom pozadí. --}}
@php
    $steps = collect(range(1, 3))->map(fn ($i) => ['image' => $themeOptions->imageUrl('step_'.$i.'_image'), 'title' => $opt('step_'.$i.'_title'), 'text' => trim((string) $themeOptions->get('step_'.$i.'_title')) !== '' ? trim((string) $themeOptions->get('step_'.$i.'_text')) : $opt('step_'.$i.'_text')]);
@endphp
<section class="bg-ink text-cream" aria-labelledby="home-process">
    <div class="{{ $layout === 'full' ? 'container-shop' : $wrap }} py-16 sm:py-20">
        <p class="dielna-kicker text-center !text-[color-mix(in_srgb,var(--brand-primary)_45%,white)]">{{ $opt('process_kicker') }}</p>
        <h2 id="home-process" class="dielna-h2 text-center">{{ $opt('process_title') }}</h2>
        <ol class="mt-12 grid gap-8 sm:grid-cols-3">
            @foreach ($steps as $index => $step)
                <li>
                    @if ($step['image'])
                        <img {{ responsive_img($step['image'], '(min-width: 640px) 33vw, 90vw') }} alt="" class="arch-sm aspect-[4/3] w-full object-cover" loading="lazy">
                    @endif
                    <p class="mt-4 font-hand text-3xl text-[color-mix(in_srgb,var(--brand-primary)_45%,white)]" aria-hidden="true">{{ $index + 1 }}.</p>
                    <h3 class="font-serif text-xl">{{ $step['title'] }}</h3>
                    @if ($step['text'] !== '')
                        <p class="mt-1 text-sm text-[color-mix(in_srgb,var(--dielna-bg)_75%,transparent)]">{{ $step['text'] }}</p>
                    @endif
                </li>
            @endforeach
        </ol>
    </div>
</section>
