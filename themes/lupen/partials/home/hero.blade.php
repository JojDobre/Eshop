{{--
    Úvod: nadpis, text a panel „Kedy chcete doručiť?“ (Dnes do uzávierky / Zajtra / Iný deň), vedľa fotka v tvare lupeňa
    s nálepkou a odpočtom do uzávierky. Nálepka aj odpočet sú vo vnútri fotky, aby na mobile nič nepresahovalo.
--}}
@php
    $heroImage = $themeOptions->imageUrl('hero_image');
    $heroLink = trim((string) $themeOptions->get('delivery_link')) ?: (! empty($homeCategories) && $homeCategories->isNotEmpty() ? $homeCategories->first()->url() : null);
    $cutoff = trim((string) $themeOptions->get('cutoff_time'));
    $deadline = preg_match('/^(\d{1,2}):(\d{2})$/', $cutoff, $parts) ? now()->setTime((int) $parts[1], (int) $parts[2]) : null;
    $todayOpen = $deadline !== null && now()->lt($deadline);
    $minutesLeft = $todayOpen ? (int) now()->diffInMinutes($deadline) : 0;
    $area = trim((string) $themeOptions->get('delivery_area'));
@endphp
<section @class([$wrap, 'grid items-center gap-10 pt-8 sm:pt-12', 'lg:grid-cols-2' => $heroImage]) aria-labelledby="home-hero">
    <div @class(['lg:order-2' => $reverse && $heroImage])>
        <span class="inline-flex items-center gap-2 rounded-full bg-soft2 px-3 py-1.5 text-sm font-semibold">@include('theme::partials.icon', ['name' => 'leaf', 'class' => 'size-4']){{ $opt('hero_badge') }}</span>
        <h1 id="home-hero" class="mt-5 text-[2.8rem] leading-[1.03] sm:text-6xl lg:text-7xl">{{ $opt('hero_title') }}</h1>
        <p class="mt-5 max-w-md text-lg text-muted">{{ $opt('hero_text') }}</p>

        @if ($themeOptions->get('show_delivery', true) && $heroLink)
            <div class="leaf-sm mt-7 max-w-lg bg-card p-4 shadow-[0_20px_50px_-35px_rgba(0,0,0,.4)] ring-1 ring-line">
                <p class="text-sm font-bold">{{ __('theme::lupen.delivery_question') }}@if ($area !== '') <span class="font-normal text-muted">· {{ $area }}</span>@endif</p>
                <div class="mt-3 grid grid-cols-3 gap-2 text-center text-sm">
                    @if ($deadline !== null)
                        <a href="{{ $heroLink }}" @class(['rounded-2xl py-2.5', 'bg-ac text-onac' => $todayOpen, 'text-muted ring-1 ring-line' => ! $todayOpen]) @unless ($todayOpen) aria-disabled="true" @endunless>
                            <b class="block">{{ __('theme::lupen.today') }}</b><span class="text-xs opacity-90">{{ __('theme::lupen.today_until', ['time' => $cutoff]) }}</span>
                        </a>
                    @endif
                    <a href="{{ $heroLink }}" @class(['rounded-2xl py-2.5', 'bg-ac text-onac' => ! $todayOpen, 'ring-1 ring-line' => $todayOpen])>
                        <b class="block">{{ __('theme::lupen.tomorrow') }}</b><span @class(['text-xs', 'opacity-90' => ! $todayOpen, 'text-muted' => $todayOpen])>{{ \Illuminate\Support\Str::ucfirst(now()->addDay()->locale(app()->getLocale())->dayName) }}</span>
                    </a>
                    <a href="{{ $heroLink }}" class="rounded-2xl py-2.5 ring-1 ring-line @if ($deadline === null) col-span-2 @endif"><b class="block">{{ __('theme::lupen.other_day') }}</b><span class="text-xs text-muted">{{ __('theme::lupen.other_day_note') }}</span></a>
                </div>
                <a href="{{ $heroLink }}" class="btn btn-ink mt-3 w-full">{{ __('theme::lupen.choose_bouquet') }}</a>
            </div>
        @endif
    </div>

    @if ($heroImage)
        <div @class(['relative', 'lg:order-1' => $reverse])>
            <img {{ responsive_img($heroImage, '(min-width: 1024px) 45vw, 95vw') }} alt="" class="leaf aspect-[4/5] w-full object-cover" fetchpriority="high">
            <span class="petal absolute right-3 top-3 grid size-24 place-items-center bg-ac2 p-3.5 text-center font-head text-[13px] leading-tight text-onac2 shadow-xl [text-wrap:balance] sm:right-6 sm:top-6 sm:size-28 sm:p-3 sm:text-lg">{{ $opt('hero_sticker') }}</span>
            @if ($deadline !== null)
                <div class="absolute bottom-4 left-4 rounded-2xl bg-card px-4 py-3 shadow-xl ring-1 ring-line sm:bottom-6 sm:left-6">
                    <p class="flex items-center gap-2 text-sm font-bold">@include('theme::partials.icon', ['name' => 'truck', 'class' => 'size-4']){{ $todayOpen ? __('theme::lupen.delivery_today') : __('theme::lupen.delivery_tomorrow') }}</p>
                    <p class="text-xs text-muted">{{ $todayOpen ? __('theme::lupen.time_left', ['hours' => intdiv($minutesLeft, 60), 'minutes' => $minutesLeft % 60]) : __('theme::lupen.today_until', ['time' => $cutoff]) }}</p>
                </div>
            @endif
        </div>
    @endif
</section>
