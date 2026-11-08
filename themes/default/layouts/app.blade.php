<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    @php($pageTitle = trim($__env->yieldContent('title')))
    <title>{{ $pageTitle !== '' ? $pageTitle.' · '.$shop->shop_name : $shop->shop_name.($shop->tagline ? ' · '.$shop->tagline : '') }}</title>
    @php($metaDescription = trim($__env->yieldContent('meta_description')) ?: $shop->tagline)
    @if ($metaDescription)
        <meta name="description" content="{{ $metaDescription }}">
    @endif
    @hasSection('noindex')
        <meta name="robots" content="noindex, nofollow">
    @endif
    <link rel="canonical" href="{{ trim($__env->yieldContent('canonical')) ?: url()->current() }}">
    @if (! empty($alternateUrls))
        @foreach ($alternateUrls as $alternateLocale => $alternateUrl)
            <link rel="alternate" hreflang="{{ $alternateLocale }}" href="{{ $alternateUrl }}">
        @endforeach
        @isset($alternateUrls[$shopLocales->default()])
            <link rel="alternate" hreflang="x-default" href="{{ $alternateUrls[$shopLocales->default()] }}">
        @endisset
    @endif
    <meta property="og:site_name" content="{{ $shop->shop_name }}">
    <meta property="og:title" content="{{ $pageTitle ?: $shop->shop_name }}">
    @if ($metaDescription)
        <meta property="og:description" content="{{ $metaDescription }}">
    @endif
    @hasSection('og_image')
        <meta property="og:image" content="@yield('og_image')">
    @endif
    @if ($favicon = $branding->faviconUrl())
        <link rel="icon" href="{{ $favicon }}">
    @endif
    {{-- Nastavenia šablóny (Vzhľad → Prispôsobiť) ako premenné CSS. --}}
    @php($themeRadius = ['pill' => '9999px', 'square' => '0', 'rounded' => '0.5rem'][$themeOptions->get('button_style', 'rounded')] ?? '0.5rem')
    @php($themeFont = ['system' => "ui-sans-serif, system-ui, -apple-system, 'Segoe UI', sans-serif", 'serif' => "Georgia, 'Times New Roman', serif"][$themeOptions->get('font', 'inter')] ?? null)
    <style>:root { --brand-primary: {{ $branding->primaryColor() }}; --brand-primary-foreground: {{ $branding->primaryForegroundColor() }}; --theme-button-radius: {{ $themeRadius }};@if ($themeFont) --theme-font: {!! $themeFont !!};@endif }</style>
    {{-- Štýly šablóny: skompilované (Vite) pre dodávané šablóny, hotové súbory pre nahraté šablóny. --}}
    @vite(app(\App\Core\Themes\ThemeManager::class)->viteEntries() ?: ['themes/default/assets/css/theme.css', 'themes/default/assets/js/theme.js'])
    @foreach (app(\App\Core\Themes\ThemeManager::class)->assetUrls()['styles'] as $themeStylesheet)
        <link rel="stylesheet" href="{{ $themeStylesheet }}">
    @endforeach
    @foreach (app(\App\Core\Themes\ThemeManager::class)->assetUrls()['scripts'] as $themeScript)
        <script src="{{ $themeScript }}" defer></script>
    @endforeach
    @stack('head')
    @isset($cookieConsent)
        {{-- Kódy analytiky a marketingu iba so súhlasom zákazníka (vkladá ich obchodník v administrácii). --}}
        @foreach ($cookieConsent->allowedCodes() as $consentCode)
            {!! $consentCode !!}
        @endforeach
    @endisset
</head>
<body class="flex min-h-screen flex-col bg-white font-sans text-zinc-900 antialiased">
    <a href="#content" class="sr-only focus:not-sr-only focus:absolute focus:left-4 focus:top-4 focus:z-50 focus:rounded-md focus:bg-white focus:px-4 focus:py-2 focus:shadow">
        {{ __('theme::shop.skip_to_content') }}
    </a>

    @if (filled($themeOptions->get('announcement')))
        <div class="bg-zinc-900 px-4 py-2 text-center text-sm text-white">{{ $themeOptions->get('announcement') }}</div>
    @endif

    @include('theme::partials.header')

    <main id="content" class="flex-1">
        @include('theme::partials.flash')
        @yield('content')
    </main>

    {!! block_area('before_footer') !!}

    @include('theme::partials.footer')

    @include('theme::partials.cookie-bar')

    @stack('scripts')
</body>
</html>
