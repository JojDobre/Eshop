<?php

namespace Modules\Customers\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Prihlásenie a registrácia iba pre neprihláseného zákazníka.
 */
class RedirectIfCustomer
{
    public function handle(Request $request, Closure $next): Response
    {
        if (auth('customer')->check()) {
            return redirect()->route('customer.account');
        }

        return $next($request);
    }
}
