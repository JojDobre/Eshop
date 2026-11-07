<x-filament-panels::page>
    <form wire:submit="save" style="display: grid; gap: 1.5rem;">
        {{ $this->form }}

        <div>
            <x-filament::button type="submit">{{ __('admin.features.save') }}</x-filament::button>
        </div>
    </form>
</x-filament-panels::page>
