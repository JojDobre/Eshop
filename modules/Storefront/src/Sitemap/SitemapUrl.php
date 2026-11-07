<?php

namespace Modules\Storefront\Sitemap;

use DateTimeInterface;

final class SitemapUrl
{
    public function __construct(
        public readonly string $location,
        public readonly ?DateTimeInterface $lastModified = null,
    ) {}
}
