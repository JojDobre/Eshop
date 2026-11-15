{{-- Galéria (napr. z Instagramu): nadpis s odkazom a až 6 fotiek. --}}
@php($galleryImages = collect(range(1, 6))->map(fn ($i) => $themeOptions->imageUrl('gallery_'.$i))->filter())
@if ($galleryImages->isNotEmpty())
    <section class="pb-16" aria-label="{{ trim((string) $themeOptions->get('gallery_title')) ?: __('theme::dielna.gallery_follow') }}">
        <p class="text-center">
            @if (filled($themeOptions->get('gallery_link')))
                <a href="{{ $themeOptions->get('gallery_link') }}" target="_blank" rel="noopener" class="font-hand text-2xl text-accent hover:underline">{{ trim((string) $themeOptions->get('gallery_title')) ?: __('theme::dielna.gallery_follow') }}</a>
            @else
                <span class="font-hand text-2xl text-accent">{{ trim((string) $themeOptions->get('gallery_title')) ?: __('theme::dielna.gallery_follow') }}</span>
            @endif
        </p>
        <div class="mt-4 grid grid-cols-3 gap-1.5 px-1.5 sm:grid-cols-6">
            @foreach ($galleryImages as $galleryImage)
                <img {{ responsive_img($galleryImage, '(min-width: 640px) 17vw, 33vw') }} alt="" class="aspect-square w-full object-cover" loading="lazy">
            @endforeach
        </div>
    </section>
@endif
