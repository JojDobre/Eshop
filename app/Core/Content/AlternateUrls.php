<?php

namespace App\Core\Content;

use Closure;
use Illuminate\Support\Facades\Route;
use Throwable;

/**
 * Adresy aktuálnej stránky v ostatných jazykoch (prepínač jazyka, hreflang).
 * Stránky s preloženou adresou (produkt, kategória) nastavia vlastný výpočet adresy.
 */
class AlternateUrls
{
    /** @var (Closure(string): ?string)|null */
    private ?Closure $resolver = null;

    public function __construct(private readonly ShopLocales $locales, private readonly LocalizedUrls $urls) {}

    /**
     * @param  Closure(string): ?string  $resolver  Adresa stránky v danom jazyku (null = stránka v jazyku nie je).
     */
    public function using(Closure $resolver): void
    {
        $this->resolver = $resolver;
    }

    /**
     * @return array<string, string> jazyk => adresa
     */
    public function all(): array
    {
        if (! $this->locales->isMultilingual()) {
            return [];
        }

        $route = Route::current();
        $urls = [];

        foreach ($this->locales->enabled() as $locale) {
            try {
                $url = $this->urls->in($locale, function () use ($locale, $route): ?string {
                    if ($this->resolver !== null) {
                        return ($this->resolver)($locale);
                    }

                    return $route?->getName() ? route($route->getName(), $route->parameters()) : null;
                });
            } catch (Throwable) {
                $url = null;
            }

            if ($url !== null) {
                $urls[$locale] = $url;
            }
        }

        return $urls;
    }
}
