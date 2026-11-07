<?php

namespace Modules\Storefront\Http\Controllers;

use App\Core\Content\ContentLocale;
use App\Core\Content\LocalizedUrls;
use App\Core\Content\ShopLocales;
use Illuminate\Http\Response;
use Illuminate\Routing\Controller;
use Modules\Storefront\Sitemap\SitemapRegistry;
use Modules\Storefront\Sitemap\SitemapUrl;

class SeoController extends Controller
{
    public function sitemap(SitemapRegistry $registry, ShopLocales $locales, LocalizedUrls $urls, ContentLocale $content): Response
    {
        $xml = '<?xml version="1.0" encoding="UTF-8"?>'."\n";
        $xml .= '<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">'."\n";
        $original = $content->current();

        // Adresy v každom jazyku obchodu (preložené adresy a predpona jazyka).
        foreach ($locales->enabled() as $locale) {
            $content->set($locale);

            $xml .= $urls->in($locale, function () use ($registry): string {
                $part = $this->url(new SitemapUrl(route('storefront.home')));

                foreach ($registry->urls() as $url) {
                    $part .= $this->url($url);
                }

                return $part;
            });
        }

        $content->set($original);

        $xml .= '</urlset>'."\n";

        return response($xml, 200, ['Content-Type' => 'application/xml; charset=UTF-8']);
    }

    public function robots(): Response
    {
        $lines = [
            'User-agent: *',
            'Disallow: /admin',
            'Disallow: /kosik',
            'Disallow: /pokladna',
            'Disallow: /objednavka',
            '',
            'Sitemap: '.route('storefront.sitemap'),
        ];

        return response(implode("\n", $lines)."\n", 200, ['Content-Type' => 'text/plain; charset=UTF-8']);
    }

    private function url(SitemapUrl $url): string
    {
        $xml = '  <url><loc>'.htmlspecialchars($url->location, ENT_XML1).'</loc>';

        if ($url->lastModified !== null) {
            $xml .= '<lastmod>'.$url->lastModified->format('Y-m-d').'</lastmod>';
        }

        return $xml.'</url>'."\n";
    }
}
