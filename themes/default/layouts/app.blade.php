<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>@hasSection('title')@yield('title') · @endif{{ $shop->shop_name }}</title>
    @if ($shop->tagline)
        <meta name="description" content="{{ $shop->tagline }}">
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
        @yield('content')
    </main>

    @include('theme::partials.footer')

    @stack('scripts')
</body>
</html>
