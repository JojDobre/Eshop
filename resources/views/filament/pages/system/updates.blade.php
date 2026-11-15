<x-filament-panels::page>
    <x-filament::section>
        <dl class="grid gap-4 sm:grid-cols-2 lg:grid-cols-4">
            <div>
                <dt class="text-sm text-gray-500 dark:text-gray-400">{{ __('admin.updates.current') }}</dt>
                <dd class="text-2xl font-semibold">{{ $version }}</dd>
            </div>
            <div>
                <dt class="text-sm text-gray-500 dark:text-gray-400">{{ __('admin.updates.signatures') }}</dt>
                <dd class="mt-1 text-sm">{{ $signed ? __('admin.updates.signatures_on') : __('admin.updates.signatures_off') }}</dd>
            </div>
            <div>
                <dt class="text-sm text-gray-500 dark:text-gray-400">{{ __('admin.updates.auto') }}</dt>
                <dd class="mt-1 text-sm">{{ $autoInstall ? __('admin.updates.auto_on') : __('admin.updates.auto_off') }}<br><span class="text-gray-500 dark:text-gray-400">{{ __('admin.updates.source', ['source' => $source]) }}</span></dd>
            </div>
            @if ($lastUpdate)
                <div>
                    <dt class="text-sm text-gray-500 dark:text-gray-400">{{ __('admin.updates.last') }}</dt>
                    <dd class="mt-1 text-sm">{{ __('admin.updates.last_value', ['from' => $lastUpdate['from'], 'to' => $lastUpdate['to'], 'date' => \Illuminate\Support\Carbon::parse($lastUpdate['updated_at'])->format('j. n. Y H:i')]) }}</dd>
                </div>
            @endif
        </dl>
    </x-filament::section>

    @if ($available)
        <x-filament::section :heading="__('admin.updates.available', ['version' => $available['version']])">
            @if (filled($available['notes'] ?? null))
                <div class="max-h-80 overflow-y-auto whitespace-pre-line text-sm">{{ $available['notes'] }}</div>
            @endif
            @if (filled($available['url'] ?? null))
                <p class="mt-3 text-sm"><a href="{{ $available['url'] }}" class="text-primary-600 underline" target="_blank" rel="noopener">{{ __('admin.updates.release_page') }}</a></p>
            @endif
        </x-filament::section>
    @endif

    <x-filament::section :heading="__('admin.updates.how')">
        <ol class="list-decimal space-y-1 pl-5 text-sm text-gray-600 dark:text-gray-300">
            @foreach (__('admin.updates.steps') as $step)
                <li>{{ $step }}</li>
            @endforeach
        </ol>
    </x-filament::section>
</x-filament-panels::page>
