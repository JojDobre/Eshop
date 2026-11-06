<?php

namespace App\Http\Middleware;

use App\Core\Installer\Installation;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Kým e-shop nie je nainštalovaný, presmeruje všetky požiadavky na inštalátor.
 * Po inštalácii je inštalátor nedostupný (404).
 */
class EnsureInstalled
{
    public function __construct(private readonly Installation $installation) {}

    public function handle(Request $request, Closure $next): Response
    {
        $isInstallerRoute = $request->routeIs('install.*');

        if ($this->installation->isInstalled()) {
            // Po inštalácii zostane prístupná iba záverečná stránka, a to len raz.
            $showsCompletion = $request->routeIs('install.done') && $request->session()->has('installer.completed');

            abort_if($isInstallerRoute && ! $showsCompletion, 404);

            return $next($request);
        }

        if (! $isInstallerRoute) {
            return redirect()->route('install.welcome');
        }

        return $next($request);
    }
}
