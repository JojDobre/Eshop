{{--
    Prispôsobenie šablóny: horná lišta (späť, jazyk, stránka, zariadenie, uloženie), vľavo zoznam sekcií
    s preklikom do nastavení, vpravo živý náhľad v šírke počítača, tabletu alebo mobilu.
    Na mobile a tablete sa medzi nastaveniami a náhľadom prepína záložkami.
--}}
@php
    $groups = $this->groups();
    $hasSettings = app(\App\Core\Themes\ThemeManager::class)->settingDefinitions($this->themeModel()) !== [];
    $devices = [
        'desktop' => ['icon' => 'heroicon-o-computer-desktop', 'label' => __('admin.themes.devices.desktop')],
        'tablet' => ['icon' => 'heroicon-o-device-tablet', 'label' => __('admin.themes.devices.tablet')],
        'mobile' => ['icon' => 'heroicon-o-device-phone-mobile', 'label' => __('admin.themes.devices.mobile')],
    ];
@endphp

<x-filament-panels::page class="admin-customize-page">
    <div class="admin-customizer"
         x-data="{
             group: null,
             device: (() => { try { return localStorage.getItem('admin:customize:device') || 'desktop' } catch (e) { return 'desktop' } })(),
             pane: 'settings',
             scale: 1,
             widths: { desktop: 1280, tablet: 820, mobile: 390 },
             setDevice(value) { this.device = value; try { localStorage.setItem('admin:customize:device', value) } catch (e) {} this.$nextTick(() => this.fit()) },
             {{-- Stránka sa vykreslí v skutočnej šírke zariadenia a zmenší sa, aby sa zmestila do panela. --}}
             {{-- Na telefóne sa náhľad vždy zobrazí v šírke mobilu. --}}
             effective() { return window.innerWidth < 640 ? 'mobile' : this.device },
             fit() {
                 const stage = this.$refs.stage
                 if (! stage || stage.clientWidth === 0) return
                 this.scale = Math.min(1, (stage.clientWidth - 32) / this.widths[this.effective()])
             },
         }"
         x-init="
             $nextTick(() => fit()); new ResizeObserver(() => fit()).observe($refs.stage);
             {{-- Hlavné menu sa na počítači zbalí, aby mal náhľad viac miesta; po odchode zo stránky sa znova otvorí. --}}
             if ($store.sidebar && ! $store.sidebar.isMobile && $store.sidebar.isOpen) {
                 $store.sidebar.close();
                 window.addEventListener('pagehide', () => $store.sidebar.open(), { once: true });
             }
         "
         x-on:theme-preview-refresh.window="$refs.frame.contentWindow.location.reload()"
         x-bind:data-pane="pane">

        {{-- Horná lišta --}}
        <div class="admin-customizer-bar">
            <a href="{{ \App\Filament\Pages\Themes::getUrl() }}" class="admin-customizer-icon-btn" aria-label="{{ __('admin.themes.back') }}" x-tooltip="{ content: @js(__('admin.themes.back')), theme: $store.theme }">
                <x-filament::icon icon="heroicon-m-arrow-left" class="size-5" />
            </a>
            <div class="admin-customizer-title">
                <span class="admin-customizer-title-main">{{ $this->themeModel()->title() }}</span>
                <span class="admin-customizer-title-sub">{{ __('admin.themes.customize') }}</span>
            </div>

            <div class="admin-customizer-bar-center">
                <label for="preview-page" class="sr-only">{{ __('admin.themes.preview_page') }}</label>
                <select id="preview-page" wire:model.live="previewPath" class="admin-customizer-select">
                    @foreach ($this->previewPages() as $path => $label)
                        <option value="{{ $path }}">{{ $label }}</option>
                    @endforeach
                </select>
                <div class="admin-segmented admin-customizer-devices" role="group" aria-label="{{ __('admin.themes.devices.label') }}">
                    @foreach ($devices as $key => $device)
                        <button type="button" x-on:click="setDevice('{{ $key }}')" x-bind:aria-pressed="(device === '{{ $key }}').toString()"
                                aria-label="{{ $device['label'] }}" x-tooltip="{ content: @js($device['label']), theme: $store.theme }">
                            <x-filament::icon :icon="$device['icon']" class="size-4.5" />
                        </button>
                    @endforeach
                </div>
                @if ($this->textLocales() !== [])
                    <div class="admin-segmented" role="group" aria-label="{{ __('admin.themes.text_locale') }}" x-tooltip="{ content: @js(__('admin.themes.text_locale_help')), theme: $store.theme }">
                        @foreach ($this->textLocales() as $code => $label)
                            <button type="button" wire:click="switchTextLocale('{{ $code }}')" aria-pressed="{{ $code === $textLocale ? 'true' : 'false' }}" class="admin-segmented-text">{{ strtoupper($code) }}</button>
                        @endforeach
                    </div>
                @endif
            </div>

            <div class="admin-customizer-bar-actions">
                @foreach ($this->getCachedHeaderActions() as $action)
                    @if ($action->isVisible())
                        {{ $action }}
                    @endif
                @endforeach
            </div>
        </div>

        {{-- Prepínač panelov na mobile a tablete --}}
        <div class="admin-customizer-panes admin-segmented" role="tablist">
            <button type="button" role="tab" x-on:click="pane = 'settings'" x-bind:aria-pressed="(pane === 'settings').toString()" class="admin-segmented-text">{{ __('admin.themes.settings') }}</button>
            <button type="button" role="tab" x-on:click="pane = 'preview'; $nextTick(() => fit())" x-bind:aria-pressed="(pane === 'preview').toString()" class="admin-segmented-text">{{ __('admin.themes.preview') }}</button>
        </div>

        {{-- Ľavý panel: zoznam sekcií alebo nastavenia otvorenej sekcie --}}
        <aside x-ref="side" class="admin-customizer-side">
            @if (! $hasSettings)
                <p class="admin-customizer-empty">{{ __('admin.themes.no_settings') }}</p>
            @endif

            <nav x-show="group === null" aria-label="{{ __('admin.themes.sections') }}" class="admin-customizer-nav">
                <p class="admin-customizer-nav-heading">{{ __('admin.themes.sections') }}</p>
                @foreach ($groups as $key => $item)
                    <button type="button" x-on:click="group = '{{ $key }}'; $nextTick(() => $refs.side.scrollTo({ top: 0 }))" class="admin-customizer-nav-item">
                        <span class="admin-customizer-nav-icon"><x-filament::icon :icon="$item['icon']" class="size-5" /></span>
                        <span class="admin-customizer-nav-label">{{ $item['label'] }}</span>
                        @if ($item['count'])
                            <span class="admin-customizer-nav-count">{{ $item['count'] }}</span>
                        @endif
                        <x-filament::icon icon="heroicon-m-chevron-right" class="admin-customizer-nav-chevron size-4" />
                    </button>
                @endforeach
            </nav>

            <div x-show="group !== null" x-cloak class="admin-customizer-group-head">
                <button type="button" x-on:click="group = null" class="admin-customizer-icon-btn" aria-label="{{ __('admin.themes.all_sections') }}">
                    <x-filament::icon icon="heroicon-m-chevron-left" class="size-5" />
                </button>
                @foreach ($groups as $key => $item)
                    <span x-show="group === '{{ $key }}'" class="admin-customizer-group-title">{{ $item['label'] }}</span>
                @endforeach
                <button type="button" x-show="group === 'blocks'" wire:click="refreshPreview" class="admin-customizer-icon-btn ms-auto" aria-label="{{ __('admin.themes.refresh_preview') }}" x-tooltip="{ content: @js(__('admin.themes.refresh_preview')), theme: $store.theme }">
                    <x-filament::icon icon="heroicon-m-arrow-path" class="size-4.5" />
                </button>
            </div>

            <div x-show="group !== null" x-cloak class="admin-customizer-form">
                {{ $this->form }}
            </div>
        </aside>

        {{-- Náhľad --}}
        <section x-ref="stage" class="admin-customizer-stage" aria-label="{{ __('admin.themes.preview') }}"
                 x-bind:style="`--frame-width: ${widths[effective()]}px; --frame-scale: ${scale}`">
            <div class="admin-customizer-frame" x-bind:data-device="effective()">
                <iframe x-ref="frame" wire:key="preview-{{ $textLocale }}-{{ $previewPath }}" src="{{ $this->previewUrl() }}" title="{{ __('admin.themes.preview') }}"></iframe>
            </div>
        </section>
    </div>
</x-filament-panels::page>
