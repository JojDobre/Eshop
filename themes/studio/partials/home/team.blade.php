{{-- Tím: portréty s menom a pozíciou (zobrazia sa iba členovia s vyplneným menom). --}}
@php
    $members = collect(range(1, 4))
        ->map(fn ($i) => ['name' => trim((string) $themeOptions->get('member_'.$i.'_name')), 'role' => trim((string) $themeOptions->get('member_'.$i.'_role')), 'image' => $themeOptions->imageUrl('member_'.$i.'_image')])
        ->filter(fn ($member) => $member['name'] !== '');
@endphp
@if ($members->isNotEmpty())
    <section class="{{ $wrap }} py-16 sm:py-20" aria-labelledby="home-team">
        <p class="eyebrow text-center">{{ $opt('team_kicker') }}</p>
        <h2 id="home-team" class="studio-h2 mt-3 text-center">{{ $opt('team_title') }}</h2>
        <div @class(['mt-12 grid grid-cols-2 gap-5 sm:gap-6', 'lg:grid-cols-4' => $members->count() === 4, 'lg:grid-cols-3' => $members->count() === 3, 'mx-auto max-w-3xl' => $members->count() < 3])>
            @foreach ($members as $member)
                <figure>
                    @if ($member['image'])
                        <img {{ responsive_img($member['image'], '(min-width: 1024px) 22vw, 45vw') }} alt="" class="aspect-[3/4] w-full rounded-[2rem] object-cover" loading="lazy">
                    @else
                        <span class="grid aspect-[3/4] w-full place-items-center rounded-[2rem] bg-stone font-serif text-6xl text-muted" aria-hidden="true">{{ mb_substr($member['name'], 0, 1) }}</span>
                    @endif
                    <figcaption class="mt-4">
                        <span class="block font-serif text-2xl">{{ $member['name'] }}</span>
                        <span class="text-sm text-muted">{{ $member['role'] }}</span>
                    </figcaption>
                </figure>
            @endforeach
        </div>
    </section>
@endif
