{{-- Ako funguje rezervácia: tri kroky na jemnom podklade. --}}
<section class="{{ $wrap }} pb-16 sm:pb-20" aria-labelledby="home-steps">
    <div class="rounded-[2.5rem] bg-blush px-6 py-12 sm:px-12">
        <h2 id="home-steps" class="studio-h2 text-center">{{ $opt('steps_title') }}</h2>
        <ol class="mt-10 grid gap-8 text-center sm:grid-cols-3">
            @foreach (range(1, 3) as $step)
                <li>
                    <span class="mx-auto grid size-14 place-items-center rounded-full bg-white font-serif text-2xl" aria-hidden="true">{{ $step }}</span>
                    <p class="mt-4 font-semibold">{{ $opt('step_'.$step.'_title') }}</p>
                    <p class="mt-1 text-sm text-muted">{{ $opt('step_'.$step.'_text') }}</p>
                </li>
            @endforeach
        </ol>
    </div>
</section>
