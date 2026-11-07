<?php

namespace App\Http\Middleware;

use App\Core\Content\ShopLocales;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Predpona jazyka v adrese obchodu (/en/produkt/…): odstráni ju pred smerovaním,
 * takže moduly registrujú trasy iba raz. Jazyk si zapamätá v atribúte požiadavky.
 */
class ResolveLocalePrefix
{
    public const ATTRIBUTE = 'eshop.locale';

    public function __construct(private readonly ShopLocales $locales) {}

    public function handle(Request $request, Closure $next): Response
    {
        $path = $request->getPathInfo();

        if (! preg_match('#^/([a-z]{2})(/.*)?$#', $path, $match) || ! $this->locales->isMultilingual()) {
            return $next($request);
        }

        $locale = $this->locales->fromUrlCode($match[1]);

        if ($locale === null) {
            return $next($request);
        }

        $rest = ($match[2] ?? '') !== '' ? $match[2] : '/';
        $query = $request->getQueryString();

        // Predvolený jazyk nemá predponu – presmerovanie na jedinú adresu (bez duplicitného obsahu).
        if ($locale === $this->locales->default()) {
            return redirect()->to($request->getBaseUrl().$rest.($query ? '?'.$query : ''), 301);
        }

        $server = $request->server->all();
        $server['REQUEST_URI'] = $request->getBaseUrl().$rest.($query ? '?'.$query : '');

        $localized = $request->duplicate(server: $server);
        $localized->attributes->set(self::ATTRIBUTE, $locale);
        app()->instance('request', $localized);

        return $next($localized);
    }
}
