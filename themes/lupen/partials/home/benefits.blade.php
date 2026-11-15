{{-- Výhody: tri dlaždice v tvare lupeňa s ikonou. --}}
<section class="{{ $wrap }} py-10">
    <ul class="grid gap-4 md:grid-cols-3">
        @foreach (range(1, 3) as $i)
            <li @class(['leaf-sm flex gap-4 p-6', 'bg-soft' => $i !== 2, 'bg-soft2' => $i === 2])>
                <span class="grid size-12 shrink-0 place-items-center rounded-full bg-card">@include('theme::partials.icon', ['name' => (string) $themeOptions->get('benefit_'.$i.'_icon', 'leaf'), 'class' => 'size-5'])</span>
                <span><b class="block font-head text-xl font-normal">{{ $opt('benefit_'.$i.'_title') }}</b><span class="text-sm text-muted">{{ $opt('benefit_'.$i.'_text') }}</span></span>
            </li>
        @endforeach
    </ul>
</section>
