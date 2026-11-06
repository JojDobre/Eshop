<header class="border-b border-zinc-100">
    <div class="mx-auto flex h-16 max-w-6xl items-center justify-between gap-6 px-4 sm:px-6">
        <a href="{{ route('storefront.home') }}" class="flex items-center gap-3 font-semibold tracking-tight">
            @if ($logo = $branding->logoUrl())
                <img src="{{ $logo }}" alt="{{ $shop->shop_name }}" class="h-8 w-auto">
            @else
                <span class="text-lg">{{ $shop->shop_name }}</span>
            @endif
        </a>

        @stack('header-actions')
    </div>
</header>
