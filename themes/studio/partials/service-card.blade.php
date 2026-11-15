{{-- Karta služby: fotka, názov, cena, krátky popis, dĺžka a odkaz na rezerváciu. --}}
@php
    $serviceImage = $service->imageUrl();
    $serviceDue = $service->amountDue();
@endphp
<article class="group relative flex gap-4 rounded-3xl bg-white p-3.5 pr-5 transition hover:shadow-[0_20px_50px_-35px_rgba(0,0,0,.45)] sm:gap-5 sm:p-4 sm:pr-6">
    @if ($serviceImage)
        <div class="studio-zoom size-24 shrink-0 overflow-hidden rounded-2xl sm:size-32">
            <img {{ responsive_img($serviceImage, '128px') }} alt="" class="size-full object-cover" loading="lazy">
        </div>
    @endif
    <div class="flex min-w-0 flex-1 flex-col">
        <div class="flex items-start justify-between gap-3">
            <h3 class="min-w-0 hyphens-auto break-words font-serif text-[1.45rem] leading-tight sm:text-2xl">
                <a href="{{ route('reservations.service', $service) }}" class="after:absolute after:inset-0">{{ $service->name }}</a>
            </h3>
            <p class="whitespace-nowrap pt-1 font-semibold">{{ $service->price > 0 ? format_price($service->price, $service->currency) : __('theme::studio.free') }}</p>
        </div>
        @if (filled($service->description))
            <p class="mt-1 line-clamp-2 text-sm text-muted">{{ $service->description }}</p>
        @endif
        <div class="mt-auto flex items-center justify-between gap-3 pt-3">
            <span class="flex items-center gap-1.5 text-xs font-semibold text-muted">
                <svg class="size-3.5" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" aria-hidden="true"><circle cx="12" cy="12" r="9" /><path stroke-linecap="round" d="M12 7v5l3 2" /></svg>
                {{ __('theme::studio.minutes', ['minutes' => $service->duration_minutes]) }}
                @if ($serviceDue > 0)
                    · {{ $service->payment_mode === 'deposit' ? __('theme::studio.deposit', ['amount' => format_price($serviceDue, $service->currency)]) : __('theme::studio.prepay') }}
                @endif
            </span>
            <span class="whitespace-nowrap text-sm font-semibold text-pine" aria-hidden="true">{{ __('theme::studio.book') }} →</span>
        </div>
    </div>
</article>
