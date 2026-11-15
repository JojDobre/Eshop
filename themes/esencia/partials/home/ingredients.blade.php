{{-- Zložky: tri farebné karty s ikonou, popisom a odkazom na produkty (ak nie sú vyplnené, použijú sa predvolené). --}}
@php
    $custom = collect(range(1, 3))->contains(fn ($i) => filled($themeOptions->get('ingredient_'.$i.'_title')));
    $ingredients = collect(range(1, 3))->map(fn ($i) => [
        'title' => $custom ? trim((string) $themeOptions->get('ingredient_'.$i.'_title')) : __('theme::esencia.ingredient_'.$i.'_title'),
        'text' => $custom ? trim((string) $themeOptions->get('ingredient_'.$i.'_text')) : __('theme::esencia.ingredient_'.$i.'_text'),
        'link' => trim((string) $themeOptions->get('ingredient_'.$i.'_link')),
        'icon' => (string) $themeOptions->get('ingredient_'.$i.'_icon', 'drop'),
    ])->filter(fn ($ingredient) => $ingredient['title'] !== '');
@endphp
@if ($ingredients->isNotEmpty())
    <section class="{{ $wrap }} py-10" aria-labelledby="home-ingredients">
        <h2 id="home-ingredients" class="es-h2">{{ $opt('ingredients_title') }}</h2>
        <div @class(['mt-6 grid gap-4', 'md:grid-cols-3' => $ingredients->count() === 3, 'md:grid-cols-2' => $ingredients->count() < 3])>
            @foreach ($ingredients as $ingredient)
                <div class="rounded-[1.75rem] bg-t{{ [2, 1, 3][$loop->index] }} p-7">
                    <span class="grid size-14 place-items-center rounded-full bg-white">@include('theme::partials.icon', ['name' => $ingredient['icon'], 'class' => 'size-7', 'stroke' => 1.6])</span>
                    <h3 class="mt-5 font-head text-2xl">{{ $ingredient['title'] }}</h3>
                    @if ($ingredient['text'] !== '')
                        <p class="mt-2 text-muted">{{ $ingredient['text'] }}</p>
                    @endif
                    @if ($ingredient['link'] !== '')
                        <a href="{{ $ingredient['link'] }}" class="mt-5 inline-flex items-center gap-2 text-sm font-semibold">{{ __('theme::esencia.ingredient_products') }}
                            <span class="grid size-7 place-items-center rounded-full bg-ink text-white">@include('theme::partials.icon', ['name' => 'arrow', 'class' => 'size-4', 'stroke' => 2])</span></a>
                    @endif
                </div>
            @endforeach
        </div>
    </section>
@endif
