<x-filament-panels::page>
    @if ($this->file === null)
        <x-filament::section>{{ __('admin.theme_editor.empty') }}</x-filament::section>
    @endif
    {{ $this->form }}
</x-filament-panels::page>
