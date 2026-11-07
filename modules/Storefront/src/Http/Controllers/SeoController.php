<?php

namespace Modules\Storefront\Http\Controllers;

use Illuminate\Http\Response;
use Illuminate\Routing\Controller;
use Modules\Storefront\Sitemap\SitemapRegistry;
use Modules\Storefront\Sitemap\SitemapUrl;

class SeoController extends Controller
{
    public function sitemap(SitemapRegistry $registry): Response
    {
        $xml = '<?xml version="1.0" encoding="UTF-8"?>'."\n";
        $xml .= '<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">'."\n";
        $xml .= $this->url(new SitemapUrl(route('storefront.home')));

        foreach ($registry->urls() as $url) {
            $xml .= $this->url($url);
        }

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
