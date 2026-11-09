{{-- O nás a výhody (nadpisy a texty výhod sa dajú upraviť v prispôsobení šablóny). --}}
@php
    $benefitIcons = [
        'box' => 'M3 7l9-4 9 4v10l-9 4-9-4zM3 7l9 4 9-4M12 11v10',
        'leaf' => 'M12 3c4 0 7 7 7 11a7 7 0 0 1-14 0c0-4 3-11 7-11z',
        'chat' => 'M4 5h16v11H8l-4 4z',
        'home' => 'M3 11l9-7 9 7v9H3zM9 20v-6h6v6',
        'truck' => 'M3 6h11v10H3zM14 10h4l3 3v3h-7M7 18a2 2 0 1 0 0-.01M17 18a2 2 0 1 0 0-.01',
        'star' => 'M12 3l2.8 5.7 6.2.9-4.5 4.4 1 6.2L12 17.3 6.5 20.2l1-6.2L3 9.6l6.2-.9z',
        'heart' => 'M12 20s-7-4.5-7-10a4 4 0 0 1 7-2.6A4 4 0 0 1 19 10c0 5.5-7 10-7 10z',
        'shield' => 'M12 3l8 3v6c0 4.5-3.4 8.3-8 9-4.6-.7-8-4.5-8-9V6z',
        'clock' => 'M12 21a9 9 0 1 0 0-18 9 9 0 0 0 0 18zM12 7v5l3 2',
        'gift' => 'M4 10h16v10H4zM3 7h18v3H3zM12 7v13M12 7c-1.5-3-5-3-5-1s3 1 5 1c2 0 5 1 5-1s-3.5-2-5 1',
    ];
    $defaultBenefits = ['packing', 'fresh', 'advice', 'own'];
    $benefits = collect(range(1, 4))->map(function (int $i) use ($themeOptions, $defaultBenefits) {
        $title = trim((string) $themeOptions->get('benefit_'.$i.'_title'));
        $text = trim((string) $themeOptions->get('benefit_'.$i.'_text'));

        return [
            'icon' => $themeOptions->get('benefit_'.$i.'_icon', 'box'),
            'title' => $title !== '' ? $title : __('theme::farma.benefit_'.$defaultBenefits[$i - 1]),
            'text' => $title !== '' ? $text : ($text !== '' ? $text : __('theme::farma.benefit_'.$defaultBenefits[$i - 1].'_text')),
            'hidden' => $title === '-',
        ];
    })->reject(fn ($benefit) => $benefit['hidden']);
    $aboutImage = $themeOptions->imageUrl('about_image');
@endphp
@if (filled($themeOptions->get('about_title')))
    <section class="{{ $layout === 'full' ? 'container-shop' : $wrap }} farma-section grid items-center gap-8 py-14 sm:py-20 {{ $aboutImage && $layout !== 'narrow' ? 'lg:grid-cols-2 lg:gap-16' : '' }}">
        @if ($aboutImage)
            <img src="{{ $aboutImage }}" alt="" @class(['w-full rounded-[28px] object-cover', 'lg:order-2' => $reverse]) loading="lazy">
        @endif
        <div>
            <h2 class="farma-h2">{{ $themeOptions->get('about_title') }}</h2>
            @if (trim(strip_tags((string) $themeOptions->get('about_text'))) !== '')
                <div class="farma-rich mt-4 text-lg text-zinc-600">{{ safe_html($themeOptions->get('about_text')) }}</div>
            @endif
            @if ($themeOptions->get('show_benefits', true) && $benefits->isNotEmpty())
                <ul class="mt-7 grid gap-x-7 gap-y-4 sm:grid-cols-2">
                    @foreach ($benefits as $benefit)
                        <li class="flex gap-3">
                            <span class="grid size-9 shrink-0 place-items-center rounded-xl bg-soft text-accent">
                                <svg class="size-4.5" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="{{ $benefitIcons[$benefit['icon']] ?? $benefitIcons['box'] }}" /></svg>
                            </span>
                            <span class="font-extrabold">{{ $benefit['title'] }}@if ($benefit['text'] !== '')<span class="block text-sm font-semibold text-muted">{{ $benefit['text'] }}</span>@endif</span>
                        </li>
                    @endforeach
                </ul>
            @endif
            @if (filled($themeOptions->get('about_link')))
                <a href="{{ $themeOptions->get('about_link') }}" class="farma-link mt-7 inline-block">{{ __('theme::farma.about_more') }}</a>
            @endif
        </div>
    </section>
@endif
