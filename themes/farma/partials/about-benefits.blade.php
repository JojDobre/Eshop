{{-- Výhody v sekcii O nás: ikona, nadpis a krátky text v dvoch stĺpcoch. --}}
<ul class="mt-7 grid gap-x-7 gap-y-4 {{ $columns ?? 'sm:grid-cols-2' }}">
    @foreach ($benefits as $benefit)
        <li class="flex gap-3">
            <span class="grid size-9 shrink-0 place-items-center rounded-xl bg-soft text-accent">
                <svg class="size-4.5" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="{{ $benefitIcons[$benefit['icon']] ?? $benefitIcons['box'] }}" /></svg>
            </span>
            <span class="font-extrabold">{{ $benefit['title'] }}@if ($benefit['text'] !== '')<span class="block text-sm font-semibold text-muted">{{ $benefit['text'] }}</span>@endif</span>
        </li>
    @endforeach
</ul>
