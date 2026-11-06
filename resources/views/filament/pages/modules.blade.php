<x-filament-panels::page>
    @if ($this->isSafeMode())
        <div role="alert" class="rounded-xl border border-amber-300 bg-amber-50 p-4 text-sm text-amber-900 dark:border-amber-500/30 dark:bg-amber-500/10 dark:text-amber-200">
            {{ __('admin.modules.safe_mode') }}
        </div>
    @endif

    {{ $this->table }}
</x-filament-panels::page>
