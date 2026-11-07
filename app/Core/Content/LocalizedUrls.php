<?php

namespace App\Core\Content;

use Closure;
use Illuminate\Routing\Route;

/**
 * Predpona jazyka v odkazoch obchodu. Odkazy na trasy obchodu (označené middlewarom
 * jazyka obchodu) dostanú predponu aktuálneho jazyka, ak nie je predvolený.
 */
class LocalizedUrls
{
    /** @var list<string> Middleware, podľa ktorých sa pozná trasa obchodu. */
    private array $markers = [];

    private ?string $forced = null;

    public function __construct(private readonly ShopLocales $locales, private readonly ContentLocale $content) {}

    public function markRoutesWith(string $middleware): void
    {
        $this->markers[] = $middleware;
    }

    /** Jazyk, pre ktorý sa práve tvoria odkazy. */
    public function current(): string
    {
        return $this->forced ?? $this->content->current();
    }

    /**
     * Vytvorí odkazy pre iný jazyk (napr. e-mail zákazníkovi v jeho jazyku, prepínač jazyka).
     *
     * @template T
     *
     * @param  Closure(): T  $callback
     * @return T
     */
    public function in(string $locale, Closure $callback): mixed
    {
        $previous = $this->forced;
        $this->forced = $locale;

        try {
            return $callback();
        } finally {
            $this->forced = $previous;
        }
    }

    /**
     * Úprava cesty pri tvorbe odkazu (URL::formatPathUsing).
     */
    public function formatPath(string $path, ?Route $route): string
    {
        if ($route === null || ! $this->isLocalizedRoute($route)) {
            return $path;
        }

        $locale = $this->current();

        if ($locale === $this->locales->default() || ! $this->locales->isEnabled($locale)) {
            return $path;
        }

        $prefix = '/'.$this->locales->urlCode($locale);
        // Cesta môže obsahovať základ inštalácie v podadresári (napr. /obchod/produkt/x).
        $base = rtrim((string) parse_url((string) config('app.url'), PHP_URL_PATH), '/');

        if ($base !== '' && str_starts_with($path, $base)) {
            return $base.$prefix.(substr($path, strlen($base)) === '/' ? '' : substr($path, strlen($base)));
        }

        return $prefix.($path === '/' ? '' : $path);
    }

    private function isLocalizedRoute(Route $route): bool
    {
        if ($this->markers === []) {
            return false;
        }

        return array_intersect($this->markers, $route->middleware()) !== [];
    }
}
