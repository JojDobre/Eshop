{{-- O štúdiu: podklad vo farbe značky, mozaika troch fotiek, text a zoznam výhod. --}}
@php
    $aboutImages = array_values(array_filter([$themeOptions->imageUrl('about_image_1'), $themeOptions->imageUrl('about_image_2'), $themeOptions->imageUrl('about_image_3')]));
    $aboutText = trim(strip_tags((string) $themeOptions->get('about_text'))) !== '' ? $themeOptions->get('about_text') : __('theme::studio.about_text');
    $aboutPoints = collect(preg_split('/\R/', (string) $themeOptions->get('about_points')))->map(fn ($line) => trim($line))->filter();
@endphp
<section class="bg-pine text-white" aria-labelledby="home-about">
    <div @class([$layout === 'full' ? 'container-shop' : $wrap, 'grid items-center gap-12 py-16 sm:py-24', 'lg:grid-cols-2' => $aboutImages !== []])>
        @if ($aboutImages !== [])
            <div @class(['grid grid-cols-2 gap-4', 'lg:order-2' => $reverse])>
                @foreach ($aboutImages as $index => $aboutImage)
                    <img {{ responsive_img($aboutImage, '(min-width: 1024px) 25vw, 50vw') }} alt="" loading="lazy"
                         @class(['w-full rounded-[2rem] object-cover', 'row-span-2 h-full' => $index === 0 && count($aboutImages) === 3, 'aspect-square' => ! ($index === 0 && count($aboutImages) === 3)])>
                @endforeach
            </div>
        @endif
        <div @class(['mx-auto max-w-3xl text-center' => $aboutImages === []])>
            <p class="eyebrow !text-on-pine">{{ $opt('about_kicker') }}</p>
            <h2 id="home-about" class="studio-h2 mt-3">{{ $opt('about_title') }}</h2>
            <div class="prose prose-lg mt-5 max-w-none leading-relaxed text-on-pine prose-p:text-on-pine prose-strong:text-white prose-a:text-white">{{ safe_html($aboutText) }}</div>
            @if ($aboutPoints->isNotEmpty())
                <ul @class(['mt-7 grid gap-3 text-[15px] sm:grid-cols-2', 'text-left' => $aboutImages !== []])>
                    @foreach ($aboutPoints as $point)
                        <li class="flex gap-3"><span class="text-gold" aria-hidden="true">✦</span> {{ $point }}</li>
                    @endforeach
                </ul>
            @endif
        </div>
    </div>
</section>
