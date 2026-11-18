{{-- Menu „…“ pri šablóne: menej časté akcie. --}}
@php($theme = $item['theme'])
<x-filament::dropdown placement="bottom-end">
    <x-slot name="trigger">
        <x-filament::icon-button icon="heroicon-m-ellipsis-horizontal" color="gray" :label="__('admin.themes.more')" />
    </x-slot>

    <x-filament::dropdown.list>
        @unless ($item['active'])
            <x-filament::dropdown.list.item :href="url('/').'?theme_preview='.$theme->name" tag="a" target="_blank" icon="heroicon-m-eye">{{ __('admin.themes.preview') }}</x-filament::dropdown.list.item>
        @endunless
        @if ($canEditFiles)
            <x-filament::dropdown.list.item :href="\App\Filament\Pages\ThemeEditor::getUrl(['theme' => $theme->name])" tag="a" icon="heroicon-m-code-bracket">{{ __('admin.themes.edit_files') }}</x-filament::dropdown.list.item>
        @endif
        @if ($theme->demoPath())
            <x-filament::dropdown.list.item wire:click="mountAction('importThemeDemo', { theme: '{{ $theme->name }}' })" icon="heroicon-m-sparkles">{{ __('admin.demo.import_theme') }}</x-filament::dropdown.list.item>
        @endif
        <x-filament::dropdown.list.item wire:click="mountAction('check', { theme: '{{ $theme->name }}' })" icon="heroicon-m-shield-check">{{ __('admin.themes.check') }}</x-filament::dropdown.list.item>
        <x-filament::dropdown.list.item wire:click="mountAction('export', { theme: '{{ $theme->name }}' })" icon="heroicon-m-arrow-down-tray">{{ __('admin.themes.export') }}</x-filament::dropdown.list.item>
    </x-filament::dropdown.list>

    @if ($item['deletable'])
        <x-filament::dropdown.list>
            <x-filament::dropdown.list.item wire:click="mountAction('delete', { theme: '{{ $theme->name }}' })" icon="heroicon-m-trash" color="danger">{{ __('admin.themes.delete') }}</x-filament::dropdown.list.item>
        </x-filament::dropdown.list>
    @endif
</x-filament::dropdown>
