<?php

namespace App\Core\Events;

use Illuminate\Foundation\Events\Dispatchable;

/**
 * E-shop bol práve nainštalovaný. Moduly na túto udalosť reagujú a vytvoria
 * predvolené dáta (spôsoby dopravy a platby, právne stránky, ukážkový katalóg).
 */
class EshopInstalled
{
    use Dispatchable;

    public function __construct(
        public readonly string $locale,
        public readonly string $currency,
        public readonly string $country,
        public readonly bool $withDemoData = false,
    ) {}
}
