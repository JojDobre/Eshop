{{-- Kontakt: adresa s tlačidlami Navigovať a Zavolať, otváracie hodiny a mapa (odkaz na mapy, bez načítania cudzích skriptov). --}}
@php
    $contactAddress = trim((string) $themeOptions->get('contact_address')) ?: ($company->hasAddress() ? $company->street.', '.trim($company->zip.' '.$company->city) : '');
    $contactText = trim((string) $themeOptions->get('contact_text'));
    $hours = collect(preg_split('/\R/', (string) $themeOptions->get('opening_hours')))
        ->map(fn ($line) => array_map('trim', explode('|', $line, 2)))
        ->filter(fn ($parts) => $parts[0] !== '');
    $mapsUrl = $contactAddress !== '' ? 'https://www.google.com/maps/search/?api=1&query='.rawurlencode($contactAddress) : null;
@endphp
<section class="bg-stone" aria-labelledby="home-contact">
    <div @class([$layout === 'full' ? 'container-shop' : $wrap, 'grid gap-10 py-16', 'lg:grid-cols-3' => $hours->isNotEmpty() && $mapsUrl, 'lg:grid-cols-2' => ! ($hours->isNotEmpty() && $mapsUrl)])>
        <div>
            <p class="eyebrow">{{ __('theme::studio.contact_kicker') }}</p>
            <h2 id="home-contact" class="studio-h2 mt-3">{{ $opt('contact_title') }}</h2>
            @if ($contactAddress !== '' || $contactText !== '')
                <p class="mt-4 leading-relaxed text-muted">{{ $contactAddress }}@if ($contactAddress !== '' && $contactText !== '') – @endif{{ $contactText }}</p>
            @endif
            <div class="mt-6 flex flex-wrap gap-3">
                @if ($mapsUrl)
                    <a href="{{ $mapsUrl }}" target="_blank" rel="noopener" class="btn btn-pine">{{ __('theme::studio.navigate') }}</a>
                @endif
                @if ($shop->contact_phone)
                    <a href="tel:{{ preg_replace('/\s+/', '', $shop->contact_phone) }}" class="btn btn-ghost">{{ __('theme::studio.call') }}</a>
                @endif
            </div>
        </div>

        @if ($hours->isNotEmpty())
            <div class="rounded-3xl bg-white p-7">
                <p class="font-serif text-2xl">{{ __('theme::studio.opening_hours') }}</p>
                <dl class="mt-4 space-y-2.5 text-[15px]">
                    @foreach ($hours as $hour)
                        <div class="flex justify-between gap-4"><dt class="text-muted">{{ $hour[0] }}</dt><dd class="text-right font-semibold">{{ $hour[1] ?? '' }}</dd></div>
                    @endforeach
                </dl>
            </div>
        @endif

        @if ($mapsUrl)
            <a href="{{ $mapsUrl }}" target="_blank" rel="noopener" class="relative block min-h-64 overflow-hidden rounded-3xl bg-[color-mix(in_srgb,var(--brand-primary)_12%,white)]" aria-label="{{ __('theme::studio.navigate') }}: {{ $contactAddress }}">
                <svg class="absolute inset-0 size-full" viewBox="0 0 400 300" preserveAspectRatio="xMidYMid slice" aria-hidden="true"><g stroke="color-mix(in srgb, var(--brand-primary) 18%, white)" stroke-width="10" fill="none"><path d="M-10 80 L410 120" /><path d="M60 -10 L120 310" /><path d="M-10 230 L410 200" /><path d="M260 -10 L300 310" /></g><path d="M-10 160 Q200 120 410 170" stroke="#fff" stroke-width="4" fill="none" /></svg>
                <span class="absolute left-1/2 top-1/2 grid size-12 -translate-x-1/2 -translate-y-full place-items-center rounded-full bg-pine text-white shadow-lg" aria-hidden="true">
                    <svg class="size-5" fill="currentColor" viewBox="0 0 24 24"><path d="M12 2a7 7 0 0 0-7 7c0 5.2 7 13 7 13s7-7.8 7-13a7 7 0 0 0-7-7zm0 9.5A2.5 2.5 0 1 1 12 6.5a2.5 2.5 0 0 1 0 5z" /></svg>
                </span>
                <span class="absolute bottom-4 left-4 rounded-full bg-white px-4 py-2 text-sm font-semibold shadow">{{ $contactAddress }}</span>
            </a>
        @endif
    </div>
</section>
