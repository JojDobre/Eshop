{{-- Pozvánka na farmu: fotka a tmavý panel s dátumom, nadpisom, programom a tlačidlom rezervácie. --}}
@php
    $eventTitle = trim((string) $themeOptions->get('event_title'));
    $eventImage = $themeOptions->imageUrl('event_image');
    $eventPoints = collect(preg_split('/\R/', (string) $themeOptions->get('event_points')))->map(fn ($line) => trim($line))->filter()->take(6);
    $eventLink = trim((string) $themeOptions->get('event_link'));
    $eventButton = trim((string) $themeOptions->get('event_button'));
    $eventNote = trim((string) $themeOptions->get('event_note'));
    $eventDate = trim((string) $themeOptions->get('event_date'));
    $eventText = trim((string) $themeOptions->get('event_text'));
@endphp
@if ($eventTitle !== '')
    <section class="{{ $wrap }} pb-16" aria-labelledby="home-event">
        <div @class(['grid overflow-hidden rounded-[2rem] bg-deep text-white', 'lg:grid-cols-2' => $eventImage])>
            @if ($eventImage)
                <img {{ responsive_img($eventImage, '(min-width: 1024px) 45vw, 95vw') }} alt="" loading="lazy" @class(['aspect-[16/11] size-full object-cover lg:aspect-auto', 'lg:order-2' => $reverse])>
            @endif
            <div class="p-8 sm:p-12">
                @if ($eventDate !== '')
                    <p class="inline-flex items-center gap-2 rounded-full bg-white/10 px-3 py-1.5 text-sm font-bold">@include('theme::partials.icon', ['name' => 'calendar', 'class' => 'size-4']){{ $eventDate }}</p>
                @endif
                <h2 id="home-event" class="dv-h2 mt-5 !text-white">{{ $eventTitle }}</h2>
                @if ($eventText !== '')<p class="mt-4 max-w-md text-white/80">{{ $eventText }}</p>@endif
                @if ($eventPoints->isNotEmpty())
                    <ul class="mt-6 grid gap-2 text-[15px] font-semibold sm:grid-cols-2">
                        @foreach ($eventPoints as $point)
                            <li class="flex items-center gap-2"><span class="grid size-6 shrink-0 place-items-center rounded-full bg-sun text-onsun">@include('theme::partials.icon', ['name' => 'check', 'class' => 'size-3.5', 'stroke' => 3])</span>{{ $point }}</li>
                        @endforeach
                    </ul>
                @endif
                @if ($eventLink !== '' && $eventButton !== '')
                    <div class="mt-8 flex flex-wrap items-center gap-4">
                        <a href="{{ $eventLink }}" class="btn btn-sun">{{ $eventButton }}</a>
                        @if ($eventNote !== '')<span class="text-sm text-white/70">{{ $eventNote }}</span>@endif
                    </div>
                @endif
            </div>
        </div>
    </section>
@endif
