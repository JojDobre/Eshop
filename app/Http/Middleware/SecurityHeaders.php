<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Bezpečnostné hlavičky HTTP pre obchod aj administráciu.
 *
 * Zakazujú vkladanie stránok do cudzích webov (clickjacking), hádanie typu súborov a posielanie
 * celej adresy iným webom. Na HTTPS zapína HSTS (dá sa vypnúť v config/eshop.php).
 */
class SecurityHeaders
{
    public function handle(Request $request, Closure $next): Response
    {
        $response = $next($request);
        $headers = $response->headers;

        $headers->set('X-Content-Type-Options', 'nosniff', false);
        $headers->set('X-Frame-Options', 'SAMEORIGIN', false);
        $headers->set('Referrer-Policy', 'strict-origin-when-cross-origin', false);
        $headers->set('Permissions-Policy', 'camera=(), microphone=(), geolocation=(), payment=(self)', false);
        $headers->set('Cross-Origin-Opener-Policy', 'same-origin-allow-popups', false);

        if ($request->isSecure() && config('eshop.security.hsts', true)) {
            $headers->set('Strict-Transport-Security', 'max-age=15552000', false);
        }

        return $response;
    }
}
