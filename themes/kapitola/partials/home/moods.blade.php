{{-- Nálady: tri fotky s názvom a krátkym popisom, napr. „Romány na dlhé večery“. --}}
@php
    $moods = collect(range(1, 3))->map(fn ($i) => [
        'title' => trim((string) $themeOptions->get('mood_'.$i.'_title')),
        'text' => trim((string) $themeOptions->get('mood_'.$i.'_text')),
        'link' => trim((string) $themeOptions->get('mood_'.$i.'_link')),
        'image' => $themeOptions->imageUrl('mood_'.$i.'_image'),
    ])->filter(fn ($mood) => $mood['title'] !== '' && $mood['image']);
@endphp
@if ($moods->isNotEmpty())
    <section class="{{ $wrap }} pb-16">
        <ul @class(['grid gap-5', 'md:grid-cols-3' => $moods->count() === 3, 'md:grid-cols-2' => $moods->count() === 2])>
            @foreach ($moods as $mood)
                <li>
                    <a href="{{ $mood['link'] ?: '#' }}" class="kp-zoom group relative block aspect-[4/5] overflow-hidden rounded-[1.75rem] max-md:aspect-[16/11]">
                        <img {{ responsive_img($mood['image'], '(min-width: 768px) 33vw, 95vw') }} alt="" loading="lazy" class="absolute inset-0 size-full object-cover">
                        <span class="absolute inset-0 bg-gradient-to-t from-black/75 via-black/15"></span>
                        <span class="absolute inset-x-6 bottom-6 text-white">
                            <span class="kp-heading block text-3xl leading-tight">{{ $mood['title'] }}</span>
                            @if ($mood['text'] !== '')<span class="mt-1 block text-sm text-white/85">{{ $mood['text'] }}</span>@endif
                        </span>
                    </a>
                </li>
            @endforeach
        </ul>
    </section>
@endif
