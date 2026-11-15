{{-- Fotky z Instagramu (nahrané v Prispôsobení, bez načítania cudzích skriptov) s odkazom na profil. --}}
@php
    $instagramTitle = trim((string) $themeOptions->get('instagram_title'));
    $instagramLink = trim((string) $themeOptions->get('instagram_link'));
    $photos = collect(range(1, 6))->map(fn ($i) => $themeOptions->imageUrl('instagram_'.$i))->filter()->values();
@endphp
@if ($instagramTitle !== '' && $photos->isNotEmpty())
    <section class="pb-16" aria-labelledby="home-instagram">
        <div class="container-shop flex flex-wrap items-end justify-between gap-4">
            <h2 id="home-instagram" class="bt-h2">{{ $instagramTitle }}</h2>
            @if ($instagramLink !== '')
                <a href="{{ $instagramLink }}" target="_blank" rel="noopener" class="bt-link">{{ __('theme::butik.instagram_follow') }}</a>
            @endif
        </div>
        <div class="mt-8 grid grid-cols-3 lg:grid-cols-6">
            @foreach ($photos as $photo)
                <a href="{{ $instagramLink ?: '#' }}" @if ($instagramLink !== '') target="_blank" rel="noopener" @endif class="bt-zoom block aspect-square overflow-hidden" tabindex="-1">
                    <img {{ responsive_img($photo, '(min-width: 1024px) 17vw, 33vw') }} alt="" class="size-full object-cover" loading="lazy">
                </a>
            @endforeach
        </div>
    </section>
@endif
