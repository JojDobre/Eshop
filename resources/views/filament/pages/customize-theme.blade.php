<x-filament-panels::page>
    <div class="admin-customize-grid">
        <div style="display: grid; gap: 1rem;">
            @if (app(\App\Core\Themes\ThemeManager::class)->settingDefinitions($this->themeModel()) === [])
                <x-filament::section>{{ __('admin.themes.no_settings') }}</x-filament::section>
            @else
                @if ($this->textLocales() !== [])
                    <x-filament::section compact>
                        <div style="display: flex; flex-wrap: wrap; gap: .5rem; align-items: center;">
                            <span style="font-size: .875rem; font-weight: 600; margin-right: .25rem;">{{ __('admin.themes.text_locale') }}</span>
                            @foreach ($this->textLocales() as $code => $label)
                                <x-filament::button size="sm" :color="$code === $textLocale ? 'primary' : 'gray'" :outlined="$code !== $textLocale" wire:click="switchTextLocale('{{ $code }}')">{{ $label }}</x-filament::button>
                            @endforeach
                        </div>
                        <p style="margin-top: .5rem; font-size: .8125rem; opacity: .75;">{{ __('admin.themes.text_locale_help') }}</p>
                    </x-filament::section>
                @endif
                {{ $this->form }}
            @endif
        </div>

        <div style="position: sticky; top: 1rem;"
             x-data="{ src: @js($this->previewUrl()) }"
             x-on:theme-preview-refresh.window="$refs.frame.contentWindow.location.reload()">
            <div style="display: flex; gap: .75rem; align-items: center; margin-bottom: .75rem;">
                <label for="preview-page" style="font-size: .875rem; white-space: nowrap;">{{ __('admin.themes.preview_page') }}</label>
                <select id="preview-page" wire:model.live="previewPath" class="fi-select-input" style="flex: 1; min-width: 0; border: 1px solid rgb(212 212 216); border-radius: .5rem; padding: .25rem .5rem;">
                    @foreach ($this->previewPages() as $path => $label)
                        <option value="{{ $path }}">{{ $label }}</option>
                    @endforeach
                </select>
            </div>
            <iframe x-ref="frame" wire:key="preview-{{ $textLocale }}" src="{{ $this->previewUrl() }}" title="{{ __('admin.themes.preview') }}"
                    class="admin-customize-preview"></iframe>
        </div>
    </div>
</x-filament-panels::page>
