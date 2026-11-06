<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Jazyk inštalátora: zvolený v prvom kroku, inak podľa prehliadača, inak slovenčina.
 */
class SetInstallerLocale
{
    public function handle(Request $request, Closure $next): Response
    {
        $available = array_keys(config('eshop.locales'));

        $locale = $request->session()->get('installer.locale')
            ?? $request->getPreferredLanguage($available)
            ?? 'sk';

        app()->setLocale(in_array($locale, $available, true) ? $locale : 'sk');

        return $next($request);
    }
}
