{{-- Výhody: štyri dlaždice s ikonou, striedavo v dvoch jemných farbách. --}}
<section class="{{ $wrap }} py-10">
    <ul class="grid gap-3 text-sm font-semibold sm:grid-cols-2 sm:gap-4 lg:grid-cols-4">
        @foreach (range(1, 4) as $i)
            <li @class(['flex items-center gap-3 rounded-2xl px-5 py-4', 'bg-soft' => $i % 2 === 1, 'bg-soft2' => $i % 2 === 0])>
                <span class="grid size-10 shrink-0 place-items-center rounded-full bg-card">@include('theme::partials.icon', ['name' => (string) $themeOptions->get('benefit_'.$i.'_icon', 'book'), 'class' => 'size-5'])</span>
                {{ $opt('benefit_'.$i) }}
            </li>
        @endforeach
    </ul>
</section>
