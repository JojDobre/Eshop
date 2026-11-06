<!DOCTYPE html>
<html lang="{{ app()->getLocale() }}" class="h-full">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="robots" content="noindex, nofollow">
    <title>{{ __('installer.title') }}</title>
    @vite('resources/css/installer.css')
</head>
<body class="min-h-full bg-zinc-50 font-sans text-zinc-900 antialiased">
    <main class="mx-auto flex min-h-screen max-w-2xl flex-col px-4 py-10 sm:py-16">
        <header class="mb-8">
            <p class="text-sm font-medium text-zinc-500">{{ __('installer.title') }}</p>

            <ol class="mt-4 grid grid-cols-5 gap-2" aria-label="{{ __('installer.title') }}">
                @foreach (__('installer.steps') as $number => $label)
                    <li @class(['flex flex-col gap-2 text-xs', 'text-zinc-900' => $number <= $step, 'text-zinc-400' => $number > $step])
                        @if ($number === $step) aria-current="step" @endif>
                        <span @class(['h-1 rounded-full', 'bg-zinc-900' => $number <= $step, 'bg-zinc-200' => $number > $step])></span>
                        <span class="hidden sm:block">{{ $label }}</span>
                    </li>
                @endforeach
            </ol>
        </header>

        <section class="rounded-2xl border border-zinc-200 bg-white p-6 shadow-sm sm:p-10">
            @yield('content')
        </section>

        <footer class="mt-6 text-center text-xs text-zinc-400">
            E-shop {{ \App\Core\Eshop::VERSION }}
        </footer>
    </main>
</body>
</html>
