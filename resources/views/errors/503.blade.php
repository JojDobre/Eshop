{{--
    Stránka údržby (aktualizácia obchodu). Je samostatná – bez šablóny a bez skriptov z Vite, lebo počas
    aktualizácie sa súbory menia. Pri `artisan down --render` sa vopred vykreslí a slúži sa aj bez spustenia aplikácie.
--}}
@php
    $shop = rescue(fn () => app(\App\Settings\GeneralSettings::class)->shop_name, null, false);
    $retry = 15;
@endphp
<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="robots" content="noindex">
    <meta http-equiv="refresh" content="{{ $retry }}">
    <title>{{ __('maintenance.title') }}{{ $shop ? ' – '.$shop : '' }}</title>
    <style>
        :root { --bg: #f6f7f9; --card: #fff; --text: #18181b; --muted: #5f6470; --line: #e3e5e9; --accent: #15803d; }
        @media (prefers-color-scheme: dark) {
            :root { --bg: #0c0c0e; --card: #141417; --text: #f4f4f5; --muted: #a1a1aa; --line: #26262b; --accent: #4ade80; }
        }
        * { box-sizing: border-box; }
        body { margin: 0; min-height: 100vh; display: grid; place-items: center; padding: 24px; background: var(--bg); color: var(--text);
            font-family: system-ui, -apple-system, 'Segoe UI', Roboto, sans-serif; -webkit-font-smoothing: antialiased; }
        main { width: 100%; max-width: 440px; padding: 40px 32px; text-align: center; background: var(--card); border-radius: 20px; box-shadow: 0 0 0 1px var(--line), 0 20px 50px -24px rgb(0 0 0 / .25); }
        .icon { width: 64px; height: 64px; margin: 0 auto 20px; display: grid; place-items: center; border-radius: 18px; background: color-mix(in oklab, var(--accent) 14%, transparent); color: var(--accent); }
        .icon svg { width: 30px; height: 30px; animation: spin 2.4s linear infinite; }
        .shop { margin: 0 0 6px; font-size: 13px; font-weight: 600; letter-spacing: .04em; text-transform: uppercase; color: var(--muted); }
        h1 { margin: 0 0 10px; font-size: 22px; line-height: 1.3; letter-spacing: -.01em; }
        p { margin: 0; font-size: 15px; line-height: 1.6; color: var(--muted); }
        .bar { height: 4px; margin: 28px 0 24px; border-radius: 99px; background: var(--line); overflow: hidden; }
        .bar span { display: block; width: 40%; height: 100%; border-radius: inherit; background: var(--accent); animation: slide 1.6s ease-in-out infinite; }
        a { display: inline-flex; align-items: center; justify-content: center; min-height: 44px; padding: 0 20px; border-radius: 12px; border: 1px solid var(--line); color: var(--text); font-weight: 600; font-size: 14px; text-decoration: none; }
        a:hover { background: var(--bg); }
        @keyframes spin { to { transform: rotate(360deg); } }
        @keyframes slide { 0% { transform: translateX(-100%); } 100% { transform: translateX(250%); } }
        @media (prefers-reduced-motion: reduce) { .icon svg, .bar span { animation: none; } }
    </style>
</head>
<body>
    <main>
        <div class="icon" aria-hidden="true">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M21 12a9 9 0 1 1-3-6.7"/><path d="M21 4v5h-5"/></svg>
        </div>
        @if ($shop)
            <p class="shop">{{ $shop }}</p>
        @endif
        <h1>{{ __('maintenance.heading') }}</h1>
        <p>{{ __('maintenance.text') }}</p>
        <div class="bar" role="progressbar" aria-label="{{ __('maintenance.title') }}"><span></span></div>
        <a href="">{{ __('maintenance.retry') }}</a>
    </main>
</body>
</html>
