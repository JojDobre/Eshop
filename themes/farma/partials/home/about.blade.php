{{--
    O nás a výhody (nadpisy a texty výhod sa dajú upraviť v prispôsobení šablóny). Vzhľad podľa nastavenia „O nás – vzhľad“:
    split (fotka vedľa textu), overlay (fotka na pozadí, text na karte), card (farebná karta, výhody pod textom),
    centered (text na stred, široká fotka a výhody v riadku).
--}}
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
    $aboutStyle = (string) $themeOptions->get('about_style', 'split');
    // Fotka na pozadí bez fotky nedáva zmysel – použije sa farebná karta.
    if ($aboutStyle === 'overlay' && ! $aboutImage) {
        $aboutStyle = 'card';
    }
    $aboutText = trim(strip_tags((string) $themeOptions->get('about_text'))) !== '' ? $themeOptions->get('about_text') : null;
    $showBenefits = $themeOptions->get('show_benefits', true) && $benefits->isNotEmpty();
    $sectionWrap = $layout === 'full' ? 'container-shop' : $wrap;
@endphp
@if (filled($themeOptions->get('about_title')))
    @switch($aboutStyle)
        @case('overlay')
            <section class="{{ $sectionWrap }} farma-section py-14 sm:py-20">
                <div class="relative isolate flex min-h-[30rem] items-end overflow-hidden rounded-[28px] p-4 sm:items-center sm:p-10 lg:min-h-[34rem]">
                    <img {{ responsive_img($aboutImage, '100vw') }} alt="" class="absolute inset-0 -z-10 size-full object-cover" loading="lazy">
                    <div class="absolute inset-0 -z-10 bg-gradient-to-t from-black/45 to-transparent sm:bg-gradient-to-r sm:from-black/35"></div>
                    <div @class(['w-full max-w-xl rounded-[24px] bg-white p-6 shadow-xl sm:p-9', 'sm:ml-auto' => $reverse])>
                        <h2 class="farma-h2">{{ $themeOptions->get('about_title') }}</h2>
                        @if ($aboutText)<div class="farma-rich mt-4 text-zinc-600">{{ safe_html($aboutText) }}</div>@endif
                        @if ($showBenefits)
                            @include('theme::partials.about-benefits', ['benefits' => $benefits, 'benefitIcons' => $benefitIcons, 'columns' => 'sm:grid-cols-2'])
                        @endif
                        @if (filled($themeOptions->get('about_link')))
                            <a href="{{ $themeOptions->get('about_link') }}" class="btn btn-primary mt-7">{{ __('theme::farma.about_more') }}</a>
                        @endif
                    </div>
                </div>
            </section>
            @break

        @case('card')
            <section class="{{ $sectionWrap }} farma-section py-14 sm:py-20">
                <div class="rounded-[28px] bg-soft p-6 sm:p-10 lg:p-14">
                    <div @class(['grid items-center gap-8 lg:gap-14', 'lg:grid-cols-[1.1fr_.9fr]' => $aboutImage])>
                        <div @class(['lg:order-2' => $reverse && $aboutImage])>
                            <h2 class="farma-h2">{{ $themeOptions->get('about_title') }}</h2>
                            @if ($aboutText)<div class="farma-rich mt-4 text-lg text-zinc-600">{{ safe_html($aboutText) }}</div>@endif
                            @if (filled($themeOptions->get('about_link')))
                                <a href="{{ $themeOptions->get('about_link') }}" class="btn btn-primary mt-7">{{ __('theme::farma.about_more') }}</a>
                            @endif
                        </div>
                        @if ($aboutImage)
                            <img {{ responsive_img($aboutImage, '(min-width: 1024px) 40vw, 100vw') }} alt="" class="aspect-[4/3] w-full rounded-[22px] object-cover" loading="lazy">
                        @endif
                    </div>
                    @if ($showBenefits)
                        <ul class="mt-10 grid gap-4 sm:grid-cols-2 lg:grid-cols-4">
                            @foreach ($benefits as $benefit)
                                <li class="rounded-[20px] bg-white p-5">
                                    <span class="grid size-11 place-items-center rounded-2xl bg-soft text-accent">
                                        <svg class="size-5" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="{{ $benefitIcons[$benefit['icon']] ?? $benefitIcons['box'] }}" /></svg>
                                    </span>
                                    <p class="mt-4 font-extrabold">{{ $benefit['title'] }}</p>
                                    @if ($benefit['text'] !== '')<p class="mt-1 text-sm font-semibold text-muted">{{ $benefit['text'] }}</p>@endif
                                </li>
                            @endforeach
                        </ul>
                    @endif
                </div>
            </section>
            @break

        @case('centered')
            <section class="{{ $sectionWrap }} farma-section py-14 sm:py-20">
                <div class="mx-auto max-w-3xl text-center">
                    <h2 class="farma-h2">{{ $themeOptions->get('about_title') }}</h2>
                    @if ($aboutText)<div class="farma-rich mt-4 text-lg text-zinc-600">{{ safe_html($aboutText) }}</div>@endif
                </div>
                @if ($aboutImage)
                    <img {{ responsive_img($aboutImage, '100vw') }} alt="" class="mt-10 aspect-[16/9] w-full rounded-[28px] object-cover sm:aspect-[21/9]" loading="lazy">
                @endif
                @if ($showBenefits)
                    <ul class="mt-10 grid gap-8 text-center sm:grid-cols-2 lg:grid-cols-4">
                        @foreach ($benefits as $benefit)
                            <li>
                                <span class="mx-auto grid size-12 place-items-center rounded-2xl bg-soft text-accent">
                                    <svg class="size-5" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="{{ $benefitIcons[$benefit['icon']] ?? $benefitIcons['box'] }}" /></svg>
                                </span>
                                <p class="mt-3 font-extrabold">{{ $benefit['title'] }}</p>
                                @if ($benefit['text'] !== '')<p class="mt-1 text-sm font-semibold text-muted">{{ $benefit['text'] }}</p>@endif
                            </li>
                        @endforeach
                    </ul>
                @endif
                @if (filled($themeOptions->get('about_link')))
                    <p class="mt-10 text-center"><a href="{{ $themeOptions->get('about_link') }}" class="btn btn-primary">{{ __('theme::farma.about_more') }}</a></p>
                @endif
            </section>
            @break

        @default
            <section class="{{ $sectionWrap }} farma-section grid items-center gap-8 py-14 sm:py-20 {{ $aboutImage && $layout !== 'narrow' ? 'lg:grid-cols-2 lg:gap-16' : '' }}">
                @if ($aboutImage)
                    <img {{ responsive_img($aboutImage, '(min-width: 1024px) 50vw, 100vw') }} alt="" @class(['w-full rounded-[28px] object-cover', 'lg:order-2' => $reverse]) loading="lazy">
                @endif
                <div>
                    <h2 class="farma-h2">{{ $themeOptions->get('about_title') }}</h2>
                    @if ($aboutText)
                        <div class="farma-rich mt-4 text-lg text-zinc-600">{{ safe_html($aboutText) }}</div>
                    @endif
                    @if ($showBenefits)
                        @include('theme::partials.about-benefits', ['benefits' => $benefits, 'benefitIcons' => $benefitIcons, 'columns' => 'sm:grid-cols-2'])
                    @endif
                    @if (filled($themeOptions->get('about_link')))
                        <a href="{{ $themeOptions->get('about_link') }}" class="farma-link mt-7 inline-block">{{ __('theme::farma.about_more') }}</a>
                    @endif
                </div>
            </section>
    @endswitch
@endif
