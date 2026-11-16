{{-- Dni rozvozu a odberu: lístky s polkruhovými výrezmi, striedavo v dvoch jemných farbách. --}}
@php
    $icons = ['truck', 'barn', 'pin', 'calendar', 'egg', 'leaf'];
    $days = collect(preg_split('/\R/', (string) $themeOptions->get('delivery_days')))
        ->map(fn ($line) => array_map('trim', explode('|', $line, 3)))
        ->filter(fn ($parts) => $parts[0] !== '')->take(4)->values();
@endphp
@if ($days->isNotEmpty())
    <section class="{{ $wrap }}" aria-label="{{ __('theme::dvor.notice') }}">
        <ul @class(['grid gap-3 sm:grid-cols-2', 'md:grid-cols-4' => $days->count() === 4, 'md:grid-cols-3' => $days->count() === 3])>
            @foreach ($days as $day)
                <li @class(['ticket flex items-center gap-4 px-6 py-5', 'bg-soft' => $loop->even === false, 'bg-soft2' => $loop->even])>
                    <span class="grid size-12 shrink-0 place-items-center rounded-2xl bg-card">@include('theme::partials.icon', ['name' => in_array($day[2] ?? '', $icons, true) ? $day[2] : 'truck'])</span>
                    <span class="min-w-0"><b class="block text-[15px]">{{ $day[0] }}</b>@if (($day[1] ?? '') !== '')<span class="text-sm text-muted">{{ $day[1] }}</span>@endif</span>
                </li>
            @endforeach
        </ul>
    </section>
@endif
