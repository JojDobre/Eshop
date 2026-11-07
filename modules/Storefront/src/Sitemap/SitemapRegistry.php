<?php

namespace Modules\Storefront\Sitemap;

use Closure;

/**
 * Zoznam zdrojov adries pre sitemap.xml. Každý modul pridá svoje adresy (kategórie, produkty, stránky).
 */
class SitemapRegistry
{
    /** @var list<Closure(): iterable<SitemapUrl>> */
    private array $sources = [];

    /**
     * @param  Closure(): iterable<SitemapUrl>  $source
     */
    public function add(Closure $source): void
    {
        $this->sources[] = $source;
    }

    /**
     * @return iterable<SitemapUrl>
     */
    public function urls(): iterable
    {
        foreach ($this->sources as $source) {
            yield from $source();
        }
    }
}
