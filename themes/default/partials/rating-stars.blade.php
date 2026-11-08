{{-- Hviezdičky: $rating (0–5, aj desatinné), voliteľne $size (CSS trieda). --}}
@php($filled = (int) round((float) $rating))
<span class="inline-flex items-center gap-0.5 text-amber-500 {{ $size ?? 'text-sm' }}" role="img" aria-label="{{ __('reviews::shop.stars', ['rating' => number_format((float) $rating, 1, ',', '')]) }}">
    @for ($i = 1; $i <= 5; $i++)
        <span aria-hidden="true" @class(['text-zinc-300' => $i > $filled])>★</span>
    @endfor
</span>
