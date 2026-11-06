<?php

namespace App\Http\Middleware;

use App\Core\Installer\Installation;
use App\Models\User;
use App\Settings\GeneralSettings;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;
use Throwable;

/**
 * Jazyk administrácie: podľa profilu prihláseného používateľa, inak podľa jazyka obchodu.
 */
class SetAdminLocale
{
    public function __construct(private readonly Installation $installation) {}

    public function handle(Request $request, Closure $next): Response
    {
        $available = array_keys(config('eshop.locales'));
        $user = $request->user();

        $locale = $user instanceof User ? $user->locale : $this->shopLocale();

        if (in_array($locale, $available, true)) {
            app()->setLocale($locale);
        }

        return $next($request);
    }

    private function shopLocale(): ?string
    {
        if (! $this->installation->isInstalled()) {
            return null;
        }

        try {
            return app(GeneralSettings::class)->default_locale;
        } catch (Throwable) {
            return null;
        }
    }
}
