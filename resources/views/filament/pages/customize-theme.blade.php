<x-filament-panels::page>
    <div style="display: grid; gap: 1.5rem; grid-template-columns: minmax(18rem, 24rem) 1fr; align-items: start;">
        <div style="display: grid; gap: 1rem;">
            @if (app(\App\Core\Themes\ThemeManager::class)->settingDefinitions($this->themeModel()) === [])
                <x-filament::section>{{ __('admin.themes.no_settings') }}</x-filament::section>
            @else
                {{ $this->form }}
            @endif
        </div>

        <div style="position: sticky; top: 1rem;"
             x-data="{ src: @js($this->previewUrl()) }"
             x-on:theme-preview-refresh.window="$refs.frame.contentWindow.location.reload()">
            <div style="display: flex; gap: .75rem; align-items: center; margin-bottom: .75rem;">
                <label for="preview-page" style="font-size: .875rem;">{{ __('admin.themes.preview_page') }}</label>
                <select id="preview-page" wire:model.live="previewPath" class="fi-select-input" style="border: 1px solid rgb(212 212 216); border-radius: .5rem; padding: .25rem .5rem;">
                    @foreach ($this->previewPages() as $path => $label)
                        <option value="{{ $path }}">{{ $label }}</option>
                    @endforeach
                </select>
            </div>
            <iframe x-ref="frame" src="{{ $this->previewUrl() }}" title="{{ __('admin.themes.preview') }}"
                    style="width: 100%; height: 75vh; border: 1px solid rgb(228 228 231); border-radius: .75rem; background: #fff;"></iframe>
        </div>
    </div>
</x-filament-panels::page>
