<?php

namespace Modules\Storefront\Http\Middleware;

use App\Settings\GeneralSettings;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Jazyk obchodu podľa nastavení. Výber jazyka zákazníkom príde s viacjazyčnosťou (krok 4a).
 */
class SetStorefrontLocale
{
    public function __construct(private readonly GeneralSettings $settings) {}

    public function handle(Request $request, Closure $next): Response
    {
        app()->setLocale($this->settings->default_locale);

        return $next($request);
    }
}
