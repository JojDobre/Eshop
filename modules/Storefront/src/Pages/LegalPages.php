<?php

namespace Modules\Storefront\Pages;

use Closure;

/**
 * Odkazy na právne stránky (obchodné podmienky, ochrana osobných údajov), ktoré potrebuje
 * pokladňa a päta obchodu. Adresy dodá modul, ktorý stránky spravuje (modul Stránky).
 */
class LegalPages
{
    public const TERMS = 'terms';

    public const PRIVACY = 'privacy';

    /** @var array<string, Closure(): ?string> */
    private array $resolvers = [];

    /** @var array<string, ?string> */
    private array $resolved = [];

    /** @var array<string, Closure(): ?LegalDocument> */
    private array $documentResolvers = [];

    /**
     * @param  Closure(): ?string  $resolver
     */
    public function register(string $key, Closure $resolver): void
    {
        $this->resolvers[$key] = $resolver;
        unset($this->resolved[$key]);
    }

    public function url(string $key): ?string
    {
        if (! array_key_exists($key, $this->resolved)) {
            $this->resolved[$key] = isset($this->resolvers[$key]) ? ($this->resolvers[$key])() : null;
        }

        return $this->resolved[$key];
    }

    /**
     * @param  Closure(): ?LegalDocument  $resolver  Aktuálna verzia dokumentu.
     */
    public function registerDocument(string $key, Closure $resolver): void
    {
        $this->documentResolvers[$key] = $resolver;
    }

    public function current(string $key): ?LegalDocument
    {
        return isset($this->documentResolvers[$key]) ? ($this->documentResolvers[$key])() : null;
    }

    /**
     * Záznamy súhlasu s aktuálnymi verziami dokumentov (obchodné podmienky, ochrana osobných údajov).
     *
     * @return list<array<string, mixed>>
     */
    public function consentRecords(string ...$keys): array
    {
        $keys = $keys ?: [self::TERMS, self::PRIVACY];

        return array_values(array_filter(array_map(fn (string $key) => $this->current($key)?->consentRecord(), $keys)));
    }
}
