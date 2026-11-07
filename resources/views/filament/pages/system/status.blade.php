<x-filament-panels::page>
    @foreach ($checks as $group => $rows)
        <x-filament::section :heading="__('admin.status.groups.'.$group)">
            <dl class="divide-y divide-gray-100 dark:divide-white/5">
                @foreach ($rows as $row)
                    <div class="grid gap-1 py-3 sm:grid-cols-3 sm:gap-4">
                        <dt class="text-sm font-medium text-gray-950 dark:text-white">{{ $row['label'] }}</dt>
                        <dd class="text-sm text-gray-700 sm:col-span-2 dark:text-gray-300">
                            <span class="inline-flex items-center gap-2">
                                <x-filament::badge :color="match ($row['status']) { 'ok' => 'success', 'warning' => 'warning', default => 'danger' }">
                                    {{ __('admin.status.state_'.$row['status']) }}
                                </x-filament::badge>
                                <span class="break-all">{{ $row['value'] }}</span>
                            </span>
                            @if (! empty($row['help']))
                                <p class="mt-1 text-xs text-gray-500 dark:text-gray-400">{{ $row['help'] }}</p>
                            @endif
                        </dd>
                    </div>
                @endforeach
            </dl>
        </x-filament::section>
    @endforeach

    <x-filament::section :heading="__('admin.status.cron_setup')" :description="__('admin.status.cron_setup_help')">
        <div class="space-y-4 text-sm">
            <div>
                <p class="font-medium text-gray-950 dark:text-white">{{ __('admin.status.cron_command') }}</p>
                <code class="mt-1 block overflow-x-auto rounded-lg bg-gray-50 p-3 font-mono text-xs dark:bg-white/5">{{ $cronCommand }}</code>
            </div>
            <div>
                <p class="font-medium text-gray-950 dark:text-white">{{ __('admin.status.web_cron') }}</p>
                <code class="mt-1 block overflow-x-auto rounded-lg bg-gray-50 p-3 font-mono text-xs dark:bg-white/5">{{ $webCronUrl }}</code>
                <p class="mt-1 text-xs text-gray-500 dark:text-gray-400">{{ __('admin.status.web_cron_help') }}</p>
            </div>
        </div>
    </x-filament::section>
</x-filament-panels::page>
