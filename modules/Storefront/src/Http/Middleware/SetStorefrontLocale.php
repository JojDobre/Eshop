<?php

namespace Modules\Storefront\Http\Middleware;

use App\Core\Content\ContentLocale;
use App\Core\Content\ShopLocales;
use App\Http\Middleware\ResolveLocalePrefix;
use Closure;
use Illuminate\Http\Request;
use Spatie\Translatable\Translatable;
use Symfony\Component\HttpFoundation\Response;

/**
 * Jazyk obchodu: podľa predpony v adrese (/en/…), inak predvolený jazyk obchodu.
 * Chýbajúce preklady obsahu sa zobrazia v predvolenom jazyku.
 */
class SetStorefrontLocale
{
    public function __construct(private readonly ShopLocales $locales, private readonly ContentLocale $content) {}

    public function handle(Request $request, Closure $next): Response
    {
        $locale = $request->attributes->get(ResolveLocalePrefix::ATTRIBUTE) ?? $this->locales->default();

        $this->content->set($locale);
        app()->setLocale($locale);
        app(Translatable::class)->fallback(fallbackLocale: $this->locales->default(), fallbackAny: true);

        return $next($request);
    }
}
