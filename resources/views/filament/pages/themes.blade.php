{{--
    Šablóny vzhľadu: aktívna šablóna veľká hore s tlačidlom Prispôsobiť, ostatné ako karty v mriežke.
    Menej časté akcie (kontrola, export, demo, súbory, odstránenie) sú v menu „…“.
--}}
@php
    $items = $this->themes();
    $active = collect($items)->firstWhere('active', true);
    $others = collect($items)->reject(fn ($item) => $item['active'])->values();
    $canEditFiles = \App\Filament\Pages\ThemeEditor::canAccess();
    $files = app(\App\Core\Themes\ThemeFiles::class);
@endphp

<x-filament-panels::page>
    @php($menu = function (array $item) use ($canEditFiles, $files) {
        return view('filament.pages.partials.theme-menu', ['item' => $item, 'canEditFiles' => $canEditFiles && $files->canEdit($item['theme'])]);
    })

    @if ($active)
        @php($theme = $active['theme'])
        <section class="admin-theme-hero" aria-label="{{ __('admin.themes.active') }}">
            <div class="admin-theme-shot admin-theme-hero-shot">
                @if ($theme->screenshot)
                    <img src="{{ route('theme.screenshot', $theme->name) }}" alt="">
                @else
                    <span>{{ mb_substr($theme->title(), 0, 1) }}</span>
                @endif
            </div>
            <div class="admin-theme-hero-body">
                <div class="admin-theme-meta">
                    <x-filament::badge color="success" icon="heroicon-m-check-circle">{{ __('admin.themes.active') }}</x-filament::badge>
                    <span>{{ __('admin.themes.version', ['version' => $theme->version]) }}</span>
                </div>
                <h2 class="admin-theme-hero-title">{{ $theme->title() }}</h2>
                <p class="admin-theme-desc">{{ $theme->description() }}</p>
                @if ($active['parent'])
                    <p class="admin-theme-note">{{ __('admin.themes.child_of', ['parent' => $active['parent']]) }}</p>
                @endif
                <div class="admin-theme-actions">
                    <x-filament::button :href="\App\Filament\Pages\CustomizeTheme::getUrl(['theme' => $theme->name])" tag="a" icon="heroicon-m-paint-brush">{{ __('admin.themes.customize') }}</x-filament::button>
                    <x-filament::button :href="url('/')" tag="a" target="_blank" color="gray" icon="heroicon-m-arrow-top-right-on-square">{{ __('admin.themes.view_shop') }}</x-filament::button>
                    {{ $menu($active) }}
                </div>
            </div>
        </section>
    @endif

    @if ($others->isNotEmpty())
        <h2 class="admin-theme-heading">{{ __('admin.themes.other') }} <span>{{ $others->count() }}</span></h2>
        <div class="admin-theme-grid">
            @foreach ($others as $item)
                @php($theme = $item['theme'])
                <article class="admin-theme-card">
                    <a href="{{ url('/').'?theme_preview='.$theme->name }}" target="_blank" class="admin-theme-shot" aria-label="{{ __('admin.themes.preview') }}: {{ $theme->title() }}">
                        @if ($theme->screenshot)
                            <img src="{{ route('theme.screenshot', $theme->name) }}" alt="" loading="lazy">
                        @else
                            <span>{{ mb_substr($theme->title(), 0, 1) }}</span>
                        @endif
                        <span class="admin-theme-shot-hover"><x-filament::icon icon="heroicon-m-eye" class="size-4" />{{ __('admin.themes.preview') }}</span>
                    </a>
                    <div class="admin-theme-card-body">
                        <div class="admin-theme-card-head">
                            <h3>{{ $theme->title() }}</h3>
                            {{ $menu($item) }}
                        </div>
                        <p class="admin-theme-desc admin-theme-desc-clamp">{{ $theme->description() }}</p>
                        <div class="admin-theme-meta">
                            <span>{{ __('admin.themes.version', ['version' => $theme->version]) }}</span>
                            @if ($item['parent'])
                                <span>· {{ __('admin.themes.child_of', ['parent' => $item['parent']]) }}</span>
                            @endif
                            @unless ($item['compatible'])
                                <x-filament::badge color="danger">{{ __('admin.themes.incompatible') }}</x-filament::badge>
                            @endunless
                        </div>
                        <div class="admin-theme-actions">
                            {{ ($this->activateAction)(['theme' => $theme->name]) }}
                            <x-filament::button :href="\App\Filament\Pages\CustomizeTheme::getUrl(['theme' => $theme->name])" tag="a" color="gray" size="sm">{{ __('admin.themes.customize') }}</x-filament::button>
                        </div>
                    </div>
                </article>
            @endforeach
        </div>
    @endif

    <x-filament-actions::modals />
</x-filament-panels::page>
