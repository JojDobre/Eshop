{{-- Výhody obchodu: štyri farebné dlaždice s ikonou. --}}
@php
    $benefits = collect(range(1, 4))->map(fn ($i) => ['text' => $opt('benefit_'.$i), 'icon' => (string) $themeOptions->get('benefit_'.$i.'_icon', 'check')]);
@endphp
<section class="{{ $wrap }} py-6">
    <ul class="grid grid-cols-2 gap-3 text-sm font-semibold lg:grid-cols-4">
        @foreach ($benefits as $benefit)
            <li class="flex items-center gap-3 rounded-2xl bg-t{{ [2, 6, 3, 4][$loop->index] }} px-4 py-4 sm:px-5">
                <span class="grid size-10 shrink-0 place-items-center rounded-full bg-white">@include('theme::partials.icon', ['name' => $benefit['icon'], 'class' => 'size-5'])</span>
                {{ $benefit['text'] }}
            </li>
        @endforeach
    </ul>
</section>
