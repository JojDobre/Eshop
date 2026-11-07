<?php

namespace App\Core\Support;

use App\Core\Installer\Installation;
use App\Settings\GeneralSettings;
use Throwable;

/**
 * Mena, v ktorej obchod práve zobrazuje ceny a prijíma objednávky.
 * Zatiaľ je to predvolená mena obchodu, výber meny zákazníkom pribudne v kroku 4a.
 */
class CurrencyContext
{
    private ?string $current = null;

    public function current(): string
    {
        return $this->current ??= $this->resolve();
    }

    public function set(string $currency): void
    {
        $this->current = strtoupper($currency);
    }

    private function resolve(): string
    {
        if (app(Installation::class)->isInstalled()) {
            try {
                return app(GeneralSettings::class)->default_currency;
            } catch (Throwable) {
                // Databáza nie je dostupná.
            }
        }

        return 'EUR';
    }
}
