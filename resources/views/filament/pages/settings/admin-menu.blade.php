<x-filament-panels::page>
    <form wire:submit="save" class="space-y-6">
        {{ $this->form }}
        <x-filament::button type="submit">{{ __('admin.admin_menu.save') }}</x-filament::button>
    </form>
</x-filament-panels::page>
