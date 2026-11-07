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
    <style>:root { --brand-primary: {{ $branding->primaryColor() }}; --brand-primary-foreground: {{ $branding->primaryForegroundColor() }}; }</style>
    @vite(['themes/default/assets/css/theme.css', 'themes/default/assets/js/theme.js'])
    @stack('head')
</head>
<body class="flex min-h-screen flex-col bg-white font-sans text-zinc-900 antialiased">
    <a href="#content" class="sr-only focus:not-sr-only focus:absolute focus:left-4 focus:top-4 focus:z-50 focus:rounded-md focus:bg-white focus:px-4 focus:py-2 focus:shadow">
        {{ __('theme::shop.skip_to_content') }}
    </a>

    @include('theme::partials.header')

    <main id="content" class="flex-1">
        @include('theme::partials.flash')
        @yield('content')
    </main>

    @include('theme::partials.footer')

    @stack('scripts')
</body>
</html>
