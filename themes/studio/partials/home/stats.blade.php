{{-- Čísla o štúdiu: riadky „hodnota | popis“ z Prispôsobenia. --}}
@php
    $stats = collect(preg_split('/\R/', (string) $themeOptions->get('stats')))
        ->map(fn ($line) => array_map('trim', explode('|', $line, 2)))
        ->filter(fn ($parts) => $parts[0] !== '')->take(4);
@endphp
@if ($stats->isNotEmpty())
    <section class="{{ $wrap }} pb-6 pt-10">
        <dl class="grid grid-cols-2 gap-6 border-y border-line py-8 lg:grid-cols-4">
            @foreach ($stats as $stat)
                <div class="flex flex-col-reverse">
                    <dt class="text-sm text-muted">{{ $stat[1] ?? '' }}</dt>
                    <dd class="font-serif text-3xl sm:text-4xl">{{ $stat[0] }}</dd>
                </div>
            @endforeach
        </dl>
    </section>
@endif
