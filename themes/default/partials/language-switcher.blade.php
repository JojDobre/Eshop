{{-- Prepínač jazyka obchodu (iba pri viacjazyčnom obchode). --}}
@if (! empty($alternateUrls) && count($alternateUrls) > 1)
    @php($currentLocale = app()->getLocale())
    <div class="relative" x-data="{ open: false }" @click.outside="open = false" @keydown.escape="open = false">
        <button type="button" class="flex items-center gap-1 rounded-md px-2 py-2 text-sm font-medium uppercase text-zinc-700 hover:bg-zinc-100"
                @click="open = ! open" :aria-expanded="open.toString()" aria-haspopup="true" aria-label="{{ __('theme::shop.language') }}">
            {{ $shopLocales->urlCode($currentLocale) }}
            <svg class="size-4" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="m19.5 8.25-7.5 7.5-7.5-7.5" /></svg>
        </button>
        <ul x-show="open" x-cloak class="absolute right-0 z-50 mt-1 min-w-40 rounded-xl border border-zinc-200 bg-white py-1 text-sm shadow-lg">
            @foreach ($alternateUrls as $alternateLocale => $alternateUrl)
                <li>
                    <a href="{{ $alternateUrl }}" hreflang="{{ $alternateLocale }}" lang="{{ $alternateLocale }}"
                       @class(['block px-4 py-2 hover:bg-zinc-50', 'font-semibold' => $alternateLocale === $currentLocale])
                       @if ($alternateLocale === $currentLocale) aria-current="true" @endif>{{ $shopLocales->label($alternateLocale) }}</a>
                </li>
            @endforeach
        </ul>
    </div>
@endif
