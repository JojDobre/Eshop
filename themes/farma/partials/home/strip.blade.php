{{-- Pruh s tipom (napr. doplnkový produkt). --}}
@if (filled($themeOptions->get('strip_title')))
    <div class="{{ $layout === 'full' ? 'container-shop' : $wrap }} farma-section mt-5">
        <section @class(['flex flex-wrap items-center gap-4 rounded-[20px] border border-line p-3.5 sm:flex-nowrap sm:gap-5', 'sm:pr-6' => ! $reverse, 'sm:flex-row-reverse sm:pl-6' => $reverse])>
            @if ($stripImage = $themeOptions->imageUrl('strip_image'))
                <img src="{{ $stripImage }}" alt="" class="w-18 rounded-xl sm:w-28" loading="lazy">
            @endif
            <div class="min-w-0 flex-1">
                <p class="font-black">{{ $themeOptions->get('strip_title') }}</p>
                @if (filled($themeOptions->get('strip_text')))
                    <p class="whitespace-pre-line text-sm text-muted">{{ $themeOptions->get('strip_text') }}</p>
                @endif
            </div>
            @if (filled($themeOptions->get('strip_link')))
                <a href="{{ $themeOptions->get('strip_link') }}" class="btn btn-primary w-full py-2.5 sm:w-auto">{{ $themeOptions->get('strip_button') ?: __('theme::farma.more') }}</a>
            @endif
        </section>
    </div>
@endif
