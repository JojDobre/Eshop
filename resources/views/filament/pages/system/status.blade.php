{{-- Vložené štýly: skompilované CSS administrácie neobsahuje ľubovoľné triedy Tailwindu. --}}
<x-filament-panels::page>
    @foreach ($checks as $group => $rows)
        <x-filament::section :heading="__('admin.status.groups.'.$group)">
            <dl style="margin: 0">
                @foreach ($rows as $row)
                    <div style="display: grid; grid-template-columns: minmax(0, 1fr) minmax(0, 2fr); gap: 1rem; padding: 0.75rem 0; {{ $loop->first ? '' : 'border-top: 1px solid rgb(127 127 127 / 0.15);' }}">
                        <dt style="font-size: 0.875rem; font-weight: 500;">{{ $row['label'] }}</dt>
                        <dd style="margin: 0; font-size: 0.875rem;">
                            <div style="display: flex; flex-wrap: wrap; align-items: center; gap: 0.5rem;">
                                <x-filament::badge :color="match ($row['status']) { 'ok' => 'success', 'warning' => 'warning', default => 'danger' }">
                                    {{ __('admin.status.state_'.$row['status']) }}
                                </x-filament::badge>
                                <span style="word-break: break-all;">{{ $row['value'] }}</span>
                            </div>
                            @if (! empty($row['help']))
                                <p style="margin: 0.25rem 0 0; font-size: 0.75rem; opacity: 0.7;">{{ $row['help'] }}</p>
                            @endif
                        </dd>
                    </div>
                @endforeach
            </dl>
        </x-filament::section>
    @endforeach

    <x-filament::section :heading="__('admin.status.cron_setup')" :description="__('admin.status.cron_setup_help')">
        @php($code = 'display: block; overflow-x: auto; margin-top: 0.25rem; padding: 0.75rem; border-radius: 0.5rem; background: rgb(127 127 127 / 0.1); font-family: ui-monospace, monospace; font-size: 0.75rem;')
        <div style="display: grid; gap: 1rem; font-size: 0.875rem;">
            <div>
                <p style="margin: 0; font-weight: 500;">{{ __('admin.status.cron_command') }}</p>
                <code style="{{ $code }}">{{ $cronCommand }}</code>
            </div>
            <div>
                <p style="margin: 0; font-weight: 500;">{{ __('admin.status.web_cron') }}</p>
                <code style="{{ $code }}">{{ $webCronUrl }}</code>
                <p style="margin: 0.25rem 0 0; font-size: 0.75rem; opacity: 0.7;">{{ __('admin.status.web_cron_help') }}</p>
            </div>
        </div>
    </x-filament::section>
</x-filament-panels::page>
