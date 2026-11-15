{{-- Pruh so štyrmi výhodami (ikona, nadpis, krátky text). --}}
@php
    $values = collect(range(1, 4))->map(fn ($i) => [
        'icon' => $themeOptions->get('value_'.$i.'_icon', 'hand'),
        'title' => $opt('value_'.$i.'_title'),
        'text' => trim((string) $themeOptions->get('value_'.$i.'_title')) !== '' ? trim((string) $themeOptions->get('value_'.$i.'_text')) : $opt('value_'.$i.'_text'),
    ])->filter(fn ($value) => $value['title'] !== '');
@endphp
<section class="border-y border-line bg-[color-mix(in_srgb,var(--dielna-sand)_60%,transparent)]">
    <ul class="{{ $wrap }} grid grid-cols-2 gap-5 py-6 text-sm lg:grid-cols-4">
        @foreach ($values as $value)
            <li class="flex items-center gap-3">
                <span class="grid size-10 shrink-0 place-items-center rounded-full bg-cream text-accent">@include('theme::partials.dielna-icon', ['icon' => $value['icon']])</span>
                <span><strong class="block font-semibold">{{ $value['title'] }}</strong>@if ($value['text'] !== '')<span class="text-muted">{{ $value['text'] }}</span>@endif</span>
            </li>
        @endforeach
    </ul>
</section>
