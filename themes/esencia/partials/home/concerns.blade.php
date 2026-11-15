{{-- Rýchly výber podľa potreby: riadok tlačidiel „text | odkaz“. --}}
@php
    $concerns = collect(preg_split('/\R/', (string) $themeOptions->get('concerns')))
        ->map(fn ($line) => array_map('trim', explode('|', $line, 2)))
        ->filter(fn ($parts) => $parts[0] !== '')->take(10);
@endphp
@if ($concerns->isNotEmpty())
    <nav class="{{ $wrap }} pt-6" aria-label="{{ $opt('concerns_title') }}">
        <div class="-mx-4 flex items-center gap-2 overflow-x-auto px-4 pb-2 sm:mx-0 sm:flex-wrap sm:px-0">
            <span class="shrink-0 py-2 pr-2 text-sm font-semibold">{{ $opt('concerns_title') }}</span>
            @foreach ($concerns as $concern)
                <a href="{{ $concern[1] ?? '#' }}" class="es-chip shrink-0">{{ $concern[0] }}</a>
            @endforeach
        </div>
    </nav>
@endif
