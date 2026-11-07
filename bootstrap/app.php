<?php

use App\Http\Middleware\EnsureInstalled;
use App\Http\Middleware\ResolveLocalePrefix;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Http\Request;

require __DIR__.'/prepare-env.php';

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        // Pred inštaláciou presmeruje všetko na inštalátor, po inštalácii inštalátor vypne.
        $middleware->web(append: [EnsureInstalled::class]);
        // Predpona jazyka (/en/…) sa spracuje ešte pred výberom trasy.
        $middleware->prepend(ResolveLocalePrefix::class);
        // Voľbu cookies nastavuje lišta v prehliadači (JavaScript), preto nie je šifrovaná.
        $middleware->encryptCookies(except: ['eshop_consent']);
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        $exceptions->shouldRenderJsonWhen(
            fn (Request $request) => $request->is('api/*') || $request->expectsJson(),
        );
    })->create();
