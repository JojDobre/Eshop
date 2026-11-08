<x-filament-panels::page>
    <div style="display: grid; gap: 1.5rem; grid-template-columns: repeat(auto-fill, minmax(18rem, 1fr));">
        @foreach ($this->themes() as $item)
            @php($theme = $item['theme'])
            <x-filament::section>
                <div style="aspect-ratio: 16 / 10; overflow: hidden; border-radius: .5rem; background: rgb(244 244 245); display: flex; align-items: center; justify-content: center;">
                    @if ($theme->screenshot)
                        <img src="{{ route('theme.screenshot', $theme->name) }}" alt="" style="width: 100%; height: 100%; object-fit: cover;">
                    @else
                        <span style="font-size: 2rem; font-weight: 600; color: rgb(161 161 170);">{{ mb_substr($theme->title(), 0, 1) }}</span>
                    @endif
                </div>

                <div style="margin-top: 1rem; display: flex; gap: .5rem; align-items: center; flex-wrap: wrap;">
                    <h3 style="font-weight: 600; font-size: 1rem;">{{ $theme->title() }}</h3>
                    <span style="font-size: .75rem; color: rgb(113 113 122);">{{ $theme->version }}</span>
                    @if ($item['active'])
                        <x-filament::badge color="success">{{ __('admin.themes.active') }}</x-filament::badge>
                    @endif
                    @unless ($item['compatible'])
                        <x-filament::badge color="danger">{{ __('admin.themes.incompatible') }}</x-filament::badge>
                    @endunless
                </div>
                <p style="margin-top: .25rem; font-size: .875rem; color: rgb(113 113 122);">{{ $theme->description() }}</p>
                @if ($item['parent'])
                    <p style="margin-top: .25rem; font-size: .75rem; color: rgb(113 113 122);">{{ __('admin.themes.child_of', ['parent' => $item['parent']]) }}</p>
                @endif
                @if ($theme->author)
                    <p style="margin-top: .25rem; font-size: .75rem; color: rgb(113 113 122);">{{ __('admin.themes.author', ['author' => $theme->author]) }}</p>
                @endif

                <div style="margin-top: 1rem; display: flex; gap: .75rem; align-items: center; flex-wrap: wrap;">
                    @unless ($item['active'])
                        {{ ($this->activateAction)(['theme' => $theme->name]) }}
                        <x-filament::link :href="url('/').'?theme_preview='.$theme->name" target="_blank" size="sm">{{ __('admin.themes.preview') }}</x-filament::link>
                    @endunless
                    <x-filament::link :href="\App\Filament\Pages\CustomizeTheme::getUrl(['theme' => $theme->name])" size="sm">{{ __('admin.themes.customize') }}</x-filament::link>
                    @if (\App\Filament\Pages\ThemeEditor::canAccess() && app(\App\Core\Themes\ThemeFiles::class)->canEdit($theme))
                        <x-filament::link :href="\App\Filament\Pages\ThemeEditor::getUrl(['theme' => $theme->name])" size="sm">{{ __('admin.themes.edit_files') }}</x-filament::link>
                    @endif
                    {{ ($this->checkAction)(['theme' => $theme->name]) }}
                    {{ ($this->exportAction)(['theme' => $theme->name]) }}
                    @if ($item['deletable'])
                        {{ ($this->deleteAction)(['theme' => $theme->name]) }}
                    @endif
                </div>
            </x-filament::section>
        @endforeach
    </div>

    <x-filament-actions::modals />
</x-filament-panels::page>
