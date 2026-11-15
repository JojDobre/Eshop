{{-- Výroba na mieru: text s číslami a kontaktný formulár (Obsah → Formuláre). --}}
@php
    $customForm = null;
    if (class_exists(\Modules\Forms\Models\Form::class)) {
        $customForm = rescue(fn () => filled($themeOptions->get('custom_form'))
            ? \Modules\Forms\Models\Form::query()->where('is_active', true)->find((int) $themeOptions->get('custom_form'))
            : \Modules\Forms\Models\Form::query()->where('is_active', true)->orderBy('id')->first(), null, false);
    }
    $customStats = collect(preg_split('/\R/', $opt('custom_stats')) ?: [])
        ->map(fn ($line) => array_map('trim', explode('|', $line, 2)))
        ->filter(fn ($parts) => count($parts) === 2 && $parts[0] !== '');
@endphp
<section class="{{ $wrap }} py-16 sm:py-20" aria-labelledby="home-custom">
    <div class="grid items-center gap-10 rounded-[2rem] bg-blush p-6 sm:p-12 {{ $customForm ? 'lg:grid-cols-2' : '' }} [&>*]:min-w-0">
        <div @class(['lg:order-2' => $reverse && $customForm])>
            <p class="dielna-kicker">{{ $opt('custom_kicker') }}</p>
            <h2 id="home-custom" class="dielna-heading mt-1 text-3xl sm:text-4xl">{{ $opt('custom_title') }}</h2>
            <p class="mt-4 leading-relaxed text-muted">{{ $opt('custom_text') }}</p>
            @if ($customStats->isNotEmpty())
                <dl class="mt-6 flex flex-wrap gap-x-8 gap-y-3 text-sm">
                    @foreach ($customStats as [$statValue, $statLabel])
                        <div><dt class="sr-only">{{ $statLabel }}</dt><dd><strong class="block font-serif text-2xl font-medium">{{ $statValue }}</strong>{{ $statLabel }}</dd></div>
                    @endforeach
                </dl>
            @endif
            @if (! $customForm && $shop->contact_email)
                <a href="mailto:{{ $shop->contact_email }}" class="btn btn-primary mt-6">{{ __('theme::dielna.custom_contact') }}</a>
            @endif
        </div>
        @if ($customForm)
            <div class="rounded-2xl bg-white p-5 shadow-sm sm:p-7">
                @include('theme::partials.contact-form', ['form' => $customForm])
            </div>
        @endif
    </div>
</section>
