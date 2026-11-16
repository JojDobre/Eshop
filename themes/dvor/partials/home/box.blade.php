{{-- Debnička: fotka v tvare stodoly s nálepkou, text a veľkosti debničky (druhá zvýraznená), na konci vlnka. --}}
@php
    $boxImage = $themeOptions->imageUrl('box_image');
    $boxBadge = trim((string) $themeOptions->get('box_badge'));
    $boxLink = trim((string) $themeOptions->get('box_link'));
    $plans = collect(preg_split('/\R/', (string) $themeOptions->get('box_plans')))
        ->map(fn ($line) => array_map('trim', explode('|', $line, 3)))
        ->filter(fn ($parts) => $parts[0] !== '')->take(3)->values();
@endphp
<section class="bg-soft2" aria-labelledby="home-box">
    <div @class(['container-shop grid items-center gap-10 py-16', 'lg:grid-cols-2' => $boxImage])>
        @if ($boxImage)
            <div @class(['relative', 'lg:order-2' => $reverse])>
                <img {{ responsive_img($boxImage, '(min-width: 1024px) 45vw, 95vw') }} alt="" loading="lazy" class="barn aspect-[5/4] w-full object-cover">
                @if ($boxBadge !== '')
                    <span class="absolute right-4 top-[26%] max-w-[60%] rotate-6 rounded-2xl bg-ac2 px-4 py-2 font-head text-lg leading-tight text-onac2 shadow-lg sm:right-8 sm:text-xl">{{ $boxBadge }}</span>
                @endif
            </div>
        @endif
        <div class="min-w-0">
            <p class="dv-kicker !text-[var(--dv-actext)]">{{ $opt('box_kicker') }}</p>
            <h2 id="home-box" class="dv-h2 mt-2">{{ $opt('box_title') }}</h2>
            <p class="mt-4 max-w-lg text-muted">{{ $opt('box_text') }}</p>
            @if ($plans->isNotEmpty())
                <ul @class(['mt-6 grid gap-3', 'sm:grid-cols-3' => $plans->count() === 3, 'sm:grid-cols-2' => $plans->count() === 2])>
                    @foreach ($plans as $plan)
                        <li @class(['dv-card p-4', '!shadow-[0_0_0_2px_var(--dv-ac)]' => $loop->index === 1])>
                            <span class="flex items-center justify-between gap-2">
                                <b class="font-head text-xl">{{ $plan[0] }}</b>
                                @if ($loop->index === 1)
                                    <span class="grid size-6 place-items-center rounded-full bg-ac text-onac">@include('theme::partials.icon', ['name' => 'check', 'class' => 'size-4', 'stroke' => 3])</span>
                                @endif
                            </span>
                            @if (($plan[1] ?? '') !== '')<span class="mt-1 block text-sm text-muted">{{ $plan[1] }}</span>@endif
                            @if (($plan[2] ?? '') !== '')<span class="mt-3 block text-lg font-extrabold">{{ $plan[2] }}</span>@endif
                        </li>
                    @endforeach
                </ul>
            @endif
            @if ($boxLink !== '')
                <a href="{{ $boxLink }}" class="btn btn-ac mt-6">{{ $opt('box_button') }}</a>
            @endif
        </div>
    </div>
</section>
<div class="dv-wave h-7" aria-hidden="true"></div>
