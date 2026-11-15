{{-- Lookbook banner s veľkým nadpisom a tmavý blok s troma dôvodmi, prečo nakúpiť práve tu. --}}
@php
    $lookbookTitle = trim((string) $themeOptions->get('lookbook_title'));
    $lookbookImage = $themeOptions->imageUrl('lookbook_image');
    $reasons = collect(range(1, 3))->map(fn ($i) => ['title' => $opt('why_'.$i.'_title'), 'text' => $opt('why_'.$i.'_text')]);
@endphp
<section class="{{ $wrap }} py-16">
    <div @class(['grid gap-6', 'lg:grid-cols-3' => $lookbookTitle !== ''])>
        @if ($lookbookTitle !== '')
            <a href="{{ trim((string) $themeOptions->get('lookbook_link')) ?: '#' }}" @class(['bt-zoom relative block aspect-[4/5] overflow-hidden bg-soft sm:aspect-[16/9] lg:col-span-2', 'lg:order-2' => $reverse])>
                @if ($lookbookImage)
                    <img {{ responsive_img($lookbookImage, '(min-width: 1024px) 66vw, 100vw') }} alt="" class="absolute inset-0 size-full object-cover" loading="lazy">
                @endif
                <span class="absolute left-6 top-6 sm:left-10 sm:top-10">
                    @if (filled($themeOptions->get('lookbook_label')))<span class="cap inline-block bg-white px-2 py-1">{{ $themeOptions->get('lookbook_label') }}</span>@endif
                    <span class="font-head mt-3 block text-6xl sm:text-8xl">{!! collect(explode('|', $lookbookTitle))->map(fn ($line) => e(trim($line)))->implode('<br>') !!}</span>
                </span>
            </a>
        @endif
        <div class="flex flex-col justify-between gap-8 bg-ink p-8 text-white">
            <h2 class="cap text-white/65">{{ $opt('why_title') }}</h2>
            <ol class="space-y-6">
                @foreach ($reasons as $reason)
                    <li class="flex gap-4">
                        <span class="font-head text-3xl" style="color: var(--bt-ac)" aria-hidden="true">0{{ $loop->iteration }}</span>
                        <span><b class="block">{{ $reason['title'] }}</b>@if ($reason['text'] !== '')<span class="text-sm text-white/70">{{ $reason['text'] }}</span>@endif</span>
                    </li>
                @endforeach
            </ol>
            @if (filled($themeOptions->get('why_button')) && filled($themeOptions->get('why_link')))
                <a href="{{ $themeOptions->get('why_link') }}" class="btn btn-ac w-fit">{{ $themeOptions->get('why_button') }}</a>
            @endif
        </div>
    </div>
</section>
