<?php

namespace Modules\Storefront\Pages;

use DateTimeInterface;

/**
 * Konkrétna verzia právneho dokumentu (napr. obchodné podmienky, verzia 3).
 * Uloží sa k objednávke ako doklad, s čím zákazník súhlasil.
 */
final class LegalDocument
{
    public function __construct(
        public readonly string $key,
        public readonly int $version,
        public readonly string $title,
        public readonly string $html,
        public readonly string $url,
        public readonly DateTimeInterface $publishedAt,
    ) {}

    /**
     * Záznam súhlasu na uloženie (JSON v objednávke, účte zákazníka…).
     *
     * @return array{document: string, version: int, title: string, url: string, accepted_at: string}
     */
    public function consentRecord(): array
    {
        return [
            'document' => $this->key,
            'version' => $this->version,
            'title' => $this->title,
            'url' => $this->url,
            'accepted_at' => now()->toIso8601String(),
        ];
    }
}
