{{-- Predplatné kvetov: farebný blok s možnosťami (druhá je zvýraznená), tlačidlom a fotkou. --}}
@php
    $subLink = trim((string) $themeOptions->get('sub_link'));
    $subImage = $themeOptions->imageUrl('sub_image');
    $plans = collect(preg_split('/\R/', (string) $themeOptions->get('sub_plans')))
        ->map(fn ($line) => array_map('trim', explode('|', $line, 3)))->filter(fn ($parts) => $parts[0] !== '')->take(3)->values();
@endphp
@if ($subLink !== '')
    <section class="{{ $wrap }} py-10" aria-labelledby="home-subscription">
        <div @class(['leaf grid items-center overflow-hidden bg-ac text-onac', 'lg:grid-cols-[1fr_1.1fr]' => $subImage])>
            <div @class(['p-8 sm:p-12', 'lg:order-2' => $reverse && $subImage])>
                <p class="text-sm font-bold uppercase tracking-[0.2em] opacity-80">{{ $opt('sub_label') }}</p>
                <h2 id="home-subscription" class="mt-3 text-4xl leading-[1.03] sm:text-6xl">{{ $opt('sub_title') }}</h2>
                <p class="mt-4 max-w-md opacity-90">{{ $opt('sub_text') }}</p>
                @if ($plans->isNotEmpty())
                    <div class="mt-7 grid gap-3 sm:grid-cols-3">
                        @foreach ($plans as $plan)
                            <div @class(['rounded-2xl p-4', 'bg-white text-[#2a0f1c]' => $loop->index === 1, 'bg-white/10' => $loop->index !== 1])>
                                <p class="text-sm font-semibold opacity-80">{{ $plan[0] }}</p>
                                @if (($plan[1] ?? '') !== '')<p class="mt-1 font-head text-3xl">{{ $plan[1] }}</p>@endif
                                @if (($plan[2] ?? '') !== '')<p class="text-xs opacity-75">{{ $plan[2] }}</p>@endif
                            </div>
                        @endforeach
                    </div>
                @endif
                <a href="{{ $subLink }}" class="btn btn-ac2 mt-7">{{ $opt('sub_button') }}</a>
            </div>
            @if ($subImage)
                <img {{ responsive_img($subImage, '(min-width: 1024px) 50vw, 95vw') }} alt="" class="h-72 w-full object-cover lg:h-full" loading="lazy">
            @endif
        </div>
    </section>
@endif
