@if ($paginator->hasPages())
    <nav class="mt-12 flex items-center justify-between gap-4 text-sm" aria-label="{{ __('theme::shop.pagination') }}">
        @if ($paginator->onFirstPage())
            <span class="btn btn-secondary pointer-events-none opacity-40">{{ __('theme::shop.previous') }}</span>
        @else
            <a href="{{ $paginator->previousPageUrl() }}" rel="prev" class="btn btn-secondary">{{ __('theme::shop.previous') }}</a>
        @endif

        <span class="text-zinc-500">{{ __('theme::shop.page_of', ['page' => $paginator->currentPage(), 'total' => $paginator->lastPage()]) }}</span>

        @if ($paginator->hasMorePages())
            <a href="{{ $paginator->nextPageUrl() }}" rel="next" class="btn btn-secondary">{{ __('theme::shop.next') }}</a>
        @else
            <span class="btn btn-secondary pointer-events-none opacity-40">{{ __('theme::shop.next') }}</span>
        @endif
    </nav>
@endif
