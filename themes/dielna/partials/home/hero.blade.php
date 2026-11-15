{{-- Úvod: ručne písaný nadpis, veľký nadpis so zvýrazneným slovom, text, tlačidlá a fotka v oblúku s pečiatkou a lístkom. --}}
@php
    $heroTitle = preg_replace('/\*(.+?)\*/u', '<em class="font-normal text-accent">$1</em>', e($opt('hero_title')));
    $heroLink = trim((string) $themeOptions->get('hero_link')) ?: (! empty($homeCategories) && $homeCategories->isNotEmpty() ? $homeCategories->first()->url() : null);
    $heroImage = $themeOptions->imageUrl('hero_image');
    $heroStamp = trim((string) $themeOptions->get('hero_stamp'));
@endphp
<section class="dielna-grain overflow-hidden" aria-labelledby="home-hero">
    <div @class([$wrap, 'grid items-center gap-10 pb-14 pt-10 sm:pt-14 lg:pb-20', 'lg:grid-cols-[1.05fr_.95fr]' => $heroImage])>
        <div @class(['lg:order-2' => $reverse && $heroImage, 'mx-auto max-w-3xl text-center' => ! $heroImage])>
            <p class="dielna-kicker">{{ $opt('hero_kicker') }}</p>
            <h1 id="home-hero" class="mt-2 text-[2.5rem] leading-[1.06] tracking-tight sm:text-6xl lg:text-[4.2rem]">{!! $heroTitle !!}</h1>
            <p @class(['mt-6 max-w-lg text-lg leading-relaxed text-muted', 'mx-auto' => ! $heroImage])>{{ $opt('hero_text') }}</p>
            <div @class(['mt-8 flex flex-wrap gap-3', 'justify-center' => ! $heroImage])>
                @if ($heroLink)
                    <a href="{{ $heroLink }}" class="btn btn-primary">{{ $opt('hero_button') }} <span aria-hidden="true">→</span></a>
                @endif
                @if (filled($themeOptions->get('hero_button_2')) && filled($themeOptions->get('hero_link_2')))
                    <a href="{{ $themeOptions->get('hero_link_2') }}" class="btn btn-secondary">{{ $themeOptions->get('hero_button_2') }}</a>
                @endif
            </div>
            @if (filled($themeOptions->get('hero_note')))
                <p class="mt-8 text-sm text-muted">{{ $themeOptions->get('hero_note') }}</p>
            @endif
        </div>

        @if ($heroImage)
            <div @class(['relative mx-auto w-full max-w-md lg:max-w-none', 'lg:order-1' => $reverse])>
                <img {{ responsive_img($heroImage, '(min-width: 1024px) 45vw, 90vw') }} alt="" class="arch aspect-[4/5] w-full object-cover shadow-[0_30px_60px_-30px_rgba(43,37,32,.45)]" fetchpriority="high">
                @if ($heroStamp !== '')
                    <div @class(['absolute bottom-10 grid size-28 place-items-center rounded-full bg-primary text-primary-foreground shadow-lg sm:size-32', '-left-3 sm:-left-8' => ! $reverse, '-right-3 sm:-right-8' => $reverse]) aria-hidden="true">
                        <svg viewBox="0 0 100 100" class="dielna-spin absolute inset-0 size-full p-1.5"><defs><path id="dielna-stamp" d="M50,50 m-38,0 a38,38 0 1,1 76,0 a38,38 0 1,1 -76,0" /></defs><text font-size="10" letter-spacing="2.4" fill="currentColor"><textPath href="#dielna-stamp">{{ $heroStamp }}</textPath></text></svg>
                        <span class="font-serif text-3xl italic">{{ mb_strtolower(mb_substr($shop->shop_name, 0, 1)) }}</span>
                    </div>
                @endif
                @if (filled($themeOptions->get('hero_sticker')))
                    <div @class(['absolute top-10 rotate-2 rounded-2xl bg-white/95 px-4 py-3 shadow-lg', '-right-1 sm:-right-6' => ! $reverse, '-left-1 sm:-left-6' => $reverse])>
                        <p class="font-hand text-xl leading-none text-accent">{{ $themeOptions->get('hero_sticker') }}</p>
                        @if (filled($themeOptions->get('hero_sticker_text')))
                            <p class="mt-1 text-xs text-muted">{{ $themeOptions->get('hero_sticker_text') }}</p>
                        @endif
                    </div>
                @endif
            </div>
        @endif
    </div>
</section>
