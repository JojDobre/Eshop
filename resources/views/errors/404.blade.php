{{-- Stránka 404: v obchode sa zobrazí v aktívnej šablóne vzhľadu, inde (administrácia, pred inštaláciou) štandardná. --}}
@php
    $useTheme = app(\App\Core\Installer\Installation::class)->isInstalled()
        && ! request()->is('admin', 'admin/*', 'install', 'install/*', 'livewire*')
        && view()->exists('theme::templates.404');
@endphp
@if ($useTheme)
    @include('theme::templates.404')
@else
    {{-- Pozor: nevkladať 'errors::404' – to je tento istý súbor (nekonečná slučka). --}}
    @include('errors.system-404')
@endif
