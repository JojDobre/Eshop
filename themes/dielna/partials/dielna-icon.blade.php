{{-- Jednoduché ikony Dielne (ruka, list, darček, pero, srdce, auto, hviezda, recyklácia). --}}
<svg class="{{ $class ?? 'size-5' }}" fill="none" viewBox="0 0 24 24" stroke-width="1.6" stroke="currentColor" aria-hidden="true">
    @switch($icon ?? 'hand')
        @case('leaf')<path stroke-linecap="round" stroke-linejoin="round" d="M5 19c0-8 5-13 14-14 0 9-5 14-13 14zM5 19l7-7" />@break
        @case('gift')<path stroke-linejoin="round" d="M4 10h16v10H4zM3 7h18v3H3zM12 7v13" /><path stroke-linecap="round" d="M12 7c-1-2.5-4.5-3.5-5-1.5S9.5 7 12 7zm0 0c1-2.5 4.5-3.5 5-1.5S14.5 7 12 7z" />@break
        @case('pen')<path stroke-linecap="round" stroke-linejoin="round" d="m15 5 4 4L8 20H4v-4zM13 7l4 4" />@break
        @case('heart')<path stroke-linejoin="round" d="M12 20s-7-4.3-8.8-8.6C2 8.4 4 5 7.2 5c1.9 0 3.2 1 4.8 2.8C13.6 6 14.9 5 16.8 5 20 5 22 8.4 20.8 11.4 19 15.7 12 20 12 20z" />@break
        @case('truck')<path stroke-linejoin="round" d="M3 6h11v10H3zM14 10h4l3 3v3h-7" /><circle cx="7" cy="18" r="2" /><circle cx="17" cy="18" r="2" />@break
        @case('star')<path stroke-linejoin="round" d="m12 3 2.7 5.6 6.1.9-4.4 4.3 1 6.1L12 17l-5.4 2.9 1-6.1-4.4-4.3 6.1-.9z" />@break
        @case('recycle')<path stroke-linecap="round" stroke-linejoin="round" d="M7 19H4l3-5M17 19h3l-3-5M9 5l3-2 3 5M7 19h10M8.5 9.5 12 3M15.5 9.5 17 14" />@break
        @default<path stroke-linecap="round" stroke-linejoin="round" d="M8 13V5.5a1.5 1.5 0 0 1 3 0V11m0-6a1.5 1.5 0 0 1 3 0v6m0-4.5a1.5 1.5 0 0 1 3 0V13c0 4-2.5 7-6.5 7S6 18 5 15.5L3.8 12.6a1.5 1.5 0 0 1 2.6-1.4L8 13" />
    @endswitch
</svg>
