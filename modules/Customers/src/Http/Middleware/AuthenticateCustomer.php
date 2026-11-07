<?php

namespace Modules\Customers\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Stránky účtu iba pre prihláseného zákazníka.
 */
class AuthenticateCustomer
{
    public function handle(Request $request, Closure $next): Response
    {
        if (! auth('customer')->check()) {
            return redirect()->guest(route('customer.login'));
        }

        return $next($request);
    }
}
