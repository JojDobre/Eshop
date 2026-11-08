<x-filament-panels::page>
    {{ $this->form }}

    @if ($this->templates->isNotEmpty())
        <x-filament::section :heading="__('transfer::admin.export.templates')">
            <div style="display: grid; gap: .5rem;">
                @foreach ($this->templates as $template)
                    <div style="display: flex; justify-content: space-between; align-items: center; gap: 1rem;">
                        <span>
                            <strong>{{ $template->name }}</strong>
                            <span style="opacity: .7;">· {{ strtoupper($template->config['format'] ?? 'csv') }} · {{ __('transfer::admin.export.schedules.'.$template->schedule) }}@if ($template->email) → {{ $template->email }}@endif</span>
                        </span>
                        <span style="display: flex; gap: .5rem;">
                            <x-filament::button size="sm" color="gray" wire:click="runTemplate({{ $template->id }})">{{ __('transfer::admin.export.download') }}</x-filament::button>
                            <x-filament::button size="sm" color="danger" wire:click="deleteTemplate({{ $template->id }})" wire:confirm="{{ __('transfer::admin.export.delete_confirm') }}">{{ __('transfer::admin.export.delete') }}</x-filament::button>
                        </span>
                    </div>
                @endforeach
            </div>
        </x-filament::section>
    @endif
</x-filament-panels::page>
