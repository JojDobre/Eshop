<?php

namespace Modules\Storefront\Http\Middleware;

use App\Core\Content\ContentLocale;
use App\Core\Content\ShopLocales;
use App\Core\Support\Currencies;
use App\Core\Support\CurrencyContext;
use App\Http\Middleware\ResolveLocalePrefix;
use Closure;
use Illuminate\Http\Request;
use Spatie\Translatable\Translatable;
use Symfony\Component\HttpFoundation\Response;

/**
 * Jazyk obchodu: podľa predpony v adrese (/en/…), inak predvolený jazyk obchodu.
 * Chýbajúce preklady obsahu sa zobrazia v predvolenom jazyku. Nastaví aj menu vybranú zákazníkom.
 */
class SetStorefrontLocale
{
    public const CURRENCY_SESSION = 'eshop.currency';

    public function __construct(private readonly ShopLocales $locales, private readonly ContentLocale $content) {}

    public function handle(Request $request, Closure $next): Response
    {
        $locale = $request->attributes->get(ResolveLocalePrefix::ATTRIBUTE) ?? $this->locales->default();

        $this->content->set($locale);
        app()->setLocale($locale);
        app(Translatable::class)->fallback(fallbackLocale: $this->locales->default(), fallbackAny: true);

        // Mena, ktorú si zákazník vybral (iba zapnuté meny; administrácia ostáva v predvolenej mene).
        $currency = $request->hasSession() ? $request->session()->get(self::CURRENCY_SESSION) : null;

        if (is_string($currency) && app(Currencies::class)->isEnabled($currency)) {
            app(CurrencyContext::class)->set($currency);
        }

        return $next($request);
    }
}
