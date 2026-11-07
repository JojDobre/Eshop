{{-- $items: zoznam [názov, adresa] – posledná položka je aktuálna stránka. --}}
<nav aria-label="{{ __('theme::shop.breadcrumbs') }}" class="text-sm text-zinc-500">
    <ol class="flex flex-wrap items-center gap-1.5">
        <li><a href="{{ route('storefront.home') }}" class="hover:text-zinc-900">{{ __('theme::shop.home') }}</a></li>
        @foreach ($items as [$label, $url])
            <li aria-hidden="true">/</li>
            <li>
                @if ($loop->last || $url === null)
                    <span class="text-zinc-900" @if ($loop->last) aria-current="page" @endif>{{ $label }}</span>
                @else
                    <a href="{{ $url }}" class="hover:text-zinc-900">{{ $label }}</a>
                @endif
            </li>
        @endforeach
    </ol>
</nav>
