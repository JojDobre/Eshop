<?php

namespace App\Core\Content;

use App\Core\Installer\Installation;
use App\Settings\GeneralSettings;
use Throwable;

/**
 * Jazyk, v ktorom sa ukladá a zobrazuje obsah obchodu (produkty, kategórie, stránky).
 *
 * Nezávisí od jazyka administrácie: administrátor s anglickým rozhraním upravuje
 * obsah v jazyku obchodu. Viac jazykov obsahu pribudne v kroku 4a.
 */
class ContentLocale
{
    private ?string $current = null;

    private ?string $default = null;

    public function current(): string
    {
        return $this->current ??= $this->resolve();
    }

    /** Predvolený jazyk obchodu (bez ohľadu na jazyk aktuálnej stránky). */
    public function default(): string
    {
        return $this->default ??= $this->resolve();
    }

    public function set(string $locale): void
    {
        $this->current = $locale;
    }

    private function resolve(): string
    {
        if (app(Installation::class)->isInstalled()) {
            try {
                return app(GeneralSettings::class)->default_locale;
            } catch (Throwable) {
                // Databáza nie je dostupná, použije sa jazyk aplikácie.
            }
        }

        return (string) config('app.locale', 'sk');
    }
}
