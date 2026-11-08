{{-- Položky hlavnej ponuky na počítači; podpoložky sa rozbalia po kliknutí alebo prejdení myšou. --}}
@foreach ($items as $item)
    @if ($item['children'] === [])
        <a href="{{ $item['url'] }}" @class(['hover:text-zinc-900', 'font-medium text-zinc-900' => $item['active']]) @if ($item['active']) aria-current="page" @endif @if ($item['new_window']) target="_blank" rel="noopener" @endif>{{ $item['label'] }}</a>
    @else
        <div class="relative" x-data="{ open: false }" @mouseenter="open = true" @mouseleave="open = false" @keydown.escape="open = false">
            <button type="button" class="inline-flex items-center gap-1 hover:text-zinc-900" @click="open = ! open" :aria-expanded="open.toString()">
                {{ $item['label'] }}
                <svg class="size-4" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="m19.5 8.25-7.5 7.5-7.5-7.5" /></svg>
            </button>
            <div x-show="open" x-cloak class="absolute left-0 top-full z-50 min-w-48 rounded-lg border border-zinc-100 bg-white py-2 shadow-lg">
                <a href="{{ $item['url'] }}" class="block px-4 py-2 font-medium text-zinc-900 hover:bg-zinc-50" @if ($item['new_window']) target="_blank" rel="noopener" @endif>{{ $item['label'] }}</a>
                @foreach ($item['children'] as $child)
                    <a href="{{ $child['url'] }}" class="block px-4 py-2 text-zinc-600 hover:bg-zinc-50 hover:text-zinc-900" @if ($child['new_window']) target="_blank" rel="noopener" @endif>{{ $child['label'] }}</a>
                @endforeach
            </div>
        </div>
    @endif
@endforeach
